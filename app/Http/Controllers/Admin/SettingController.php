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
        // Owner requests from the website estimate form, newest first, with their follow-up status.
        $show = in_array($request->show, ['new', 'done', 'all'], true) ? $request->show : 'new';   // opens on what still needs a call
        $estimates = PropertyEstimate::query()
            ->when($show === 'new', fn ($q) => $q->where('status', '!=', PropertyEstimate::STATUS_FOLLOWED_UP))
            ->when($show === 'done', fn ($q) => $q->where('status', PropertyEstimate::STATUS_FOLLOWED_UP))
            ->orderByDesc('id')->limit(300)->get();
        $done = PropertyEstimate::where('status', PropertyEstimate::STATUS_FOLLOWED_UP)->count();
        $counts = ['all' => PropertyEstimate::count(), 'done' => $done];
        $counts['new'] = $counts['all'] - $done;
        $userModel = config('auth.providers.users.model');
        $names = $userModel::whereIn('id', $estimates->pluck('followed_up_by')->filter()->unique())->pluck('name', 'id');
        return view('admin.setting.estimate', compact('estimates', 'show', 'counts', 'names'));
    }

    /**
     * Mark an owner request as followed up, or put it back to new.
     */
    public function estimateFollowUp(Request $request, $id)
    {
        $estimate = PropertyEstimate::findOrFail($id);
        $done = $request->boolean('done');
        $estimate->status = $done ? PropertyEstimate::STATUS_FOLLOWED_UP : PropertyEstimate::STATUS_NEW;
        $estimate->followed_up_at = $done ? now() : null;
        $estimate->followed_up_by = $done ? auth()->id() : null;
        $estimate->save();
        return back()->with('success', $done ? 'Marked as followed up.' : 'Put back to new.');
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