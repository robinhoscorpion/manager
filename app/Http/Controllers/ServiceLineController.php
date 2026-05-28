<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceLineController extends Controller
{
    public function index()
    {
        return Inertia::render('Sales/ServiceLine/Index');
    }
}
