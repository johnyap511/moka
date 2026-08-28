<?php

namespace App\Support;

use App\EzeeGroup;

/**
 * Reads booking data straight from EZEE.
 *
 * Deals with the three things that make the endpoint awkward:
 *
 *  - ranges over a year are refused ("Date range should be less then 365 days"),
 *    so requests are split into windows and merged;
 *  - closely-spaced requests get "Duplicate request. Please try again after
 *    1 minute.", so they are paced and retried once;
 *  - every response is HTTP 200, including failures, and a *successful* one
 *    carries <ErrorMessage>Success</ErrorMessage> — so the message has to be
 *    read rather than merely detected.
 */
class EzeeBookingFeed
{
    private const ENDPOINT     = 'https://live.ipms247.com/pmsinterface/getdataAPI.php';
    private const WINDOW_DAYS  = 360;
    private const PACE_SECONDS = 3;

    /** @var callable|null Receives progress lines. */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Unit id per reservation, across the whole range.
     *
     * @return array<string,string> SubBookingId => eZeePMSRoomid
     */
    public function roomIds(EzeeGroup $group, string $from, string $to): array
    {
        $map = [];

        foreach ($this->windows($from, $to) as $index => [$wFrom, $wTo]) {
            if ($index > 0) {
                sleep(self::PACE_SECONDS);
            }

            $xml   = $this->fetch($group, $wFrom, $wTo);
            $error = $xml === null ? 'request failed' : $this->errorFrom($xml);

            if ($error !== null && stripos($error, 'duplicate request') !== false) {
                $this->log("    {$wFrom}..{$wTo}: throttled, waiting 60s");
                sleep(60);
                $xml   = $this->fetch($group, $wFrom, $wTo);
                $error = $xml === null ? 'request failed' : $this->errorFrom($xml);
            }

            if ($error !== null) {
                $this->log("    {$wFrom}..{$wTo}: EZEE said: {$error}");
                continue;
            }

            $found = $this->parseRoomIds($xml);
            $this->log("    {$wFrom}..{$wTo}: " . count($found) . ' unit id(s)');
            $map += $found;
        }

        return $map;
    }

    /**
     * Every unit the property has used in the range, with its room type and
     * how many reservations referenced it.
     *
     * Unlike roomIds(), this is not keyed to a set of bookings we already
     * hold — it reports what EZEE itself knows about, which is what a room
     * catalogue needs.
     *
     * @return array<string,array{room_type:string,bookings:int}>
     */
    public function roomCatalogue(EzeeGroup $group, string $from, string $to): array
    {
        $rooms = [];

        foreach ($this->windows($from, $to) as $index => [$wFrom, $wTo]) {
            if ($index > 0) {
                sleep(self::PACE_SECONDS);
            }

            $xml   = $this->fetch($group, $wFrom, $wTo);
            $error = $xml === null ? 'request failed' : $this->errorFrom($xml);

            if ($error !== null && stripos($error, 'duplicate request') !== false) {
                $this->log("    {$wFrom}..{$wTo}: throttled, waiting 60s");
                sleep(60);
                $xml   = $this->fetch($group, $wFrom, $wTo);
                $error = $xml === null ? 'request failed' : $this->errorFrom($xml);
            }

            if ($error !== null) {
                $this->log("    {$wFrom}..{$wTo}: EZEE said: {$error}");
                continue;
            }

            $before = count($rooms);
            $this->collectRooms($xml, $rooms);
            $this->log("    {$wFrom}..{$wTo}: " . (count($rooms) - $before) . ' new unit(s), ' . count($rooms) . ' total');
        }

        ksort($rooms);

        return $rooms;
    }

    /**
     * @param array<string,array{room_type:string,bookings:int}> $rooms
     */
    private function collectRooms(string $xml, array &$rooms): void
    {
        if (!preg_match_all('#<BookingTran>(.*?)</BookingTran>#s', $xml, $blocks)) {
            return;
        }

        foreach ($blocks[1] as $block) {
            if (!preg_match('#<eZeePMSRoomid>([^<]*)</eZeePMSRoomid>#', $block, $room)) {
                continue;
            }
            $roomId = trim($room[1]);
            if ($roomId === '') {
                continue;
            }

            $type = preg_match('#<RoomTypeName>([^<]*)</RoomTypeName>#', $block, $t)
                ? trim($t[1])
                : '';

            if (!isset($rooms[$roomId])) {
                $rooms[$roomId] = ['room_type' => $type, 'bookings' => 0];
            }
            if ($rooms[$roomId]['room_type'] === '' && $type !== '') {
                $rooms[$roomId]['room_type'] = $type;
            }
            $rooms[$roomId]['bookings']++;
        }
    }
    private function fetch(EzeeGroup $group, string $from, string $to): ?string
    {
        $body = '<RES_Request>'
            . '<Request_Type>Booking</Request_Type>'
            . '<Authentication>'
            . '<HotelCode>' . $group->hotel_code . '</HotelCode>'
            . '<AuthCode>' . $group->auth_key . '</AuthCode>'
            . '</Authentication>'
            . '<FromDate>' . $from . '</FromDate>'
            . '<ToDate>' . $to . '</ToDate>'
            . '</RES_Request>';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::ENDPOINT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
        ]);
        $response = curl_exec($ch);
        $failed   = curl_errno($ch) !== 0;
        curl_close($ch);

        return $failed ? null : $response;
    }

    /**
     * Only two fields per booking are needed, so the response is scanned
     * directly — the sync's full parse is sensitive to how EZEE collapses
     * single-element lists.
     *
     * @return array<string,string>
     */
    private function parseRoomIds(string $xml): array
    {
        $map = [];

        if (!preg_match_all('#<BookingTran>(.*?)</BookingTran>#s', $xml, $blocks)) {
            return $map;
        }

        foreach ($blocks[1] as $block) {
            if (!preg_match('#<SubBookingId>([^<]*)</SubBookingId>#', $block, $sub)) {
                continue;
            }
            if (!preg_match('#<eZeePMSRoomid>([^<]*)</eZeePMSRoomid>#', $block, $room)) {
                continue;
            }
            $roomId = trim($room[1]);
            if ($roomId !== '') {
                $map[trim($sub[1])] = $roomId;
            }
        }

        return $map;
    }

    /** @return array<array{0:string,1:string}> */
    private function windows(string $from, string $to): array
    {
        $windows = [];
        $start   = strtotime($from);
        $end     = strtotime($to);

        while ($start <= $end) {
            $stop      = min($end, strtotime('+' . self::WINDOW_DAYS . ' days', $start));
            $windows[] = [date('Y-m-d', $start), date('Y-m-d', $stop)];
            $start     = strtotime('+1 day', $stop);
        }

        return $windows;
    }

    private function errorFrom(string $xml): ?string
    {
        foreach (['#<error>([^<]*)</error>#i',
                  '#"ErrorMessage"\s*:\s*"([^"]*)"#',
                  '#<ErrorMessage>([^<]*)</ErrorMessage>#'] as $pattern) {
            if (!preg_match($pattern, $xml, $m)) {
                continue;
            }
            $message = trim($m[1]);
            if ($message === '' || strcasecmp($message, 'Success') === 0) {
                continue;
            }
            return $message;
        }

        return null;
    }

    private function log(string $line): void
    {
        if ($this->logger) {
            ($this->logger)($line);
        }
    }
}
