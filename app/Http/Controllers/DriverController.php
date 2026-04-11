<?php

namespace App\Http\Controllers;


class DriverController extends Controller
{
    public function login()
    {
        return view('driver.auth.login');
    }
    public function dashboardDriver(){
        return view('driver.dashboard');
    }
}
