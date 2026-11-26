<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingCotnroller extends Controller
{
    public function index(){
        return view('agency.bookings.addBookings');
    }
}
