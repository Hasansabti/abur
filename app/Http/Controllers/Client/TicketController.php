<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        // جلب تذاكر العميل المسجل حالياً مرتبة من الأحدث للأقدم
        $tickets = Ticket::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('client.tickets.index2', compact('tickets'));
    }

    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $validated = $request->validate([
            'subject'  => 'required|string|max:255',
            'category' => 'required|string|in:general,changeD,booking,technical,complaint',
            'message'  => 'required|string|min:10',
        ], [
            'subject.required'  => 'يرجى إدخال عنوان التذكرة.',
            'category.required' => 'يرجى اختيار القسم.',
            'message.required'  => 'يرجى إدخال نص الرسالة.',
            'message.min'       => 'يجب ألا يقل نص الرسالة عن 10 حروف.',
        ]);

        $userId = Auth::id();

        // 🛑 2. فحص فترة الانتظار (5 دقائق بين كل تذكرة وأخرى)
        $lastTicket = Ticket::where('user_id', $userId)->latest()->first();

        if ($lastTicket && $lastTicket->created_at->gt(now()->subMinutes(5))) {
            $secondsRemaining = now()->diffInSeconds($lastTicket->created_at->addMinutes(5));
            $minutesRemaining = ceil($secondsRemaining / 60);

            return redirect()->back()->with('error', "يرجى الانتظار {$minutesRemaining} دقائق قبل إنشاء تذكرة جديدة لحماية النظام.");
        }

        // 🛑 3. فحص الحد الأقصى اليومي (تذكرتين في اليوم)
        $dailyTicketsCount = Ticket::where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyTicketsCount >= 2) { // تم التعديل إلى 2 لتطابق التعليق
            return redirect()->back()->with('error', 'عذراً، لقد وصلت للحد الأقصى المسموح به لإنشاء التذاكر اليوم (تذكرتين في اليوم).');
        }

        // 4. حفظ التذكرة
        Ticket::create([
            'user_id'  => $userId,
            'subject'  => $validated['subject'],
            'category' => $validated['category'],
            'message'  => $validated['message'],
            'status'   => 'open',
        ]);

        // 5. إعادة التوجيه
        return redirect()->back()->with('success', 'تم إرسال تذكرتك بنجاح! سيتواصل معك الدعم الفني قريباً.');
    }
}