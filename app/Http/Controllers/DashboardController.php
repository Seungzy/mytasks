<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('membership.dashboard', ['total' => Member::count(), 'active' => Member::withActiveMembership()->count(), 'pending' => MembershipTransaction::where('status', 'pending')->count(), 'collected' => Payment::sum('amount'), 'recent' => MembershipTransaction::with('member', 'level')->latest('id')->limit(6)->get()]);
    }
}
