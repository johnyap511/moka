<?php
namespace App\Http\Controllers\Admin;

use App\AccessToken;
use App\Booking;
use App\Export\ListingExport;
use App\Group;
use App\Http\Controllers\Controller;
use App\Listing;
use App\ListingCategory;
use App\ListingDetail;
use App\ListingGroup;
use App\ListingPrice;
use App\ListingPriceDetail;
use App\ListingReport;
use App\Support\EzeePricing;
use App\Mail\MonthlyReport;
use App\update_excel;
use App\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Exception;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;
use Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        // Archived properties are ones the business no longer manages. They are
        // kept, not deleted, so the decision can be reversed and their booking
        // history stays intact.
        $listings = $showArchived
            ? Listing::archived()->get()
            : Listing::active()->get();

        $archivedCount = Listing::archived()->count();

        return view('admin.listing.index', compact('listings', 'showArchived', 'archivedCount'));
    }

    /**
     * Archive or restore properties.
     *
     * Takes a list so the screen can act on a selection in one request, and
     * answers JSON so rows can be removed in place — a form post reloaded the
     * page and threw the reader back to the top, which is unusable on a list of
     * three hundred.
     */
    public function setArchived(Request $request)
    {
        $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer',
            'archived' => 'required|boolean',
        ]);

        $archived = $request->boolean('archived');

        // withArchived: restoring has to reach rows the global scope hides.
        $count = Listing::withArchived()
            ->whereIn('id', $request->input('ids'))
            ->update(['archived_at' => $archived ? now() : null]);

        $noun = $count === 1 ? 'property' : 'properties';

        return response()->json([
            'ok'      => true,
            'count'   => $count,
            'message' => $archived
                ? "Archived {$count} {$noun}. They will not be assigned new bookings."
                : "Restored {$count} {$noun}.",
        ]);
    }

    /**
     * Flip a property between active and inactive.
     *
     * Status is the live/not-live switch, separate from archiving. Toggling it
     * from the badge saves opening the edit form for a one-field change.
     */
    public function toggleStatus(Request $request, $id)
    {
        $listing = Listing::findOrFail($id);

        $listing->status = (int) $listing->status === 1 ? 0 : 1;
        $listing->save();

        return response()->json([
            'ok'     => true,
            'status' => (int) $listing->status,
            'label'  => (int) $listing->status === 1 ? 'Active' : 'Inactive',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = Group::all();
        $owners = $this->owners();
        return view('admin.listing.listingCreate', compact('groups', 'owners'));
    }

    /**
     * Owners are role 3. The listing forms need them so listings.user_id can be
     * set — without it a listing is created unowned and cannot be attached to
     * an owner from the UI at all.
     */
    private function owners()
    {
        return User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 3)
            ->orderBy('users.email')
            ->get(['users.id', 'users.name', 'users.last_name', 'users.email']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only(
            "name",
            "title",
            "address",
            "agent_code",
            "default_price",
            "status",
            "user_id",
            "type",
            "profit",
            'tourism_tax_type',
            'tourism_tax_amount',
            "cleaning_fee",
            'ezee_hotel_code',
            'room_type'
        );
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:120',
            'title'              => 'required|string|max:250',
            'address'            => 'required|string|max:200',
            'agent_code'         => 'required|string|max:200',
            'default_price'      => 'required|numeric',
            'user_id'            => 'nullable|integer',
            'status'             => 'required|integer',
            'tourism_tax_type'   => 'required|string|max:20',
            'tourism_tax_amount' => 'required|numeric',
            'type'               => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $photo = $request->banner;
        if (! empty($photo)) {
            $filename  = 'bn' . time() . '.jpg';
            $path      = public_path('images/listing/' . $filename);
            $watermark = public_path('images/layout/logo4.png');
            $watermark = Image::make($watermark);
            Image::make($photo)->resize(1400, null, function ($constraint) {
                $constraint->aspectRatio();
            })->insert($watermark, 'center')->save($path);
            $data['banner'] = $filename;
        }
        $exist = Listing::where([['title', $data['title']], ['name', $data['name']]])->first();
        if (! empty($exist)) {
            return back()->with('error', 'A listing with this name and title exist!')->withInput();
        }

        $listing = Listing::create($data);
        $key     = sha1($listing->id);
        $listing->update(['key' => $key]);

        $priceDetailData = $request->only(
            'discount_has',
            'discount_fixed',
            'discount_amount',
            'insurance_has',
            'insurance_fixed',
            'insurance_amount',
            'promo_has',
            'promo_code',
            'promo_amount',
            'advance_rental',
            'security_deposit',
            'utility_deposit'
        );
        $priceDetailData['listing_id'] = $listing->id;
        if (empty($priceDetailData['advance_rental'])) {
            $priceDetailData['advance_rental'] = 0;
        }
        if (empty($priceDetailData['security_deposit'])) {
            $priceDetailData['security_deposit'] = 0;
        }
        if (empty($priceDetailData['utility_deposit'])) {
            $priceDetailData['utility_deposit'] = 0;
        }
        ListingPriceDetail::create($priceDetailData);

        $cat = $request->category;
        ListingCategory::create(['listing_id' => $listing->id, 'category_id' => $cat]);

        if ($request->type == "group") {
            ListingGroup::create(['group_id' => $request->group_id, 'listing_id' => $listing->id, 'group_type_id' => $request->group_type]);
            $listing->update(['profit' => 20]);
        }

        if ($request->user_listing == 1) {
            return redirect('/admin/owners/' . $request->user_id . '/listing')->with('success', 'Listing is created successfully!');
        }
        return redirect('/admin/listing')->with('success', 'Listing is created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $groups             = Group::all();
        $owners             = $this->owners();
        $listing            = Listing::find($id);
        $categories         = ListingCategory::where('listing_id', $id)->pluck('category_id')->toArray();
        $listingGroup       = ListingGroup::where('listing_id', $id)->first();
        $listingPriceDetail = ListingPriceDetail::where('listing_id', $id)->first();
        if (empty($listingPriceDetail)) {
            $listingPriceDetail = ListingPriceDetail::create(['listing_id' => $id]);
        }
        return view('admin.listing.listingEdit', compact('listing', 'categories', 'groups', 'owners', 'listingGroup', 'listingPriceDetail'));
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
        $data = $request->only(
            "name",
            "title",
            "address",
            "agent_code",
            "default_price",
            "status",
            "user_id",
            "type",
            "profit",
            "cleaning_fee",
            'tourism_tax_type',
            'tourism_tax_amount',
            'ezee_hotel_code',
            'ezee_auth_code',
            'ezee_room_id',
            'room_type'
        );
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:120',
            'title'              => 'required|string|max:250',
            'address'            => 'required|string|max:200',
            'agent_code'         => 'required|string|max:200',
            'default_price'      => 'required|numeric',
            'user_id'            => 'nullable|integer',
            'status'             => 'required|integer',
            'tourism_tax_type'   => 'required|string|max:20',
            'tourism_tax_amount' => 'required|numeric',
        ]);
        if ($request->waterO == 1) {
            $data['water']            = $request->water;
            $approval['water_option'] = $request->water;
        } else {
            $data['water']            = null;
            $approval['water_option'] = null;
        }
        if ($request->internetO == 1) {
            $data['internet']            = $request->internet;
            $approval['internet_option'] = $request->internet;
        } else {
            $data['internet']            = null;
            $approval['internet_option'] = null;
        }
        if ($request->electricityO == 1) {
            $data['electricity']            = $request->electricity;
            $approval['electricity_option'] = $request->electricity;
        } else {
            $data['electricity']            = null;
            $approval['electricity_option'] = $request->electricity;
        }
        if ($request->mfsfO == 1) {
            $data['mfsf']            = $request->mfsf;
            $approval['mfsf_option'] = $request->mfsf;
        } else {
            $data['mfsf']            = null;
            $approval['mfsf_option'] = null;
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $photo   = $request->banner;
        $listing = Listing::find($id);
        if (! empty($photo)) {
            $filename  = 'bn' . time() . '.jpg';
            $path      = public_path('images/listing/' . $filename);
            $watermark = public_path('images/layout/logo4.png');
            $watermark = Image::make($watermark);
            Image::make($photo)->resize(1400, null, function ($constraint) {
                $constraint->aspectRatio();
            })->insert($watermark, 'center')->save($path);
            $data['banner'] = $filename;
            if (! empty($listing->banner)) {
                \File::delete('images/listing/' . $listing->banner);
            }
        }
        // dd($data);
        $listing->update($data);

        $priceDetailData = $request->only(
            'discount_has',
            'discount_fixed',
            'discount_amount',
            'insurance_has',
            'insurance_fixed',
            'insurance_amount',
            'promo_has',
            'promo_code',
            'promo_amount',
            'advance_rental',
            'security_deposit',
            'utility_deposit'
        );
        if (empty($priceDetailData['promo_has'])) {
            $priceDetailData['promo_has'] = 0;
        }
        if (empty($priceDetailData['discount_has'])) {
            $priceDetailData['discount_has'] = 0;
        }
        if (empty($priceDetailData['insurance_has'])) {
            $priceDetailData['insurance_has'] = 0;
        }
        $listingPriceDetail = ListingPriceDetail::where('listing_id', $id)->first();
        if (empty($listingPriceDetail)) {
            $priceDetailData['listing_id'] = $id;
            ListingPriceDetail::create($priceDetailData);
        } else {
            $listingPriceDetail->update($priceDetailData);
        }

        // change options in utility report
        $approvals = update_excel::where('listing_id', $id)->get();
        if ($approvals) {
            foreach ($approvals as $approve) {
                update_excel::where('listing_id', $approve->id)->update($approval);
            }
        }
        ListingCategory::where('listing_id', $id)->delete();
        $cat = $request->category;
        ListingCategory::create(['listing_id' => $id, 'category_id' => $cat]);

        if ($request->type == "group") {
            $groupListing = ListingGroup::where('listing_id', $id)->first();
            if (! empty($groupListing)) {
                $groupListing->update(['group_id' => $request->group_id, 'group_type_id' => $request->group_type]);
            } else {
                ListingGroup::create(['group_id' => $request->group_id, 'listing_id' => $id, 'group_type_id' => $request->group_type]);
            }
            //            $listing->update(['profit'=>20]);
        } else {
            ListingGroup::where('listing_id', $id)->delete();
        }

        if ($request->user_listing == 1) {
            return redirect('/admin/owners/' . $request->user_id . '/listing')->with('success', 'Listing is created successfully!');
        }
        return redirect('/admin/listing')->with('success', 'Listing is updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generateEzeeToken(Request $request)
    {
        $id        = $request->id;
        $newToken  = Str::random(40);
        $ezeeToken = AccessToken::where([['name', 'EZEE_API'], ['tokenable_id', $id]])->first();
        if (empty($ezeeToken)) {
            AccessToken::create(['tokenable_type' => 'API', 'tokenable_id' => $id, 'name' => 'EZEE_API', 'token' => sha1($newToken), 'abilities' => '["ezee_api"]', 'last_use' => \Carbon\Carbon::now()]);
        } else {
            $ezeeToken->update(['token' => sha1($newToken)]);
        }

        $data['success'] = 'true';
        $data['token']   = $newToken;
        return $data;
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pricing($id)
    {
        $dates = ListingPrice::where('listing_id', $id)->get();
        return view('admin.listing.listingPrice', compact('id', 'dates'));
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pricingStore(Request $request, $id)
    {
        ListingPrice::where('listing_id', $id)->delete();
        $dates = $request->date;
        $price = $request->price;
        $i     = 0;
        if (! empty($dates)) {
            foreach ($dates as $date) {
                if (! empty($price[$i])) {
                    ListingPrice::create(["listing_id" => $id, "date" => $date, "price" => $price[$i]]);
                }
                $i++;
            }
        }
        return back()->with('success', 'Listing price is updated successfully!');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        return Excel::download(new ListingExport(), 'listing.xlsx');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        $rawDate = $request->date;
        if (empty($rawDate)) {
            $selDate = Carbon::now()->startOfMonth();
        } else {
            $selDate = Carbon::parse(strlen($rawDate) === 7 ? $rawDate . '-01' : $rawDate)->startOfMonth();
        }

        $thisMonth = $selDate->format('m');
        $thisYear  = $selDate->format('Y');

        $allListings = Listing::where('status', 1)->orderBy('name')->get();

        // Utility records for this month keyed by listing_id
        $utilities = update_excel::whereMonth('excel_date', $thisMonth)
            ->whereYear('excel_date', $thisYear)
            ->get()
            ->keyBy('listing_id');

        return view('admin.listing.report', compact('allListings', 'selDate', 'utilities'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function reportExport(Request $request, $id)
    {
        $listing = Listing::where('id', $id)->first();
        if (empty($listing)) {
            return back()->with('error', 'This listing does not exist!');
        }
        $date = date_create($request->date);
        $y    = date_format($date, 'Y');
        $m    = date_format($date, 'm');
        if ($y < 2020) {
            return back()->with('error', 'Invalid date!');
        }
        $reportData = $request->only(
            'water',
            'water_amount',
            'internet',
            'internet_amount',
            'electricity',
            'electricity_amount',
            'mfsf',
            'mfsf_amount',
            'adjustment1',
            'adjustment1_text',
            'adjustment1_amount',
            'adjustment2',
            'adjustment2_text',
            'adjustment2_amount',
            'adjustment3',
            'adjustment3_text',
            'adjustment3_amount'
        );

        $report = ListingReport::where([['listing_id', $id], ['year', $y], ['month', $m]])->first();

        $thisMonthStart = date_format($date, 'Y-m-01');
        $nextMonth3     = date_create($thisMonthStart)->modify('+1 month');
        $thisMonthEnd   = date_format($nextMonth3, 'Y-m-01');
        $books          = Booking::where([['listing_id', $id], ['status', '>=', 5], ['check_in', '>=', $thisMonthStart], ['check_in', '<', $thisMonthEnd], ['check_out', '>', $thisMonthStart]])->orderBy('check_in', 'ASC')->get();

        $alphabet = range('A', 'Z');
        //        $alphas = range('A', 'Z');
        $hostProfit  = round($listing->profit);
        $ownerProfit = 100 - $hostProfit;
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        for ($i = 0; $i <= 9; $i++) {
            $sheet->mergeCells($alphabet[$i] . '19:' . $alphabet[$i] . '20');
        }

        // Insert the image
        $sheet->setCellValue('M2', '');
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('MOKA');
        $drawing->setDescription('MOKA');
        $drawing->setPath(storage_path('app/public/logo1.png')); // Set the storage path of the image
        $drawing->setCoordinates('M2');
        $drawing->setWidth(90);
        $drawing->setHeight(90);
        $drawing->setWorksheet($sheet);
        // ***************************

        $sheet->mergeCells('M19:M20');
        $sheet->mergeCells('K19:L19');
        $exportData[0]  = ['', 'Moka Venture Sdn Bhd', '', '', '', '', '', '', '', '', '', ''];
        $exportData[1]  = ['', '11.1, Menara Lien Hoe,'];
        $exportData[2]  = ['', '8, Persiaran Tropicana, 47410 Petaling Jaya.'];
        $exportData[3]  = ['', 'hello@homemoka.com'];
        $exportData[4]  = ['', 'www.homemoka.com'];
        $exportData[5]  = [''];
        $exportData[6]  = ['', '', '', '', '', '', 'MONTHLY REPORT'];
        $exportData[7]  = ['', 'Report for the month of ' . $m . '/' . $y];
        $exportData[8]  = [''];
        $exportData[9]  = [''];
        $exportData[10] = ['', 'Period:', $m . '/' . $y];
        $exportData[11] = ['', 'Unit:', $listing->name];
        $exportData[12] = ['', 'Profit Share %:', '"' . $ownerProfit . ':' . $hostProfit . '"'];
        $exportData[13] = ['', 'Landlord:', $ownerProfit . '%'];
        $exportData[14] = ['', 'Host:', $hostProfit . '%'];
        $exportData[15] = [''];
        $exportData[16] = [''];

        $exportData[17] = ['No.', 'OTA', 'Check-in Date', 'Check-out Date', 'No. of Nights', 'Rate/Night', 'Total Price Charged', 'M&A Fee', 'Total Revenue (RM)', 'Gross Profit (RM)', 'Remarks', 'Remarks'];
        $exportData[18] = ['', '', '', '', '', '', '', '', '', 'Landlord', 'Host'];

        $x                   = 19;
        $no                  = 1;
        $bookedDays          = 0;
        $totalOwnerPrice     = 0;
        $new_totalOwnerPrice = 0;
        $totalHostPrice      = 0;
        $ota                 = 0;
        $ota1                = 0;
        $discount            = 0;
        $ota2                = 0;
        $water               = null;
        $internet            = null;
        $electricity         = null;
        $adjustment1         = null;
        $adjustment2         = null;
        $adjustment3         = null;
        foreach ($books as $book) {
            $checkIn  = $book->check_in;
            $checkOut = $book->check_out;
            $nights   = $book->nights;
            $otaPrice = $book->ota_fee;
            // $booking_id = $book->id;
            if ($book->check_in >= $thisMonthStart && $book->check_in <= $thisMonthEnd) {
                // dd("1");
                if ($book->check_out <= $thisMonthEnd) {
                    $nights = $book->nights;
                } else {
                    $earlier = new \DateTime($thisMonthEnd);
                    $later   = new \DateTime($book->check_out);
                    $diff    = $later->diff($earlier)->format("%a");
                    // dd($book->nights);
                    if ($book->nights > $diff) {
                        $nights = $book->nights - $diff;
                    } else {
                        $nights = $diff - $book->nights;
                    }

                    $checkOut = $thisMonthEnd;
                    //                    $otaPrice = round($otaPrice*($nights/$book->nights), 2);
                }
            } elseif ($book->check_out > $thisMonthStart && $book->check_out <= $thisMonthEnd) {

                $earlier = new \DateTime($book->check_in);
                $later   = new \DateTime($thisMonthStart);
                $diff    = $later->diff($earlier)->format("%a");
                //                $nights = $diff;
                if ($book->nights > $diff) {
                    $nights = $book->nights - $diff;
                } else {
                    $nights = $diff - $book->nights;
                }
                // dd($nights);
                $checkIn = $thisMonthStart;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            } elseif ($book->check_in <= $thisMonthStart && $book->check_out >= $thisMonthEnd) {
                $nights   = date_format($date, 't');
                $checkIn  = $thisMonthStart;
                $checkOut = $thisMonthEnd;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            }
            $discount = round($book->discount_fee, 2);
            if ($checkIn != $checkOut && $checkOut <= $thisMonthEnd) {
                $todays               = $book->created_at->format('Y-m-d');
                $chackdate            = '2022-11-30';
                $ota_cal              = (($book->price_night * $nights));
                $new_ota              = (($book->price_night * $nights)) + $book->cleaning_fee;
                $new_ota1             = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                $sst                  = 0;
                if ($book->source == 'Long Term Rental') {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $sst + $book->sst_cf;
                } else {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                }
                $createdMonth    = $book->created_at->format('m');
                $create_check_in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $book->created_at)->format('Y-m-d');
                // Rates live in EzeePricing so every screen agrees on the fee.
                $ota = EzeePricing::marketingFee(
                    $book->source,
                    $book->price_night * $nights,
                    $book->cleaning_fee,
                    $book->sst,
                    $book->sst_cf,
                    $todays ?: null
                );
                $totalPrice = round($nights * $book->price_night, 2);
                $bookedDays = $bookedDays + $nights;

                // if ($book->source == 'Traveloka') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else if ($book->source == 'Expedia') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else {

                // }
                // $totalPrice = round($nights * $book->price_night, 2);
                // $bookedDays = $bookedDays + $nights;
                $revenue = $totalPrice - $ota - abs($discount);

                $sst = $book->sst;

                $rp_night = ($book->price_night);

                $hostPrice       = round(($revenue * $hostProfit) / 100, 2);
                $ownerPrice      = $revenue - $hostPrice;
                $totalOwnerPrice = $totalOwnerPrice + $ownerPrice;
                $totalHostPrice  = $totalHostPrice + $hostPrice;

                $otaText = '';
                if ($book->source == 'Booking') {
                    $otaText = 'Booking.com';
                } else if ($book->source == 'PMS' || $book->source == 'Walk-in' || $book->source == 'Walk In') {
                    $otaText = 'Website';
                } else {
                    $otaText = $book->source;
                }
                $otaText       = preg_replace('/[^A-Za-z\. ]/', '', $otaText);
                $checkInArray  = explode('-', $checkIn);
                $checkOutArray = explode('-', $checkOut);
                if (count($checkInArray) == 3) {
                    $checkIn = $checkInArray[2] . '/' . $checkInArray[1] . '/' . $checkInArray[0];
                }
                if (count($checkOutArray) == 3) {
                    $checkOut = $checkOutArray[2] . '/' . $checkOutArray[1] . '/' . $checkOutArray[0];
                }

                $exportData[$x] = [$no, $otaText, $checkIn, $checkOut, $nights, $rp_night, $totalPrice, $ota ?? 0, $revenue, round($ownerPrice, 2), $hostPrice, ''];
                $no++;
                $x++;
            }
        }
        $orangeRow                 = $x + 2;
        $exportData[$x]            = ["", "", "", "Total", $bookedDays, "", "", "", "", round($totalOwnerPrice, 2), $totalHostPrice];
        $exportData[$x + 1]        = [""];
        $exportData[$x + 2]        = [""];
        $x                         = $x + 3;
        $bottomCalRow              = $x;
        $exportData[$bottomCalRow] = ["", "", "", "", "", "", "", "", "Gross Profit", round($totalOwnerPrice, 2), $totalHostPrice];
        $x++;
        if ($request->water == 1) {
            if ($listing->water == "A") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                    // $waterOwner =  $waterHost;

                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } elseif ($listing->water == "B") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", $waterOwner, -$waterHost, -$water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } elseif ($listing->water == "C") {
                $water = round($request->water_amount, 2);
                if ($ownerProfit == 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                }
                // $waterHost = 0.00;
                // $waterOwner = $water ;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } elseif ($listing->water == "D") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $waterHost;
                }
                // $waterHost = $water;
                // $waterOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } elseif ($listing->water == "E") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water;
                }
                // $waterHost = $water;
                // $waterOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice + 0;
                $totalHostPrice  = 0;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", $waterOwner, 0, $water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } elseif ($listing->water == "F") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost = $water;
                }
                // $waterHost = $water;
                // $waterOwner = 0.00;
                // $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $waterHost;
                // $totalHostPrice = 0;
                $exportData[$x] = ["", "", "", "", "", "", "", "", "Water Bill", 0, $waterHost, $water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            } else {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                }

                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
                $file = $request->water_receipt;
                if (! empty($file)) {
                    $reportData['water_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->water_receipt)) {
                    Storage::disk('upload_files')->delete($report->water_receipt);
                }
            }
        }
        if ($request->internet == 1) {
            if ($listing->internet == "A") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } elseif ($listing->internet == "B") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;

                $totalHostPrice = $totalHostPrice - $internetHost;
                $exportData[$x] = ["", "", "", "", "", "", "", "", "Internet Bill", $internetOwner, -$internetHost, -$internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } elseif ($listing->internet == "C") {
                $internet = round($request->internet_amount, 2);
                if ($ownerProfit == 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                }
                // $internetHost = 0.00;
                // $internetOwner = $internet ;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } elseif ($listing->internet == "D") {
                $internet = round($request->internet_amount, 2);
                if ($hostProfit == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                }
                // $internetHost = $internet;
                // $internetOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } elseif ($listing->internet == "E") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet;
                }
                // $internetHost = $internet;
                // $internetOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice - 0;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", $internetOwner, 0, $internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } elseif ($listing->internet == "F") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost = $internet;
                }
                // $internetHost = $internet;
                // $internetOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", 0, $internetHost, $internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            } else {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
                $file = $request->internet_receipt;
                if (! empty($file)) {
                    $reportData['internet_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->internet_receipt)) {
                    Storage::disk('upload_files')->delete($report->internet_receipt);
                }
            }
        }
        if ($request->electricity == 1) {
            if ($listing->electricity == "A") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } elseif ($listing->electricity == "B") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;

                $totalHostPrice = $totalHostPrice - $electricityHost;
                $exportData[$x] = ["", "", "", "", "", "", "", "", "Electricity Bill", $electricityOwner, -$electricityHost, -$electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } elseif ($listing->electricity == "C") {
                $electricity = round($request->electricity_amount, 2);
                if ($ownerProfit == 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                }
                // $electricityHost = 0.00;
                // $electricityOwner = $electricity ;
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } elseif ($listing->electricity == "D") {
                $electricity = round($request->electricity_amount, 2);
                if ($hostProfit == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                }
                // $electricityHost = $electricity;
                // $electricityOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } elseif ($listing->electricity == "E") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity;
                }
                // $electricityHost = $electricity;
                // $electricityOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice + 0;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", $electricityOwner, 0, $electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } elseif ($listing->electricity == "F") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost = $electricity;
                }
                // $electricityHost = $electricity;
                // $electricityOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", 0, $electricityHost, $electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            } else {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
                $file = $request->electricity_receipt;
                if (! empty($file)) {
                    $reportData['electricity_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->electricity_receipt)) {
                    Storage::disk('upload_files')->delete($report->electricity_receipt);
                }
            }
        }

        if ($request->mfsf == 1) {
            if ($listing->mfsf == "A") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } elseif ($listing->mfsf == "B") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", $mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } elseif ($listing->mfsf == "C") {
                $mfsf = round($request->mfsf_amount, 2);
                if ($ownerProfit == 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                }
                // $mfsfHost = 0.00;
                // $mfsfOwner = $mfsf ;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } elseif ($listing->mfsf == "D") {
                $mfsf = round($request->mfsf_amount, 2);
                if ($hostProfit == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                }
                // $mfsfHost = $mfsf;
                // $mfsfOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } elseif ($listing->mfsf == "E") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf;
                }
                // $mfsfHost = $mfsf;
                // $mfsfOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice + 0;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", $mfsfOwner, 0, $mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } elseif ($listing->mfsf == "F") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost = $mfsf;
                }
                // $mfsfHost = $mfsf;
                // $mfsfOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", 0, $mfsfHost, $mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            } else {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF +SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
                $file = $request->mfsf_receipt;
                if (! empty($file)) {
                    $reportData['mfsf_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->mfsf_receipt)) {
                    Storage::disk('upload_files')->delete($report->mfsf_receipt);
                }
            }
        }
        if ($request->adjustment1 == 1) {
            if ($request->adjustment1_option == "A") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1 - $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, -$adjustment1Host, $request->adjustment1_remark];
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            } elseif ($request->adjustment1_option == "B") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, -$adjustment1Host, $request->adjustment1_remark];
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            } elseif ($request->adjustment1_option == "C") {
                $adjustment1 = round($request->adjustment1_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                }
                // $adjustment1Host = 0.00;
                // $adjustment1Owner = $adjustment1 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                if ($ownerProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, -$adjustment1Host, $request->adjustment1_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, $adjustment1Host, $request->adjustment1_remark];
                }
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            } elseif ($request->adjustment1_option == "D") {
                $adjustment1 = round($request->adjustment1_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                }
                // $adjustment1Host = $adjustment1;
                // $adjustment1Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, $adjustment1Host, $request->adjustment1_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, -$adjustment1Host, $request->adjustment1_remark];
                }
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            } elseif ($request->adjustment1_option == "E") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1;
                }
                // $adjustment1Host = $adjustment1;
                // $adjustment1Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - 0;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, 0, $request->adjustment1_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, 0, $request->adjustment1_remark];
                }
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            } elseif ($request->adjustment1_option == "F") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host = $adjustment1;
                }
                // $adjustment1Host = $adjustment1;
                // $adjustment1Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment1Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", 0, $adjustment1Host, $request->adjustment1_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment1", 0, $adjustment1Host, $request->adjustment1_remark];
                }
                $x++;
                $file = $request->adjustment1_receipt;
                if (! empty($file)) {
                    $reportData['adjustment1_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment1_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment1_receipt);
                }
            }
        }
        if ($request->adjustment2 == 1) {
            if ($request->adjustment2_option == "A") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2 - $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, -$adjustment2Host, $request->adjustment2_remark];
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            } elseif ($request->adjustment2_option == "B") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, -$adjustment2Host, $request->adjustment2_remark];
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            } elseif ($request->adjustment2_option == "C") {
                $adjustment2 = round($request->adjustment2_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                }
                // $adjustment2Host = 0.00;
                // $adjustment2Owner = $adjustment2 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                if ($ownerProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, -$adjustment2Host, $request->adjustment2_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, $adjustment2Host, $request->adjustment2_remark];
                }
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            } elseif ($request->adjustment2_option == "D") {
                $adjustment2 = round($request->adjustment2_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                }
                // $adjustment2Host = $adjustment2;
                // $adjustment2Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, $adjustment2Host, $request->adjustment2_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, -$adjustment2Host, $request->adjustment2_remark];
                }
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            } elseif ($request->adjustment2_option == "E") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2;
                }
                // $adjustment2Host = $adjustment2;
                // $adjustment2Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice + 0;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, 0, $request->adjustment2_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, 0, $request->adjustment2_remark];
                }
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            } elseif ($request->adjustment2_option == "F") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host = $adjustment2;
                }
                // $adjustment2Host = $adjustment2;
                // $adjustment2Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment2Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", 0, $adjustment2Host, $request->adjustment2_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment2", 0, $adjustment2Host, $request->adjustment2_remark];
                }
                $x++;
                $file = $request->adjustment2_receipt;
                if (! empty($file)) {
                    $reportData['adjustment2_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment2_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment2_receipt);
                }
            }
        }
        if ($request->adjustment3 == 1) {
            if ($request->adjustment3_option == "A") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3 - $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, -$adjustment3Host, $request->adjustment3_remark];
                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            } elseif ($request->adjustment3_option == "B") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, -$adjustment3Host, $request->adjustment3_remark];
                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            } elseif ($request->adjustment3_option == "C") {
                $adjustment3 = round($request->adjustment3_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                }
                // $adjustment3Host = 0.00;
                // $adjustment3Owner = $adjustment3 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                if ($ownerProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, -$adjustment3Host, $request->adjustment3_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, $adjustment3Host, $request->adjustment3_remark];
                }
                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            } elseif ($request->adjustment3_option == "D") {
                $adjustment3 = round($request->adjustment3_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                }
                // $adjustment3Host = $adjustment3;
                // $adjustment3Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, $adjustment3Host, $request->adjustment3_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, -$adjustment3Host, $request->adjustment3_remark];
                }

                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            } elseif ($request->adjustment3_option == "E") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3;
                }
                // $adjustment3Host = $adjustment3;
                // $adjustment3Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice + 0;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, 0, $request->adjustment3_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, 0, $request->adjustment3_remark];
                }

                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            } elseif ($request->adjustment3_option == "F") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host = $adjustment3;
                }
                // $adjustment3Host = $adjustment3;
                // $adjustment3Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment3Host;
                if ($hostProfit == 0) {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", 0, $adjustment3Host, $request->adjustment3_remark];
                } else {
                    $exportData[$x] = ["", "", "", "", "", "", "", "", "Adjustment3", 0, $adjustment3Host, $request->adjustment3_remark];
                }

                $x++;
                $file = $request->adjustment3_receipt;
                if (! empty($file)) {
                    $reportData['adjustment3_receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
                }
                if (! empty($report) && ! empty($report->adjustment3_receipt)) {
                    Storage::disk('upload_files')->delete($report->adjustment3_receipt);
                }
            }
        }

        $reportData['owner_income']    = round($totalOwnerPrice, 2);
        $reportData['platform_income'] = round($totalHostPrice, 2);
        $exportData[$x]                = ["", "", "", "", "", "", "", "", "Net Profit", round($totalOwnerPrice, 2), $totalHostPrice];
        $exportData[$x + 1]            = ["This is a computer-generated document. No signature is required."];
        $lastRow                       = $x + 2;
        $sheet->fromArray(
            $exportData,
            null,
            'B2',
            true
        );

        $sheet->getStyle('C2:C9')->getFont()->setBold(true);
        // $sheet->getStyle('B34')->getFont()->setBold(true)->getColor()->setRGB('000000');
        $sheet->getStyle('B19:M20')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
        $sheet->getStyle('B19:M20')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('44546a');
        $sheet->getStyle('K20:L20')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('a9d08e');
        $sheet->getStyle('K20:L20')->getFont()->getColor()->setRGB('000000');
        $sheet->getStyle('J' . $bottomCalRow . ':M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set narrow margins
        $sheet->getPageMargins()->setTop(0.751969);
        $sheet->getPageMargins()->setBottom(0.751969);
        $sheet->getPageMargins()->setLeft(0.251969);
        $sheet->getPageMargins()->setRight(0.251969);
        $sheet->getPageMargins()->setHeader(0.299213);
        $sheet->getPageMargins()->setFooter(0.299213);

        $styleArray = [
            'borders'   => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ];
        $styleArrayOrange = [
            'borders'   => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'fill'      => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'ffc000',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font'      => [
                'bold' => true,
            ],
        ];
        $totalNightStyle = [
            'borders'   => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font'      => [
                'bold' => true,
            ],
        ];
        $lastRowStyle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
                'top'    => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'fill'    => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'ffff00',
                ],
            ],
            'font'    => [
                'bold' => true,
            ],
        ];
        $sEnd  = 19 + $no;
        $unSel = $lastRow + 5;
        $sheet->getStyle('B19:M' . $sEnd)->applyFromArray($styleArray);
        $sheet->getStyle('H8')->applyFromArray($totalNightStyle);
        $sheet->getStyle('K' . $orangeRow . ':L' . $orangeRow)->applyFromArray($styleArrayOrange);
        $sheet->getStyle('E' . $orangeRow . ':F' . $orangeRow)->applyFromArray($totalNightStyle);
        $sheet->getStyle('J' . $lastRow . ':L' . $lastRow)->applyFromArray($lastRowStyle);
        $sheet->getStyle('O' . $unSel . ':O' . $unSel)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(4);
        for ($i = 2; $i <= 12; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($alphabet[$i])->setWidth(16);
        }
        //*********************************************** */

        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        //        $sheet->getColumnDimension('C')->setAutoSize(true);
        ob_end_clean();
        $extension = 'Xlsx';
        $writer    = IOFactory::createWriter($spreadsheet, $extension);

        if (empty($report)) {
            $reportData['listing_id'] = $id;
            $reportData['year']       = $y;
            $reportData['month']      = $m;
            $reportData['excel_name'] = 'r_' . $id . '_' . $y . $m . '_' . time() . '.xlsx';
            $report                   = ListingReport::create($reportData);
        } else {
            $report->update($reportData);
        }

        $filePath = public_path('/files/report/' . $report->excel_name);
        $writer->save($filePath);

        $headers = [
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, 'Report_' . $y . $m . '.xlsx', $headers);
    }

    public function reportExports(Request $request, $id)
    {
        $listing = Listing::where('id', $id)->first();
        if (empty($listing)) {
            return response()->json(['ok' => false, 'message' => 'Listing not found.']);
        }
        // Support 'Y-m' format from month picker by appending '-01'
        $rawDate = $request->date ?? '';
        if (strlen($rawDate) === 7) { $rawDate .= '-01'; }
        $date = date_create($rawDate);
        $y    = date_format($date, 'Y');
        $m    = date_format($date, 'm');
        if ($y < 2020) {
            return back()->with('error', 'Invalid date!');
        }
        $reportData = $request->only(
            'water',
            'water_amount',
            'internet',
            'internet_amount',
            'electricity',
            'electricity_amount',
            'mfsf',
            'mfsf_amount',
            'adjustment1',
            'adjustment1_text',
            'adjustment1_amount',
            'adjustment2',
            'adjustment2_text',
            'adjustment2_amount',
            'adjustment3',
            'adjustment3_text',
            'adjustment3_amount'
        );

        $report = ListingReport::where([['listing_id', $id], ['year', $y], ['month', $m]])->first();

        $thisMonthStart = date_format($date, 'Y-m-01');
        $nextMonth3     = date_create($thisMonthStart)->modify('+1 month');
        $thisMonthEnd   = date_format($nextMonth3, 'Y-m-01');
        $books          = Booking::where([['listing_id', $id], ['status', '>=', 5], ['check_in', '>=', $thisMonthStart], ['check_in', '<', $thisMonthEnd], ['check_out', '>', $thisMonthStart]])->orderBy('check_in', 'ASC')->get();
        // dd($request);
        $hostProfit          = round($listing['profit'], 2);
        $ownerProfit         = 100 - $hostProfit;
        $bookedDays          = 0;
        $totalOwnerPrice     = 0.00;
        $new_totalOwnerPrice = 0;
        $discount            = 0;
        $totalHostPrice      = 0.00;
        $no                  = 1;
        $array               = [];
        $ota                 = 0;
        $logoPath = public_path('logo1.png');
        $base64   = '';
        if (file_exists($logoPath)) {
            $type   = pathinfo($logoPath, PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $html = "<!DOCTYPE html>
        <html lang='en'>

        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='X-UA-Compatible' content='IE=edge'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>PDF</title>
            <style>
            .table-container {
                font-size:8.5px;
                font-family: 'Open Sans', sans-serif;
            }
                body {
                    margin: -38px 40px;
                     font-size:8.5px;
                    font-family: 'Open Sans', sans-serif;
                }

                .pdf-monthly{.
                    text-align:center;
                }

                .pdf-monthly{
                    border-bottom: 4px double #000;
                    margin-left: 50%;
                    transform: translate(-50%);
                }

                p,
                b {
                    margin: 5px 0;
                }

                table {
                    padding: 0;
                     font-size:8.5px;
                    font-family: 'Open Sans', sans-serif;
                }

                .main-table {
                    border-collapse: collapse;
                    border: none;
                }

                .main-table td {
                    text-align: center;
                    border: 1px solid black;
                    padding: 2px;
                }

                .main-table th {
                    width:75px;
                    background-color: #44546a;
                    color: #fff;
                    border: 1px solid black;
                    padding: 2px;
                    text-align: center;
                }

                .main-table tr:nth-child(1) > th:nth-child(1) {
                    width: 4px;
                }

                .main-table tr:nth-child(1) > th:nth-child(2) {
                    width: 109px;
                }

                .last-row td,
                .end-row td {
                    border: none;
                }

                .last-row td:nth-child(4),
                .last-row td:nth-child(5) {
                    border-bottom: 4px double #000;
                    font-weight: bold;
                }

                .last-row td:nth-child(10),
                .last-row td:nth-child(11) {
                    border-bottom: 4px double #000;
                    background-color: #ffc000;
                    font-weight: bold;
                }

                .border-none td {
                    border: none;
                }

                .end-row td:nth-child(9),
                .end-row td:nth-child(10),
                .end-row td:nth-child(11) {
                    border-top: 2px solid #000;
                    border-bottom: 4px double #000;
                    background-color: #ffff00;
                    font-weight: bold;
                }
            </style>
        </head>

        <body>
            <table class='table-container' width=100%>
                <tr>
                    <td style='text-align:left;'>
                        <b>
                            Moka Venture Sdn Bhd <br>
                            11.1, Menara Lien Hoe, <br>
                            8, Persiaran Tropicana, 47410 Petaling Jaya. <br>
                            hello@homemoka.com <br>
                            www.homemoka.com
                        </b>
                    </td>
                    <td style='text-align:right;'>
                    <img  src='$base64' alt='' srcset='' width='90px'>
                    </td>
                </tr>
            </table>
            </div>
            <br>
            <b class='pdf-monthly'>MONTHLY REPORT</b>
            <br><br>
            <b class=''>Report for the month of $m/$y</b>
            <br><br>
            <table class='table-container'>
                <tr>
                    <td>Period:</td>
                    <td>$m  /  $y</td>
                </tr>
                <tr>
                    <td>Unit:</td>
                    <td>$listing->name</td>
                </tr>
                <tr>
                    <td>Profit Share %:</td>
                    <td>$ownerProfit  :  $hostProfit</td>
                </tr>
                <tr>
                    <td>Landlord:</td>
                    <td>$ownerProfit%</td>
                </tr>
                <tr>
                    <td>Host:</td>
                    <td>$hostProfit%</td>
                </tr>
            </table>
            <br><br>
            <table border='1' class='main-table table-container'>
                <tr>
                    <th rowspan='2'>No.</th>
                    <th rowspan='2'>OTA</th>
                    <th rowspan='2'>Check-in Date</th>
                    <th rowspan='2'>Check-out Date</th>
                    <th rowspan='2'>No. of Nights</th>
                    <th rowspan='2'>Rate/Night</th>
                    <th rowspan='2'>Total Price Charged</th>
                    <th rowspan='2'>M&A Fee</th>
                    <th rowspan='2'>Total Revenue</th>
                    <th colspan='2'>Gross Profit</th>
                    <!-- <th></th> -->
                    <th rowspan='2'>Remarks</th>
                </tr>
                <tr>
                <th style='background: #a9d08e; color: #000;'>Landlord</th>
                <th style='background: #a9d08e; color: #000;'>Host</th>
            </tr>";
        $ota = 0;
        foreach ($books as $book) {
            $checkIn  = $book->check_in;
            $checkOut = $book->check_out;
            $nights   = $book->nights;
            $otaPrice = $book->ota_fee;
            // $booking_id = $book->id;
            if ($book->check_in >= $thisMonthStart && $book->check_in <= $thisMonthEnd) {
                // dd("1");
                if ($book->check_out <= $thisMonthEnd) {
                    $nights = $book->nights;
                } else {
                    $earlier = new \DateTime($thisMonthEnd);
                    $later   = new \DateTime($book->check_out);
                    $diff    = $later->diff($earlier)->format("%a");
                    // dd($book->nights);
                    if ($book->nights > $diff) {
                        $nights = $book->nights - $diff;
                    } else {
                        $nights = $diff - $book->nights;
                    }

                    $checkOut = $thisMonthEnd;
                    //                    $otaPrice = round($otaPrice*($nights/$book->nights), 2);
                }
            } elseif ($book->check_out > $thisMonthStart && $book->check_out <= $thisMonthEnd) {

                $earlier = new \DateTime($book->check_in);
                $later   = new \DateTime($thisMonthStart);
                $diff    = $later->diff($earlier)->format("%a");
                //                $nights = $diff;
                if ($book->nights > $diff) {
                    $nights = $book->nights - $diff;
                } else {
                    $nights = $diff - $book->nights;
                }
                // dd($nights);
                $checkIn = $thisMonthStart;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            } elseif ($book->check_in <= $thisMonthStart && $book->check_out >= $thisMonthEnd) {
                $nights   = date_format($date, 't');
                $checkIn  = $thisMonthStart;
                $checkOut = $thisMonthEnd;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            }

            if ($checkIn != $checkOut && $checkOut <= $thisMonthEnd) {
                $todays               = $book->created_at->format('Y-m-d');
                $chackdate            = '2022-11-30';
                $ota_cal              = (($book->price_night * $nights));
                $new_ota              = (($book->price_night * $nights)) + $book->cleaning_fee;
                $new_ota1             = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                $sst                  = 0;
                if ($book->source == 'Long Term Rental') {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $sst + $book->sst_cf;
                } else {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                }
                $createdMonth    = $book->created_at->format('m');
                $create_check_in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $book->created_at)->format('Y-m-d');
                // Rates live in EzeePricing so every screen agrees on the fee.
                $ota = EzeePricing::marketingFee(
                    $book->source,
                    $book->price_night * $nights,
                    $book->cleaning_fee,
                    $book->sst,
                    $book->sst_cf,
                    $todays ?: null
                );

                $totalPrice = round($nights * $book->price_night, 2);
                $bookedDays = $bookedDays + $nights;

                // if ($book->source == 'Traveloka') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else if ($book->source == 'Expedia') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else {
                //     $revenue = $totalPrice - $ota;
                // }
                // $totalPrice = round($nights * $book->price_night, 2);
                // $bookedDays = $bookedDays + $nights;
                $discount = round($book->discount_fee, 2);

                $revenue = $totalPrice - $ota - abs($discount);

                $sst = $book->sst;

                $rp_night = ($book->price_night);

                $hostPrice       = round(($revenue * $hostProfit) / 100, 2);
                $ownerPrice      = round(($revenue - $hostPrice), 2);
                $totalOwnerPrice = round($totalOwnerPrice + $ownerPrice, 2) ?? '';
                $totalHostPrice  = round($totalHostPrice + $hostPrice, 2) ?? '';

                $otaText = '';
                if ($book->source == 'Booking') {
                    $otaText = 'Booking.com';
                } else if ($book->source == 'PMS' || $book->source == 'Walk-in' || $book->source == 'Walk In') {
                    $otaText = 'Website';
                } else {
                    $otaText = $book->source;
                }
                $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);

                $checkInArray  = explode('-', $checkIn);
                $checkOutArray = explode('-', $checkOut);
                if (count($checkInArray) == 3) {
                    $checkIn = $checkInArray[2] . '/' . $checkInArray[1] . '/' . $checkInArray[0];
                }
                if (count($checkOutArray) == 3) {
                    $checkOut = $checkOutArray[2] . '/' . $checkOutArray[1] . '/' . $checkOutArray[0];
                }
            }
            if ($hostPrice == 0) {
                $hostPrice = '' ?? 0;
            }
            if ($ownerPrice == 0) {
                $ownerPrice = '' ?? 0;
            }

            $html .= " <tr>
                <td>$no</td>
                <td>$otaText</td>
                <td>$checkIn</td>
                <td>$checkOut</td>
                <td>$nights</td>
                <td>$rp_night</td>
                <td>$totalPrice</td>
                <td>$ota</td>
                <td>$revenue</td>
                <td>$ownerPrice</td>
                <td>$hostPrice</td>
                <td></td>
            </tr>";
            $no++;
        }
        if (empty($totalHostPrice) || $totalHostPrice == 0) {
            $totalHostPrice = '' ?? 0;
        }
        if (empty($totalOwnerPrice) || $totalOwnerPrice == 0) {
            $totalOwnerPrice = '' ?? 0;
        }

        if (empty($bookedDays) || $bookedDays == 0) {
            $bookedDays = '' ?? 0;
        }
        $html .= " <tr class='last-row'>
            <td></td>
            <td></td>
            <td></td>
            <td>Total</td>
            <td>$bookedDays</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>$totalOwnerPrice</td>
            <td>$totalHostPrice</td>
            <td></td>
        </tr>
        <tr class='border-none' style='height: 30px;'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <br><br>
        <tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Gross Profit</td>
            <td>$totalOwnerPrice</td>
            <td>$totalHostPrice</td>
            <td></td>
        </tr>";

        if (empty($totalHostPrice) || $totalHostPrice == 0) {
            $totalHostPrice = '' ? '' : 0;
        }
        if (empty($totalOwnerPrice) || $totalOwnerPrice == 0) {
            $totalOwnerPrice = '' ? '' : 0;
        }

        $waters       = '';
        $water_option = '';
        if (isset($_POST['water'])) {
            $waters = $_POST['water'];
        }

        if ($waters == 1) {
            $water        = $_POST['water_amount'];
            $water_option = $listing->water;
            if (! empty($water)) {
                $water = $_POST['water_amount'];
            } else {
                $water = 0;
            }

            if ($water_option == "A") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>- " . abs($waterOwner) . "</td>
            <td>-" . abs($waterHost) . "</td>
            <td>-" . abs($water) . "</td>
            </tr>";
            } elseif ($water_option == "B") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td> " . $waterOwner . "</td>
            <td>-" . abs($waterHost) . "</td>
            <td>-" . abs($water) . "</td>
            </tr>";
            } elseif ($water_option == "C") {
                $water = round($request->water_amount, 2);
                if ($ownerProfit == 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>-" . abs($waterOwner) . "</td>
            <td>" . $waterHost . "</td>
            <td>-" . abs($water) . "</td>
            </tr>";
            } elseif ($water_option == "D") {
                $water = round($request->water_amount, 2);
                if ($hostProfit == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>" . $waterOwner . "</td>
            <td>-" . abs($waterHost) . "</td>
            <td>-" . abs($water) . "</td>
            </tr>";
            } elseif ($water_option == "E") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>" . $waterOwner . "</td>
            <td>" . 0 . "</td>
            <td>" . $water . "</td>
            </tr>";
            } elseif ($water_option == "F") {
                $water     = round($request->water_amount, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>" . 0 . "</td>
            <td>" . $waterHost . "</td>
            <td>" . $water . "</td>
            </tr>";
            } else {
                $water           = round($request->water_amount, 2);
                $waterHost       = round(($water * $hostProfit) / 100, 2);
                $waterOwner      = $water - $waterHost;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Water Bill</td>
            <td>- " . abs($waterOwner) . "</td>
            <td>-" . abs($waterHost) . "</td>
            <td>-" . abs($water) . "</td>
            </tr>";
            }
        }

        $internets = '';
        if (isset($_POST['internet'])) {
            $internets = $_POST['internet'];
        }

        if ($internets == 1) {
            $internet        = $_POST['internet_amount'];
            $internet_option = $listing->internet;
            if (! empty($internet)) {
                $internet = $_POST['internet_amount'];
            } else {
                $internet = 0;
            }

            if ($internet_option == "A") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>- " . abs($internetOwner) . "</td>
            <td>-" . abs($internetHost) . "</td>
            <td>-" . abs($internet) . "</td>
            </tr>";
            } elseif ($internet_option == "B") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td> " . $internetOwner . "</td>
            <td>-" . abs($internetHost) . "</td>
            <td>-" . abs($internet) . "</td>
            </tr>";
            } elseif ($internet_option == "C") {
                $internet = round($request->internet_amount, 2);
                if ($ownerProfit == 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>-" . abs($internetOwner) . "</td>
            <td>-" . abs($internetHost) . "</td>
            <td>-" . abs($internet) . "</td>
            </tr>";
            } elseif ($internet_option == "D") {
                $internet = round($request->internet_amount, 2);
                if ($hostProfit == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>-" . abs($internetOwner) . "</td>
            <td>-" . abs($internetHost) . "</td>
            <td>-" . abs($internet) . "</td>
            </tr>";
            } elseif ($internet_option == "E") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>" . $internetOwner . "</td>
            <td>" . 0 . "</td>
            <td>" . $internet . "</td>
            </tr>";
            } elseif ($internet_option == "F") {
                $internet     = round($request->internet_amount, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>" . 0 . "</td>
            <td>" . $internetHost . "</td>
            <td>" . $internet . "</td>
            </tr>";
            } else {
                $internet        = round($request->internet_amount, 2);
                $internetHost    = round(($internet * $hostProfit) / 100, 2);
                $internetOwner   = $internet - $internetHost;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Internet Bill</td>
            <td>- " . abs($internetOwner) . "</td>
            <td>-" . abs($internetHost) . "</td>
            <td>-" . abs($internet) . "</td>
            </tr>";
            }
        }
        $electricitys = '';
        if (isset($_POST['electricity'])) {
            $electricitys = $_POST['electricity'];
        }

        if ($electricitys == 1) {
            $electricity        = $_POST['electricity_amount'];
            $electricity_option = $listing->electricity;
            if (! empty($electricity)) {
                $electricity = $_POST['electricity_amount'];
            } else {
                $electricity = 0;
            }

            if ($electricity_option == "A") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>- " . abs($electricityOwner) . "</td>
                <td>-" . abs($electricityHost) . "</td>
                <td>-" . abs($electricity) . "</td>
                </tr>";
            } elseif ($electricity_option == "B") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td> " . $electricityOwner . "</td>
                <td>-" . abs($electricityHost) . "</td>
                <td>-" . abs($electricity) . "</td>
                </tr>";
            } elseif ($electricity_option == "C") {
                $electricity = round($request->electricity_amount, 2);
                if ($ownerProfit == 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>-" . abs($electricityOwner) . "</td>
                <td>-" . abs($electricityHost) . "</td>
                <td>-" . abs($electricity) . "</td>
                </tr>";
            } elseif ($electricity_option == "D") {
                $electricity = round($request->electricity_amount, 2);
                if ($hostProfit == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>-" . abs($electricityOwner) . "</td>
                <td>-" . abs($electricityHost) . "</td>
                <td>-" . abs($electricity) . "</td>
                </tr>";
            } elseif ($electricity_option == "E") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>" . $electricityOwner . "</td>
                <td>" . 0 . "</td>
                <td>" . $electricity . "</td>
                </tr>";
            } elseif ($electricity_option == "F") {
                $electricity     = round($request->electricity_amount, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>" . 0 . "</td>
                <td>" . $electricityHost . "</td>
                <td>" . $electricity . "</td>
                </tr>";
            } else {
                $electricity      = round($request->electricity_amount, 2);
                $electricityHost  = round(($electricity * $hostProfit) / 100, 2);
                $electricityOwner = $electricity - $electricityHost;
                $totalOwnerPrice  = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice   = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Electricity Bill</td>
                <td>- " . abs($electricityOwner) . "</td>
                <td>-" . abs($electricityHost) . "</td>
                <td>-" . abs($electricity) . "</td>
                </tr>";
            }
        }

        $mfsfs = '';
        if (isset($_POST['mfsf'])) {
            $mfsfs = $_POST['mfsf'];
        }

        if ($mfsfs == 1) {
            $mfsf        = $_POST['mfsf_amount'];
            $mfsf_option = $listing->mfsf;
            if (! empty($mfsf)) {
                $mfsf = $_POST['mfsf_amount'];
            } else {
                $mfsf = 0;
            }

            if ($mfsf_option == "A") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>- " . abs($mfsfOwner) . "</td>
                <td>-" . abs($mfsfHost) . "</td>
                <td>-" . abs($mfsf) . "</td>
                </tr>";
            } elseif ($mfsf_option == "B") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td> " . $mfsfOwner . "</td>
                <td>-" . abs($mfsfHost) . "</td>
                <td>-" . abs($mfsf) . "</td>
                </tr>";
            } elseif ($mfsf_option == "C") {
                $mfsf = round($request->mfsf_amount, 2);
                if ($ownerProfit == 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>-" . abs($mfsfOwner) . "</td>
                <td>-" . abs($mfsfHost) . "</td>
                <td>-" . abs($mfsf) . "</td>
                </tr>";
            } elseif ($mfsf_option == "D") {
                $mfsf = round($request->mfsf_amount, 2);
                if ($hostProfit == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>-" . abs($mfsfOwner) . "</td>
                <td>-" . abs($mfsfHost) . "</td>
                <td>-" . abs($mfsf) . "</td>
                </tr>";
            } elseif ($mfsf_option == "E") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>" . $mfsfOwner . "</td>
                <td>" . 0 . "</td>
                <td>" . $mfsf . "</td>
                </tr>";
            } elseif ($mfsf_option == "F") {
                $mfsf     = round($request->mfsf_amount, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>" . 0 . "</td>
                <td>" . $mfsfHost . "</td>
                <td>" . $mfsf . "</td>
                </tr>";
            } else {
                $mfsf            = round($request->mfsf_amount, 2);
                $mfsfHost        = round(($mfsf * $hostProfit) / 100, 2);
                $mfsfOwner       = $mfsf - $mfsfHost;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>MF + SF Bill</td>
                <td>- " . abs($mfsfOwner) . "</td>
                <td>-" . abs($mfsfHost) . "</td>
                <td>-" . abs($mfsf) . "</td>
                </tr>";
            }
        }
        $adjustment1s = '';
        if (isset($_POST['adjustment1'])) {
            $adjustment1s = $_POST['adjustment1'];
        }

        if ($adjustment1s == 1) {
            $adjustment1        = $_POST['adjustment1_amount'];
            $adjustment1_option = $_POST['adjustment1_option'];
            if (! empty($adjustment1)) {
                $adjustment1 = $_POST['adjustment1_amount'];
            } else {
                $adjustment1 = 0;
            }

            if ($adjustment1_option == "A") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1 - $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                if ($adjustment1Host == 0) {
                    $adjustment1Host = '';
                }
                if ($adjustment1Owner == 0) {
                    $adjustment1Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
                <td>- " . abs($adjustment1Owner) . "</td>
                <td>-" . abs($adjustment1Host) . "</td>
                <td>" . $request->adjustment1_remark . "</td>
                </tr>";
            } elseif ($adjustment1_option == "B") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                if ($adjustment1Host == 0) {
                    $adjustment1Host = '';
                }
                if ($adjustment1Owner == 0) {
                    $adjustment1Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
                <td>" . $adjustment1Owner . "</td>
                <td>-" . abs($adjustment1Host) . "</td>
                <td>" . $request->adjustment1_remark . "</td>
                </tr>";
            } elseif ($adjustment1_option == "C") {
                $adjustment1 = round($request->adjustment1_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
                <td>-" . abs($adjustment1Owner) . "</td>
                <td>-" . abs($adjustment1Host) . "</td>
                <td>" . $request->adjustment1_remark . "</td>
                </tr>";
            } elseif ($adjustment1_option == "D") {
                $adjustment1 = round($request->adjustment1_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
           <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
                <td>-" . abs($adjustment1Owner) . "</td>
                <td>-" . abs($adjustment1Host) . "</td>
                <td>" . $request->adjustment1_remark . "</td>
                </tr>";
            } elseif ($adjustment1_option == "E") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice + 0;

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>" . $adjustment1Owner . "</td>
            <td>" . 0 . "</td>
            <td>" . $request->adjustment1_remark . "</td>
            </tr>";
            } elseif ($adjustment1_option == "F") {
                $adjustment1     = round($request->adjustment1_amount, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $totalHostPrice = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
      <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>" . 0 . "</td>
            <td>" . $adjustment1Host . "</td>
            <td>" . $request->adjustment1_remark . "</td>
            </tr>";
            }
        }

        $adjustment2s = '';
        if (isset($_POST['adjustment2'])) {
            $adjustment2s = $_POST['adjustment2'];
        }

        if ($adjustment2s == 1) {
            $adjustment2        = $_POST['adjustment2_amount'];
            $adjustment2_option = $_POST['adjustment2_option'];
            if (! empty($adjustment2)) {
                $adjustment2 = $_POST['adjustment2_amount'];
            } else {
                $adjustment2 = 0;
            }

            if ($adjustment2_option == "A") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2 - $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                if ($adjustment2Host == 0) {
                    $adjustment2Host = '';
                }
                if ($adjustment2Owner == 0) {
                    $adjustment2Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
               <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>- " . abs($adjustment2Owner) . "</td>
                <td>-" . abs($adjustment2Host) . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            } elseif ($adjustment2_option == "B") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                if ($adjustment2Host == 0) {
                    $adjustment2Host = '';
                }
                if ($adjustment2Owner == 0) {
                    $adjustment2Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                 <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>" . $adjustment2Owner . "</td>
                <td>-" . abs($adjustment2Host) . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            } elseif ($adjustment2_option == "C") {
                $adjustment2 = round($request->adjustment2_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>-" . abs($adjustment2Owner) . "</td>
                <td>-" . abs($adjustment2Host) . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            } elseif ($adjustment2_option == "D") {
                $adjustment2 = round($request->adjustment2_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                 <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>-" . abs($adjustment2Owner) . "</td>
                <td>-" . abs($adjustment2Host) . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            } elseif ($adjustment2_option == "E") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice + 0;

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>" . $adjustment2Owner . "</td>
                <td>" . 0 . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            } elseif ($adjustment2_option == "F") {
                $adjustment2     = round($request->adjustment2_amount, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment2Host;

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
                <td>" . 0 . "</td>
                <td>" . $adjustment2Host . "</td>
                <td>" . $request->adjustment2_remark . "</td>
                </tr>";
            }
        }

        $adjustment3s = '';
        if (isset($_POST['adjustment3'])) {
            $adjustment3s = $_POST['adjustment3'];
        }

        if ($adjustment3s == 1) {
            $adjustment3        = $_POST['adjustment3_amount'];
            $adjustment3_option = $_POST['adjustment3_option'];
            if (! empty($adjustment3)) {
                $adjustment3 = $_POST['adjustment3_amount'];
            } else {
                $adjustment3 = 0;
            }

            if ($adjustment3_option == "A") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3 - $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                if ($adjustment3Host == 0) {
                    $adjustment3Host = '';
                }
                if ($adjustment3Owner == 0) {
                    $adjustment3Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>- " . abs($adjustment3Owner) . "</td>
                <td>-" . abs($adjustment3Host) . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            } elseif ($adjustment3_option == "B") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                if ($adjustment3Host == 0) {
                    $adjustment3Host = '';
                }
                if ($adjustment3Owner == 0) {
                    $adjustment3Owner = '';
                }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
              <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>" . $adjustment3Owner . "</td>
                <td>-" . abs($adjustment3Host) . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            } elseif ($adjustment3_option == "C") {
                $adjustment3 = round($request->adjustment3_amount, 2);
                if ($ownerProfit == 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>-" . abs($adjustment3Owner) . "</td>
                <td>-" . abs($adjustment3Host) . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            } elseif ($adjustment3_option == "D") {
                $adjustment3 = round($request->adjustment3_amount, 2);
                if ($hostProfit == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                }

                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>-" . abs($adjustment3Owner) . "</td>
                <td>-" . abs($adjustment3Host) . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            } elseif ($adjustment3_option == "E") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
              <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>" . $adjustment3Owner . "</td>
                <td>" . 0 . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            } elseif ($adjustment3_option == "F") {
                $adjustment3     = round($request->adjustment3_amount, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host = $adjustment3;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
                <td>" . 0 . "</td>
                <td>" . $adjustment3Host . "</td>
                <td>" . $request->adjustment3_remark . "</td>
                </tr>";
            }
        }
        // if ($totalOwnerPrice == 0) {
        //     $totalOwnerPrice = '' ?? 0;
        // }
        // if ($totalHostPrice == 0) {
        //     $totalHostPrice = '' ?? 0;
        // }
         $html .= "
    <tr class='end-row'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Net Profit</td>
        <td>" . number_format($totalOwnerPrice, 2) . "</td>
        <td>" . number_format($totalHostPrice, 2) . "</td>
        <td></td>
    </tr>
</table>";
        $html .= "<br>
             <p>This is a computer-generated document. No signature is required</p>
             </body>
             </html>";

        // Preview mode — return HTML as JSON for modal display
        if ($request->preview === 'preview') {
            return response()->json(['ok' => true, 'data' => $html]);
        }

        $pdf_name = 'r_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $listing->name) . '_' . $y . $m . '.pdf';
        $saveDir  = public_path('files/utility');
        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0775, true);
        }
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->getOptions()->setIsFontSubsettingEnabled(true);
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($saveDir . '/' . $pdf_name, $output);
        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf_name . '"',
        ]);
    }
    // return response()->download($filePath, 'Report_' . $y . $m . '.pdf', $headers);

    /**
     * @param $listingId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listingDetails($listingId)
    {
        $listing = Listing::find($listingId);
        if (empty($listing)) {
            return back()->with('error', 'Listing not found!');
        }
        $listingDetails = ListingDetail::where('listing_id', $listingId)->first();
        return view('admin.listing.listingDetail', compact('listing', 'listingDetails'));
    }

    /**
     * @param $listingId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listingDetailsStore(Request $request, $listingId)
    {
        $data      = $request->only('host_name', 'host_contact', 'description', 'distances', 'during_stay');
        $validator = Validator::make($request->all(), [
            'host_name'    => 'required|string|max:80',
            'host_contact' => 'required|string|max:50',
            'description'  => 'required',
            'distances'    => 'required',
            'during_stay'  => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $listingDetails = ListingDetail::where('listing_id', $listingId)->first();
        if (empty($listingDetails)) {
            $data['listing_id'] = $listingId;
            ListingDetail::create($data);
        } else {
            $listingDetails->update($data);
        }

        return back()->with('success', 'Listing details updated successfully!');
    }

    public function downloadTemplate(Request $request)
    {
        $arr                 = [];
        $array               = [];
        $arr['listing_id']   = 'Listing Id';
        $arr['listing_name'] = 'Listing Name';
        $arr['water']        = 'Water';
        $arr['internet']     = 'Internet';
        $arr['electricity']  = 'Electricity';
        $arr['mfsf']         = 'MF + SF';

        if ($request->adjustment1 == 1) {
            $arr['adjustment1']        = 'Adjustment1';
            $arr['adjustment1_option'] = 'Adjustment1 Options';
            $arr['adjustment1_remark'] = 'Adjustment1 Remark';
        }
        if ($request->adjustment2 == 1) {
            $arr['adjustment2']        = 'Adjustment2';
            $arr['adjustment2_option'] = 'Adjustment2 Options';
            $arr['adjustment2_remark'] = 'Adjustment2 Remark';
        }
        if ($request->adjustment3 == 1) {
            $arr['adjustment3']        = 'Adjustment3';
            $arr['adjustment3_option'] = 'Adjustment3 Options';
            $arr['adjustment3_remark'] = 'Adjustment3 Remark';
        }
        $listings = Listing::all('id', 'name');

        $spreadsheet   = new Spreadsheet();
        $sheet         = $spreadsheet->getActiveSheet();
        $exportData[0] = $arr;
        $x             = 1;
        foreach ($listings as $listing) {
            $exportData[$x] = [$listing->id, $listing->name];
            $x++;
        }

        $sheet->fromArray(
            $exportData,
            null,
            'A1'
        );
        $sheet->getStyle('A1:O1')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'FF8C00']]);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(48);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(16);

        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(20);

        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(20);
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        ob_end_clean();
        $extension = 'Xlsx';
        $writer    = IOFactory::createWriter($spreadsheet, $extension);

        $filePath = public_path('/files/report/utility.xlsx');
        $writer->save($filePath);

        $headers = [
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, 'Utility_template' . '.xlsx', $headers);
    }
    public function reportImports(Request $request)
    {
        $date       = date_create($request->date);
        $month      = date_format($date, 'Y-m-d');
        $y          = date_format($date, 'Y');
        $m          = date_format($date, 'm');
        $excel_date = "$y-$m-01";
        if ($y < 2020) {
            return back()->with('error', 'Invalid date!');
        }

        $file = $request->water_receipt;

        if (! empty($file)) {
            $this->validate($request, [
                'water_receipt' => 'required|file|mimes:xls,xlsx',
                // 'date' => 'required',
            ]);
            $the_file = $request->file('water_receipt');
            try {
                $spreadsheet  = IOFactory::load($the_file->getRealPath());
                $sheet        = $spreadsheet->getActiveSheet();
                $readCellValue = function ($column, $row, $useCalculated = false) use ($sheet) {
                    $cell = $sheet->getCell($column . $row);
                    $value = $useCalculated ? $cell->getCalculatedValue() : $cell->getValue();

                    if (is_string($value)) {
                        $value = trim($value);
                    }

                    return $value === '' ? null : $value;
                };
                $row_limit    = $sheet->getHighestDataRow();
                $column_limit = $sheet->getHighestDataColumn();
                $row_range    = range(1, $row_limit);
                $column_range = range('H', $column_limit);
                $startcount   = 1;
                $data         = [];
                $datas        = [];
                $alphabets    = range('A', 'Z');
                $array        = [];
                foreach ($row_range as $row) {
                    if ($row == 1) {
                        foreach ($alphabets as $alphabet) {
                            $newdata = $readCellValue($alphabet, $row);
                            if ($newdata == 'Water') {
                                $array['water'] = $alphabet;
                            } elseif ($newdata == 'Internet') {
                                $array['internet'] = $alphabet;
                            } elseif ($newdata == 'Electricity') {
                                $array['electricity'] = $alphabet;
                            } elseif ($newdata == 'MF + SF') {
                                $array['mfsf'] = $alphabet;
                            } elseif ($newdata == 'Adjustment1') {
                                $array['adjustment1'] = $alphabet;
                            } elseif ($newdata == 'Adjustment1 Options') {
                                $array['adjustment1_option'] = $alphabet;
                            } elseif ($newdata == 'Adjustment1 Remark') {
                                $array['adjustment1_remark'] = $alphabet;
                            } elseif ($newdata == 'Adjustment2') {
                                $array['adjustment2'] = $alphabet;
                            } elseif ($newdata == 'Adjustment2 Options') {
                                $array['adjustment2_option'] = $alphabet;
                            } elseif ($newdata == 'Adjustment2 Remark') {
                                $array['adjustment2_remark'] = $alphabet;
                            } elseif ($newdata == 'Adjustment3') {
                                $array['adjustment3'] = $alphabet;
                            } elseif ($newdata == 'Adjustment3 Options') {
                                $array['adjustment3_option'] = $alphabet;
                            } elseif ($newdata == 'Adjustment3 Remark') {
                                $array['adjustment3_remark'] = $alphabet;
                            }
                        }
                    }
                }
                foreach ($row_range as $row) {
                    $water_ammount       = null;
                    $internet_ammount    = null;
                    $electricity_ammount = null;
                    $mfsf_ammount        = null;
                    $adjustment1_ammount = null;
                    $adjustment1_option  = null;
                    $adjustment1_remark  = null;
                    $adjustment2_ammount = null;
                    $adjustment2_option  = null;
                    $adjustment2_remark  = null;
                    $adjustment3_ammount = null;
                    $adjustment3_option  = null;
                    $adjustment3_remark  = null;
                    if ($row > 1) {
                        $listing_id   = $readCellValue('A', $row);
                        $listing_name = $readCellValue('B', $row);

                        if (isset($array['water'])) {
                            $water_ammount = $readCellValue($array['water'], $row, true);
                        }
                        if (isset($array['internet'])) {
                            $internet_ammount = $readCellValue($array['internet'], $row, true);
                        }
                        if (isset($array['electricity'])) {
                            $electricity_ammount = $readCellValue($array['electricity'], $row, true);
                        }
                        if (isset($array['mfsf'])) {
                            $mfsf_ammount = $readCellValue($array['mfsf'], $row, true);
                        }
                        if (isset($array['adjustment1'])) {
                            $adjustment1_ammount = $readCellValue($array['adjustment1'], $row, true);
                        }
                        if (isset($array['adjustment1_option'])) {
                            $adjustment1_option = $readCellValue($array['adjustment1_option'], $row);
                        }
                        if (isset($array['adjustment1_remark'])) {
                            $adjustment1_remark = $readCellValue($array['adjustment1_remark'], $row);
                        }
                        if (isset($array['adjustment2'])) {
                            $adjustment2_ammount = $readCellValue($array['adjustment2'], $row, true);
                        }
                        if (isset($array['adjustment2_option'])) {
                            $adjustment2_option = $readCellValue($array['adjustment2_option'], $row);
                        }
                        if (isset($array['adjustment2_remark'])) {
                            $adjustment2_remark = $readCellValue($array['adjustment2_remark'], $row);
                        }
                        if (isset($array['adjustment3'])) {
                            $adjustment3_ammount = $readCellValue($array['adjustment3'], $row, true);
                        }
                        if (isset($array['adjustment3_option'])) {
                            $adjustment3_option = $readCellValue($array['adjustment3_option'], $row);
                        }
                        if (isset($array['adjustment3_remark'])) {
                            $adjustment3_remark = $readCellValue($array['adjustment3_remark'], $row);
                        }
                        $data[] = [
                            'listing_id'         => $listing_id,
                            'listing_name'       => $listing_name,
                            'water'              => $water_ammount,
                            'internet'           => $internet_ammount,
                            'electricity'        => $electricity_ammount,
                            'mfsf'               => $mfsf_ammount,
                            'adjustment1'        => $adjustment1_ammount,
                            'adjustment1_option' => strtoupper($adjustment1_option),
                            'adjustment1_remark' => $adjustment1_remark,
                            'adjustment2'        => $adjustment2_ammount,
                            'adjustment2_option' => strtoupper($adjustment2_option),
                            'adjustment2_remark' => $adjustment2_remark,
                            'adjustment3'        => $adjustment3_ammount,
                            'adjustment3_option' => strtoupper($adjustment3_option),
                            'adjustment3_remark' => $adjustment3_remark,
                            'excel_date'         => $excel_date,
                        ];
                    }
                    $startcount++;
                }
                // dd($data);
                foreach ($data as $arr) {
                    $date              = date_create($arr['excel_date']);
                    $y                 = date_format($date, 'Y');
                    $m                 = date_format($date, 'm');
                    $arr['excel_name'] = 'r_' . $arr['listing_name'] . '_' . $y . $m . '.pdf';
                    if (
                        ($arr['water'] === null || $arr['water'] === '') &&
                        ($arr['internet'] === null || $arr['internet'] === '') &&
                        ($arr['electricity'] === null || $arr['electricity'] === '') &&
                        ($arr['mfsf'] === null || $arr['mfsf'] === '') &&
                        ($arr['adjustment1'] === null || $arr['adjustment1'] === '') &&
                        ($arr['adjustment2'] === null || $arr['adjustment2'] === '') &&
                        ($arr['adjustment3'] === null || $arr['adjustment3'] === '')
                    ) {

                    } else {
                        $exist = update_excel::where([['listing_id', $arr['listing_id']], ['excel_date', $arr['excel_date']]])->orderBy("listing_id", "DESC")->get();
                        if (count($exist) > 0) {
                            update_excel::where([['listing_id', $arr['listing_id']], ['excel_date', $arr['excel_date']]])->orderBy("listing_id", "DESC")->update(['status' => 0]);
                            update_excel::where([['listing_id', $arr['listing_id']], ['excel_date', $arr['excel_date']]])->orderBy("listing_id", "DESC")->update($arr);
                        } else {
                            update_excel::create($arr);
                        }
                    }
                }
                foreach ($row_range as $row) {
                    if ($row > 1) {
                        $listing_id   = $sheet->getCell('A' . $row)->getValue();
                        $profits      = Listing::where('id', $listing_id)->pluck('profit')->toArray();
                        $waters       = Listing::where('id', $listing_id)->pluck('water')->toArray();
                        $internets    = Listing::where('id', $listing_id)->pluck('internet')->toArray();
                        $electricitys = Listing::where('id', $listing_id)->pluck('electricity')->toArray();
                        $mfsfs        = Listing::where('id', $listing_id)->pluck('mfsf')->toArray();
                        $test2        = Listing::where('id', $listing_id)->pluck('id')->toArray();
                        foreach ($profits as $profit) {
                            update_excel::whereIn('listing_id', $test2)->update(['old_profit' => $profit]);
                        }
                        foreach ($waters as $water) {
                            update_excel::whereIn('listing_id', $test2)->update(['water_option' => $water]);
                        }
                        foreach ($internets as $internet) {
                            update_excel::whereIn('listing_id', $test2)->update(['internet_option' => $internet]);
                        }
                        foreach ($electricitys as $electricity) {
                            update_excel::whereIn('listing_id', $test2)->update(['electricity_option' => $electricity]);
                        }
                        foreach ($mfsfs as $mfsf) {
                            update_excel::whereIn('listing_id', $test2)->update(['mfsf_option' => $mfsf]);
                        }
                    }
                }
            } catch (Exception $e) {
                // dd($e);
                return back()->withErrors('There was a problem uploading the data!');
            }
            return back()->withSuccess('Great! Excel has been successfully Updated.');
        }
    }

    public function listingApproval()
    {
        $utilities = update_excel::orderBy("listing_id", "DESC")->get();
        return view('admin.listing.approval', compact('utilities'));
    }

    public function destroyUtility(Request $request, $id)
    {
        $delete = update_excel::find($id);

        if (! $delete) {
            return back()->with('error', 'Record not found.');
        }

        $delete->delete();

        $date = $request->input('date');
        if (! $date) {
            return back()->with('success', 'Deleted successfully!', $delete);
        }
        return redirect()->route('approval.month', ['date' => $date])
            ->with('success', 'Deleted successfully!');
    }

    public function approval_new(Request $request)
    {
        $date = $request->input('date');

        if (! $date) {
            return view('admin.listing.selectDate'); // or handle accordingly
        }

        $nextMonth      = date_create($date);
        $thisMonth      = date_format($nextMonth, 'm');
        $thisYear       = date_format($nextMonth, 'Y');
        $thisMonthStart = date_format($nextMonth, 'Y-m-01');
        $nextMonth3     = date_create($thisMonthStart)->modify('+1 month');
        $thisMonthEnd   = date_format($nextMonth3, 'Y-m-01');

        $utilities = update_excel::whereMonth('excel_date', $thisMonth)
            ->whereYear('excel_date', $thisYear)
            ->orderBy('excel_date', 'ASC')
            ->get();

        return view('admin.listing.approvalByMonth', compact('utilities'));
    }

    public function importApproval(Request $request)
    {

        $listing = Listing::where('id', $request->listing_id)->first();
        $id      = $request->listing_id;
        if (empty($listing)) {
            return back()->with('error', 'This listing does not exist!');
        }
        // $date = date_create("2022-12-12");
        $date = date_create($request->date);
        $y    = date_format($date, 'Y');
        $m    = date_format($date, 'm');
        if ($y < 2020) {
            return back()->with('error', 'Invalid date!');
        }
        $reportData = $request->only(
            'water',
            'water_amount',
            'internet',
            'internet_amount',
            'electricity',
            'electricity_amount',
            'mfsf',
            'mfsf_amount',
            'adjustment1',
            'adjustment1_text',
            'adjustment1_amount',
            'adjustment2',
            'adjustment2_text',
            'adjustment2_amount',
            'adjustment3',
            'adjustment3_text',
            'adjustment3_amount'
        );

        $report = ListingReport::where([['listing_id', $listing->id], ['year', $y], ['month', $m]])->first();

        $thisMonthStart = date_format($date, 'Y-m-01');
        $nextMonth3     = date_create($thisMonthStart)->modify('+1 month');
        $thisMonthEnd   = date_format($nextMonth3, 'Y-m-01');
        $books          = Booking::where([['listing_id', $listing->id], ['status', '>=', 5], ['check_out', '>', $thisMonthStart], ['check_in', '<=', $thisMonthEnd]])->orderBy('check_in', 'ASC')->get();

        $alphabet = range('A', 'Z');
        //        $alphas = range('A', 'Z');
        $hostProfit  = round($listing->profit);
        $ownerProfit = 100 - $hostProfit;
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        for ($i = 0; $i <= 9; $i++) {
            $sheet->mergeCells($alphabet[$i] . '19:' . $alphabet[$i] . '20');
        }

        // Insert the image
        $sheet->setCellValue('M2', '');
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('MOKA');
        $drawing->setDescription('MOKA');
        $drawing->setPath(storage_path('app/public/logo1.png')); // Set the storage path of the image
        $drawing->setCoordinates('M2');
        $drawing->setWidth(90);
        $drawing->setHeight(90);
        $drawing->setWorksheet($sheet);
        // ***************************

        $sheet->mergeCells('M19:M20');
        $sheet->mergeCells('K19:L19');
        $exportData[0]  = ['', 'Moka Venture Sdn Bhd', '', '', '', '', '', '', '', '', '', ''];
        $exportData[1]  = ['', '11.1, Menara Lien Hoe,'];
        $exportData[2]  = ['', '8, Persiaran Tropicana, 47410 Petaling Jaya.'];
        $exportData[3]  = ['', 'hello@homemoka.com'];
        $exportData[4]  = ['', 'www.homemoka.com'];
        $exportData[5]  = [''];
        $exportData[6]  = ['', '', '', '', '', '', 'MONTHLY REPORT'];
        $exportData[7]  = ['', 'Report for the month of ' . $m . '/' . $y];
        $exportData[8]  = [''];
        $exportData[9]  = [''];
        $exportData[10] = ['', 'Period:', $m . '/' . $y];
        $exportData[11] = ['', 'Unit:', $listing->name];
        $exportData[12] = ['', 'Profit Share %:', '"' . $ownerProfit . ':' . $hostProfit . '"'];
        $exportData[13] = ['', 'Landlord:', $ownerProfit . '%'];
        $exportData[14] = ['', 'Host:', $hostProfit . '%'];
        $exportData[15] = [''];
        $exportData[16] = [''];

        $exportData[17]      = ['No.', 'OTA', 'Check-in Date', 'Check-out Date', 'No. of Nights', 'Rate/Night', 'Total Price Charged', 'M&A Fee', 'Total Revenue (RM)', 'Gross Profit (RM)', 'Remarks', 'Remarks'];
        $exportData[18]      = ['', '', '', '', '', '', '', '', '', 'Landlord', 'Host'];
        $x                   = 19;
        $no                  = 1;
        $bookedDays          = 0;
        $discount            = 0;
        $totalOwnerPrice     = 0;
        $new_totalOwnerPrice = 0;
        $totalHostPrice      = 0;
        $ota                 = 0;
        $ota1                = 0;
        $ota2                = 0;
        $otaBooking1         = 0;
        $water               = null;
        $internet            = null;
        $electricity         = null;
        $adjustment1         = null;
        $adjustment2         = null;
        $adjustment3         = null;
        // dd($books);
        $discount = 0;
        foreach ($books as $book) {

            $checkIn  = $book->check_in;
            $checkOut = $book->check_out;
            $nights   = $book->nights;
            $otaPrice = $book->ota_fee;

            if ($book->check_in >= $thisMonthStart && $book->check_in <= $thisMonthEnd) {

                if ($book->check_out <= $thisMonthEnd) {
                    $nights = $book->nights;
                } else {
                    $earlier = new \DateTime($thisMonthEnd);
                    $later   = new \DateTime($book->check_out);
                    $diff    = $later->diff($earlier)->format("%a");

                    if ($book->nights > $diff) {
                        $nights = $book->nights - $diff;
                    } else {
                        $nights = $diff - $book->nights;
                    }
                    $checkOut = $thisMonthEnd;
                }
            } elseif ($book->check_out > $thisMonthStart && $book->check_out <= $thisMonthEnd) {

                $earlier = new \DateTime($book->check_in);
                $later   = new \DateTime($thisMonthStart);
                $diff    = $later->diff($earlier)->format("%a");
                if ($book->nights > $diff) {
                    $nights = $book->nights - $diff;
                } else {
                    $nights = $diff - $book->nights;
                }
                $checkIn = $thisMonthStart;
            } elseif ($book->check_in <= $thisMonthStart && $book->check_out >= $thisMonthEnd) {
                $nights   = date_format($date, 't');
                $checkIn  = $thisMonthStart;
                $checkOut = $thisMonthEnd;
            }

            if ($checkIn != $checkOut && $checkOut <= $thisMonthEnd) {
                $todays               = $book->created_at->format('Y-m-d');
                $chackdate            = '2022-11-30';
                $ota_cal              = (($book->price_night * $nights));
                $new_ota              = (($book->price_night * $nights)) + $book->cleaning_fee;
                $new_ota1             = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                $sst                  = 0;
                if ($book->source == 'Long Term Rental') {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $sst + $book->sst_cf;
                } else {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                }
                $createdMonth    = $book->created_at->format('m');
                $create_check_in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $book->created_at)->format('Y-m-d');
                // Rates live in EzeePricing so every screen agrees on the fee.
                $ota = EzeePricing::marketingFee(
                    $book->source,
                    $book->price_night * $nights,
                    $book->cleaning_fee,
                    $book->sst,
                    $book->sst_cf,
                    $todays ?: null
                );

                $totalPrice = round($nights * $book->price_night, 2);
                $bookedDays = $bookedDays + $nights;
                $discount   = round($book->discount_fee, 2);

                $revenue = $totalPrice - $ota - abs($discount);

                $sst = $book->sst;

                $rp_night = ($book->price_night);

                $hostPrice       = round(($revenue * $hostProfit) / 100, 2);
                $ownerPrice      = $revenue - $hostPrice;
                $totalOwnerPrice = $totalOwnerPrice + $ownerPrice;
                $totalHostPrice  = $totalHostPrice + $hostPrice;

                $otaText = '';
                if ($book->source == 'Booking') {
                    $otaText = 'Booking.com';
                } else if ($book->source == 'PMS' || $book->source == 'Walk-in' || $book->source == 'Walk In') {
                    $otaText = 'Website';
                } else {
                    $otaText = $book->source;
                }
                $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);

                $checkInArray  = explode('-', $checkIn);
                $checkOutArray = explode('-', $checkOut);
                if (count($checkInArray) == 3) {
                    $checkIn = $checkInArray[2] . '/' . $checkInArray[1] . '/' . $checkInArray[0];
                }
                if (count($checkOutArray) == 3) {
                    $checkOut = $checkOutArray[2] . '/' . $checkOutArray[1] . '/' . $checkOutArray[0];
                }

                $exportData[$x] = [$no, $otaText, $checkIn, $checkOut, $nights, $rp_night, $totalPrice, $ota, $revenue, round($ownerPrice, 2), $hostPrice, ''];
                $no++;
                $x++;
            }
        }
        $orangeRow                 = $x + 2;
        $exportData[$x]            = ["", "", "", "Total", $bookedDays, "", "", "", "", round($totalOwnerPrice, 2), $totalHostPrice];
        $exportData[$x + 1]        = [""];
        $exportData[$x + 2]        = [""];
        $x                         = $x + 3;
        $bottomCalRow              = $x;
        $exportData[$bottomCalRow] = ["", "", "", "", "", "", "", "", "Gross Profit", round($totalOwnerPrice, 2), $totalHostPrice];
        $x++;
        $profitshare = update_excel::where('listing_id', $request->listing_id)->first();
        $landlordSum = 0;
        $hostSum     = 0;

        if (! empty($profitshare->water)) {
            if ($profitshare->water_option == "A") {
                $water     = round($profitshare->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;

                $totalHostPrice = $totalHostPrice - $waterHost;
                $exportData[$x] = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
            } elseif ($profitshare->water_option == "B") {
                $water     = round($profitshare->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $waterHost;
                }

                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;

                $totalHostPrice = $totalHostPrice - $waterHost;
                $exportData[$x] = ["", "", "", "", "", "", "", "", "Water Bill", $waterOwner, -$waterHost, -$water];
                $x++;
            } elseif ($profitshare->water_option == "C") {
                $water = round($profitshare->water, 2);
                if ($ownerProfit == 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                }
                // $waterHost = 0.00;
                // $waterOwner = $water ;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
            } elseif ($profitshare->water_option == "D") {
                $water = round($profitshare->water, 2);
                if ($hostProfit == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                }
                // $waterHost = $water;
                // $waterOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
            } else {
                $water     = round($profitshare->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Water Bill", -$waterOwner, -$waterHost, -$water];
                $x++;
            }
        }
        if (! empty($profitshare->internet)) {
            if ($profitshare->internet_option == "A") {
                $internet     = round($profitshare->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
            } elseif ($profitshare->internet_option == "B") {
                $internet     = round($profitshare->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", $internetOwner, -$internetHost, -$internet];
                $x++;
            } elseif ($profitshare->internet_option == "C") {
                $internet = round($profitshare->internet, 2);
                if ($ownerProfit == 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                }
                // $internetHost = 0.00;
                // $internetOwner = $internet ;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
            } elseif ($profitshare->internet_option == "D") {
                $internet = round($profitshare->internet, 2);
                if ($hostProfit == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                }
                // $internetHost = $internet;
                // $internetOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
            } else {
                $internet     = round($profitshare->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Internet Bill", -$internetOwner, -$internetHost, -$internet];
                $x++;
            }
        }
        if (! empty($profitshare->electricity)) {
            if ($profitshare->electricity_option == "A") {
                $electricity     = round($profitshare->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
            } elseif ($profitshare->electricity_option == "B") {
                $electricity     = round($profitshare->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", $electricityOwner, -$electricityHost, -$electricity];
                $x++;
            } elseif ($profitshare->electricity_option == "C") {
                $electricity = round($profitshare->electricity, 2);
                if ($ownerProfit == 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                }
                // $electricityHost = 0.00;
                // $electricityOwner = $electricity ;
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
            } elseif ($profitshare->electricity_option == "D") {
                $electricity = round($profitshare->electricity, 2);
                if ($hostProfit == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                }
                // $electricityHost = $electricity;
                // $electricityOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
            } else {
                $electricity     = round($profitshare->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Electricity Bill", -$electricityOwner, -$electricityHost, -$electricity];
                $x++;
            }
        }
        if (! empty($profitshare->mfsf)) {
            if ($profitshare->mfsf_option == "A") {
                $mfsf     = round($profitshare->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF + SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
            } elseif ($profitshare->mfsf_option == "B") {
                $mfsf     = round($profitshare->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF + SF Bill", $mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
            } elseif ($profitshare->mfsf_option == "C") {
                $mfsf = round($profitshare->mfsf, 2);
                if ($ownerProfit == 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                }
                // $mfsfHost = 0.00;
                // $mfsfOwner = $mfsf ;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF + SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
            } elseif ($profitshare->mfsf_option == "D") {
                $mfsf = round($profitshare->mfsf, 2);
                if ($hostProfit == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                }
                // $mfsfHost = $mfsf;
                // $mfsfOwner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF + SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
            } else {
                $mfsf     = round($profitshare->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "MF + SF Bill", -$mfsfOwner, -$mfsfHost, -$mfsf];
                $x++;
            }
        }

        if (! empty($profitshare->adjustment1)) {
            if ($profitshare->adjustment1_option == "A") {
                $adjustment1     = round($profitshare->adjustment1, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1 - $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, -$adjustment1Host, $profitshare->adjustment1_remark];
                $x++;
            } elseif ($profitshare->adjustment1_option == "B") {
                $adjustment1     = round($profitshare->adjustment1, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", $adjustment1Owner, -$adjustment1Host, $profitshare->adjustment1_remark];
                $x++;
            } elseif ($profitshare->adjustment1_option == "C") {
                $adjustment1 = round($profitshare->adjustment1, 2);
                if ($ownerProfit == 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                }
                // $adjustment1Host = 0.00;
                // $adjustment1Owner = $adjustment1 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, -$adjustment1Host, $profitshare->adjustment1_remark];
                $x++;
            } elseif ($profitshare->adjustment1_option == "D") {
                $adjustment1 = round($profitshare->adjustment1, 2);
                if ($hostProfit == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                }
                // $adjustment1Host = $adjustment1;
                // $adjustment1Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment1", -$adjustment1Owner, -$adjustment1Host, $profitshare->adjustment1_remark];
                $x++;
            }
        }

        if (! empty($profitshare->adjustment2)) {
            if ($profitshare->adjustment2_option == "A") {
                $adjustment2     = round($profitshare->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2 - $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, -$adjustment2Host, $profitshare->adjustment2_remark];
                $x++;
            } elseif ($profitshare->adjustment2_option == "B") {
                $adjustment2     = round($profitshare->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", $adjustment2Owner, -$adjustment2Host, $profitshare->adjustment2_remark];
                $x++;
            } elseif ($profitshare->adjustment2_option == "C") {
                $adjustment2 = round($profitshare->adjustment2, 2);
                if ($ownerProfit == 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                }
                // $adjustment2Host = 0.00;
                // $adjustment2Owner = $adjustment2 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, -$adjustment2Host, $profitshare->adjustment2_remark];
                $x++;
            } elseif ($profitshare->adjustment2_option == "D") {
                $adjustment2 = round($profitshare->adjustment2, 2);
                if ($hostProfit == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                }
                // $adjustment2Host = $adjustment2;
                // $adjustment2Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment2", -$adjustment2Owner, -$adjustment2Host, $profitshare->adjustment2_remark];
                $x++;
            }
        }

        if (! empty($profitshare->adjustment3)) {
            if ($profitshare->adjustment3_option == "A") {
                $adjustment3     = round($profitshare->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3 - $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, -$adjustment3Host, $profitshare->adjustment3_remark];
                $x++;
            } elseif ($profitshare->adjustment3_option == "B") {
                $adjustment3     = round($profitshare->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", $adjustment3Owner, -$adjustment3Host, $profitshare->adjustment3_remark];
                $x++;
            } elseif ($profitshare->adjustment3_option == "C") {
                $adjustment3 = round($profitshare->adjustment3, 2);
                if ($ownerProfit == 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                }
                // $adjustment3Host = 0.00;
                // $adjustment3Owner = $adjustment3 ;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, -$adjustment3Host, $profitshare->adjustment3_remark];
                $x++;
            } elseif ($profitshare->adjustment3_option == "D") {
                $adjustment3 = round($profitshare->adjustment3, 2);
                if ($hostProfit == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                }
                // $adjustment3Host = $adjustment3;
                // $adjustment3Owner = 0.00;
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;
                $exportData[$x]  = ["", "", "", "", "", "", "", "", "Adjustment3", -$adjustment3Owner, -$adjustment3Host, $profitshare->adjustment3_remark];
                $x++;
            }
        }

        if ($hostProfit > 0 && $ownerProfit > 0) {
            $reportData['owner_income']    = round($totalOwnerPrice, 2);
            $reportData['platform_income'] = round($totalHostPrice, 2);
            $exportData[$x]                = ["", "", "", "", "", "", "", "", "Net Profit", round($totalOwnerPrice, 2), round($totalHostPrice, 2)];
        } elseif ($hostProfit == 0 && $ownerProfit > 0) {
            $reportData['owner_income']    = round($totalOwnerPrice, 2);
            $reportData['platform_income'] = round($totalHostPrice, 2);
            $exportData[$x]                = ["", "", "", "", "", "", "", "", "Net Profit", round($totalOwnerPrice, 2), ''];
        } elseif ($hostProfit > 0 && $ownerProfit == 0) {
            $reportData['owner_income']    = round($totalOwnerPrice, 2);
            $reportData['platform_income'] = round($totalHostPrice, 2);
            $exportData[$x]                = ["", "", "", "", "", "", "", "", "Net Profit", '', round($totalHostPrice, 2)];
        }

        $exportData[$x + 1] = ["This is a computer-generated document. No signature is required."];
        $lastRow            = $x + 2;
        $sheet->fromArray(
            $exportData,
            null,
            'B2'
        );

        $sheet->getStyle('C2:C9')->getFont()->setBold(true);
        // $sheet->getStyle('B34')->getFont()->setBold(true)->getColor()->setRGB('000000');
        $sheet->getStyle('B19:M20')->getFont()->setBold(true)->getColor()->setRGB('ffffff');
        $sheet->getStyle('B19:M20')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('44546a');
        $sheet->getStyle('K20:L20')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('a9d08e');
        $sheet->getStyle('K20:L20')->getFont()->getColor()->setRGB('000000');
        $sheet->getStyle('J' . $bottomCalRow . ':M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set narrow margins
        $sheet->getPageMargins()->setTop(0.751969);
        $sheet->getPageMargins()->setBottom(0.751969);
        $sheet->getPageMargins()->setLeft(0.251969);
        $sheet->getPageMargins()->setRight(0.251969);
        $sheet->getPageMargins()->setHeader(0.299213);
        $sheet->getPageMargins()->setFooter(0.299213);

        $styleArray = [
            'borders'   => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ];
        $styleArrayOrange = [
            'borders'   => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'fill'      => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'ffc000',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font'      => [
                'bold' => true,
            ],
        ];
        $totalNightStyle = [
            'borders'   => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font'      => [
                'bold' => true,
            ],
        ];
        $lastRowStyle = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '000000'],
                ],
                'top'    => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
            'fill'    => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'ffff00',
                ],
            ],
            'font'    => [
                'bold' => true,
            ],
        ];
        $sEnd  = 19 + $no;
        $unSel = $lastRow + 5;
        $sheet->getStyle('B19:M' . $sEnd)->applyFromArray($styleArray);
        $sheet->getStyle('H8')->applyFromArray($totalNightStyle);
        $sheet->getStyle('K' . $orangeRow . ':L' . $orangeRow)->applyFromArray($styleArrayOrange);
        $sheet->getStyle('E' . $orangeRow . ':F' . $orangeRow)->applyFromArray($totalNightStyle);
        $sheet->getStyle('J' . $lastRow . ':L' . $lastRow)->applyFromArray($lastRowStyle);
        $sheet->getStyle('O' . $unSel . ':O' . $unSel)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(4);
        for ($i = 2; $i <= 12; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($alphabet[$i])->setWidth(16);
        }
        //*********************************************** */

        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        ob_end_clean();
        $extension  = 'Xlsx';
        $writer     = IOFactory::createWriter($spreadsheet, $extension);
        $excel_name = 'r_' . $id . '_' . $y . $m . '.xlsx';
        if (\File::exists(public_path() . "/files/utility/$excel_name")) {

            \File::delete(public_path() . "/files/utility/$excel_name");
            $filePath = public_path() . "/files/utility/$excel_name";
            $writer->save($filePath);
        } else {

            $filePath = public_path() . "/files/utility/$excel_name";
            $writer->save($filePath);
        }

        $headers = [
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    public function sendApproval(Request $request)
    {
        $data = [];

        if (! empty($request->ids) && is_array($request->ids)) {
            foreach ($request->ids as $listings_ids) {
                $exceldata = update_excel::where('id', $listings_ids['id'])->get();

                foreach ($exceldata as $value) {
                    // Skip if already approved
                    $excel_date = update_excel::where('id', $value->id)
                        ->where('status', '!=', 1)
                        ->first();

                    if (! $excel_date) {
                        continue;
                    }

                    $listing = Listing::find($value->listing_id);
                    if (! $listing) {
                        continue;
                    }

                    $users = User::where('id', $listing->user_id)->get();

                    $date = date_create($excel_date->excel_date);
                    $y    = date_format($date, 'Y');
                    $m    = date_format($date, 'F');

                    foreach ($users as $user) {
                        $data[] = [
                            'name'      => 'HOMEMOKA',
                            'user_name' => $user->name,
                            'email'     => $user->email,
                            'id'        => $user->id,
                            'filename'  => (function() use ($y, $m, $value) {
                                $source = public_path("/files/PDFS/{$y}/{$m}/{$value->excel_name}");
                                $dest = public_path("/files/pdf/{$y}/{$m}/{$value->excel_name}");
                                
                                // Create PDFS directory if it doesn't exist
                                if (!is_dir(dirname($dest))) {
                                    mkdir(dirname($dest), 0777, true);
                                }
                                
                                // Copy the file to PDFS directory
                                if (file_exists($source)) {
                                    try {
                                        copy($source, $dest);
                                    } catch (\Exception $e) {
                                        \Log::error("Failed to copy PDF file: " . $e->getMessage());
                                    }
                                }
                                
                                return $source;
                            })(),
                            'title'     => "($m $y) {$listing->name} Monthly Property Performance Report",
                        ];
                    }

                    // Booking check for approval
                    $thisMonthStart = date_format($date, 'Y-m-01');
                    $thisMonthEnd   = date_create($thisMonthStart)->modify('+1 month')->format('Y-m-01');

                    $books = Booking::where('listing_id', $value->listing_id)
                        ->where('status', '>=', 5)
                        ->where('check_in', '>=', $thisMonthStart)
                        ->where('check_in', '<', $thisMonthEnd)
                        ->where('check_out', '>', $thisMonthStart)
                        ->orderBy('check_in', 'ASC')
                        ->get();
                    if ($books->isNotEmpty()) {
                        // dd($data);
                        update_excel::where('id', $value->id)->update(['status' => 1]);
                    }
                }
            }
        }

        if (! empty($data)) {
            foreach ($data as $dataItem) {
                if (! empty($dataItem['email'])) {
                    Mail::to($dataItem['email'])->queue(new MonthlyReport($dataItem));
                }
            }

            return response()->json([
                'ok'      => true,
                'status'  => 200,
                'message' => 'Mail Sent Successfully!',
            ]);
        } else {
            return response()->json([
                'ok'      => false,
                'status'  => 400,
                'message' => 'Something went wrong or no eligible to approved listing.',
            ]);
        }
    }

    public function editUtility(Request $request, $id)
    {
        $getData = update_excel::where('id', $id)->first();
        return $getData;
    }
    public function updateUtility(Request $request, $id)
    {
        $data   = [];
        $data[] = [
            'water'              => $request->water,
            'electricity'        => $request->electricity,
            'internet'           => $request->internet,
            'mfsf'               => $request->mfsf,
            'adjustment1'        => $request->adjustment1,
            'adjustment2'        => $request->adjustment2,
            'adjustment3'        => $request->adjustment3,
            'water_option'       => $request->water_option,
            'internet_option'    => $request->internet_option,
            'electricity_option' => $request->electricity_option,
            'mfsf_option'        => $request->mfsf_option,
            'adjustment1_option' => $request->adjustment1_option,
            'adjustment2_option' => $request->adjustment2_option,
            'adjustment3_option' => $request->adjustment3_option,
            'adjustment1_name'   => $request->adjustment1_name,
            'adjustment2_name'   => $request->adjustment2_name,
            'adjustment3_name'   => $request->adjustment3_name,

        ];
        $updateData = update_excel::where('id', $id)->first();
        foreach ($data as $arr) {
            $update = $updateData->update($arr);
        }
        if ($request->type == "month") {
            return redirect()->route('approval.month', ['date' => request('date')])->with('success', 'Update successfully!');
        }
        if ($update) {
            return redirect()->route('approval.month', ['date' => request('date')])->with('success', 'Update successfully!');
        } else {
            return redirect()->route('approval.month', ['date' => request('date')])->with('error', 'Something went wrong!');
        }
    }

    public function storeUtility(Request $request)
    {
        try {
            $listing = Listing::find($request->listing_id);
            if (!$listing) {
                return back()->with('error', 'Listing not found.');
            }
            $rawDate  = $request->date ?? '';
            if (strlen($rawDate) === 7) { $rawDate .= '-01'; }
            $excelDate = date_create($rawDate) ? date_format(date_create($rawDate), 'Y-m-01') : date('Y-m-01');

            $existing = update_excel::where('listing_id', $listing->id)
                ->whereYear('excel_date', substr($excelDate, 0, 4))
                ->whereMonth('excel_date', substr($excelDate, 5, 2))
                ->first();

            $fields = [
                'listing_id'   => $listing->id,
                'listing_name' => $listing->name,
                'excel_date'   => $excelDate,
                'water'        => $request->water_amount ?: null,
                'internet'     => $request->internet_amount ?: null,
                'electricity'  => $request->electricity_amount ?: null,
                'mfsf'         => $request->mfsf_amount ?: null,
                'adjustment1'  => $request->adjustment1_amount ?: null,
                'adjustment2'  => $request->adjustment2_amount ?: null,
                'adjustment3'  => $request->adjustment3_amount ?: null,
                'adjustment1_text' => $request->adjustment1_text ?: null,
                'adjustment2_text' => $request->adjustment2_text ?: null,
                'adjustment3_text' => $request->adjustment3_text ?: null,
            ];

            if ($existing) {
                $existing->update($fields);
            } else {
                update_excel::create($fields);
            }

            return redirect('/admin/listing/chart/report?date=' . substr($excelDate, 0, 7))
                ->with('success', $listing->name . ' utility charges saved.');

        } catch (\Throwable $e) {
            return response('<pre style="font:13px monospace;padding:20px;color:darkred">'
                . '<b>' . htmlspecialchars($e->getMessage()) . '</b><br><br>'
                . 'File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '<br><br>'
                . htmlspecialchars($e->getTraceAsString())
                . '</pre>', 500);
        }
    }

    public function sendPdf(Request $request)
    {

        $id             = $request->id;
        $listing_id     = $request->listing_id;
        $listing        = update_excel::where('id', $id)->first();
        $listing_option = Listing::where('id', $listing->listing_id)->first();
        if (empty($listing)) {
            return back()->with('error', 'This listing does not exist!');
        }
        $date = date_create($request->date);
        $y    = date_format($date, 'Y');
        $m    = date_format($date, 'm');
        if ($y < 2020) {
            return back()->with('error', 'Invalid date!');
        }
        $reportData = $request->only(
            'water',
            'water_amount',
            'internet',
            'internet_amount',
            'electricity',
            'electricity_amount',
            'mfsf',
            'mfsf_amount',
            'adjustment1',
            'adjustment1_text',
            'adjustment1_amount',
            'adjustment1_name',
            'adjustment2',
            'adjustment2_text',
            'adjustment2_amount',
            'adjustment2_name',
            'adjustment3',
            'adjustment3_text',
            'adjustment3_amount',
            'adjustment3_name',
        );

        $report = ListingReport::where([['listing_id', $id], ['year', $y], ['month', $m]])->first();
        $thisMonthStart = date_format($date, 'Y-m-01');
        $nextMonth3     = date_create($thisMonthStart)->modify('+1 month');
        $thisMonthEnd   = date_format($nextMonth3, 'Y-m-01');
        $books          = Booking::where([['listing_id', $listing_id], ['status', '>=', 5], ['check_in', '>=', $thisMonthStart], ['check_in', '<', $thisMonthEnd], ['check_out', '>', $thisMonthStart]])->orderBy('check_in', 'ASC')->get();
        if ($request->preview !== 'preview' && count($books) == 0) {
            return response()->json([
                'status'  => true,
                'data'    => 101,
                'message' => 'These listing can`t be approved due to Zero records',
            ]);
        }
        // Ensure $listing_option exists before accessing properties to avoid "Trying to get property 'profit' of non-object"
        if (!empty($listing_option) && isset($listing_option->profit)) {
            $hostProfit = round($listing_option->profit);
        } else {
            // Fallback: if listing option is missing, default host profit to 0
            $hostProfit = 0;
        }
        $ownerProfit = 100 - $hostProfit;
        $bookedDays          = 0;
        $no                  = 1;
        $bookedDays          = 0;
        $totalOwnerPrice     = 0;
        $new_totalOwnerPrice = 0;
        $totalHostPrice      = 0;
        $ota                 = 0;
        $ota1                = 0;
        $ota2                = 0;
        $discount            = 0;
        $otaBooking1         = 0;
        $logoPath = public_path('logo1.png');
        $base64   = '';
        if (file_exists($logoPath)) {
            $type   = pathinfo($logoPath, PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
        //  <img src='$base64' alt='' srcset='' width='90px'>

        if ($request->preview === 'preview') {
            $css = ' .pdf-monthly{
    text-align:center;
}';
        } else {
            $css = ' .pdf-monthly{
                text-align:center;
            }

            // .pdf-monthly{
            //     border-bottom: 4px double #000;
            //     margin-left: 50%;
            //     transform: translate(-50%);
            // }';
        }
        $html = "<!DOCTYPE html>
    <html lang='en'>
    <head>
     <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css'>

        <title>PDF</title>
        <style>
            .table-container {
                font-size:8.5px;
                font-family: 'Open Sans', sans-serif;
            }

            // body {
            //     margin: -38px 40px;
            // }

            $css

            .listing_year{
                float:left;
            }
            .pdf-monthly{
                border-bottom: 4px double #000;
                // margin-left: 50%;
                // transform: translate(-50%);
                // text-align:center;
            }

            p,
            b {
                margin: 5px 0;
            }

            .table-m {
                padding: 0;
            }

            .main-table {
                border-collapse: collapse;
                border: none;
            }

            .main-table td {
                text-align: center;
                border: 1px solid black;
                padding: 2px;
            }

            .main-table th {
                width:75px;
                background-color: #44546a;
                color: #fff;
                border: 1px solid black;
                padding: 2px;
                text-align: center;
            }

            .main-table tr:nth-child(1) > th:nth-child(1) {
                width: 4px;
            }

            .main-table tr:nth-child(1) > th:nth-child(2) {
                width: 109px;
            }

            .last-row td,
            .end-row td {
                border: none;
            }

            .last-row td:nth-child(4),
            .last-row td:nth-child(5) {
                border-bottom: 4px double #000;
                font-weight: bold;
            }

            .last-row td:nth-child(10),
            .last-row td:nth-child(11) {
                border-bottom: 4px double #000;
                background-color: #ffc000;
                font-weight: bold;
            }

            .border-none td {
                border: none;
            }

            .end-row td:nth-child(9),
            .end-row td:nth-child(10),
            .end-row td:nth-child(11) {
                border-top: 2px solid #000;
                border-bottom: 4px double #000;
                background-color: #ffff00;
                font-weight: bold;
            }
            .genrate{
                float:left;
            }
        </style>
    </head>

    <body>
        <table class='table-container' style='width:100%'>
            <tr>
                <td style='text-align:left;'>
                    <b>
                        Moka Venture Sdn Bhd <br>
                        11.1, Menara Lien Hoe, <br>
                        8, Persiaran Tropicana, 47410 Petaling Jaya. <br>
                        hello@homemoka.com <br>
                        www.homemoka.com
                    </b>
                </td>
                <td style='text-align:right;'>
                <img style='margin-right: 19px;' src='$base64' alt='' srcset='' width='90px'>
                </td>
            </tr>
        </table>

        <br>
        <b class='pdf-monthly'>MONTHLY REPORT</b>
        <br><br>
        <b class='listing_year'>Report for the month of $m/$y</b>
        <br><br>
        <table class='table-container'>
            <tr>
                <td>Period:</td>
                <td>$m  /  $y</td>
            </tr>
            <tr>
                <td>Unit:</td>
                <td >$listing->listing_name</td>
            </tr>
            <tr>
                <td>Profit Share %:</td>
                <td>$ownerProfit  :  $hostProfit</td>
            </tr>
            <tr>
                <td>Landlord:</td>
                <td>$ownerProfit%</td>
            </tr>
            <tr>
                <td>Host:</td>
                <td>$hostProfit%</td>
            </tr>
        </table>
        <br><br>
        <table border='1' class='main-table table-container' style='width: 100%'>
            <tr>
                <th rowspan='2'>No.</th>
                <th rowspan='2'>OTA</th>
                <th rowspan='2'>Check-in Date</th>
                <th rowspan='2'>Check-out Date</th>
                <th rowspan='2'>No. of Nights</th>
                <th rowspan='2'>Rate/Night</th>
                <th rowspan='2'>Total Price Charged</th>
                <th rowspan='2'>M&A Fee</th>
                <th rowspan='2'>Total Revenue</th>
                <th colspan='2'>Gross Profit</th>
                <!-- <th></th> -->
                <th rowspan='2'>Remarks</th>
            </tr>
            <tr>
            <th style='background: #a9d08e; color: #000;'>Landlord</th>
            <th style='background: #a9d08e; color: #000;'>Host</th>
            </tr>";
        foreach ($books as $book) {
            $checkIn  = $book->check_in;
            $checkOut = $book->check_out;
            $nights   = $book->nights;
            $otaPrice = $book->ota_fee;
            // $booking_id = $book->id;
            if ($book->check_in >= $thisMonthStart && $book->check_in <= $thisMonthEnd) {
                // dd("1");
                if ($book->check_out <= $thisMonthEnd) {
                    $nights = $book->nights;
                } else {
                    $earlier = new \DateTime($thisMonthEnd);
                    $later   = new \DateTime($book->check_out);
                    $diff    = $later->diff($earlier)->format("%a");
                    // dd($book->nights);
                    if ($book->nights > $diff) {
                        $nights = $book->nights - $diff;
                    } else {
                        $nights = $diff - $book->nights;
                    }

                    $checkOut = $thisMonthEnd;
                    //                    $otaPrice = round($otaPrice*($nights/$book->nights), 2);
                }
            } elseif ($book->check_out > $thisMonthStart && $book->check_out <= $thisMonthEnd) {

                $earlier = new \DateTime($book->check_in);
                $later   = new \DateTime($thisMonthStart);
                $diff    = $later->diff($earlier)->format("%a");
                //                $nights = $diff;
                if ($book->nights > $diff) {
                    $nights = $book->nights - $diff;
                } else {
                    $nights = $diff - $book->nights;
                }
                // dd($nights);
                $checkIn = $thisMonthStart;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            } elseif ($book->check_in <= $thisMonthStart && $book->check_out >= $thisMonthEnd) {
                $nights   = date_format($date, 't');
                $checkIn  = $thisMonthStart;
                $checkOut = $thisMonthEnd;
                //                $otaPrice = round($otaPrice*($nights/$book->nights), 2);
            }

            if ($checkIn != $checkOut && $checkOut <= $thisMonthEnd) {
                $todays               = $book->created_at->format('Y-m-d');
                $chackdate            = '2022-11-30';
                $ota_cal              = (($book->price_night * $nights));
                $new_ota              = (($book->price_night * $nights)) + $book->cleaning_fee;
                $new_ota1             = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                $sst                  = 0;
                if ($book->source == 'Long Term Rental') {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $sst + $book->sst_cf;
                } else {
                    $new_ota1 = (($book->price_night * $nights)) + $book->cleaning_fee + $book->sst + $book->sst_cf;
                }
                $createdMonth    = $book->created_at->format('m');
                $create_check_in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $book->created_at)->format('Y-m-d');

                // Rates live in EzeePricing so every screen agrees on the fee.
                $ota = EzeePricing::marketingFee(
                    $book->source,
                    $book->price_night * $nights,
                    $book->cleaning_fee,
                    $book->sst,
                    $book->sst_cf,
                    $todays ?: null
                );

                $totalPrice = round($nights * $book->price_night, 2);
                $bookedDays = $bookedDays + $nights;

                // if ($book->source == 'Traveloka') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else if ($book->source == 'Expedia') {
                //     if ($todays > $chackdate &&  $todays < $chackdatenew) {
                //         $revenue = $totalPrice - $ota;
                //     } elseif ($todays > $chackdate &&  $todays >= $chackdatenew) {
                //         $revenue = $totalPrice + $ota;
                //     } else {
                //         $revenue = $totalPrice - $ota;
                //     }
                // } else {
                //     $revenue = $totalPrice - $ota;
                // }
                // $totalPrice = round($nights * $book->price_night, 2);
                // $bookedDays = $bookedDays + $nights;
                $discount  = round($book->discount_fee, 2);
                $revenue   = $totalPrice - $ota - abs($discount);
                $sst       = $book->sst;
                $rp_night  = ($book->price_night);
                $hostPrice = round(($revenue * $hostProfit) / 100, 2);
                // dd($revenue - $hostPrice);
                $ownerPrice      = round(($revenue - $hostPrice), 2);
                $totalOwnerPrice = round($totalOwnerPrice + $ownerPrice, 2) ?? '';
                $totalHostPrice  = round($totalHostPrice + $hostPrice, 2) ?? '';
                $otaText         = '';
                if ($book->source == 'Booking') {
                    $otaText = 'Booking.com';
                } else if ($book->source == 'PMS' || $book->source == 'Walk-in' || $book->source == 'Walk In') {
                    $otaText = 'Website';
                } else {
                    $otaText = $book->source;
                }
                $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);

                $checkInArray  = explode('-', $checkIn);
                $checkOutArray = explode('-', $checkOut);
                if (count($checkInArray) == 3) {
                    $checkIn = $checkInArray[2] . '/' . $checkInArray[1] . '/' . $checkInArray[0];
                }
                if (count($checkOutArray) == 3) {
                    $checkOut = $checkOutArray[2] . '/' . $checkOutArray[1] . '/' . $checkOutArray[0];
                }
            }
            if ($hostPrice == 0) {
                $hostPrice = '' ?? 0;
            }
            if ($ownerPrice == 0) {
                $ownerPrice = '' ?? 0;
            }

            $fota = $ota ?? 0;
            $html .= " <tr>
            <td>$no</td>
            <td>$otaText</td>
            <td>$checkIn</td>
            <td>$checkOut</td>
            <td>$nights</td>
            <td>$rp_night</td>
            <td>$totalPrice</td>
            <td>$fota</td>
            <td>$revenue</td>
            <td>$ownerPrice</td>
            <td>$hostPrice</td>
            <td></td>
        </tr>";
            $no++;
        }
        if (empty($totalHostPrice) || $totalHostPrice == 0) {
            $totalHostPrice = '';
        }
        if (empty($totalOwnerPrice) || $totalOwnerPrice == 0) {
            $totalOwnerPrice = '';
        }

        if (empty($bookedDays) || $bookedDays == 0) {
            $bookedDays = '' ?? 0;
        }
        $html .= " <tr class='last-row'>
        <td></td>
        <td></td>
        <td></td>
        <td>Total</td>
        <td>$bookedDays</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>$totalOwnerPrice</td>
        <td>$totalHostPrice</td>
        <td></td>
    </tr>
    <tr class='border-none' style='height: 30px;'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <br><br>
    <tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Gross Profit</td>
        <td>$totalOwnerPrice</td>
        <td>$totalHostPrice</td>
        <td></td>
    </tr>";

        if (empty($totalHostPrice) || $totalHostPrice == 0) {
            $totalHostPrice = '' ? '' : 0;
        }
        if (empty($totalOwnerPrice) || $totalOwnerPrice == 0) {
            $totalOwnerPrice = '' ? '' : 0;
        }

        if (! empty($listing->water)) {
            if ($listing->water_option == "A") {
                $water     = round($listing->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water - $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>- " . abs($waterOwner) . "</td>
        <td>-" . abs($waterHost) . "</td>
        <td>-" . abs($water) . "</td>
        </tr>";
            } elseif ($listing->water_option == "B") {
                $water     = round($listing->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $waterHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td> " . $waterOwner . "</td>
        <td>-" . abs($waterHost) . "</td>
        <td>-" . abs($water) . "</td>
        </tr>";
            } elseif ($listing->water_option == "C") {
                $water = round($listing->water, 2);
                if ($ownerProfit == 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>-" . abs($waterOwner) . "</td>
        <td>" . $waterHost . "</td>
        <td>-" . abs($water) . "</td>
        </tr>";
            } elseif ($listing->water_option == "D") {
                $water = round($listing->water, 2);
                if ($hostProfit == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>" . $waterOwner . "</td>
        <td>-" . abs($waterHost) . "</td>
        <td>-" . abs($water) . "</td>
        </tr>";
            } elseif ($listing->water_option == "E") {
                $water     = round($listing->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterOwner = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice + $waterOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>" . $waterOwner . "</td>
        <td>" . 0 . "</td>
        <td>" . $water . "</td>
        </tr>";
            } elseif ($listing->water_option == "F") {
                $water     = round($listing->water, 2);
                $waterHost = round(($water * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $waterHost  = $water;
                    $waterOwner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $waterHost  = 0.00;
                    $waterOwner = $water;
                } else {
                    $waterHost = $water;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $waterHost;

                // if ($waterHost == 0) {
                //     $waterHost = '';
                // }
                // if ($waterOwner == 0) {
                //     $waterOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>" . 0 . "</td>
        <td>" . $waterHost . "</td>
        <td>" . $water . "</td>
        </tr>";
            } else {
                $water           = round($listing->water, 2);
                $waterHost       = round(($water * $hostProfit) / 100, 2);
                $waterOwner      = $water - $waterHost;
                $totalOwnerPrice = $totalOwnerPrice - $waterOwner;
                $totalHostPrice  = $totalHostPrice - $waterHost;

                if ($waterHost == 0) {
                    $waterHost = '';
                }
                if ($waterOwner == 0) {
                    $waterOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Water Bill</td>
        <td>- " . abs($waterOwner) . "</td>
        <td>-" . abs($waterHost) . "</td>
        <td>-" . abs($water) . "</td>
        </tr>";
            }
        }

        if (! empty($listing->internet)) {
            if ($listing->internet_option == "A") {
                $internet     = round($listing->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet - $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>- " . abs($internetOwner) . "</td>
        <td>-" . abs($internetHost) . "</td>
        <td>-" . abs($internet) . "</td>
        </tr>";
            } elseif ($listing->internet_option == "B") {
                $internet     = round($listing->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internetHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td> " . $internetOwner . "</td>
        <td>-" . abs($internetHost) . "</td>
        <td>-" . abs($internet) . "</td>
        </tr>";
            } elseif ($listing->internet_option == "C") {
                $internet = round($listing->internet, 2);
                if ($ownerProfit == 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>-" . abs($internetOwner) . "</td>
        <td>" . $internetHost . "</td>
        <td>-" . abs($internet) . "</td>
        </tr>";
            } elseif ($listing->internet_option == "D") {
                $internet = round($listing->internet, 2);
                if ($hostProfit == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>" . $internetOwner . "</td>
        <td>-" . abs($internetHost) . "</td>
        <td>-" . abs($internet) . "</td>
        </tr>";
            } elseif ($listing->internet_option == "E") {
                $internet     = round($listing->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetOwner = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice + $internetOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>" . $internetOwner . "</td>
        <td>" . 0 . "</td>
        <td>" . $internet . "</td>
        </tr>";
            } elseif ($listing->internet_option == "F") {
                $internet     = round($listing->internet, 2);
                $internetHost = round(($internet * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $internetHost != 0) {
                    $internetHost  = $internet;
                    $internetOwner = 0.00;
                } else if ($ownerProfit != 0 && $internetHost == 0) {
                    $internetHost  = 0.00;
                    $internetOwner = $internet;
                } else {
                    $internetHost = $internet;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $internetHost;

                // if ($internetHost == 0) {
                //     $internetHost = '';
                // }
                // if ($internetOwner == 0) {
                //     $internetOwner = '';
                // }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>" . 0 . "</td>
        <td>" . $internetHost . "</td>
        <td>" . $internet . "</td>
        </tr>";
            } else {
                $internet        = round($listing->internet, 2);
                $internetHost    = round(($internet * $hostProfit) / 100, 2);
                $internetOwner   = $internet - $internetHost;
                $totalOwnerPrice = $totalOwnerPrice - $internetOwner;
                $totalHostPrice  = $totalHostPrice - $internetHost;

                if ($internetHost == 0) {
                    $internetHost = '';
                }
                if ($internetOwner == 0) {
                    $internetOwner = '';
                }

                $html .= "<tr class='border-none'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Internet Bill</td>
        <td>- " . abs($internetOwner) . "</td>
        <td>-" . abs($internetHost) . "</td>
        <td>-" . abs($internet) . "</td>
        </tr>";
            }
        }
        $electricitys = '';
        if (isset($_POST['electricity'])) {
            $electricitys = $_POST['electricity'];
        }

        if (! empty($listing->electricity)) {
            if ($listing->electricity_option == "A") {
                $electricity     = round($listing->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity - $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>- " . abs($electricityOwner) . "</td>
            <td>-" . abs($electricityHost) . "</td>
            <td>-" . abs($electricity) . "</td>
            </tr>";
            } elseif ($listing->electricity_option == "B") {
                $electricity     = round($listing->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricityHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td> " . $electricityOwner . "</td>
            <td>-" . abs($electricityHost) . "</td>
            <td>-" . abs($electricity) . "</td>
            </tr>";
            } elseif ($listing->electricity_option == "C") {
                $electricity = round($listing->electricity, 2);
                if ($ownerProfit == 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>-" . abs($electricityOwner) . "</td>
            <td>" . $electricityHost . "</td>
            <td>-" . abs($electricity) . "</td>
            </tr>";
            } elseif ($listing->electricity_option == "D") {
                $electricity = round($listing->electricity, 2);
                if ($hostProfit == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice  = $totalHostPrice - $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>" . $electricityOwner . "</td>
            <td>-" . abs($electricityHost) . "</td>
            <td>-" . abs($electricity) . "</td>
            </tr>";
            } elseif ($listing->electricity_option == "E") {
                $electricity     = round($listing->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityOwner = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice + $electricityOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>" . $electricityOwner . "</td>
            <td>" . 0 . "</td>
            <td>" . $electricity . "</td>
            </tr>";
            } elseif ($listing->electricity_option == "F") {
                $electricity     = round($listing->electricity, 2);
                $electricityHost = round(($electricity * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $electricityHost != 0) {
                    $electricityHost  = $electricity;
                    $electricityOwner = 0.00;
                } else if ($ownerProfit != 0 && $electricityHost == 0) {
                    $electricityHost  = 0.00;
                    $electricityOwner = $electricity;
                } else {
                    $electricityHost = $electricity;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $electricityHost;

                // if ($electricityHost == 0) {
                //     $electricityHost = '';
                // }
                // if ($electricityOwner == 0) {
                //     $electricityOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>" . 0 . "</td>
            <td>" . $electricityHost . "</td>
            <td>" . $electricity . "</td>
            </tr>";
            } else {
                $electricity      = round($listing->electricity, 2);
                $electricityHost  = round(($electricity * $hostProfit) / 100, 2);
                $electricityOwner = $electricity - $electricityHost;
                $totalOwnerPrice  = $totalOwnerPrice - $electricityOwner;
                $totalHostPrice   = $totalHostPrice - $electricityHost;

                if ($electricityHost == 0) {
                    $electricityHost = '';
                }
                if ($electricityOwner == 0) {
                    $electricityOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Electricity Bill</td>
            <td>- " . abs($electricityOwner) . "</td>
            <td>-" . abs($electricityHost) . "</td>
            <td>-" . abs($electricity) . "</td>
            </tr>";
            }
        }

        if (! empty($listing->mfsf)) {
            if ($listing->mfsf_option == "A") {
                $mfsf     = round($listing->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf - $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>- " . abs($mfsfOwner) . "</td>
            <td>-" . abs($mfsfHost) . "</td>
            <td>-" . abs($mfsf) . "</td>
            </tr>";
            } elseif ($listing->mfsf_option == "B") {
                $mfsf     = round($listing->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsfHost;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td> " . $mfsfOwner . "</td>
            <td>-" . abs($mfsfHost) . "</td>
            <td>-" . abs($mfsf) . "</td>
            </tr>";
            } elseif ($listing->mfsf_option == "C") {
                $mfsf = round($listing->mfsf, 2);
                if ($ownerProfit == 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>-" . abs($mfsfOwner) . "</td>
            <td>" . $mfsfHost . "</td>
            <td>-" . abs($mfsf) . "</td>
            </tr>";
            } elseif ($listing->mfsf_option == "D") {
                $mfsf = round($listing->mfsf, 2);
                if ($hostProfit == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>" . $mfsfOwner . "</td>
            <td>-" . abs($mfsfHost) . "</td>
            <td>-" . abs($mfsf) . "</td>
            </tr>";
            } elseif ($listing->mfsf_option == "E") {
                $mfsf     = round($listing->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfOwner = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice + $mfsfOwner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>" . $mfsfOwner . "</td>
            <td>" . 0 . "</td>
            <td>" . $mfsf . "</td>
            </tr>";
            } elseif ($listing->mfsf_option == "F") {
                $mfsf     = round($listing->mfsf, 2);
                $mfsfHost = round(($mfsf * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $mfsfHost != 0) {
                    $mfsfHost  = $mfsf;
                    $mfsfOwner = 0.00;
                } else if ($ownerProfit != 0 && $mfsfHost == 0) {
                    $mfsfHost  = 0.00;
                    $mfsfOwner = $mfsf;
                } else {
                    $mfsfHost = $mfsf;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $mfsfHost;

                // if ($mfsfHost == 0) {
                //     $mfsfHost = '';
                // }
                // if ($mfsfOwner == 0) {
                //     $mfsfOwner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>" . 0 . "</td>
            <td>" . $mfsfHost . "</td>
            <td>" . $mfsf . "</td>
            </tr>";
            } else {
                $mfsf            = round($listing->mfsf, 2);
                $mfsfHost        = round(($mfsf * $hostProfit) / 100, 2);
                $mfsfOwner       = $mfsf - $mfsfHost;
                $totalOwnerPrice = $totalOwnerPrice - $mfsfOwner;
                $totalHostPrice  = $totalHostPrice - $mfsfHost;

                if ($mfsfHost == 0) {
                    $mfsfHost = '';
                }
                if ($mfsfOwner == 0) {
                    $mfsfOwner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>MF + SF Bill</td>
            <td>- " . abs($mfsfOwner) . "</td>
            <td>-" . abs($mfsfHost) . "</td>
            <td>-" . abs($mfsf) . "</td>
            </tr>";
            }
        }

        if (! empty($listing->adjustment1)) {

            if ($listing->adjustment1_option == "A") {
                $adjustment1     = round($listing->adjustment1, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1 - $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                if ($adjustment1Host == 0) {
                    $adjustment1Host = '';
                }
                if ($adjustment1Owner == 0) {
                    $adjustment1Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>- " . abs($adjustment1Owner) . "</td>
            <td>-" . abs($adjustment1Host) . "</td>
            <td>-" . abs($listing->adjustment1_remark) . "</td>
            </tr>";
            } else if ($listing->adjustment1_option == "B") {
                $adjustment1     = round($listing->adjustment1, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                if ($adjustment1Host == 0) {
                    $adjustment1Host = '';
                }
                if ($adjustment1Owner == 0) {
                    $adjustment1Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>" . $adjustment1Owner . "</td>
            <td>-" . abs($adjustment1Host) . "</td>
            <td>" . $listing->adjustment1_remark . "</td>
            </tr>";
            } else if ($listing->adjustment1_option == "C") {
                $adjustment1 = round($listing->adjustment1, 2);
                if ($ownerProfit == 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
             <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>-" . abs($adjustment1Owner) . "</td>
            <td>-" . abs($adjustment1Host) . "</td>
            <td>" . $listing->adjustment1_remark . "</td>
            </tr>";
            } else if ($listing->adjustment1_option == "D") {
                $adjustment1 = round($listing->adjustment1, 2);
                if ($hostProfit == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>-" . abs($adjustment1Owner) . "</td>
            <td>-" . abs($adjustment1Host) . "</td>
            <td>" . $listing->adjustment1_remark . "</td>
            </tr>";
            } else if ($listing->adjustment1_option == "E") {
                $adjustment1     = round($listing->adjustment1, 2);
                $adjustment1Host = round(($adjustment1 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment1Host != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment1Host == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Owner = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment1Owner;
                $totalHostPrice  = $totalHostPrice + 0;
                // dd($adjustment1Host, $adjustment1Owner);
                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>" . $adjustment1Owner . "</td>
            <td>" . 0 . "</td>
            <td>" . $listing->adjustment1_remark . "</td>
            </tr>";
            } else if ($listing->adjustment1_option == "F") {
                $adjustment1 = round($listing->adjustment1, 2);
                if ($ownerProfit == 0 && $waterHost != 0) {
                    $adjustment1Host  = $adjustment1;
                    $adjustment1Owner = 0.00;
                } else if ($ownerProfit != 0 && $waterHost == 0) {
                    $adjustment1Host  = 0.00;
                    $adjustment1Owner = $adjustment1;
                } else {
                    $adjustment1Host = $adjustment1;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment1Host;

                // if ($adjustment1Host == 0) {
                //     $adjustment1Host = '';
                // }
                // if ($adjustment1Owner == 0) {
                //     $adjustment1Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
             <td>" . (! empty($listing->adjustment1_name) ? $listing->adjustment1_name : "Adjustment1") . "</td>
            <td>" . 0 . "</td>
            <td>" . $adjustment1Host . "</td>
            <td>" . $listing->adjustment1_remark . "</td>
            </tr>";
            }
        }

        if (! empty($listing->adjustment2)) {
            if ($listing->adjustment2_option == "A") {
                $adjustment2     = round($listing->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2 - $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                if ($adjustment2Host == 0) {
                    $adjustment2Host = '';
                }
                if ($adjustment2Owner == 0) {
                    $adjustment2Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>- " . abs($adjustment2Owner) . "</td>
            <td>-" . abs($adjustment2Host) . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment2_option == "B") {
                $adjustment2     = round($listing->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                if ($adjustment2Host == 0) {
                    $adjustment2Host = '';
                }
                if ($adjustment2Owner == 0) {
                    $adjustment2Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>" . $adjustment2Owner . "</td>
            <td>-" . abs($adjustment2Host) . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment2_option == "C") {
                $adjustment2 = round($listing->adjustment2, 2);
                if ($ownerProfit == 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
             <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>-" . abs($adjustment2Owner) . "</td>
            <td>-" . abs($adjustment2Host) . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment2_option == "D") {
                $adjustment2 = round($listing->adjustment2, 2);
                if ($hostProfit == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment2Host;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>-" . abs($adjustment2Owner) . "</td>
            <td>-" . abs($adjustment2Host) . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment2_option == "E") {
                $adjustment2     = round($listing->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Owner = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment2Owner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>" . $adjustment2Owner . "</td>
            <td>" . 0 . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment2_option == "F") {
                $adjustment2     = round($listing->adjustment2, 2);
                $adjustment2Host = round(($adjustment2 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment2Host != 0) {
                    $adjustment2Host  = $adjustment2;
                    $adjustment2Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment2Host == 0) {
                    $adjustment2Host  = 0.00;
                    $adjustment2Owner = $adjustment2;
                } else {
                    $adjustment2Host = $adjustment2;
                }
                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment2Host;

                // if ($adjustment2Host == 0) {
                //     $adjustment2Host = '';
                // }
                // if ($adjustment2Owner == 0) {
                //     $adjustment2Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment2_name) ? $listing->adjustment2_name : "Adjustment2") . "</td>
            <td>" . 0 . "</td>
            <td>" . $adjustment2Host . "</td>
            <td>" . $listing->adjustment2_remark . "</td>
            </tr>";
            }
        }

        if (! empty($listing->adjustment3)) {
            if ($listing->adjustment3_option == "A") {
                $adjustment3     = round($listing->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3 - $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                if ($adjustment3Host == 0) {
                    $adjustment3Host = '';
                }
                if ($adjustment3Owner == 0) {
                    $adjustment3Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>- " . abs($adjustment3Owner) . "</td>
            <td>-" . abs($adjustment3Host) . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment3_option == "B") {
                $adjustment3     = round($listing->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3Host;
                }
                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                if ($adjustment3Host == 0) {
                    $adjustment3Host = '';
                }
                if ($adjustment3Owner == 0) {
                    $adjustment3Owner = '';
                }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>" . $adjustment3Owner . "</td>
            <td>-" . abs($adjustment3Host) . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment3_option == "C") {
                $adjustment3 = round($listing->adjustment3, 2);
                if ($ownerProfit == 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                }
                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>-" . abs($adjustment3Owner) . "</td>
            <td>-" . abs($adjustment3Host) . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment3_option == "D") {
                $adjustment3 = round($listing->adjustment3, 2);
                if ($hostProfit == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                }

                $totalOwnerPrice = $totalOwnerPrice - $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice - $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>-" . abs($adjustment3Owner) . "</td>
            <td>-" . abs($adjustment3Host) . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment3_option == "E") {
                $adjustment3     = round($listing->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Owner = $adjustment3;
                }

                $totalOwnerPrice = $totalOwnerPrice + $adjustment3Owner;
                $totalHostPrice  = $totalHostPrice + 0;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>" . $adjustment3Owner . "</td>
            <td>" . 0 . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            } elseif ($listing->adjustment3_option == "F") {
                $adjustment3     = round($listing->adjustment3, 2);
                $adjustment3Host = round(($adjustment3 * $hostProfit) / 100, 2);
                if ($ownerProfit == 0 && $adjustment3Host != 0) {
                    $adjustment3Host  = $adjustment3;
                    $adjustment3Owner = 0.00;
                } else if ($ownerProfit != 0 && $adjustment3Host == 0) {
                    $adjustment3Host  = 0.00;
                    $adjustment3Owner = $adjustment3;
                } else {
                    $adjustment3Host = $adjustment3;
                }

                $totalOwnerPrice = $totalOwnerPrice + 0;
                $totalHostPrice  = $totalHostPrice + $adjustment3Host;

                // if ($adjustment3Host == 0) {
                //     $adjustment3Host = '';
                // }
                // if ($adjustment3Owner == 0) {
                //     $adjustment3Owner = '';
                // }

                $html .= "<tr class='border-none'>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
           <td>" . (! empty($listing->adjustment3_name) ? $listing->adjustment3_name : "Adjustment3") . "</td>
            <td>" . 0 . "</td>
            <td>" . $adjustment3Host . "</td>
            <td>" . $listing->adjustment3_remark . "</td>
            </tr>";
            }
        }
        // if ($totalOwnerPrice == 0) {
        //     $totalOwnerPrice = '' ?? 0;
        // }
        // if ($totalHostPrice == 0) {
        //     $totalHostPrice = '' ?? 0;
        // }
       $html .= "
    <tr class='end-row'>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Net Profit</td>
        <td>" . number_format($totalOwnerPrice, 2) . "</td>
        <td>" . number_format($totalHostPrice, 2) . "</td>
        <td></td>
    </tr>
</table>";
        $html .= "<br>
         <p class='genrate'>This is a computer-generated document. No signature is required</p>
         </body>

         </html>";
        $M        = date_format($date, 'F');
        $pdf_name = 'r_' . $listing->listing_name . '_' . $y . $m . '.pdf';

        if ($request->preview === 'preview') {
            return response()->json([
                'status' => 200,
                'data'   => $html,
            ]);
        } else {
            if (! File::exists("./files/PDFS/$y")) {
                File::makeDirectory("./files/PDFS/$y");
                if (! File::exists("./files/PDFS/$y/$M")) {
                    File::makeDirectory("./files/PDFS/$y/$M");
                }
            } else if (! File::exists("./files/PDFS/$y/$M")) {
                File::makeDirectory("./files/PDFS/$y/$M");
            }
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->getOptions()->setIsFontSubsettingEnabled(true);
            $dompdf->render();
            header('Content-Type: application/pdf; charset=utf-8');
            header("Content-disposition: inline; filename='./files/pdf/$pdf_name'", true);
            $dompdf->stream("./files/pdf/$pdf_name", ["Attachment" => 1]);
            $output = $dompdf->output();
            // file_put_contents("./files/pdf/$y/$M/$pdf_name", $output);
            file_put_contents("./files/PDFS/$y/$M/$pdf_name", $output);
        }
    }
}
