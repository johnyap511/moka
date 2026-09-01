<?php

namespace App\Support;

use App\EzeeGroup;

/**
 * Reads the list of units EZEE holds for a property.
 *
 * EZEE has no endpoint that answers "what units does this property have"
 * directly. The housekeeping room-status service is the one read that enumerates
 * every physical unit — booked or not, blocked or not — which is what mapping
 * requires. The booking feed can only ever reveal units somebody has stayed in.
 *
 * The same response also carries a list of in-house guests. That is deliberately
 * ignored: this class is about rooms, and there is no reason to move guest data
 * around for it.
 */
class EzeeRoomFeed
{
    private const ENDPOINT = 'https://live.ipms247.com/index.php/page/service.hkinfoforkaterina';

    /**
     * @return array<int,array{room_name:string,room_id:?string,unit_id:?string,room_type:?string,is_blocked:bool}>
     */
    public function unitsFor(EzeeGroup $group): array
    {
        $response = $this->post([
            'authcode'   => $group->auth_key,
            'hotel_code' => $group->hotel_code,
        ]);

        return $response === null ? [] : $this->parseUnits($response);
    }

    /**
     * Split out from the request so the shape of EZEE's reply can be exercised
     * without going near the network.
     *
     * @return array<int,array{room_name:string,room_id:?string,unit_id:?string,room_type:?string,is_blocked:bool}>
     */
    public function parseUnits(string $response): array
    {
        $decoded = json_decode($response, true);

        if (!is_array($decoded) || !isset($decoded['roomlist']) || !is_array($decoded['roomlist'])) {
            return [];
        }

        $units = [];

        foreach ($decoded['roomlist'] as $row) {
            $name = trim((string) ($row['roomname'] ?? ''));

            if ($name === '') {
                continue;
            }

            $units[] = [
                'room_name'  => $name,
                'room_id'    => $row['roomid'] ?? null,
                'unit_id'    => $row['unitid'] ?? null,
                'room_type'  => $row['roomtypename'] ?? null,
                // EZEE answers "No"/"Yes" rather than a boolean.
                'is_blocked' => strtolower((string) ($row['isblocked'] ?? 'no')) === 'yes',
            ];
        }

        return $units;
    }

    private function post(array $body): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::ENDPOINT,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $failed   = curl_errno($ch) !== 0;
        curl_close($ch);

        return $failed ? null : $response;
    }
}
