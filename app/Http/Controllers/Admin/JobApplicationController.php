<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    /** Applications from the careers page, newest first. "Filtered" holds what the bot check caught. */
    public function index(Request $request)
    {
        $show = $request->show === 'filtered' ? 'filtered' : 'real';
        $applications = JobApplication::where('spam', $show === 'filtered')->orderByDesc('id')->limit(300)->get();
        $counts = ['real' => JobApplication::where('spam', false)->count(), 'filtered' => JobApplication::where('spam', true)->count()];
        return view('admin.careers.index', compact('applications', 'show', 'counts'));
    }

    /** The applicant's CV. Stored privately, so it is only reachable through this signed-in route. */
    public function cv($id)
    {
        $application = JobApplication::findOrFail($id);
        $path = $application->cv_path ? storage_path('app/' . $application->cv_path) : null;
        abort_unless($path && is_file($path), 404);
        return response()->download($path, $application->cv_name ?: basename($path));
    }
}
