<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {

        return view('master.dashboard');
    }
}
