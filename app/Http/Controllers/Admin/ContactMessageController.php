<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales > Contact messages (Sam, 27 Nov 2026): what visitors send from the website's
 * contact form, beside the owner estimate requests, so the sales team sees both in one
 * place. Messages the spam guard caught are kept out of the way under their own filter.
 * A message is marked once it has been answered; junk can be deleted one at a time.
 */
class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(admin_can('settings.view'), 403);

        $show = in_array($request->show, ['new', 'done', 'all', 'spam'], true) ? $request->show : 'new';
        $real = fn () => DB::table('contact_messages')->where('spam', 0);
        $counts = [
            'new'  => $real()->whereNull('followed_up_at')->count(),
            'done' => $real()->whereNotNull('followed_up_at')->count(),
            'all'  => $real()->count(),
            'spam' => DB::table('contact_messages')->where('spam', 1)->count(),
        ];
        $messages = ($show === 'spam' ? DB::table('contact_messages')->where('spam', 1) : $real())
            ->when($show === 'new', fn ($q) => $q->whereNull('followed_up_at'))
            ->when($show === 'done', fn ($q) => $q->whereNotNull('followed_up_at'))
            ->orderByDesc('created_at')->orderByDesc('id')->limit(300)->get();
        $names = DB::table('users')->whereIn('id', $messages->pluck('followed_up_by')->filter()->unique())->pluck('name', 'id');

        return view('admin.sales.messages', compact('messages', 'show', 'counts', 'names'));
    }

    /** Mark a message as answered, or put it back to new. */
    public function followUp(Request $request, $id)
    {
        abort_unless(admin_can('settings.view'), 403);

        $done = $request->boolean('done');
        $n = DB::table('contact_messages')->where('id', (int) $id)->where('spam', 0)->update([
            'followed_up_at' => $done ? now() : null,
            'followed_up_by' => $done ? auth()->id() : null,
        ]);
        abort_unless($n || DB::table('contact_messages')->where('id', (int) $id)->where('spam', 0)->exists(), 404);

        return back()->with('success', $done ? 'Marked as followed up.' : 'Put back to new.');
    }

    /** Delete one message for good: a sales pitch or other junk the spam guard let through. */
    public function destroy($id)
    {
        abort_unless(admin_can('settings.view'), 403);

        $row = DB::table('contact_messages')->where('id', (int) $id)->first();
        abort_unless($row, 404);
        DB::table('contact_messages')->where('id', $row->id)->delete();
        \Log::info('Contact message deleted', ['id' => $row->id, 'spam' => (bool) $row->spam, 'by' => auth()->id()]);

        return back()->with('success', 'Message deleted.');
    }
}
