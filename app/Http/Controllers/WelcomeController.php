<?php

namespace App\Http\Controllers;

class WelcomeController extends Controller
{
    /**
     * Show the welcome page (SPA entry point).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('welcome');
    }
}
