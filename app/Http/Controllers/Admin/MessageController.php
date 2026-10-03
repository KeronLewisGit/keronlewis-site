<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $unreadOnly = $request->query('show') === 'unread';

        return view('admin.messages', [
            'messages' => ContactMessage::query()
                ->when($unreadOnly, fn ($query) => $query->unread())
                ->latest()
                ->simplePaginate(25)
                ->withQueryString(),
            'unreadOnly' => $unreadOnly,
            'total' => ContactMessage::count(),
            'unread' => ContactMessage::unread()->count(),
        ]);
    }

    /**
     * Opening a message is what marks it as read.
     */
    public function show(ContactMessage $message): View
    {
        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.message', ['message' => $message]);
    }

    public function markUnread(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => null]);

        return redirect()->route('admin.messages')->with('status', 'Marked as unread.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages')->with('status', "Deleted the message from {$message->name}.");
    }
}
