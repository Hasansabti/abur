<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\BookingSeat;
use App\Models\Agency;
use App\Models\AgencyRating;
use App\Models\Booking;
use App\Models\PrivateTripRequest;
use App\Models\Companion;
use App\Models\Trip;
use App\Models\TripSeatLog;
use App\Models\TripRoom;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Services\BookingCalculator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactFormMail;
use App\Mail\BookingTicketMail;


class ClientController extends Controller
{
    //عرض الصفحة الرئيسية
    public function index()
    {
        return view('client.index');
    }

    // عرض صفحة الحجوزات
    public function indexB()
    {
        $bookings = auth()->user()->bookings()
            ->with(['trip'])
            ->latest()
            ->get();

        // الدفعات الإضافية المسددة لكل حجز (تُضاف للتكلفة الإجمالية الظاهرة)
        $paidExtras = \App\Models\BookingExtraCharge::whereIn('booking_id', $bookings->pluck('id'))
            ->where('status', 'paid')
            ->get()
            ->groupBy('booking_id');

        $bookings->each(function ($b) use ($paidExtras) {
            $extra = (float) ($paidExtras->get($b->id)?->sum('amount') ?? 0);
            $b->extra_paid    = $extra;
            $b->display_total = (float) ($b->total_amount ?? 0) + $extra;
        });

        return view('client.booking.bookings', compact('bookings'));
    }

    public function indexT()
    {
        $user = auth()->user();

        $requests = PrivateTripRequest::with('agency')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'    => PrivateTripRequest::where('user_id', auth()->id())->count(),
            'pending'  => PrivateTripRequest::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'accepted' => PrivateTripRequest::where('user_id', auth()->id())->where('status', 'accepted')->count(),
        ];

        $saudiCities = ['الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة', 'الدمام', 'الخبر', 'أبها', 'تبوك'];
        $agencies    = Agency::select('id', 'name')->get();

        return view('client.trips.private_trip', compact('requests', 'user', 'stats', 'saudiCities', 'agencies'));
    }

    //contact
    public function sendContact(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string|min:5',
        ]);

        $subjects = [
            'general'     => 'استفسار عام',
            'support'     => 'مساعدة فنية / بلاغ عن مشكلة',
            'suggestion'  => 'اقتراح لتطوير الخدمة',
            'partnership' => 'استفسار عن الشراكات',
        ];

        $user = auth()->user();

        $data = [
            'user_name'       => $user ? $user->name : 'زائر',
            'user_email'      => $user ? $user->email : 'لا يوجد',
            'subject_label'   => $subjects[$request->subject] ?? 'استفسار عام',
            'message_content' => $request->message,
        ];

        // 1. إرسال نص الرسالة لإدارة الموقع
        Mail::to('support@aburtravel.com')->send(new ContactFormMail($data));

        // 2. إرسال تأكيد استلام فوري للعميل
        if ($user && $user->email) {
            Mail::raw("أهلاً {$user->name}،\n\nتم استلام رسالتك بنجاح بخصوص ({$data['subject_label']}). وسيقوم فريق الدعم الفني بمراجعتها والرد عليك في أقرب وقت.\n\nشكراً لتواجدك معنا 🚀\nمنصة عبور", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('تم استلام رسالتك بنجاح - منصة عبور');
            });
        }

        return redirect()->back()->with('success', 'تم إرسال رسالتك بنجاح! وصلك إيميل تأكيد واستلام.');
    }

    /* =====================================================================
     |  ظهور الرحلات للعميل (بما فيها الباص الثاني)
     | ===================================================================== */

    /**
     * الباص الثاني (الاحتياطي) يبقى مخفياً ما دامت الوكالة لم تفعّله (is_overflow_standby = true).
     * إذا فعّلته الوكالة (is_overflow_standby = false) يظهر للعميل كرحلة عادية.
     * والرحلة الأولى تختفي فقط إذا امتلأت وكان باصها الثاني مفعّلاً.
     */
    private function applyClientVisibility($query)
    {
        $table = (new Trip)->getTable();

        return $query
            ->where(function ($q) use ($table) {
                $q->where("{$table}.is_overflow_standby", false)
                    ->orWhereNull("{$table}.is_overflow_standby");
            })
            ->where(function ($q) use ($table) {
                $q->where("{$table}.available_seats", '>', 0)
                    ->orWhereNotExists(function ($sub) use ($table) {
                        $sub->select(DB::raw(1))
                            ->from("{$table} as ot")
                            ->whereColumn('ot.parent_trip_id', "{$table}.id")
                            ->where('ot.is_overflow_standby', false);
                    });
            });
    }

    /** عنوان الرحلة بدون لاحقة "الباص الثاني" */
    private function publicTitle($trip): string
    {
        return Str::replaceLast(' (الباص الثاني)', '', (string) $trip->title);
    }

    // عرض الرحلات
    public function trips(Request $request)
    {
        $selectedType = $request->query('type', 'bus');
        $fromCity     = $request->query('from_city');
        $toCity       = $request->query('to_city');
        $date         = $request->query('date');
        $triptype     = $request->query('triptype');
        $sortBy       = $request->query('sort_by', 'latest');

        $query = Trip::query()->with('agency.ratings');
        $this->applyClientVisibility($query);

        // استبعاد الرحلات الخاصة وطلبات الرحلات الخاصة
        $query->where(function ($q) {
            $q->where('is_private', 0)
                ->orWhereNull('is_private');
        })->whereNull('private_trip_request_id');

        $agencies = Agency::select('id', 'name')->get();

        if ($selectedType) {
            $query->where('transport_type', $selectedType);
        }
        if ($fromCity) {
            $query->where('from_city', $fromCity);
        }
        if ($toCity) {
            $query->where('to_city', $toCity);
        }
        if ($triptype) {
            $query->where('trip_type', $triptype);
        }

        if ($date) {
            $query->whereDate('departure_at', $date);
        } else {
            $query->where('departure_at', '>=', today());
        }

        // الأقرب موعداً أولاً
        $rawTrips = $query->orderBy('departure_at')->get();

        $availableTrips = $rawTrips->map(function ($trip) {

            $defaultImage = $trip->transport_type === 'flight'
                ? 'https://media.istockphoto.com/id/481413202/photo/passenger-airliner-flight-in-the-blue-sky.jpg?s=2048x2048&w=is&k=20&c=C8STBVw9bykfUx2b6zU3NDyR17-gEerpy31Ok2i-Rzs='
                : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&q=80&w=800';

            $finalCover = $trip->cover_image
                ? (str_starts_with($trip->cover_image, 'http')
                    ? $trip->cover_image
                    : asset(str_starts_with($trip->cover_image, 'storage/') ? $trip->cover_image : 'storage/' . $trip->cover_image))
                : $defaultImage;

            $roomOptions = $trip->room_options ?? [];
            if (is_string($roomOptions)) {
                $roomOptions = json_decode($roomOptions, true) ?? [];
            }

            $lowestRoomPrice = collect($roomOptions)
                ->pluck('price')
                ->map(fn($price) => (float) $price)
                ->filter(fn($price) => $price > 0)
                ->min() ?? ($trip->price ?? 0);

            return (object)[
                'id'                  => $trip->id,
                'title'               => $this->publicTitle($trip),
                'cover_image'         => $finalCover,
                'route_type'          => match ($trip->route_type) {
                    'international' => 'خارجية',
                    'domestic'      => 'محلية',
                    default         => $trip->route_type ?? 'غير محدد',
                },
                'agency_name'         => $trip->agency?->name ?? 'وكالة عبور المعتمدة',
                'agency_logo'         => $trip->agency?->iconA
                    ? (str_starts_with($trip->agency->iconA, 'http')
                        ? $trip->agency->iconA
                        : asset(str_starts_with($trip->agency->iconA, 'storage/') ? $trip->agency->iconA : 'storage/' . $trip->agency->iconA))
                    : null,
                'status'              => $trip->status,
                'route'               => $trip->from_city . ' ⬅️ ' . $trip->to_city,
                'trip_type'           => $trip->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
                'transport_type'      => $trip->transport_type,
                'vehicle_model'       => $trip->transport_type === 'flight' ? 'طيران - ' . $trip->title : 'حافلة مريحة',
                'rating'              => $trip->agency?->general_rating ?? 0,
                'ratings_count'       => $trip->agency?->ratings_count ?? 0,
                'prices'              => $lowestRoomPrice,
                'room_options'        => $roomOptions,
                'shared_room_price'   => $trip->shared_room_price,
                'allows_shared_rooms' => $trip->allows_shared_rooms,
                'price'               => $trip->price,
                'date'                => $trip->departure_at ? Carbon::parse($trip->departure_at)->format('Y-m-d') : null,
                'departure_at'        => $trip->departure_at,
                'return_date'         => $trip->return_at ? Carbon::parse($trip->return_at)->format('Y-m-d') : null,
                'return_at'           => $trip->return_at,
                'amenities'           => $trip->transport_type === 'flight' ? ['WiFi', 'Snacks', 'USB'] : ['WiFi', 'AC', 'Snacks'],
                'duration_days'       => $trip->duration_days,

                // الرحلات المشروطة
                'is_conditional'        => (bool) ($trip->is_conditional ?? false),
                'min_seats_required'    => $trip->min_seats_required ?? 1,
                'threshold_deadline_at' => $trip->threshold_deadline_at,
                'total_seats'           => $trip->total_seats ?? 0,
                'available_seats'       => $trip->available_seats ?? 0,

                // الباص الثاني (مفعّل من الوكالة)
                'is_second_bus'         => !empty($trip->parent_trip_id),
                'parent_trip_id'        => $trip->parent_trip_id ?? null,
            ];
        });

        if ($sortBy === 'top_rated') {
            $availableTrips = $availableTrips->sortByDesc('rating')->values();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($availableTrips->values(), 200, [], JSON_UNESCAPED_UNICODE);
        }

        return view('client.trips', [
            'availableTrips' => $availableTrips,
            'selectedType'   => $selectedType,
            'agencies'       => $agencies,
        ]);
    }

    //طلب رحلة خاصة
    public function storePrivateTrip(Request $request)
    {
        $validated = $request->validate([
            'agency_id'        => 'nullable|exists:agencies,id',
            'from_city'        => 'required|string|max:100',
            'to_city'          => 'required|string|max:100|different:from_city',
            'departure_date'   => 'required|date|after_or_equal:today',
            'return_date'      => 'nullable|date|after_or_equal:departure_date',
            'trip_type'        => 'nullable|in:one_way,round_trip',
            'transport_type'   => 'required|in:bus,van,car_vip,flight_charter',
            'passengers_count' => 'required|integer|min:1|max:500',
            'contact_name'     => 'required|string|max:150',
            'contact_phone'    => 'required|string|max:20',
            'notes'            => 'nullable|string|max:1000',
        ], [
            'to_city.different'             => 'يجب أن تكون مدينة الوصول مختلفة عن مدينة الانطلاق.',
            'departure_date.after_or_equal' => 'تاريخ المغادرة يجب أن يكون اليوم أو في المستقبل.',
            'return_date.after_or_equal'    => 'تاريخ العودة يجب أن يكون بعد أو يساوي تاريخ المغادرة.',
            'agency_id.exists'              => 'الوكالة المختارة غير مسجلة لدينا.',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status']  = 'pending';

        PrivateTripRequest::create($validated);

        return redirect()->back()->with('success', 'تم إرسال طلب الرحلة الخاصة بنجاح، وسيتم التواصل معك فور توفر العروض!');
    }

    public function agenciesshow(Request $request)
    {
        $agencies = Agency::with(['ratings'])->latest()->get()->map(function ($agency) {

            $avg = $agency->ratings->avg(function ($rating) {
                return ($rating->accommodation_rating + $rating->meals_rating + $rating->staff_rating +
                    $rating->organization_rating + $rating->price_value_rating +
                    $rating->transport_rating + $rating->services_rating) / 7;
            });

            $agency->average_rating = round($avg, 1) ?: '0.0';
            $agency->ratings_count = $agency->ratings->count();

            return $agency;
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($agencies, 200, [], JSON_UNESCAPED_UNICODE);
        }

        return view('client.agency.agencies', compact('agencies'));
    }

    /** نص طريقة الدفع بالعربي */
    private function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'bank'  => 'تحويل بنكي',
            'tabby' => 'تابي',
            'card'  => 'بطاقة بنكية',
            default => $method ? (string) $method : 'بطاقة بنكية',
        };
    }

    /** تنسيق مبلغ: بدون كسور إن كان رقماً صحيحاً، وبخانتين إن كان فيه كسر */
    private function formatMoney($value): string
    {
        $value = (float) $value;
        return fmod($value, 1.0) == 0.0 ? number_format($value, 0) : number_format($value, 2);
    }

    // بروفايل المسافر وحجوزاته (أحدث تذكرة فعالة فقط)
    public function profile(Request $request)
    {
        $currentUser = Auth::user();

        $rawBookings = Booking::where('user_id', $currentUser->id)
            ->with(['trip.agency'])
            ->latest()
            ->get();

        $companions = $currentUser->companions;

        $tripsCount = $rawBookings->where('status', '!=', 'cancelled')->count();

        // أحدث حجز غير ملغي وغير مسترد (الحجوزات مرتبة بالأحدث أولاً)
        $latestBookingRaw = $rawBookings->first(function ($booking) {
            return $booking->status !== 'cancelled'
                && $booking->payment_status !== 'refunded';
        });

        // كل طلبات الدفع الإضافية للمستخدم (معلّقة + مدفوعة)
        $allExtraCharges = \App\Models\BookingExtraCharge::with('booking')
            ->whereHas('booking', fn($q) => $q->where('user_id', $currentUser->id))
            ->latest()
            ->get();

        $pendingCharges   = $allExtraCharges->where('status', 'pending')->values();
        $paidExtraCharges = $allExtraCharges->where('status', 'paid')->values();

        // ---------- سجل المدفوعات (قيمة الحجز + المدفوعات الإضافية) ----------
        $toCarbon = function ($value) {
            if (!$value) return null;
            try {
                return $value instanceof Carbon ? $value : Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $paymentsHistory = collect();

        foreach ($rawBookings as $b) {
            if (!in_array($b->payment_status, ['paid', 'refunded'], true)) {
                continue;
            }

            $when = $toCarbon($b->paid_at ?? null) ?: $toCarbon($b->updated_at) ?: $toCarbon($b->created_at);

            $paymentsHistory->push([
                'type'        => 'booking',
                'booking_id'  => $b->id,
                'label'       => 'قيمة الحجز',
                'description' => $b->trip ? ($b->trip->from_city . ' ⬅️ ' . $b->trip->to_city) : null,
                'amount'      => (float) ($b->total_amount ?? 0),
                'method'      => $this->paymentMethodLabel($b->payment_method),
                'status'      => $b->payment_status === 'refunded' ? 'refunded' : 'paid',
                'sort_at'     => $when ? $when->timestamp : 0,
                'date'        => $when ? $when->format('Y-m-d h:i a') : '-',
            ]);
        }

        foreach ($paidExtraCharges as $charge) {
            $when = $toCarbon($charge->paid_at ?? null) ?: $toCarbon($charge->updated_at) ?: $toCarbon($charge->created_at);

            $paymentsHistory->push([
                'type'        => 'extra',
                'booking_id'  => $charge->booking_id,
                'label'       => 'دفعة إضافية',
                'description' => $charge->description,
                'amount'      => (float) ($charge->amount ?? 0),
                'method'      => !empty($charge->payment_method) ? $this->paymentMethodLabel($charge->payment_method) : null,
                'status'      => 'paid',
                'sort_at'     => $when ? $when->timestamp : 0,
                'date'        => $when ? $when->format('Y-m-d h:i a') : '-',
            ]);
        }

        $paymentsHistory = $paymentsHistory->sortByDesc('sort_at')->values();

        // إجمالي المدفوع الفعلي (المستردّ لا يُحتسب)
        $totalPaid      = (float) $paymentsHistory->where('status', 'paid')->sum('amount');
        $extraPaidTotal = (float) $paymentsHistory->where('type', 'extra')->where('status', 'paid')->sum('amount');

        $latestBooking = null;
        if ($latestBookingRaw) {
            $booking    = $latestBookingRaw;
            $trip       = $booking->trip;
            $departure  = ($trip && $trip->departure_at) ? \Carbon\Carbon::parse($trip->departure_at) : null;
            $return     = ($trip && $trip->return_at) ? \Carbon\Carbon::parse($trip->return_at) : null;
            $isUpcoming = $departure ? $departure->isFuture() : false;

            // نهاية الرحلة: تاريخ العودة إن وُجد، وإلا تاريخ الانطلاق
            $endsAt   = $return ?: $departure;
            $hasEnded = $endsAt ? $endsAt->isPast() : false;

            if ($booking->status === 'requestC') {
                $status = 'requestC';
            } elseif ($booking->status === 'cancelled') {
                $status = 'cancelled';
            } elseif ($hasEnded) {
                $status = 'completed';
            } elseif ($isUpcoming || $booking->status === 'pending') {
                $status = 'upcoming';
            } elseif ($departure) {
                $status = 'ongoing';      // انطلقت ولم تنتهِ بعد
            } else {
                $status = 'completed';    // رحلة محذوفة أو بدون تاريخ
            }

            $paymentStatusMap = [
                'pending'  => ['text' => 'قيد الانتظار ⏳', 'class' => 'warning'],
                'paid'     => ['text' => 'مدفوع ✅', 'class' => 'success'],
                'unpaid'   => ['text' => 'غير مدفوع ❌', 'class' => 'danger'],
                'failed'   => ['text' => 'فشلت العملية ⚠️', 'class' => 'danger'],
                'refunded' => ['text' => 'تم رد المبلغ ✅', 'class' => 'success'],
            ];
            $paymentInfo = $paymentStatusMap[$booking->payment_status] ?? ['text' => 'غير معروف', 'class' => 'secondary'];

            $paymentMethodText = match ($booking->payment_method) {
                'bank'  => 'تحويل بنكي',
                'tabby' => 'تابي',
                default => 'بطاقة بنكية',
            };

            // السعر الظاهر في التذكرة = قيمة الحجز + المدفوعات الإضافية المسددة لهذا الحجز
            $basePrice      = floatval($booking->total_amount ?? 0);
            $extraPaidForBk = (float) $paidExtraCharges->where('booking_id', $booking->id)->sum('amount');
            $displayPrice   = $basePrice + $extraPaidForBk;

            $latestBooking = (object)[
                'id'             => $booking->id,
                'trip_id'        => $booking->trip_id,
                'idBooking'      => $booking->trip_id,
                'route'          => $trip ? $trip->from_city . ' ⬅️ ' . $trip->to_city : 'رحلة غير معروفة',
                'date'           => $departure ? $departure->format('Y-m-d') : '-',
                'time'           => $departure ? $departure->format('h:i a') : '-',
                'return_date'    => $return ? $return->format('Y-m-d') : null,
                'return_time'    => $return ? $return->format('h:i a') : null,
                'typetrip'       => $trip ? $trip->trip_type : 'one_way',
                'status'         => $status,
                'is_upcoming'    => $isUpcoming,
                'has_ended'      => $hasEnded,
                'edit_notes'     => ($booking->edit_notes === 'تم التعديل' && $booking->updated_at->copy()->addDays(2)->isPast())
                    ? null
                    : $booking->edit_notes,
                'title'          => $trip ? $trip->title : 'بدون اسم',
                'type'           => $trip ? $trip->transport_type : 'bus',
                'price'          => $this->formatMoney($displayPrice),
                'base_price'     => $this->formatMoney($basePrice),
                'extra_paid'     => $this->formatMoney($extraPaidForBk),
                'extra_paid_raw' => $extraPaidForBk,
                'payment_status' => $booking->payment_status,
                'payment_text'   => $paymentInfo['text'],
                'payment_class'  => $paymentInfo['class'],
                'payment_method' => $paymentMethodText,
            ];
        }

        $bookings = $latestBooking ? collect([$latestBooking]) : collect([]);

        $user = (object)[
            'name'         => $currentUser->name,
            'email'        => $currentUser->email,
            'gender'       => $currentUser->gender,
            'city'         => $currentUser->city,
            'phone'        => $currentUser->phone ?? 'لا يوجد رقم هاتف',
            'national_id'  => $currentUser->national_id ?? 'لم يتم ربط الهوية',
            'is_verified'  => (int) ($currentUser->is_verified ?? 0),
            'id_image'     => $currentUser->id_image ? asset($currentUser->id_image) : null,
            'avatar'       => $currentUser->avatar ? asset($currentUser->avatar) : null,
            'birth_date'   => $currentUser->birth_date ? \Carbon\Carbon::parse($currentUser->birth_date)->format('Y-m-d') : null,
            'member_since' => $currentUser->created_at ? $currentUser->created_at->translatedFormat('F Y') : 'أكتوبر 2025',
            'trips_count'  => $tripsCount,
            'points'       => $currentUser->points ?? (250 * $tripsCount),
        ];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'user'            => $user,
                'latest_booking'  => $latestBooking,
                'bookings'        => $bookings,
                'pendingCharges'  => $pendingCharges,
                'payments'        => $paymentsHistory,
                'total_paid'      => $totalPaid,
                'extra_paid_total' => $extraPaidTotal,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return view('client.profile', compact(
            'pendingCharges',
            'user',
            'latestBooking',
            'bookings',
            'companions',
            'paymentsHistory',
            'totalPaid',
            'extraPaidTotal'
        ));
    }

    /**
     * استقبال وحفظ طلب تعديل الحجز من العميل
     */
    public function updateBookingRequest(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'edit_notes' => 'required|string|max:40',
        ], [
            'booking_id.required' => 'رقم الحجز مطلوب.',
            'booking_id.exists'   => 'الحجز غير موجود في النظام.',
            'edit_notes.required' => 'يرجى كتابة تفاصيل التعديل المطلوب.',
            'edit_notes.max'      => 'يجب ألا تتجاوز تفاصيل التعديل 40 حرفاً.',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود أو لا تملك صلاحية تعديله.'
            ], 403);
        }

        if ($booking->edit_count >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لقد استنفدت الحد الأقصى لطلبات التعديل لهذا الحجز (مرتان فقط).'
            ], 422);
        }

        $booking->update([
            'edit_notes' => $request->edit_notes,
            'edit_count' => $booking->edit_count + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ طلب التعديل بنجاح وسيتم مراجعته من الإدارة.'
        ], 200);
    }

    // حفظ المرافق
    public function storeCompanion(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            // التفرد لكل مستخدم وليس على مستوى النظام كله
            'national_id'      => ['required', 'string', 'max:20', Rule::unique('companions', 'national_id')->where('user_id', auth()->id())],
            'relationship'     => 'required|string|max:50',
            'gender'           => 'required|in:male,female,ذكر,أنثى',
            'birth_date'       => 'nullable|date',
            'dob_gregorian'    => 'nullable|date',
            'id_document'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'phone'            => 'nullable|string|max:20',
        ], [
            'name.required'         => 'يرجى إدخال اسم المرافق الكامل',
            'national_id.required'  => 'يرجى إدخال رقم الهوية أو الإقامة',
            'national_id.unique'    => 'هذا المرافق مُضاف مسبقاً في قائمتك',
            'relationship.required' => 'يرجى تحديد صلة القرابة',
            'gender.required'       => 'يرجى تحديد الجنس',
        ]);

        if ($request->hasFile('id_document')) {
            $validated['id_document_path'] = $request->file('id_document')->store('companions/documents', 'public');
        }

        auth()->user()->companions()->create($validated);

        return redirect()->back()->with('success', 'تمت إضافة المرافق بنجاح 👨‍👩‍👧‍👦');
    }

    // تعديل بيانات المرافق
    public function update(Request $request, Companion $companion)
    {
        if ($companion->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'national_id'      => [
                'required',
                'string',
                'max:20',
                Rule::unique('companions', 'national_id')
                    ->where('user_id', auth()->id())
                    ->ignore($companion->id),
            ],
            'relationship'     => 'required|string|max:50',
            'gender'           => 'required|in:male,female,ذكر,أنثى',
            'birth_date'       => 'nullable|date',
            'dob_gregorian'    => 'nullable|date',
            'id_document'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'phone'            => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('id_document')) {
            if ($companion->id_document_path) {
                Storage::disk('public')->delete($companion->id_document_path);
            }
            $validated['id_document_path'] = $request->file('id_document')->store('companions/documents', 'public');
        }

        $companion->update($validated);

        return back()->with('success', 'تم تحديث بيانات المرافق بنجاح');
    }

    /**
     * حذف مرافق محدد
     */
    public function destroyCompanion($id)
    {
        $companion = auth()->user()->companions()->findOrFail($id);
        $companion->delete();

        return redirect()->back()->with('success', 'تم حذف المرافق بنجاح 🗑️');
    }

    //تعديل بيانات المستخدم الرئيسي
    public function updateUser(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender'     => 'required|in:male,female,ذكر,أنثى',
            'city'       => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        return back()->with('success', 'تم تحديث بياناتك بنجاح');
    }

    // رفع صورة الهوية للتطبيق والموقع
    public function uploadId(Request $request)
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك، يرجى تسجيل الدخول مجدداً لإتمام العملية.'
                ], 401, [], JSON_UNESCAPED_UNICODE);
            }
            return redirect()->route('client.auth.login')->with('error', 'يرجى تسجيل الدخول أولاً.');
        }

        $request->validate([
            'id_image' => 'required|image|mimes:jpeg,png,jpg|max:4096',
        ], [
            'id_image.required' => 'يرجى اختيار ملف الصورة أولاً.',
            'id_image.image'    => 'الملف المرفوع يجب أن يكون صورة فقط.',
            'id_image.mimes'    => 'صيغ الصور المدعومة هي: jpeg, png, jpg.',
            'id_image.max'      => 'حجم الصورة كبير جداً، الحد الأقصى المسموح به هو 4 ميجابايت.',
        ]);

        $user = Auth::user();

        if ($request->hasFile('id_image')) {
            if ($user->id_image) {
                $oldPath = str_replace('storage/', '', $user->id_image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('id_image');
            $filename = 'id_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ids', $filename, 'public');

            $user->update([
                'id_image'    => 'storage/' . $path,
                'is_verified' => 3,
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success'     => true,
                    'message'     => 'تم رفع صورة الهوية بنجاح! جاري معالجة البيانات وتوثيق الحساب ⏳',
                    'id_image'    => asset('storage/' . $path),
                    'is_verified' => 3
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            return redirect()->back()->with('success', 'تم رفع صورة الهوية بنجاح! جاري معالجة البيانات وتوثيق الحساب ⏳');
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في رفع الملف، يرجى المحاولة مرة أخرى.'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return redirect()->back()->with('error', 'فشل في رفع الملف، يرجى المحاولة مرة أخرى.');
    }

    private function parseIncomingData($source)
    {
        if (empty($source)) return [];
        if (is_array($source)) return $source;

        $decoded = json_decode($source, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return collect(explode(',', $source))
            ->map(fn($item) => trim($item))
            ->filter(fn($val) => $val !== '')
            ->values()
            ->all();
    }

    /** يحوّل قيمة seat_number (رقم / نص / JSON / مصفوفة) إلى مصفوفة نصوص نظيفة */
    private function seatTokensFrom($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [$raw];
        }
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        $out = [];
        foreach (Arr::flatten($raw) as $s) {
            $s = trim((string) $s, '" ');
            if ($s !== '') {
                $out[] = $s;
            }
        }
        return $out;
    }

    /** أرقام مقاعد الحافلة فقط (يستبعد توكنات السكن H1, H2 ...) */
    private function busSeatNumbersFrom($raw): array
    {
        $nums = [];
        foreach ($this->seatTokensFrom($raw) as $tok) {
            if (str_starts_with(strtoupper($tok), 'H')) continue;
            $n = (int) preg_replace('/\D/', '', $tok);
            if ($n > 0) $nums[] = $n;
        }
        return $nums;
    }

    private function isAccommodationOnlySeat($seatNumber): bool
    {
        if (is_array($seatNumber)) {
            $seatNumber = $seatNumber[0] ?? '';
        }
        return str_starts_with(strtoupper(trim((string) $seatNumber, '" ')), 'H');
    }

    public function selectSeat(Request $request, $id)
    {
        $trip = Trip::with('agency')->findOrFail($id);
        abort_if($trip->is_overflow_standby, 404);

        $existingBooking = null;
        $prefilledSeats = [];
        $prefilledPassengers = [];
        $prefilledRoomQuantities = [];
        $prefilledRoomAssignments = [];

        $prefilledRoomBedTypes = [];
        $prefilledAgencyNote   = '';
        $bedTypeOptions        = $this->bedTypeOptionsForClient();

        if ($request->filled('booking_id')) {
            // 🔒 الحجز يجب أن يخص المستخدم الحالي وهذه الرحلة
            $existingBooking = Booking::with('bookingSeats')
                ->where('id', $request->booking_id)
                ->where('user_id', auth()->id())
                ->where('trip_id', $trip->id)
                ->first();

            if ($existingBooking) {
                $prefilledRoomBedTypes = $existingBooking->room_bed_types ?? [];
                $prefilledAgencyNote   = $existingBooking->agency_note ?? '';
                $prefilledRoomQuantities = is_string($existingBooking->room_quantities)
                    ? (json_decode($existingBooking->room_quantities, true) ?: [])
                    : ($existingBooking->room_quantities ?? []);

                $prefilledRoomAssignments = is_string($existingBooking->room_assignments)
                    ? (json_decode($existingBooking->room_assignments, true) ?: [])
                    : ($existingBooking->room_assignments ?? []);

                $hasQuantities = !empty(array_filter($prefilledRoomQuantities));
                if (
                    !$hasQuantities
                    && !empty($existingBooking->room_type)
                    && $existingBooking->room_type !== 'shared'
                    && (int) $existingBooking->room_count > 0
                ) {
                    $prefilledRoomQuantities = [
                        $existingBooking->room_type => (int) $existingBooking->room_count,
                    ];
                }

                foreach ($existingBooking->bookingSeats as $bSeat) {
                    $sn = $bSeat->seat_number;
                    if (is_string($sn)) {
                        $decoded = json_decode($sn, true);
                        $sn = is_array($decoded) ? ($decoded[0] ?? $sn) : $sn;
                    } elseif (is_array($sn)) {
                        $sn = $sn[0] ?? null;
                    }
                    $cleanSeat = trim((string)$sn, '" ');

                    if ($cleanSeat !== '') {
                        $prefilledSeats[] = $cleanSeat;
                        $prefilledPassengers[$cleanSeat] = [
                            'seat_number'           => $cleanSeat,
                            'passenger_name'        => $bSeat->passenger_name,
                            'passenger_national_id' => $bSeat->passenger_national_id,
                            'gender'                => $bSeat->gender ?? 'male',
                            'room_type'             => $bSeat->room_type,
                            'meal_options'          => is_string($bSeat->meal_options) ? json_decode($bSeat->meal_options, true) : ($bSeat->meal_options ?? []),
                            'extra_services'        => is_string($bSeat->extra_services) ? json_decode($bSeat->extra_services, true) : ($bSeat->extra_services ?? []),
                        ];
                    }
                }
            }
        }

        // المرافقون
        $authUserNationalId = auth()->check() ? (auth()->user()->national_id ?? auth()->user()->id_number) : null;
        $companions = auth()->check()
            ? auth()->user()->companions
            ->reject(fn($c) => $authUserNationalId && (string)$c->national_id === (string)$authUserNationalId)
            ->unique('national_id')
            ->values()
            : collect();

        // المقاعد المحجوزة للآخرين
        $bookedSeatsQuery = BookingSeat::where('trip_id', $id);
        if ($existingBooking) {
            $bookedSeatsQuery->where('booking_id', '!=', $existingBooking->id);
        }
        $bookedSeatsData = $bookedSeatsQuery->get();

        $nationalIds = $bookedSeatsData->pluck('passenger_national_id')->filter()->unique()->toArray();
        $usersMap = User::whereIn('national_id', $nationalIds)->get()->keyBy('national_id');

        $booked_seats = [];
        $booked_seats_details = [];
        $femaleCount = 0;
        $maleCount = 0;
        $totalBusSeats = intval($trip->total_seats ?? 0);

        foreach ($bookedSeatsData as $seat) {
            $associatedUser = $seat->passenger_national_id ? $usersMap->get($seat->passenger_national_id) : null;
            $gender = ($associatedUser && $associatedUser->gender) ? $associatedUser->gender : ($seat->gender ?: 'male');

            $rawSeat = $seat->seat_number;
            if (is_array($rawSeat)) {
                $seatNumbers = $rawSeat;
            } elseif (is_string($rawSeat)) {
                $decoded = json_decode($rawSeat, true);
                $seatNumbers = is_array($decoded) ? $decoded : [$rawSeat];
            } else {
                $seatNumbers = [$rawSeat];
            }

            foreach ($seatNumbers as $seatNum) {
                if ($seatNum === null || $seatNum === '') continue;
                $seatNumStr = (string)$seatNum;
                $booked_seats[] = $seatNumStr;

                if ($gender === 'female') {
                    $femaleCount++;
                } else {
                    $maleCount++;
                }

                $booked_seats_details[$seatNumStr] = [
                    'gender'         => $gender,
                    'room_type'      => $seat->room_type,
                    'parent_seat_id' => $seat->parent_seat_id ?? null,
                    'passenger_name' => $seat->passenger_name,
                    'national_id'    => $seat->passenger_national_id,
                ];
            }
        }

        $booked_seats = array_values(array_unique($booked_seats));

        // حساب تقسيم المقاعد (للعرض فقط — بدون حفظ في قاعدة البيانات عند فتح الصفحة)
        if (isset($trip->auto_split) && $trip->auto_split == 1 && !$trip->is_security_approved) {
            $defaultBoundary = intval($trip->split_boundary ?: 28);
            $availableFemaleSeats = $totalBusSeats - $defaultBoundary;

            if ($femaleCount >= ($availableFemaleSeats * 0.80)) {
                $newBoundary = max(5, $defaultBoundary - 5);
            } elseif ($maleCount >= ($defaultBoundary * 0.80)) {
                $newBoundary = min($totalBusSeats - 5, $defaultBoundary + 5);
            } else {
                $newBoundary = $defaultBoundary;
            }

            if ($newBoundary != $defaultBoundary) {
                $trip->split_boundary = $newBoundary; // في الذاكرة فقط
            }
        }

        $mealOptions = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options, true) ?: []);
        $roomOptions = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []);
        $extraServices = is_array($trip->extra_services) ? $trip->extra_services : (json_decode($trip->extra_services, true) ?: []);

        $trip->room_prices = [
            'single' => floatval(($roomOptions['single']['price'] ?? null) ?: ($trip->price_single ?? $trip->price)),
            'double' => floatval(($roomOptions['double']['price'] ?? null) ?: ($trip->price_double ?? ($trip->price * 2))),
            'triple' => floatval(($roomOptions['triple']['price'] ?? null) ?: ($trip->price_triple ?? ($trip->price * 3))),
            'quad'   => floatval(($roomOptions['quad']['price']   ?? null) ?: ($trip->price_quad   ?? ($trip->price * 4))),
        ];

        $rawFeatures = is_array($trip->features) ? $trip->features : json_decode($trip->features, true);
        $features = !empty($rawFeatures) ? array_unique($rawFeatures) : [];

        // الباص الثاني: إذا هذه الرحلة ممتلئة وباصها الثاني مفعّل من الوكالة وفيه مقاعد، نعرض للمسافر رابطه
        $overflowTrip = null;
        if ((int) $trip->available_seats <= 0 && !$existingBooking) {
            $overflowTrip = Trip::where('parent_trip_id', $trip->id)
                ->where('is_overflow_standby', false)
                ->where('available_seats', '>', 0)
                ->first();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success'                    => true,
                'trip'                       => $trip,
                'booked_seats'               => $booked_seats,
                'booked_seats_details'       => $booked_seats_details,
                'meal_options'               => $mealOptions,
                'extra_services'             => $extraServices,
                'features'                   => $features,
                'companions'                 => $companions,
                'existing_booking'           => $existingBooking,
                'prefilled_seats'            => $prefilledSeats,
                'prefilled_passengers'       => $prefilledPassengers,
                'prefilled_room_quantities'  => $prefilledRoomQuantities,
                'prefilled_room_assignments' => $prefilledRoomAssignments,
                'overflow_trip'              => $overflowTrip ? ['id' => $overflowTrip->id] : null,
                'prefilledRoomBedTypes'      => $prefilledRoomBedTypes,
                'prefilledAgencyNote'      => $prefilledAgencyNote,
                'bedTypeOptions'      => $bedTypeOptions,


            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return view('client.select_seat', compact(
            'trip',
            'booked_seats_details',
            'mealOptions',
            'extraServices',
            'booked_seats',
            'features',
            'companions',
            'existingBooking',
            'prefilledSeats',
            'prefilledPassengers',
            'prefilledRoomQuantities',
            'prefilledRoomAssignments',
            'overflowTrip',
            'prefilledRoomBedTypes',
            'prefilledAgencyNote',
            'bedTypeOptions'
        ));
    }
    /** خيارات الأسرّة بصيغة تناسب الواجهة */
    private function bedTypeOptionsForClient(): array
    {
        return collect(\App\Support\BedTypes::all())->map(
            fn($opts) => collect($opts)->map(fn($label, $id) => ['id' => $id, 'label' => $label])->values()->all()
        )->all();
    }

    /** السيرفر هو المرجع: أي سرير غير صالح يُستبدل بأول خيار للنوع */
    private function sanitizeBedTypes(array $bedTypes, array $roomQuantities): array
    {
        $options = \App\Support\BedTypes::all();
        $clean = [];

        foreach ($roomQuantities as $type => $qty) {
            $type = strtolower(trim((string) $type));
            $qty  = (int) $qty;
            if ($qty < 1 || empty($options[$type])) continue;

            $allowed = array_keys($options[$type]);
            for ($i = 1; $i <= $qty; $i++) {
                $uid    = "{$type}_{$i}";
                $chosen = $bedTypes[$uid] ?? null;
                $clean[$uid] = in_array($chosen, $allowed, true) ? $chosen : $allowed[0];
            }
        }
        return $clean;
    }

    private function bookingExtras(Request $request): array
    {
        $roomQuantities = $this->safeJsonParse($request->input('room_quantities'));
        $note = mb_substr(trim(strip_tags((string) $request->input('agency_note'))), 0, 500);

        return [
            'room_bed_types'     => $this->sanitizeBedTypes($this->safeJsonParse($request->input('room_bed_types')), $roomQuantities),
            'agency_note'        => $note !== '' ? $note : null,
            'agency_note_status' => $note !== '' ? 'pending' : null,
        ];
    }

    // الحفظ
    public function storeBooking(Request $request)
    {
        if (!Auth::check()) {
            return $this->unauthorizedResponse($request);
        }

        $this->normalizeRequestData($request);
        $this->validateBookingRequest($request);

        $rawSeatTokens = $this->parseIncomingData($request->input('selected_seats'));
        $accommodationOnlyIdx = [];
        $busTokens = [];
        $busPassengerIdx = []; // فهرس المسافر لكل مقعد حافلة
        foreach ($rawSeatTokens as $i => $token) {
            if (str_starts_with(strtoupper(trim((string) $token)), 'H')) {
                $accommodationOnlyIdx[] = $i;   // مسافر سكن فقط
            } else {
                $busTokens[] = $token;
                $busPassengerIdx[] = $i;
            }
        }
        $seatsArray = $this->extractCleanSeats($busTokens);
        if (empty($seatsArray) && empty($accommodationOnlyIdx)) {
            return $this->errorResponse($request, 'عذراً، حدث تضارب في بيانات المقاعد والمسافرين.', 422);
        }

        $passengerNamesArray   = $this->parseIncomingData($request->input('passenger_names'));
        $passengerIdsArray     = array_pad($this->parseIncomingData($request->input('passenger_ids')), count($passengerNamesArray), '');
        $passengerGendersArray = array_pad($this->parseIncomingData($request->input('passenger_genders')), count($passengerNamesArray), '');

        // أجناس ركاب الحافلة فقط (بنفس ترتيب المقاعد)
        $busGenders = array_map(fn($i) => $passengerGendersArray[$i] ?? null, $busPassengerIdx);

        $passengerSeatsMap = $this->safeJsonParse($request->input('passenger_seats'));
        $receiptPath       = $this->handleReceiptUpload($request);

        // الحجز القديم (إعادة محاولة الدفع) — يجب أن يكون للمستخدم ولنفس الرحلة
        $existingBookingId = $request->input('booking_id');
        $existingBooking = null;
        if ($existingBookingId) {
            $existingBooking = Booking::where('id', $existingBookingId)
                ->where('user_id', Auth::id())
                ->where('trip_id', $request->input('trip_id'))
                ->whereIn('payment_status', ['unpaid', 'pending', 'failed'])
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->first();
        }

        try {
            $booking = DB::transaction(function () use (
                $request,
                $seatsArray,
                $passengerNamesArray,
                $passengerIdsArray,
                $passengerGendersArray,
                $busGenders,
                $busPassengerIdx,
                $passengerSeatsMap,
                $receiptPath,
                $existingBooking,
                $accommodationOnlyIdx,
                $rawSeatTokens
            ) {
                $trip = Trip::lockForUpdate()->findOrFail($request->trip_id);

                // الباص الثاني غير المفعّل من الوكالة لا يُحجز
                if ($trip->is_overflow_standby) {
                    throw new \DomainException('عذراً، هذه الرحلة غير متاحة للحجز حالياً.');
                }

                // لو فيه حجز قديم: نفرّغ مقاعده وغرفه القديمة ونرجّع العداد قبل أي تحقق جديد
                if ($existingBooking) {
                    $this->releaseBookingInventory($existingBooking, $trip);
                }

                $roomQuantities    = $this->safeJsonParse($request->input('room_quantities'));
                $roomAssignments   = $this->safeJsonParse($request->input('room_assignments'));
                $passengerMeals    = $this->safeJsonParse($request->input('passenger_meals'));
                $passengerServices = $this->safeJsonParse($request->input('passenger_services'));

                // --- [ الترتيب التلقائي للمقاعد ] ---
                $originalSeatsArray = $seatsArray;
                $groupingResult = $this->autoGroupSeats($trip, $originalSeatsArray, $busGenders);
                $seatsArray = $groupingResult['new_seats'];
                $seatChangesLog = [];
                $passengerSeatsMapLocal = $passengerSeatsMap;

                if ($groupingResult['changed']) {
                    $newPassengerSeatsMap = [];
                    $seatMap = []; // مقعد قديم => مقعد جديد

                    foreach ($originalSeatsArray as $index => $oldSeat) {
                        $newSeat = $seatsArray[$index];
                        $seatMap[(string) $oldSeat] = $newSeat;

                        if (isset($passengerSeatsMapLocal[$oldSeat])) {
                            $newPassengerSeatsMap[$newSeat] = $passengerSeatsMapLocal[$oldSeat];
                        }
                        $pIdx = $busPassengerIdx[$index] ?? $index;
                        $seatChangesLog[] = [
                            'old_seat'       => $oldSeat,
                            'new_seat'       => $newSeat,
                            'passenger_name' => $passengerNamesArray[$pIdx] ?? 'غير محدد',
                            'reason'         => 'تجميع تلقائي للمقاعد في نفس الحجز'
                        ];
                    }
                    $passengerSeatsMapLocal = $newPassengerSeatsMap;

                    $roomAssignments   = $this->remapSeatKeys($roomAssignments, $seatMap);
                    $passengerMeals    = $this->remapSeatKeys($passengerMeals, $seatMap);
                    $passengerServices = $this->remapSeatKeys($passengerServices, $seatMap);
                }

                $this->verifyPassengersAndSeatsAvailability($trip, $seatsArray, $passengerIdsArray);
                $this->processAutoSplitAndSeatShifting($trip, $seatsArray, $busGenders, $passengerSeatsMapLocal);

                $this->deductAvailableRooms($trip, $roomQuantities);
                $calculation = BookingCalculator::calculate($trip, $roomQuantities, $seatsArray, $roomAssignments, $passengerMeals, $passengerServices);

                $subtotal    = floatval($calculation['total_price'] ?? 0);
                $discount    = $this->resolveDiscount($request, $subtotal);
                $totalAmount = max(0, $subtotal - $discount);

                if ($existingBooking) {
                    $booking = $this->updateBookingRecord($existingBooking, $request, $trip, $totalAmount, $discount, $receiptPath, $seatsArray);
                } else {
                    $booking = $this->createBookingRecord($request, $trip, $totalAmount, $discount, $receiptPath, $seatsArray);
                }

                $this->processSeatsAndRoomsCreation($booking, $trip, $seatsArray, $passengerNamesArray, $passengerIdsArray, $passengerGendersArray, $passengerSeatsMapLocal, $passengerMeals, $passengerServices, $roomAssignments, $accommodationOnlyIdx, $rawSeatTokens);

                if (!empty($seatChangesLog)) {
                    $newBookingSeats = BookingSeat::where('booking_id', $booking->id)->get();
                    foreach ($seatChangesLog as $log) {
                        $matchedSeat = $newBookingSeats->first(function ($seat) use ($log) {
                            return in_array((string) $log['new_seat'], $this->seatTokensFrom($seat->seat_number), true);
                        });
                        if ($matchedSeat) {
                            TripSeatLog::create([
                                'trip_id'         => $trip->id,
                                'booking_seat_id' => $matchedSeat->id,
                                'passenger_name'  => $log['passenger_name'],
                                'old_seat'        => is_array($log['old_seat']) ? json_encode($log['old_seat']) : $log['old_seat'],
                                'new_seat'        => is_array($log['new_seat']) ? json_encode($log['new_seat']) : $log['new_seat'],
                                'reason'          => $log['reason']
                            ]);
                        }
                    }
                }

                $this->syncCompanions(Auth::id(), $passengerNamesArray, $passengerIdsArray, $passengerGendersArray);
                $trip->decrement('available_seats', count($seatsArray));
                $trip->refresh()->openOverflowIfFull();

                return $booking;
            });

            if ($booking->payment_method === 'bank') {
                $this->sendBookingEmail($booking);
            }
            return $this->buildSuccessResponse($request, $booking);
        } catch (\DomainException $e) {
            // أخطاء منطقية مقصودة (مقاعد محجوزة، غرف غير كافية...) رسالتها آمنة للعرض
            $this->deleteReceipt($receiptPath);
            return $this->errorResponse($request, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            // أخطاء غير متوقعة: نسجلها ولا نعرض تفاصيلها للمستخدم
            $this->deleteReceipt($receiptPath);
            Log::error('storeBooking failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trip_id' => $request->input('trip_id'),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);
            return $this->errorResponse($request, 'حدث خطأ غير متوقع أثناء إتمام الحجز، يرجى المحاولة مرة أخرى.', 500);
        }
    }

    private function deleteReceipt(?string $receiptPath): void
    {
        if ($receiptPath) {
            $path = str_replace('storage/', '', $receiptPath);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * 🔒 الخصم لا يُؤخذ من الطلب أبداً (كان أي مستخدم يقدر يرسل discount=999999).
     * اربط هنا كود خصم/كوبون يتم التحقق منه من قاعدة البيانات.
     */
    private function resolveDiscount(Request $request, float $subtotal): float
    {
        // مثال لاحقاً:
        // $coupon = Coupon::where('code', $request->input('coupon_code'))->valid()->first();
        // return $coupon ? min($subtotal, $coupon->calculate($subtotal)) : 0.0;
        return 0.0;
    }

    /**
     * يعيد ترقيم مفاتيح المصفوفة إذا كانت مفاتيحها أرقام مقاعد قديمة
     */
    private function remapSeatKeys(array $data, array $seatMap): array
    {
        if (empty($data) || array_is_list($data)) {
            return $data;
        }

        foreach (array_keys($data) as $k) {
            if (!array_key_exists((string) $k, $seatMap)) {
                return $data;
            }
        }

        $new = [];
        foreach ($data as $k => $v) {
            $new[$seatMap[(string) $k]] = $v;
        }
        return $new;
    }

    /**
     * استخراج وتوزيع نوع الغرفة والوحدة الفرعية (مثل double_0, double_1)
     */
    private function extractRoomAssignment($roomAssignments, array $pSeats, int $pIndex, ?string $token = null, int $passengerGlobalIndex = 0, array $roomSlots = []): string
    {
        $rawVal = null;

        if (!empty($roomAssignments)) {
            $searchKeys = $pSeats;
            if ($token) {
                $searchKeys[] = $token;
            }

            foreach ($searchKeys as $st) {
                $stInt = intval($st);
                $stStr = (string)$st;

                if (isset($roomAssignments[$stInt])) {
                    $rawVal = $roomAssignments[$stInt];
                    break;
                }
                if (isset($roomAssignments[$stStr])) {
                    $rawVal = $roomAssignments[$stStr];
                    break;
                }
            }

            if ($rawVal === null) {
                $indexKeys = [$pIndex, (string)$pIndex, "passenger_{$pIndex}", "p_{$pIndex}"];
                foreach ($indexKeys as $k) {
                    if (isset($roomAssignments[$k])) {
                        $rawVal = $roomAssignments[$k];
                        break;
                    }
                }
            }

            if ($rawVal === null && array_is_list($roomAssignments) && isset($roomAssignments[$passengerGlobalIndex])) {
                $rawVal = $roomAssignments[$passengerGlobalIndex];
            }
        }

        $roomType = 'shared';
        if (is_array($rawVal)) {
            $roomType = $rawVal['unique_label']
                ?? $rawVal['room_label']
                ?? $rawVal['label']
                ?? $rawVal['room_type']
                ?? $rawVal['type']
                ?? $rawVal['room']
                ?? 'shared';
        } elseif (is_string($rawVal) && !empty($rawVal)) {
            $roomType = $rawVal;
        }

        if (($roomType === 'shared' || empty($roomType)) && !empty($roomSlots)) {
            $capacities = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4, 'shared' => 1];
            $assignedSlot = null;
            $tempSum = 0;

            foreach ($roomSlots as $slot) {
                $cap = $capacities[$slot['type']] ?? 2;
                if ($passengerGlobalIndex >= $tempSum && $passengerGlobalIndex < ($tempSum + $cap)) {
                    $assignedSlot = $slot['unique_label'];
                    break;
                }
                $tempSum += $cap;
            }

            if ($assignedSlot) {
                return $assignedSlot;
            }

            return end($roomSlots)['unique_label'];
        }

        return (!empty($roomType) && $roomType !== 'shared') ? (string) $roomType : 'shared';
    }

    private function processSeatsAndRoomsCreation(
        Booking $booking,
        Trip $trip,
        array $seatsArray,
        array $passengerNamesArray,
        array $passengerIdsArray,
        array $passengerGendersArray,
        array $passengerSeatsMap,
        array $passengerMeals,
        array $passengerServices,
        array $roomAssignments,
        array $accommodationOnlyIdx = [],
        array $rawSeatTokens = []
    ): void {
        $groupedPassengers = [];
        $busIdx = 0;
        $seatByIndex = [];
        foreach ($rawSeatTokens as $i => $tok) {
            if (in_array($i, $accommodationOnlyIdx, true)) continue;
            $seatByIndex[$i] = $seatsArray[$busIdx++] ?? null;
        }

        // [1] تجميع بيانات الركاب
        foreach ($passengerNamesArray as $pIndex => $passengerName) {
            $passengerName = trim($passengerName);
            if (empty($passengerName)) continue;

            $nationalId = trim($passengerIdsArray[$pIndex] ?? '');
            $gender     = trim($passengerGendersArray[$pIndex] ?? '');
            $groupKey   = !empty($nationalId) ? ('id_' . $nationalId) : ('name_' . mb_strtolower($passengerName) . '_' . $gender);

            $pSeats = [];
            $accommodationToken = null;
            if (in_array($pIndex, $accommodationOnlyIdx, true)) {
                $accommodationToken = strtoupper(trim((string) ($rawSeatTokens[$pIndex] ?? '')));
            } elseif (!empty($seatByIndex[$pIndex])) {
                $pSeats = [(int) $seatByIndex[$pIndex]];
            }

            if (!isset($groupedPassengers[$groupKey])) {
                $groupedPassengers[$groupKey] = [
                    'name'                => $passengerName,
                    'national_id'         => $nationalId,
                    'gender'              => $gender,
                    'seats'               => $pSeats,
                    'original_index'      => $pIndex,
                    'accommodation_token' => $accommodationToken,
                ];
            } else {
                $groupedPassengers[$groupKey]['seats'] = array_merge($groupedPassengers[$groupKey]['seats'], $pSeats);
            }
        }

        $createdSeatsModels = [];

        // [2] إنشاء سجل منفصل لكل مقعد وتصحيح ربط parent_seat_id
        foreach ($groupedPassengers as $pData) {
            if (!empty($pData['accommodation_token'])) {
                $token  = $pData['accommodation_token'];
                $pIndex = $pData['original_index'];
                $mealData    = $this->extractPassengerData($passengerMeals, [$token], $pIndex);
                $serviceData = $this->extractPassengerData($passengerServices, [$token], $pIndex);

                $assignedRoom = $this->extractRoomAssignment($roomAssignments, [], $pIndex, $token, $pIndex);

                $createdSeatsModels[] = BookingSeat::create([
                    'booking_id'            => $booking->id,
                    'trip_id'               => $trip->id,
                    'seat_number'           => $token,
                    'parent_seat_id'        => null,
                    'passenger_name'        => $pData['name'],
                    'passenger_national_id' => $pData['national_id'] ?: null,
                    'gender'                => in_array($pData['gender'], ['male', 'female']) ? $pData['gender'] : null,
                    'room_type'             => $assignedRoom,
                    'with_breakfast'        => !empty($mealData['breakfast']) ? 1 : 0,
                    'with_lunch'            => !empty($mealData['lunch']) ? 1 : 0,
                    'with_dinner'           => !empty($mealData['dinner']) ? 1 : 0,
                    'meal_options'          => is_array($mealData) ? $mealData : [],
                    'extra_services'        => is_array($serviceData) ? array_values($serviceData) : [],
                    'is_extra'              => 0,
                ]);
                continue;
            }

            $pSeats = array_values(array_unique(array_map('intval', $pData['seats'])));
            if (empty($pSeats)) continue;

            $pIndex          = $pData['original_index'];
            $seatServiceData = $this->extractPassengerData($passengerServices, $pSeats, $pIndex);
            $seatMealData    = $this->extractPassengerData($passengerMeals, $pSeats, $pIndex);

            if (is_string($seatMealData) && !empty($seatMealData)) {
                $seatMealData = ['package_name' => $seatMealData];
            }

            $assignedRoom = $this->extractRoomAssignment($roomAssignments, $pSeats, $pIndex, null, $pIndex);

            $primarySeatModelId = null;

            foreach ($pSeats as $index => $seatNum) {
                $isPrimary = ($index === 0);

                $createdSeat = BookingSeat::create([
                    'booking_id'            => $booking->id,
                    'trip_id'               => $trip->id,
                    'seat_number'           => (string)$seatNum,
                    'parent_seat_id'        => $isPrimary ? null : $primarySeatModelId,
                    'passenger_name'        => $isPrimary ? $pData['name'] : $pData['name'] . ' (مقعد إضافي)',
                    'passenger_national_id' => $pData['national_id'] ?: null,
                    'gender'                => in_array($pData['gender'], ['male', 'female']) ? $pData['gender'] : null,
                    'room_type'             => $isPrimary ? $assignedRoom : 'additional',
                    'with_breakfast'        => !empty($seatMealData['breakfast']) ? 1 : 0,
                    'with_lunch'            => !empty($seatMealData['lunch']) ? 1 : 0,
                    'with_dinner'           => !empty($seatMealData['dinner']) ? 1 : 0,
                    'meal_options'          => is_array($seatMealData) ? $seatMealData : [],
                    'extra_services'        => is_array($seatServiceData) ? array_values($seatServiceData) : [],
                    'is_extra'              => $isPrimary ? 0 : 1,
                ]);

                if ($isPrimary) {
                    $primarySeatModelId = $createdSeat->id;
                }

                $createdSeatsModels[] = $createdSeat;
            }
        }

        // [3] ربط الغرف المخصصة بجدول TripRoom
        $seatsGroupedByRoom = collect($createdSeatsModels)->groupBy('room_type');
        foreach ($seatsGroupedByRoom as $roomLabel => $seatsInThisRoom) {
            if (!$roomLabel || $roomLabel === 'shared' || $roomLabel === 'additional') continue;

            $cleanRoomType = explode('_', $roomLabel)[0];
            $newTripRoom = TripRoom::create([
                'trip_id'   => $trip->id,
                'room_type' => $cleanRoomType,
            ]);

            foreach ($seatsInThisRoom as $seatModel) {
                $seatModel->update(['trip_room_id' => $newTripRoom->id]);
            }
        }
    }

    public function sendBookingEmail(Booking $booking): void
    {
        try {
            if (empty($booking->customer_email)) {
                Log::warning('تخطي إيميل الحجز #' . $booking->id . ': لا يوجد بريد.');
                return;
            }
            Mail::to($booking->customer_email)->send(
                new BookingTicketMail($booking->load(['trip', 'bookingSeats']))
            );
        } catch (\Exception $e) {
            Log::error('فشل إرسال إيميل الحجز #' . $booking->id . ': ' . $e->getMessage());
        }
    }

    /**
     * تفريغ مقاعد وغرف حجز قديم فاشل (قبل إعادة المحاولة) وإرجاع العداد لحالته الطبيعية
     */
    private function releaseBookingInventory(Booking $existingBooking, Trip $trip): void
    {
        $oldSeats = BookingSeat::where('booking_id', $existingBooking->id)->get();
        if ($oldSeats->isEmpty()) return;

        // إرجاع عدّاد المقاعد المتاحة (مقاعد الحافلة فقط، بدون توكنات السكن H)
        $busSeatsCount = $oldSeats->reject(fn($s) => $this->isAccommodationOnlySeat($s->seat_number))->count();
        if ($busSeatsCount > 0) {
            $trip->increment('available_seats', $busSeatsCount);
        }

        // إرجاع عدّاد الغرف الخاصة لكل TripRoom مرتبط، ثم حذفه
        $roomIds = $oldSeats->pluck('trip_room_id')->filter()->unique();
        if ($roomIds->isNotEmpty()) {
            $rooms = TripRoom::whereIn('id', $roomIds)->get();
            $roomOptions = $this->safeJsonParse($trip->room_options);

            foreach ($rooms as $room) {
                if (isset($roomOptions[$room->room_type])) {
                    $roomOptions[$room->room_type]['count'] = intval($roomOptions[$room->room_type]['count'] ?? 0) + 1;
                }
            }
            $trip->update(['room_options' => $roomOptions]);
            TripRoom::whereIn('id', $roomIds)->delete();
        }

        TripSeatLog::whereIn('booking_seat_id', $oldSeats->pluck('id'))->delete();
        BookingSeat::where('booking_id', $existingBooking->id)->delete();
    }

    /**
     * تحديث بيانات حجز موجود بدل إنشاء حجز جديد (لإعادة محاولة الدفع)
     */
    private function updateBookingRecord(Booking $existingBooking, Request $request, Trip $trip, float $totalAmount, float $discount, ?string $receiptPath, array $seatsArray): Booking
    {
        $existingBooking->update(array_merge([
            'customer_name'      => Auth::user()->name ?? $existingBooking->customer_name,
            'customer_email'     => Auth::user()->email,
            'customer_phone'     => Auth::user()->phone,
            'total_amount'       => $totalAmount,
            'discount'           => $discount,
            'status'             => 'pending',
            'payment_status'     => ($request->payment_method === 'bank') ? 'pending' : 'unpaid',
            'payment_method'     => $request->payment_method,
            'expires_at'         => now()->addMinutes(15),
            'card_name'          => ($request->payment_method === 'card') ? $request->input('card_name') : null,
            'bank_receipt'       => $receiptPath ?? $existingBooking->bank_receipt,
            'room_type'          => $request->input('room_type') ?? 'shared',
            'room_count'         => intval($request->input('room_count') ?? 0),
            'selected_seats'     => array_values($seatsArray),
            'room_quantities'    => $this->safeJsonParse($request->input('room_quantities')),
            'room_assignments'   => $this->safeJsonParse($request->input('room_assignments')),
            'passenger_meals'    => $this->safeJsonParse($request->input('passenger_meals')),
            'passenger_services' => $this->safeJsonParse($request->input('passenger_services')),
            'edit_notes' => null,
        ], $this->bookingExtras($request)));

        return $existingBooking->fresh();
    }

    /**
     * ترتيب المقاعد المتفرقة في مقاعد متجاورة (مع احترام قسم الرجال/النساء عند تفعيل التقسيم التلقائي)
     */
    private function autoGroupSeats(Trip $trip, array $requestedSeats, array $genders = []): array
    {
        $unchanged = ['changed' => false, 'new_seats' => $requestedSeats];
        $seatCount = count($requestedSeats);

        if ($seatCount <= 1) {
            return $unchanged;
        }

        $sortedSeats = $requestedSeats;
        sort($sortedSeats);
        $isSequential = true;
        for ($i = 0; $i < $seatCount - 1; $i++) {
            if ($sortedSeats[$i + 1] - $sortedSeats[$i] != 1) {
                $isSequential = false;
                break;
            }
        }
        if ($isSequential) {
            return $unchanged;
        }

        $totalTripSeats = (int) ($trip->total_seats ?: 50);
        $minSeat = 1;
        $maxSeat = $totalTripSeats;

        // عند التقسيم التلقائي: نعيد الترتيب فقط إذا كان كل الركاب بنفس الجنس وداخل قسمهم
        if (isset($trip->auto_split) && $trip->auto_split == 1 && !$trip->is_security_approved) {
            $uniqueGenders = array_values(array_unique(array_filter($genders)));
            if (count($uniqueGenders) !== 1) {
                return $unchanged;
            }
            $boundary = (int) ($trip->split_boundary ?: 28);
            if ($uniqueGenders[0] === 'female') {
                $minSeat = $boundary + 1;
            } elseif ($uniqueGenders[0] === 'male') {
                $maxSeat = $boundary;
            } else {
                return $unchanged;
            }
        }

        // كل المقاعد المحجوزة (رقمياً، بدون توكنات السكن)
        $bookedSeats = [];
        foreach (BookingSeat::where('trip_id', $trip->id)->pluck('seat_number') as $raw) {
            foreach ($this->busSeatNumbersFrom($raw) as $n) {
                $bookedSeats[$n] = true;
            }
        }

        for ($start = $minSeat; $start <= ($maxSeat - $seatCount + 1); $start++) {
            $block = range($start, $start + $seatCount - 1);

            $conflict = false;
            foreach ($block as $n) {
                if (isset($bookedSeats[$n])) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                return [
                    'changed'   => true,
                    'old_seats' => $requestedSeats,
                    'new_seats' => $block,
                ];
            }
        }

        return $unchanged;
    }

    /**
     * حفظ المسافرين الجدد في جدول المرافقين تلقائياً
     */
    private function syncCompanions(int $userId, array $passengerNames, array $passengerIds, array $passengerGenders): void
    {
        $user = User::find($userId);
        $userNationalId = $user ? trim($user->national_id ?? $user->id_number ?? '') : '';

        foreach ($passengerNames as $index => $name) {
            $name = trim($name);
            $nationalId = trim($passengerIds[$index] ?? '');
            $gender = trim($passengerGenders[$index] ?? '');

            if (empty($name) || empty($nationalId) || ($userNationalId && $nationalId === $userNationalId)) {
                continue;
            }

            Companion::firstOrCreate(
                [
                    'user_id'     => $userId,
                    'national_id' => $nationalId,
                ],
                [
                    'name'   => $name,
                    'gender' => in_array($gender, ['male', 'female']) ? $gender : null,
                ]
            );
        }
    }

    /**
     * فك تشفير آمن للنصوص والـ JSON المزدوج
     */
    private function safeJsonParse($data): array
    {
        if (is_array($data)) return $data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * استخراج الوجبة أو الخدمة المخصصة للمسافر برقم المقعد أو الفهرس
     */
    private function extractPassengerData($dataSource, array $pSeats, int $pIndex): array
    {
        if (!is_array($dataSource) || empty($dataSource)) {
            return [];
        }

        foreach ($pSeats as $st) {
            $stInt = intval($st);
            $stStr = (string)$st;

            if (isset($dataSource[$stInt]) && !empty($dataSource[$stInt])) {
                return $dataSource[$stInt];
            }
            if (isset($dataSource[$stStr]) && !empty($dataSource[$stStr])) {
                return $dataSource[$stStr];
            }
        }

        if (isset($dataSource[$pIndex]) && !empty($dataSource[$pIndex])) {
            return $dataSource[$pIndex];
        }
        if (isset($dataSource[(string)$pIndex]) && !empty($dataSource[(string)$pIndex])) {
            return $dataSource[(string)$pIndex];
        }
        if (isset($dataSource["passenger_{$pIndex}"]) && !empty($dataSource["passenger_{$pIndex}"])) {
            return $dataSource["passenger_{$pIndex}"];
        }

        return [];
    }

    private function normalizeRequestData(Request $request): void
    {
        if ($request->has('seats') && !$request->has('selected_seats')) {
            $request->merge(['selected_seats' => $request->input('seats')]);
        } elseif ($request->has('selected_seats') && !$request->has('seats')) {
            $request->merge(['seats' => $request->input('selected_seats')]);
        }

        $jsonFields = ['room_bed_types', 'room_quantities', 'room_assignments', 'passenger_meals', 'passenger_services', 'room_options', 'meal_options', 'discount', 'selected_seats', 'passenger_seats'];
        foreach ($jsonFields as $field) {
            if (!$request->has($field) && $request->has($field . '_json')) {
                $request->merge([$field => $request->input($field . '_json')]);
            }
        }

        $request->merge([
            'selected_seats_arr'    => $this->parseIncomingData($request->selected_seats),
            'passenger_names_arr'   => $this->parseIncomingData($request->passenger_names),
            'passenger_ids_arr'     => $this->parseIncomingData($request->passenger_ids),
            'passenger_genders_arr' => $this->parseIncomingData($request->passenger_genders),
        ]);
    }

    private function validateBookingRequest(Request $request): void
    {
        $request->validate([
            'trip_id'                 => 'required|exists:trips,id',
            'room_type'               => 'nullable|string',
            'room_count'              => 'nullable|integer',
            'room_quantities'         => 'required',
            'selected_seats'          => 'required',
            'passenger_names'         => 'required',
            'selected_seats_arr'      => 'required|array|min:1',
            'passenger_names_arr'     => 'required|array|min:1',
            'passenger_names_arr.*'   => 'required|string|max:255',
            'passenger_ids_arr'       => 'nullable|array',
            'passenger_genders_arr'   => 'nullable|array',
            'payment_method'          => 'required|in:card,tabby,bank',
            'bank_receipt'            => 'nullable|required_if:payment_method,bank|image|mimes:jpeg,png,jpg|max:4096',

            'agency_note'    => 'nullable|string|max:500',
            'room_bed_types' => 'nullable',
        ], [
            'bank_receipt.required_if'       => 'يرجى إرفاق إيصال التحويل البنكي لتأكيد حجزك.',
            'passenger_names_arr.*.required' => 'جميع أسماء الركاب مطلوبة.',
            'selected_seats_arr.required'    => 'يرجى تحديد المقاعد المخصصة للحجز.',
        ]);
    }

    /**
     * $busGenders: أجناس ركاب الحافلة فقط بنفس ترتيب $seatsArray
     */
    private function processAutoSplitAndSeatShifting(Trip $trip, array $seatsArray, array $busGenders, array $passengerSeatsMap): void
    {
        if (!isset($trip->auto_split) || $trip->auto_split != 1 || $trip->is_security_approved) {
            return;
        }

        $currentBookedSeats = BookingSeat::where('trip_id', $trip->id)->get();
        $femaleCount = $currentBookedSeats->where('gender', 'female')->count();
        $maleCount   = $currentBookedSeats->where('gender', '!=', 'female')->count();

        foreach ($busGenders as $newGender) {
            if ($newGender === 'female') $femaleCount++;
            elseif ($newGender === 'male') $maleCount++;
        }

        $totalBusSeats   = intval($trip->total_seats ?: 49);
        $currentBoundary = intval($trip->split_boundary ?: 28);
        $oldBoundary     = $currentBoundary;
        $minSeats        = 5;
        $targetBoundary  = $currentBoundary;

        if ($maleCount > $currentBoundary) {
            $targetBoundary = min($totalBusSeats - $minSeats, $maleCount);
        } elseif (($totalBusSeats - $femaleCount) < $currentBoundary) {
            $targetBoundary = max($minSeats, $totalBusSeats - $femaleCount);
        }

        if ($targetBoundary != $oldBoundary) {
            $bookedSeatNumbers  = [];
            foreach ($currentBookedSeats as $bs) {
                foreach ($this->busSeatNumbersFrom($bs->seat_number) as $n) {
                    $bookedSeatNumbers[] = $n;
                }
            }
            $unavailableNumbers = array_merge($bookedSeatNumbers, $seatsArray);
            $canShift           = true;
            $shiftPlan          = [];

            $isExpandingFemaleZone = $targetBoundary < $oldBoundary;
            $affectedSeats = $isExpandingFemaleZone
                ? $currentBookedSeats->where('gender', '!=', 'female')
                : $currentBookedSeats->where('gender', 'female');

            $tempUnavailable = $unavailableNumbers;
            foreach ($affectedSeats as $seatModel) {
                if ($this->isAccommodationOnlySeat($seatModel->seat_number)) continue;

                $seatNums = $this->busSeatNumbersFrom($seatModel->seat_number);
                $newSeats = [];
                $needsMove = false;

                foreach ($seatNums as $sNum) {
                    $inShiftZone = $isExpandingFemaleZone
                        ? ($sNum > $targetBoundary && $sNum <= $oldBoundary)
                        : ($sNum > $oldBoundary && $sNum <= $targetBoundary);

                    if ($inShiftZone) {
                        $needsMove = true;
                        $newSeatNumber = null;
                        $startSearch = $isExpandingFemaleZone ? 1 : $targetBoundary + 1;
                        $endSearch   = $isExpandingFemaleZone ? $targetBoundary : $totalBusSeats;

                        for ($i = $startSearch; $i <= $endSearch; $i++) {
                            if (!in_array($i, $tempUnavailable)) {
                                $newSeatNumber = $i;
                                break;
                            }
                        }

                        if (!$newSeatNumber) {
                            $canShift = false;
                            break 2;
                        }
                        $newSeats[] = $newSeatNumber;
                        $tempUnavailable[] = $newSeatNumber;
                    } else {
                        $newSeats[] = $sNum;
                    }
                }

                if ($needsMove) {
                    $shiftPlan[] = [
                        'seatModel' => $seatModel,
                        'newSeats'  => $newSeats,
                        'reason'    => 'إزاحة تلقائية لتعديل النطاق الديناميكي للرجال والنساء.'
                    ];
                }
            }

            if ($canShift) {
                $currentBoundary = $targetBoundary;
                $trip->split_boundary = $targetBoundary;

                foreach ($shiftPlan as $plan) {
                    TripSeatLog::create([
                        'trip_id'         => $trip->id,
                        'booking_seat_id' => $plan['seatModel']->id,
                        'passenger_name'  => $plan['seatModel']->passenger_name,
                        'old_seat'        => json_encode($plan['seatModel']->seat_number),
                        'new_seat'        => json_encode($plan['newSeats']),
                        'reason'          => $plan['reason']
                    ]);

                    $plan['seatModel']->update(['seat_number' => $plan['newSeats']]);
                }
            } else {
                $currentBoundary = $oldBoundary;
            }
        }

        // مطابقة كل مقعد مع جنس صاحبه
        foreach ($seatsArray as $index => $seatNumber) {
            $seatPassengerGender = $busGenders[$index] ?? null;

            if (empty($seatPassengerGender)) continue;

            $isMaleZone = ($seatNumber <= $currentBoundary);

            if ($seatPassengerGender === 'female' && $isMaleZone) {
                throw new \DomainException("عذراً، المقعد رقم ($seatNumber) يقع في قسم الرجال (من 1 إلى {$currentBoundary}). يرجى اختيار مقعد في قسم النساء.");
            }

            if ($seatPassengerGender === 'male' && !$isMaleZone) {
                throw new \DomainException("عذراً، المقعد رقم ($seatNumber) يقع في قسم النساء (بعد المقعد {$currentBoundary}). يرجى اختيار مقعد في قسم الرجال.");
            }
        }

        $trip->update(['split_boundary' => $currentBoundary]);
    }

    private function verifyPassengersAndSeatsAvailability(
        Trip $trip,
        array $seatsArray,
        array $passengerIdsArray,
        ?int $bookingId = null
    ): void {
        // الهويات الوهمية (99xxxxxxxx) و 0000000000 لا تُفحص: الهوية الحقيقية تبدأ بـ 1 أو 2
        $ids = array_values(array_unique(array_filter(
            $passengerIdsArray,
            fn($id) => !empty($id)
                && (string) $id !== '0000000000'
                && !str_starts_with((string) $id, '99')
        )));

        // 1) فحص "مسافر مسجل مسبقاً"
        if (!empty($ids)) {
            $conflicts = Booking::where('trip_id', $trip->id)
                ->whereNotIn('status', ['cancelled'])
                ->when($bookingId, fn($q) => $q->where('id', '!=', $bookingId))
                ->whereHas('bookingSeats', fn($q) => $q->whereIn('passenger_national_id', $ids))
                ->get();

            // محاولات سابقة غير مكتملة لنفس المستخدم (غير مدفوعة): نلغيها ونحرر مقاعدها
            $ownStale = $conflicts->filter(
                fn($b) => (int) $b->user_id === (int) auth()->id()
                    && in_array($b->payment_status, ['unpaid', 'failed'], true)
                    && !in_array($b->status, ['cancelled', 'completed', 'requestC', 'approved', 'confirmed'], true)
            );

            foreach ($ownStale as $stale) {
                app(\App\Http\Controllers\Client\PaymentController::class)
                    ->cancelAndReleaseSeats($stale, 'استبدال بمحاولة حجز جديدة');
            }

            // أي تعارض متبقٍ هو حجز حقيقي (مدفوع / قيد التحويل / لمستخدم آخر)
            $blocking = $conflicts->reject(fn($b) => $ownStale->contains('id', $b->id));

            if ($blocking->isNotEmpty()) {
                throw new \DomainException('عذراً، أحد المسافرين مسجل بالفعل في هذه الرحلة.');
            }
        }

        // 2) لا مقاعد مختارة: لا شيء آخر يُفحص
        if (empty($seatsArray)) {
            return;
        }

        // 3) كفاية المقاعد
        if (count($seatsArray) > (int) $trip->available_seats) {
            throw new \DomainException('عذراً، لا توجد مقاعد كافية متاحة في هذه الرحلة حالياً.');
        }

        // 4) المقاعد المحجوزة مسبقاً (باستثناء مقاعد هذا الحجز نفسه عند إعادة المحاولة)
        $seatsArray = array_map('strval', $seatsArray);

        $alreadyBooked = BookingSeat::where('trip_id', $trip->id)
            ->when($bookingId, fn($q) => $q->where('booking_id', '!=', $bookingId))
            ->whereIn('seat_number', $seatsArray)
            ->exists();

        if ($alreadyBooked) {
            throw new \DomainException('عذراً، أحد المقاعد المختارة تم حجزه للتو من قبل مستخدم آخر.');
        }
    }

    private function extractCleanSeats($seatsInput): array
    {
        $rawSeats = $this->parseIncomingData($seatsInput);
        return array_values(array_filter(array_map(function ($s) {
            return intval(filter_var($s, FILTER_SANITIZE_NUMBER_INT));
        }, $rawSeats)));
    }

    private function handleReceiptUpload(Request $request): ?string
    {
        if ($request->hasFile('bank_receipt') && $request->payment_method === 'bank') {
            $file = $request->file('bank_receipt');
            $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            return 'storage/' . $file->storeAs('receipts', $filename, 'public');
        }
        return null;
    }

    private function deductAvailableRooms(Trip $trip, array $roomQuantities): void
    {
        $roomOptions    = $this->safeJsonParse($trip->room_options);
        $roomTypeLabels = ['single' => 'المفردة', 'double' => 'الثنائية', 'triple' => 'الثلاثية', 'quad' => 'الرباعية'];

        foreach ($roomQuantities as $type => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $availableCount = intval($roomOptions[$type]['count'] ?? 0);
                if ($availableCount < $qty) {
                    $label = $roomTypeLabels[$type] ?? $type;
                    throw new \DomainException("عذراً، الغرف المتاحة من النوع {$label} غير كافية لطلبك.");
                }
                $roomOptions[$type]['count'] = $availableCount - $qty;
            }
        }

        $trip->update(['room_options' => $roomOptions]);
    }

    private function createBookingRecord(Request $request, Trip $trip, float $totalAmount, float $discount, ?string $receiptPath, array $seatsArray): Booking
    {
        return Booking::create(array_merge([
            'user_id'            => Auth::id(),
            'trip_id'            => $trip->id,
            'customer_name'      => Auth::user()->name ?? 'عميل عبور',
            'customer_email'     => Auth::user()->email,
            'customer_phone'     => Auth::user()->phone,
            'total_amount'       => $totalAmount,
            'discount'           => $discount,
            'status'             => 'pending',
            'payment_status'     => ($request->payment_method === 'bank') ? 'pending' : 'unpaid',
            'payment_method'     => $request->payment_method,
            'expires_at'         => now()->addMinutes(15),
            'card_name'          => ($request->payment_method === 'card') ? $request->input('card_name') : null,
            'bank_receipt'       => $receiptPath,
            'room_type'          => $request->input('room_type') ?? 'shared',
            'room_count'         => intval($request->input('room_count') ?? 0),
            'selected_seats'     => array_values($seatsArray),
            'room_quantities'    => $this->safeJsonParse($request->input('room_quantities')),
            'room_assignments'   => $this->safeJsonParse($request->input('room_assignments')),
            'passenger_meals'    => $this->safeJsonParse($request->input('passenger_meals')),
            'passenger_services' => $this->safeJsonParse($request->input('passenger_services')),
        ], $this->bookingExtras($request)));
    }

    private function buildSuccessResponse(Request $request, Booking $booking)
    {
        if ($booking->payment_method === 'card') {
            $paymentUrl = route('client.payment.pay', ['booking' => $booking->id]);
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تسجيل الحجز مبدئياً، جاري تحويلك لصفحة الدفع...', 'booking_id' => $booking->id, 'redirect_url' => $paymentUrl], 201, [], JSON_UNESCAPED_UNICODE);
            }
            return redirect()->to($paymentUrl);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم استلام طلب حجزك الجديد بنجاح!', 'booking_id' => $booking->id, 'booking' => $booking->load('bookingSeats')], 201, [], JSON_UNESCAPED_UNICODE);
        }

        return redirect()->route('client.profile')->with(['success' => 'تم استلام طلب حجزك الجديد بنجاح! رقم الحجز: #TRP-' . $booking->id, 'booking_id' => $booking->id]);
    }

    private function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => false, 'error' => $message], $status, [], JSON_UNESCAPED_UNICODE);
        }

        $tripId = $request->input('trip_id');
        $target = $tripId
            ? route('client.select_seat', $tripId)
            : route('client.trips');

        return redirect($target)->with('error', $message);
    }

    private function unauthorizedResponse(Request $request)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'يرجى تسجيل الدخول مجدداً لإتمام الحجز.'], 401, [], JSON_UNESCAPED_UNICODE);
        }
        return redirect()->route('client.auth.login')->with('error', 'انتهت الجلسة، يرجى تسجيل الدخول مجدداً.');
    }
    //نهاية دالة الحفظ

    public function checkout(Request $request)
    {
        if (
            $request->isMethod('get')
            && !$request->has('selected_seats')
            && !$request->has('seats')
            && !old('selected_seats')
        ) {
            return redirect()->route('client.trips')
                ->with('error', 'انتهت جلسة الحجز، يرجى البدء من جديد.');
        }

        $trip_id    = $request->input('trip_id') ?? $request->query('trip_id') ?? old('trip_id');
        $booking_id = $request->input('booking_id') ?? $request->query('booking_id') ?? old('booking_id');

        if (!$trip_id) {
            return $this->errorResponse($request, 'عذراً، لم يتم تحديد الرحلة.', 400);
        }

        $trip = Trip::with('agency')->find($trip_id);
        if (!$trip || $trip->is_overflow_standby) {
            return $this->errorResponse($request, 'الرحلة المطلوبة غير موجودة حالياً.', 404);
        }

        $agency_whatsapp = $trip->agency->phone ?? '9665xxxxxxxx';

        $room_options = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options ?? '[]', true) ?? []);
        $meal_options = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options ?? '[]', true) ?? []);

        $selected_seats    = $this->parseIncomingData($request->input('selected_seats') ?? $request->input('seats') ?? $request->query('seats') ?? old('selected_seats') ?? old('seats'));
        $passenger_names   = $this->parseIncomingData($request->input('passenger_names') ?? old('passenger_names'));
        $passenger_ids     = $this->parseIncomingData($request->input('passenger_ids') ?? $request->query('passenger_ids') ?? old('passenger_ids'));
        $passenger_genders = $this->parseIncomingData($request->input('passenger_genders') ?? $request->query('passenger_genders') ?? old('passenger_genders'));

        // فحص "مسافر مسجل مسبقاً"
        $ids = array_values(array_filter(
            $passenger_ids,
            fn($id) => !empty($id)
                && $id !== '0000000000'
                && !str_starts_with((string) $id, '99000000')
        ));

        if (!empty($ids)) {
            $already = BookingSeat::where('trip_id', $trip->id)
                ->whereIn('passenger_national_id', $ids)
                ->whereHas('booking', function ($q) use ($booking_id) {
                    $q->whereNotIn('status', ['cancelled'])
                        ->when($booking_id, fn($qq) => $qq->where('id', '!=', $booking_id));
                })
                ->exists();

            if ($already) {
                $msg = 'عذراً، أحد المسافرين مسجل بالفعل في هذه الرحلة.';

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'error' => $msg], 422, [], JSON_UNESCAPED_UNICODE);
                }

                return redirect()->route('client.select_seat', $trip->id)->with('error', $msg);
            }
        }

        $room_quantities_raw    = $request->input('room_quantities') ?? old('room_quantities');
        $room_assignments_raw   = $request->input('room_assignments') ?? old('room_assignments');
        $passenger_meals_raw    = $request->input('passenger_meals') ?? old('passenger_meals');
        $passenger_services_raw = $request->input('passenger_services') ?? old('passenger_services');
        $passenger_seats_raw    = $request->input('passenger_seats') ?? old('passenger_seats');

        $room_quantities    = is_array($room_quantities_raw) ? $room_quantities_raw : (json_decode($room_quantities_raw ?? '{}', true) ?? []);
        $roomAssignments    = is_array($room_assignments_raw) ? $room_assignments_raw : (json_decode($room_assignments_raw ?? '{}', true) ?? []);
        $passenger_meals    = is_array($passenger_meals_raw) ? $passenger_meals_raw : (json_decode($passenger_meals_raw ?? '{}', true) ?? []);
        $passenger_services = is_array($passenger_services_raw) ? $passenger_services_raw : (json_decode($passenger_services_raw ?? '{}', true) ?? []);
        $passenger_seats    = is_array($passenger_seats_raw) ? $passenger_seats_raw : (json_decode($passenger_seats_raw ?? '{}', true) ?? []);

        // تنقية توزيع الغرف: تخصيص الغرفة للمقعد الرئيسي فقط لكل راكب
        $cleanRoomAssignments = [];
        if (!empty($passenger_seats)) {
            foreach ($passenger_seats as $uid => $seatsList) {
                if (is_array($seatsList) && count($seatsList) > 0) {
                    $primarySeat = (string) $seatsList[0];
                    $room = $roomAssignments[$primarySeat] ?? $roomAssignments[$uid] ?? null;
                    if ($room) {
                        $cleanRoomAssignments[$primarySeat] = $room;
                    }
                }
            }
        } else {
            $cleanRoomAssignments = $roomAssignments;
        }

        try {
            $calculation = BookingCalculator::calculate($trip, $room_quantities, $selected_seats, $cleanRoomAssignments, $passenger_meals, $passenger_services);

            if (!isset($calculation['total_price']) || !is_numeric($calculation['total_price'])) {
                throw new \Exception('فشل في احتساب إجمالي الفاتورة بشكل صحيح.');
            }
        } catch (\Exception $e) {
            Log::error('Checkout Calculation Error: ' . $e->getMessage());
            return $this->errorResponse($request, 'حدث خطأ غير متوقع أثناء حساب الفاتورة. يرجى إعادة المحاولة.', 500);
        }

        $subtotal    = floatval($calculation['total_price']);
        $discount    = $this->resolveDiscount($request, $subtotal);
        $final_price = max(0, $subtotal - $discount);

        $room_bed_types = $this->sanitizeBedTypes(
            $this->safeJsonParse($request->input('room_bed_types') ?? old('room_bed_types')),
            $room_quantities
        );
        $agency_note = mb_substr(trim(strip_tags((string) ($request->input('agency_note') ?? old('agency_note')))), 0, 500);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'trip'               => $trip,
                'booking_id'         => $booking_id,
                'selected_seats'     => $selected_seats,
                'passenger_names'    => $passenger_names,
                'room_assignments'   => $cleanRoomAssignments,
                'passenger_meals'    => $passenger_meals,
                'passenger_services' => $passenger_services,
                'room_options'       => $room_options,
                'meal_options'       => $meal_options,
                'room_bed_types'      => $room_bed_types,
                'room_bed_types_json' => json_encode($room_bed_types, JSON_UNESCAPED_UNICODE),
                'agency_note'         => $agency_note,
                'invoice_breakdown'  => [
                    'total_room_cost'     => $calculation['total_room_cost'] ?? 0,
                    'total_seats_cost'    => $calculation['total_seats_cost'] ?? 0,
                    'total_meals_cost'    => $calculation['total_meals_cost'] ?? 0,
                    'total_services_cost' => $calculation['total_services_cost'] ?? 0,
                    'subtotal'            => $subtotal,
                    'discount_amount'     => $discount,
                    'total_price'         => $final_price,
                ],
                'agency_whatsapp'    => $agency_whatsapp
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $room_quantities_json = json_encode($room_quantities, JSON_UNESCAPED_UNICODE);
        $total_seats_cost     = $calculation['total_seats_cost'] ?? 0;



        return view('client.checkout', [
            'trip'                    => $trip,
            'booking_id'              => $booking_id,

            'selected_seats'          => $selected_seats,
            'passenger_names'         => $passenger_names,
            'passenger_ids'           => $passenger_ids,
            'passenger_genders'       => $passenger_genders,
            'room_quantities_json'    => $room_quantities_json,
            'room_assignments_json'   => json_encode($cleanRoomAssignments, JSON_UNESCAPED_UNICODE),
            'passenger_meals_json'    => json_encode($passenger_meals, JSON_UNESCAPED_UNICODE),
            'passenger_services_json' => json_encode($passenger_services, JSON_UNESCAPED_UNICODE),
            'passenger_seats_json'    => json_encode($passenger_seats, JSON_UNESCAPED_UNICODE),
            'room_options_json'       => json_encode($room_options, JSON_UNESCAPED_UNICODE),
            'meal_options_json'       => json_encode($meal_options, JSON_UNESCAPED_UNICODE),
            'passenger_meals'         => $passenger_meals,
            'passenger_services'      => $passenger_services,
            'passenger_seats'         => $passenger_seats,
            'room_assignments'        => $cleanRoomAssignments,
            'room_quantities'         => $room_quantities,
            'room_options'            => $room_options,
            'meal_options'            => $meal_options,
            'mealOptionsArray'        => $meal_options,
            'roomOptionsArray'        => $room_options,
            'room_prices'             => $calculation['room_prices'] ?? [],
            'price_all_meals'         => $calculation['price_all_meals'] ?? 0,
            'price_bf_ln'             => $calculation['price_bf_ln'] ?? 0,
            'price_ln_dn'             => $calculation['price_ln_dn'] ?? 0,
            'price_bf_dn'             => $calculation['price_bf_dn'] ?? 0,
            'total_room_cost'         => $calculation['total_room_cost'] ?? 0,
            'total_seats_cost'        => $total_seats_cost,
            'total_meals_cost'        => $calculation['total_meals_cost'] ?? 0,
            'total_services_cost'     => $calculation['total_services_cost'] ?? 0,
            'subtotal'                => $subtotal,
            'discount'                => $discount,
            'total_price'             => $final_price,
            'agency_whatsapp'         => $agency_whatsapp,

            'room_bed_types'      => $room_bed_types,
            'room_bed_types_json' => json_encode($room_bed_types, JSON_UNESCAPED_UNICODE),
            'agency_note'         => $agency_note,
        ]);
    }

    // عرض التذاكر (Success)
    public function success(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())
            ->with(['trip', 'bookingSeats'])
            ->findOrFail($id);

        $roomTypesAr = [
            'single' => 'فردية',
            'double' => 'ثنائية',
            'triple' => 'ثلاثية',
            'quad'   => 'رباعية'
        ];

        $roomOptionsJson = $booking->room_options ?? ($booking->trip->room_options ?? null);
        $mealOptionsJson = $booking->meal_options ?? ($booking->trip->meal_options ?? null);
        $passengerServicesJson = $booking->passenger_services ?? null;

        $roomOptions = is_string($roomOptionsJson) ? json_decode($roomOptionsJson, true) : $roomOptionsJson;
        $mealOptions = is_string($mealOptionsJson) ? json_decode($mealOptionsJson, true) : $mealOptionsJson;
        $allPassengerServices = is_string($passengerServicesJson) ? json_decode($passengerServicesJson, true) : (is_array($passengerServicesJson) ? $passengerServicesJson : []);

        $allSeats = $booking->bookingSeats;
        $primarySeats = $allSeats->reject(function ($seat) {
            return !empty($seat->parent_seat_id) || $seat->room_type === 'additional';
        });

        if ($primarySeats->isEmpty()) {
            $primarySeats = $allSeats;
        }

        $extractSeatNumbers = function ($seat) {
            $rawSeats = $seat->seat_number;
            $seatsArr = is_string($rawSeats) ? json_decode($rawSeats, true) : $rawSeats;
            if (!is_array($seatsArr)) {
                $seatsArr = $rawSeats ? [$rawSeats] : [];
            }
            return $seatsArr;
        };

        foreach ($primarySeats as $seatRelation) {
            $seatRelation->decrypted_national_id = $seatRelation->passenger_national_id ?? '---';

            $childSeats = $allSeats->filter(function ($s) use ($seatRelation) {
                return $s->parent_seat_id == $seatRelation->id;
            });

            $combinedSeatNumbers = $extractSeatNumbers($seatRelation);
            foreach ($childSeats as $child) {
                $combinedSeatNumbers = array_merge($combinedSeatNumbers, $extractSeatNumbers($child));
            }
            $combinedSeatNumbers = array_unique(array_filter($combinedSeatNumbers));
            $seatRelation->display_seats = !empty($combinedSeatNumbers) ? implode(' & ', $combinedSeatNumbers) : '---';

            $roomType = $seatRelation->room_type;
            $typeKey = '';

            if ($roomType === 'shared' || empty($roomType)) {
                $seatRelation->room_name_ar = 'عموم (مشترك)';
            } elseif ($roomType === 'additional') {
                $seatRelation->room_name_ar = 'مقعد إضافي ➕';
            } else {
                $parts = explode('_', $roomType);
                $typeKey = $parts[0] ?? '';
                $roomNumber = $parts[1] ?? '';

                $arabicType = $roomTypesAr[$typeKey] ?? $typeKey;
                $seatRelation->room_name_ar = "غرفة " . $arabicType . ($roomNumber ? " #$roomNumber" : "");
            }

            if ($childSeats->isNotEmpty() || $seatRelation->is_extra) {
                $seatRelation->room_name_ar .= ' (شامل مقعد إضافي ➕)';
            }

            $seatRoomOpts = is_string($seatRelation->room_options) ? json_decode($seatRelation->room_options, true) : $seatRelation->room_options;
            $matchedPackageName = $seatRoomOpts['package_name'] ?? null;

            $hasBf = (bool)($seatRelation->with_breakfast ?? false);
            $hasLn = (bool)($seatRelation->with_lunch ?? false);
            $hasDn = (bool)($seatRelation->with_dinner ?? false);

            if (!$matchedPackageName && !empty($typeKey) && !empty($roomOptions[$typeKey]['packages'])) {
                foreach ($roomOptions[$typeKey]['packages'] as $pkg) {
                    $pkgBf = !empty($pkg['meals']['breakfast']);
                    $pkgLn = !empty($pkg['meals']['lunch']);
                    $pkgDn = !empty($pkg['meals']['dinner']);

                    if ($hasBf === $pkgBf && $hasLn === $pkgLn && $hasDn === $pkgDn) {
                        $matchedPackageName = $pkg['name'] ?? null;
                        break;
                    }
                }
            }

            if (!$matchedPackageName && !empty($mealOptions['packages'])) {
                $comboKey = null;
                if ($hasBf && $hasLn && $hasDn) $comboKey = 'all';
                elseif ($hasBf && !$hasLn && $hasDn) $comboKey = 'bf_dn';
                elseif ($hasBf && $hasLn && !$hasDn) $comboKey = 'bf_ln';
                elseif (!$hasBf && $hasLn && $hasDn) $comboKey = 'ln_dn';

                if ($comboKey && !empty($mealOptions['packages'][$comboKey])) {
                    $mealPackageNames = [
                        'all'   => 'الوجبات الكاملة',
                        'bf_dn' => 'إفطار + عشاء',
                        'bf_ln' => 'إفطار + غداء',
                        'ln_dn' => 'غداء + عشاء',
                    ];
                    $matchedPackageName = $mealPackageNames[$comboKey] ?? 'باقة وجبات مخصصة';
                }
            }

            $seatRelation->package_name = $matchedPackageName;

            $clientMeals = [];
            if ($hasBf) $clientMeals[] = 'إفطار';
            if ($hasLn) $clientMeals[] = 'غداء';
            if ($hasDn) $clientMeals[] = 'عشاء';

            $seatRelation->meals_summary_ar = !empty($clientMeals) ? implode(' + ', $clientMeals) : 'لا تشمل وجبات ❌';

            $primarySeatKey = reset($combinedSeatNumbers) ?: null;
            $rawSeatServices = $seatRelation->services
                ?? $seatRelation->extra_services
                ?? ($primarySeatKey && isset($allPassengerServices[$primarySeatKey]) ? $allPassengerServices[$primarySeatKey] : []);

            if (is_string($rawSeatServices)) {
                $rawSeatServices = json_decode($rawSeatServices, true) ?: [];
            }

            foreach ($childSeats as $child) {
                $childServices = $child->services ?? $child->extra_services ?? [];
                if (is_string($childServices)) {
                    $childServices = json_decode($childServices, true) ?: [];
                }
                if (is_array($childServices)) {
                    $rawSeatServices = array_merge((array)$rawSeatServices, $childServices);
                }
            }

            $uniqueServices = [];
            if (!empty($rawSeatServices) && is_array($rawSeatServices)) {
                foreach ($rawSeatServices as $srv) {
                    $serviceName = is_array($srv)
                        ? ($srv['name'] ?? $srv['title'] ?? 'خدمة إضافية')
                        : (string)$srv;

                    if (!empty($serviceName) && !isset($uniqueServices[$serviceName])) {
                        $uniqueServices[$serviceName] = [
                            'name'        => $serviceName,
                            'price'       => is_array($srv) ? ($srv['price'] ?? $srv['cost'] ?? null) : null,
                            'description' => is_array($srv) ? ($srv['description'] ?? $srv['desc'] ?? null) : null,
                        ];
                    }
                }
            }

            $seatRelation->formatted_services = array_values($uniqueServices);
        }

        $booking->setRelation('bookingSeats', $primarySeats);

        $isFlight = isset($booking->trip->transport_type) && $booking->trip->transport_type === 'flight';

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'is_flight' => $isFlight,
                'booking'   => $booking
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return view('client.success', compact('booking', 'isFlight'));
    }

    // إلغاء الحجز
    public function cancel(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if (in_array($booking->status, ['cancelled', 'requestC'])) {
            $message = $booking->status === 'requestC'
                ? 'لقد قمت بتقديم طلب إلغاء لهذا الحجز مسبقاً وهو قيد المراجعة.'
                : 'هذا الحجز ملغى بالفعل مسبقاً.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400, [], JSON_UNESCAPED_UNICODE);
            }
            return redirect()->route('client.profile')->with('error', $message);
        }

        try {
            $booking->status = 'requestC';
            $booking->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تقديم طلب إلغاء الحجز بنجاح وهو قيد المراجعة الآن.'
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            return redirect()->route('client.profile')->with('success', 'تم تقديم طلب إلغاء الحجز بنجاح وهو قيد المراجعة الآن. ✅');
        } catch (\Exception $e) {
            Log::error('Booking cancel request failed #' . $booking->id . ': ' . $e->getMessage());

            $msg = 'حدث خطأ أثناء تقديم طلب الإلغاء، يرجى المحاولة لاحقاً.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500, [], JSON_UNESCAPED_UNICODE);
            }

            return redirect()->route('client.profile')->with('error', $msg);
        }
    }

    // تسجيل الدخول (صفحة الويب)
    public function login(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'يرجى استخدام بروتوكول POST لتسجيل الدخول.'], 405);
        }
        return view('client.auth.login');
    }


    // معالجة تسجيل الدخول
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($request->expectsJson()) {
                $token = method_exists($user, 'createToken') ? $user->createToken('AburMobileToken')->plainTextToken : null;
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح ✨',
                    'user'    => $user,
                    'token'   => $token
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $request->session()->regenerate();
            return redirect()->route('client.index');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة.'
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('email');
    }

    // صفحة إنشاء مستخدم جديد (ويب فقط)
    public function create(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'يرجى استخدام بروتوكول POST لإنشاء الحساب الجديد.'], 405);
        }
        return view('client.auth.register');
    }

    // معالجة إنشاء مستخدم جديد


    public function register(Request $request)
    {
        // ---------- 1) تنظيف المدخلات قبل التحقق ----------
        $request->merge([
            'name'        => trim((string) $request->input('name')),
            'email'       => strtolower(trim((string) $request->input('email'))),
            'national_id' => $this->normalizeDigits($request->input('national_id')),
            'phone'       => $this->normalizeDigits($request->input('phone')),
        ]);

        $isHijri = $request->input('birth_type') === 'hijri';

        // ---------- 2) قواعد التحقق ----------
        $rules = [
            'name'         => ['required', 'string', 'min:3', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'avatar'       => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'id_image'     => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:4096'], // غيّرها إلى required لو تبغاها إجبارية
            'national_id'  => [
                'bail',
                'required',
                'regex:/^[12]\d{9}$/',
                'unique:users,national_id',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidSaudiId($value)) {
                        $fail('رقم الهوية / الإقامة غير صحيح، يرجى التأكد من الأرقام.');
                    }
                },
            ],
            'country_code' => ['nullable', 'in:+966,+971'],
            'phone'        => ['required', 'regex:/^\d{9,10}$/'],
            'gender'       => ['required', 'in:male,female'],
            'city'         => ['required', 'string', 'max:255'],
            'password'     => ['required', 'confirmed', 'digits:8'],
            'birth_type'   => ['required', 'in:gregorian,hijri'],
        ];

        if ($isHijri) {
            $rules['hijri_day']   = ['required', 'integer', 'between:1,30'];
            $rules['hijri_month'] = ['required', 'integer', 'between:1,12'];
            $rules['hijri_year']  = ['required', 'integer', 'between:1350,1450'];
        } else {
            $rules['birth_date'] = [
                'bail',
                'required',
                'date_format:Y-m-d',
                'before:today',
                'after:1900-01-01',
                function ($attribute, $value, $fail) {
                    [$y, $m, $d] = array_map('intval', explode('-', $value));
                    if (!checkdate($m, $d, $y)) {
                        $fail('تاريخ الميلاد غير صحيح.');
                    }
                },
            ];
        }

        $messages = [
            'name.required'          => 'الاسم الكامل مطلوب.',
            'name.min'               => 'الاسم الكامل قصير جداً.',
            'email.required'         => 'البريد الإلكتروني مطلوب.',
            'email.email'            => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'           => 'هذا البريد الإلكتروني مسجل مسبقاً.',
            'national_id.required'   => 'رقم الهوية / الإقامة مطلوب.',
            'national_id.regex'      => 'رقم الهوية / الإقامة يجب أن يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
            'national_id.unique'     => 'رقم الهوية / الإقامة مسجل مسبقاً.',
            'phone.required'         => 'رقم الجوال مطلوب.',
            'phone.regex'            => 'رقم الجوال غير صحيح.',
            'gender.required'        => 'يرجى اختيار الجنس.',
            'gender.in'              => 'قيمة الجنس غير صحيحة.',
            'city.required'          => 'يرجى اختيار المدينة الحالية.',
            'password.required'      => 'كلمة المرور مطلوبة.',
            'password.digits'        => 'كلمة المرور يجب أن تتكون من 8 أرقام بالضبط.',
            'password.confirmed'     => 'تأكيد كلمة المرور غير مطابق.',
            'birth_type.required'    => 'يرجى تحديد نوع تاريخ الميلاد.',
            'birth_date.required'    => 'يرجى إكمال تاريخ الميلاد (اليوم والشهر والسنة).',
            'birth_date.date_format' => 'تاريخ الميلاد غير صحيح.',
            'birth_date.before'      => 'تاريخ الميلاد يجب أن يكون في الماضي.',
            'birth_date.after'       => 'تاريخ الميلاد غير منطقي.',
            'hijri_day.required'     => 'يرجى إكمال تاريخ الميلاد الهجري.',
            'hijri_month.required'   => 'يرجى إكمال تاريخ الميلاد الهجري.',
            'hijri_year.required'    => 'يرجى إكمال تاريخ الميلاد الهجري.',
            'hijri_day.between'      => 'يوم الميلاد الهجري غير صحيح.',
            'hijri_month.between'    => 'شهر الميلاد الهجري غير صحيح.',
            'hijri_year.between'     => 'سنة الميلاد الهجري غير صحيحة.',
            'avatar.image'           => 'الصورة الشخصية يجب أن تكون صورة.',
            'avatar.mimes'           => 'صيغ الصورة الشخصية المدعومة: jpeg, png, jpg.',
            'avatar.max'             => 'حجم الصورة الشخصية يجب ألا يتجاوز 2 ميجابايت.',
            'avatar.uploaded'        => 'تعذر رفع الصورة الشخصية، جرّب صورة أصغر.',
            'id_image.image'         => 'ملف الهوية يجب أن يكون صورة.',
            'id_image.mimes'         => 'صيغ صورة الهوية المدعومة: jpeg, png, jpg.',
            'id_image.max'           => 'حجم صورة الهوية يجب ألا يتجاوز 4 ميجابايت.',
            'id_image.uploaded'      => 'تعذر رفع صورة الهوية، جرّب صورة أصغر.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // ---------- 3) عند الفشل: رجوع صريح لصفحة التسجيل (وليس back()) ----------
        if ($validator->fails()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // مسار صفحة التسجيل نفسه (GET) — نفس رابط الـ POST.
            // لو صفحة التسجيل (GET) لها رابط مختلف استبدله بـ route('اسم_مسار_صفحة_التسجيل')
            return redirect()->to($request->url())
                ->withErrors($validator)
                ->withInput($request->except(['password', 'password_confirmation', 'avatar', 'id_image']));
        }

        $validated = $validator->validated();

        // ---------- 4) معالجة تاريخ الميلاد (ميلادي <-> هجري) ----------
        $birthDate      = null;
        $birthDateHijri = null;

        if (!$isHijri) {
            $birthDate = $validated['birth_date'];

            try {
                $date      = new \DateTime($birthDate . ' 12:00:00', new \DateTimeZone('Asia/Riyadh'));
                $formatter = new \IntlDateFormatter(
                    'en_US@calendar=islamic-umalqura',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::FULL,
                    'Asia/Riyadh',
                    \IntlDateFormatter::TRADITIONAL,
                    'yyyy-MM-dd'
                );
                $birthDateHijri = $formatter->format($date);
            } catch (\Throwable $e) {
                Log::error('خطأ تحويل من ميلادي لهجري: ' . $e->getMessage());
            }
        } else {
            $day   = (int) $validated['hijri_day'];
            $month = (int) $validated['hijri_month'];
            $year  = (int) $validated['hijri_year'];

            $birthDateHijri = sprintf('%04d-%02d-%02d', $year, $month, $day);

            try {
                $cal = \IntlCalendar::createInstance('Asia/Riyadh', 'en_US@calendar=islamic-umalqura');
                $cal->clear();
                $cal->set($year, $month - 1, $day, 12, 0, 0);

                $birthDate = Carbon::createFromTimestamp((int) ($cal->getTime() / 1000), 'Asia/Riyadh')->format('Y-m-d');
            } catch (\Throwable $e) {
                Log::error('خطأ تحويل من هجري لميلادي: ' . $e->getMessage());
            }
        }

        // ---------- 5) الصورة الشخصية ----------
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $file       = $request->file('avatar');
            $filename   = time() . '_avatar.' . $file->getClientOriginalExtension();
            $path       = $file->storeAs('avatars', $filename, 'public');
            $avatarPath = 'storage/' . $path;
        }

        // ---------- 6) إنشاء المستخدم ----------
        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'avatar'           => $avatarPath,
            'national_id'      => $validated['national_id'],
            'phone'            => $validated['phone'],
            'birth_date'       => $birthDate,
            'birth_date_hijri' => $birthDateHijri,
            'city'             => $validated['city'],
            'password'         => Hash::make($validated['password']),
            'gender'           => $validated['gender'],
        ]);

        // ---------- 7) صورة الهوية (من نفس صفحة التسجيل) ----------
        if ($request->hasFile('id_image')) {
            try {
                $this->storeIdImage($user, $request->file('id_image'));
                $user->refresh();
            } catch (\Throwable $e) {
                // فشل رفع الهوية لا يلغي إنشاء الحساب، يمكنه رفعها لاحقاً عبر uploadId
                Log::error('فشل حفظ صورة الهوية عند التسجيل: ' . $e->getMessage());
            }
        }

        // ---------- 8) الاستجابة ----------
        if ($request->expectsJson() || $request->wantsJson()) {
            $token = method_exists($user, 'createToken') ? $user->createToken('AburMobileToken')->plainTextToken : null;

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح وتسجيل الدخول ✨',
                'user'    => $user,
                'token'   => $token,
            ], 201, [], JSON_UNESCAPED_UNICODE);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('client.index');
    }

    /**
     * حفظ صورة الهوية للمستخدم (تُستخدم في register، ويمكنك استخدامها داخل uploadId أيضاً).
     */
    private function storeIdImage(User $user, \Illuminate\Http\UploadedFile $file): string
    {
        if ($user->id_image) {
            $oldPath = str_replace('storage/', '', $user->id_image);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $filename = 'id_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('ids', $filename, 'public');

        $user->update([
            'id_image'    => 'storage/' . $path,
            'is_verified' => 3, // قيد المراجعة
        ]);

        return 'storage/' . $path;
    }

    /**
     * تحويل الأرقام العربية/الفارسية إلى لاتينية وإبقاء الأرقام فقط.
     */
    private function normalizeDigits($value): string
    {
        $value = strtr((string) $value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);

        return preg_replace('/\D/', '', $value);
    }

    /**
     * التحقق من رقم الهوية الوطنية / الإقامة (10 أرقام، خوارزمية لون المعدلة).
     */
    private function isValidSaudiId(string $id): bool
    {
        if (!preg_match('/^[12]\d{9}$/', $id)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = (int) $id[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    // صفحة من نحن
    public function we(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success'     => true,
                'agency_name' => 'عبور | Abur',
                'description' => 'بوابة عبور لإدارة وكالات السفر والرحلات وحجوزات النقل الاستراتيجية.'
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        return view('abur.we');
    }

    //السياسات
    public function policies()
    {
        return view('abur.policies');
    }

    // معالجة تسجيل الخروج
    public function logout(Request $request)
    {
        if ($request->expectsJson()) {
            if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
                $request->user()->currentAccessToken()->delete();
            }
            return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح.'], 200, [], JSON_UNESCAPED_UNICODE);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.index');
    }

    // نسيت كلمة المرور - إرسال رابط الاستعادة بعد التحقق من رقم الهوية
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'national_id' => 'required|numeric',
        ], [
            'email.required'       => 'يرجى إدخال البريد الإلكتروني.',
            'email.email'          => 'صيغة البريد الإلكتروني غير صحيحة.',
            'national_id.required' => 'يرجى إدخال رقم الهوية.',
        ]);

        $user = User::where('email', $request->email)
            ->where('national_id', $request->national_id)
            ->first();

        // 🔒 رد موحد سواء تطابقت البيانات أم لا (يمنع تخمين الهويات/الإيميلات)
        if ($user) {
            Password::broker('users')->sendResetLink(['email' => $user->email]);
        }

        $message = 'إذا كانت البيانات مطابقة لحساب مسجل لدينا، فسيصلك رابط إعادة تعيين كلمة المرور على بريدك الإلكتروني.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message], 200, [], JSON_UNESCAPED_UNICODE);
        }
        return redirect()->back()->with('status', $message);
    }

    // معالجة اعادة كلمة المرور
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        $tokenExpired = $resetData && Carbon::parse($resetData->created_at)->addMinutes(60)->isPast();

        if (!$resetData || !Hash::check($request->token, $resetData->token) || $tokenExpired) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'رابط استعادة كلمة المرور غير صالح أو انتهت صلاحيته.'], 422, [], JSON_UNESCAPED_UNICODE);
            }
            return redirect()->back()->withErrors(['email' => 'رابط استعادة كلمة المرور غير صالح أو انتهت صلاحيته.']);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
        } else {
            $agency = Agency::where('email', $request->email)->first();
            if ($agency) {
                $agency->update(['password' => Hash::make($request->password)]);
            } else {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'الحساب المرتبط بهذا البريد الإلكتروني غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
                }
                return redirect()->back()->withErrors(['email' => 'الحساب المرتبط بهذا البريد الإلكتروني غير موجود.']);
            }
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تم إعادة تعيين كلمة المرور بنجاح! يمكنك تسجيل الدخول الآن.'], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return redirect()->route('client.auth.login')->with('status', 'تم إعادة تعيين كلمة المرور بنجاح! يمكنك تسجيل الدخول الآن بكلمة المرور الجديدة. ✨');
    }

    public function storeRating(Request $request)
    {
        $request->validate([
            'booking_id'           => 'required|exists:bookings,id',
            'accommodation_rating' => 'required|integer|between:1,5',
            'meals_rating'         => 'required|integer|between:1,5',
            'staff_rating'         => 'required|integer|between:1,5',
            'organization_rating'  => 'required|integer|between:1,5',
            'price_value_rating'   => 'required|integer|between:1,5',
            'transport_rating'     => 'required|integer|between:1,5',
            'services_rating'      => 'required|integer|between:1,5',
            'comment'              => 'nullable|string|max:1000',
        ]);

        $booking = Booking::with('trip.agency')->findOrFail($request->booking_id);

        if ($booking->user_id !== auth()->id()) {
            return redirect()->back()->with('error', '⚠️ عذراً، لا تملك الصلاحية لتقييم هذا الحجز.');
        }

        $alreadyRated = AgencyRating::where('user_id', auth()->id())
            ->where('trip_id', $booking->trip_id)
            ->exists();
        if ($alreadyRated) {
            return redirect()->back()->with('error', '⚠️ لقد قمت بتقييم هذه الرحلة مسبقاً، شكراً لك!');
        }

        AgencyRating::create([
            'agency_id'            => $booking->trip->agency_id,
            'user_id'              => auth()->id(),
            'trip_id'              => $booking->trip_id,
            'accommodation_rating' => $request->accommodation_rating,
            'meals_rating'         => $request->meals_rating,
            'staff_rating'         => $request->staff_rating,
            'organization_rating'  => $request->organization_rating,
            'price_value_rating'   => $request->price_value_rating,
            'transport_rating'     => $request->transport_rating,
            'services_rating'      => $request->services_rating,
            'comment'              => $request->comment,
        ]);

        return redirect()->back()->with('success', '✨ شكراً لك! تم تسجيل تقييمك الشامل للوكالة بنجاح وبانتظار مساهمتك في تحسين خدماتنا.');
    }

    public function connectUs()
    {
        return view('abur.connectUs');
    }
}