<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
public function index(Request $request)
{
    $search = $request->input('search');
    $category = $request->input('category');
    $status = $request->input('status');

    $tickets = Ticket::with('user')
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        })
        ->when($category, function ($query) use ($category) {
            $query->where('category', $category);
        })
        ->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return view('admin.tickets.index', compact('tickets'));
}

    // الرد على التذكرة وتحديث حالتها
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string|min:5',
            'status' => 'required|string|in:open,pending,resolved,closed',
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'reply' => $request->reply,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'تم إرسال الرد وتحديث حالة التذكرة بنجاح.');
    }
}
