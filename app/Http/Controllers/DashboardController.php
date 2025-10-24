<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $parentLink = __('Dashboard');
        $link = __('My Dashboard');

        return view('pages.dashboard', compact('parentLink', 'parentLink', 'link'));
    }
}
