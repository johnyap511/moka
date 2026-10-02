<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VerificationRejectMail;
use App\OtherModel\UserMail;
use App\User;
use App\VerifyAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Every guest: the ones the eZee sync creates from bookings (no login) and the
     * ones who registered on the website. Until 2 Oct 2026 this page listed only the
     * 615 website accounts, oldest first, so new booking guests never appeared.
     */
    public function index(Request $request)
    {
        $type = in_array($request->input('type'), ['booking', 'website'], true) ? $request->input('type') : 'all';
        $q    = trim((string) $request->input('q'));

        $base = fn () => User::join('role_user', 'users.id', '=', 'role_user.user_id')->where('role_user.role_id', 2);
        $website = fn ($w) => $w->whereNotNull('users.password')->orWhereNotNull('users.provider');
        if ($request->input('export') === 'csv') {
            return $this->exportGuests($base(), $website, $type, $q);
        }

        $counts = [
            'all'     => $base()->count(),
            'website' => $base()->where($website)->count(),
        ];
        $counts['booking'] = $counts['all'] - $counts['website'];

        $query = $base()->select('users.*')
            ->selectSub(DB::table('bookings')->selectRaw('COUNT(*)')->whereColumn('bookings.user_id', 'users.id')->where('bookings.status', '!=', 1), 'bookings_count')
            ->selectSub(DB::table('bookings')->selectRaw('MAX(check_in)')->whereColumn('bookings.user_id', 'users.id')->where('bookings.status', '!=', 1), 'last_stay');
        if ($type === 'website') {
            $query->where($website);
        } elseif ($type === 'booking') {
            $query->whereNull('users.password')->whereNull('users.provider');
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $w->where('users.name', 'like', $like)->orWhere('users.last_name', 'like', $like)
                    ->orWhere('users.email', 'like', $like)->orWhere('users.phone', 'like', $like);
                if (ctype_digit(ltrim($q, '#'))) {
                    $w->orWhere('users.id', (int) ltrim($q, '#'));
                }
            });
        }
        $users = $query->orderByDesc('users.id')->paginate(50)->appends($request->only('type', 'q'));

        return view('admin.user.index', compact('users', 'type', 'counts', 'q'));
    }

    /** CSV of the guests in the current tab and search, streamed so 60,000 rows do not exhaust memory. */
    private function exportGuests($query, $website, string $type, string $q)
    {
        $query->select('users.id', 'users.name', 'users.last_name', 'users.email', 'users.country_code', 'users.phone', 'users.created_at', 'users.password', 'users.provider');
        if ($type === 'website') {
            $query->where($website);
        } elseif ($type === 'booking') {
            $query->whereNull('users.password')->whereNull('users.provider');
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(fn ($w) => $w->where('users.name', 'like', $like)->orWhere('users.last_name', 'like', $like)->orWhere('users.email', 'like', $like)->orWhere('users.phone', 'like', $like));
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Guest ID', 'First name', 'Last name', 'Email', 'Phone', 'Type', 'Added']);
            $query->orderBy('users.id')->chunk(2000, function ($rows) use ($out) {
                foreach ($rows as $u) {
                    fputcsv($out, [$u->id, $u->name, $u->last_name, $u->email, trim($u->country_code . ' ' . $u->phone), ($u->password !== null || $u->provider !== null) ? 'Website' : 'Booking', $u->created_at]);
                }
            });
            fclose($out);
        }, 'Guests_' . $type . '_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function userVerify()
    {
//        $verifications = VerifyAccount::where('status', 1)->get();
        $verifications = VerifyAccount::all();
        return view('admin.user.userVerify', compact('verifications'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userVerifyStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'status' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $verify = VerifyAccount::where('user_id', $request->user_id)->first();
        $verify->update(['status' => $request->status]);
        if ($request->status == 0) {
            $user = User::find($request->user_id);
            $data22['name'] = $user->name ?? '';
            $userEmail = $user->email ?? '';
            $userMail = UserMail::create(['user_id' => $request->user_id, 'mailable_type' => 'verify_rejected', 'mailable_id' => $request->user_id, 'status' => 1]);
            Mail::to($userEmail)->queue(new VerificationRejectMail($data22));
        }
        return back()->with('success', 'User status is updated successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        return view('admin.user.userDetail', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        return view('admin.user.editUser', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->only("name", "email", "phone", "status", "country_code");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|max:150',
            'password' => 'nullable|string|max:120',
            'country_code' => 'required|numeric',
            'phone' => 'required|string|max:50',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $user = User::find($id);
        if (empty($user)) {
            return back()->with('error', 'Invalid user detail!')->withInput();
        }
        $exist = User::where('email', $data['email'])->first();
        if (!empty($data['email']) && !empty($exist) && $exist->id != $user->id) {
            return back()->with('error', 'This email is already registered!')->withInput();
        }

        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return back()->with('success', 'User is updated successfully!');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function userListAdmin()
    {
        $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 2)->whereNull('password')->whereNull('provider')
            ->orderBy('users.created_at', 'desc')
            ->limit(40)->get();
        // dd($users);
        // The Guests page now lists everyone; this old list of the latest 40 booking guests goes there.
        return redirect('/admin/users?type=booking');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function admin_Ajex(Request $request)
    {
        // Page Length
        $pageNumber = ($request->start / $request->length) + 1;
        $pageLength = $request->length;
        $skip = ($pageNumber - 1) * $pageLength;

        // Page Order
        $orderColumnIndex = $request->order[0]['column'] ?? '0';
        $orderBy = $request->order[0]['dir'] ?? 'desc';

        // Build Query
        // Main
        $query = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 2)->whereNull('password')->whereNull('provider');
        // Search
        $search = $request->cSearch;
        $query = $query->where(function ($query) use ($search) {
            $query->orWhere('name', 'like', "%" . $search . "%");
            $query->orWhere('last_name', 'like', "%" . $search . "%");
            $query->orWhere('status', 'like', "%" . $search . "%");
            $query->orWhere('email', 'like', "%" . $search . "%");
            $query->orWhere('remember_token', 'like', "%" . $search . "%");
            $query->orWhere('phone', 'like', "%" . $search . "%");
        });

        $orderByName = 'name';

        switch ($orderColumnIndex) {
            case '0':
                $orderByName = 'id';
                break;
            case '1':
                $orderByName = 'created_at';
                break;
            case '2':
                $orderByName = 'name';
                break;
            case '3':
                $orderByName = 'email';
                break;
            case '4':
                $orderByName = 'phone';
                break;
            case '5':
                $orderByName = 'status';
                break;
            default:
                $orderByName = 'name';
                break;
        }

        $query = $query->orderBy($orderByName, $orderBy);
        $recordsFiltered = $recordsTotal = $query->count();
        $users = $query->skip($skip)->take($pageLength)->get();

        return response()->json(["draw" => $request->draw, "recordsTotal" => $recordsTotal, "recordsFiltered" => $recordsFiltered, 'data' => $users], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
        return back()->with('success', 'User is deleted successfully!');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportUsers($type)
    {
        if ($type == 'user_admin') {
            $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
                ->where('role_id', 2)->whereNull('password')->whereNull('provider')
                ->get();
            $title = 'Users_List_Admin_' . date('Y-m-d');
            $exportData[0] = ['Users list - Admin'];
        } elseif ($type == 'user_website') {
            $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
                ->where('role_id', 2)->where(function ($query) {
                $query->orwhereNotNull('password')
                    ->orwhereNotNull('provider');
            })->get();
            $title = 'Users_List_Website_' . date('Y-m-d');
            $exportData[0] = ['Users list - Website'];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $exportData[1] = ['generated on ' . date('d F Y')];
        $exportData[2] = [];
        $exportData[3] = ['User ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Created At'];
        foreach ($users as $user) {
            $exportData[] = [$user->id, $user->name, $user->last_name, $user->email, $user->country_code . $user->phone, $user->created_at];
        }

        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:D2')->getFont()->setSize(9);

        $styleArrayHeader = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'ffc000',
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font' => ['bold' => true],
        ];
        $sheet->getStyle('A4:F4')->applyFromArray($styleArrayHeader);

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(8);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $sheet->fromArray(
            $exportData,
            null,
            'A1'
        );

        ob_end_clean();
        $extension = 'Xlsx';
        $writer = IOFactory::createWriter($spreadsheet, $extension);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"" . $title . ".{$extension}\"");
        $writer->save('php://output');
        exit();
    }

}
