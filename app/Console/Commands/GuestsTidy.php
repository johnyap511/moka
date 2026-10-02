<?php

namespace App\Console\Commands;

use App\Support\Guests;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GuestsTidy extends Command
{
    protected $signature = 'guests:tidy {--apply : write the changes (default: report only)}';
    protected $description = 'Give every guest one clean international phone number and a proper dialling code; originals are kept';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $n = ['guests' => 0, 'phone_clean' => 0, 'phone_unclear' => 0, 'phone_blank' => 0, 'country_fixed' => 0, 'samples_unclear' => []];
        DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')->where('role_user.role_id', 2)
            ->select('users.id', 'users.phone', 'users.country_code', 'users.phone_e164')->orderBy('users.id')
            ->chunk(2000, function ($rows) use (&$n, $apply) {
                foreach ($rows as $u) {
                    $n['guests']++;
                    $set = [];
                    $e164 = Guests::e164($u->phone, $u->country_code);
                    if ($u->phone === null || trim($u->phone) === '') {
                        $n['phone_blank']++;
                    } elseif ($e164) {
                        $n['phone_clean']++;
                        if ($u->phone_e164 !== $e164) {
                            $set['phone_e164'] = $e164;
                        }
                    } else {
                        $n['phone_unclear']++;
                        if (count($n['samples_unclear']) < 12) {
                            $n['samples_unclear'][] = $u->phone . ' [' . $u->country_code . ']';
                        }
                    }
                    if (preg_match('/[A-Za-z]/', (string) $u->country_code)) {
                        $dial = Guests::dialOf($e164) ?: Guests::dialCode($u->country_code);
                        if ($dial) {
                            $n['country_fixed']++;
                            $set['country_code'] = $dial;
                            if ($apply) {
                                DB::table('users_contact_backup')->insertOrIgnore(['user_id' => $u->id, 'country_code' => $u->country_code, 'phone' => $u->phone, 'backed_up_at' => now()]);
                            }
                        }
                    }
                    if ($apply && $set) {
                        DB::table('users')->where('id', $u->id)->update($set);
                    }
                }
            });
        $samples = $n['samples_unclear'];
        unset($n['samples_unclear']);
        $this->line(($apply ? 'APPLIED ' : 'DRY RUN ') . json_encode($n));
        $this->line('unclear examples: ' . implode(' | ', $samples));
        if ($apply) {
            \App\DataLog::create(['related_id' => 0, 'title' => 'Guest contact tidy', 'status' => 'done', 'data' => json_encode($n + ['note' => 'phone_e164 filled; country names in country_code replaced by dialling codes (originals in users_contact_backup). Phones as typed were not changed.'])]);
        }

        return 0;
    }
}
