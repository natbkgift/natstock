<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index()
    {
        $activities = Activity::latest()->paginate(20);
        return view('admin.audit.index', compact('activities'));
    }
}
