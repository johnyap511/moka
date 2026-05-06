<?php

namespace App\Http\Controllers\Admin;

use App\DataLog;
use App\Http\Controllers\Controller;
use App\OtherModel\PropertyEstimate;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function estimate(Request $request)
    {
        $type = $request->type;
        $estimates = PropertyEstimate::where('type', $type)->get();
        return view('admin.setting.estimate', compact('estimates'));
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function estimateDestroy($id)
    {
        $estimate = PropertyEstimate::find($id);
        $estimate->delete();
        return back()->with('success', 'The estimate is deleted successfully!');
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function logs()
    {
        $logs = DataLog::limit(5000)->orderBy('id', 'desc')->get();
        // dd($logs);
        return view('admin.setting.logs', compact('logs'));
    }
}