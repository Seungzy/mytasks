<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['pending', 'paid', 'approved', 'expired', 'cancelled'])], 'q' => 'nullable|string|max:100']);
        $transactions = MembershipTransaction::with('member', 'level', 'payments')->when($data['status'] ?? null, fn ($q, $status) => $q->statusIs($status))->when($data['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('reference_number', 'like', "%$term%")->orWhereHas('member', fn ($q) => $q->where('last_name', 'like', "%$term%")->orWhere('first_name', 'like', "%$term%"))))->latest('id')->paginate(12)->withQueryString();

        return view('membership.transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        return view('membership.transactions.form', ['members' => Member::where('is_active', true)->orderBy('last_name')->get(), 'levels' => MembershipLevel::orderBy('fee')->get(), 'selectedMember' => $request->integer('member')]);
    }

    public function store(Request $request, MembershipService $service)
    {
        $data = $request->validate(['member_id' => 'required|exists:members,id', 'membership_level_id' => 'required|exists:membership_levels,id', 'type' => ['required', Rule::in(['application', 'renewal'])]]);
        $transaction = $service->create(Member::findOrFail($data['member_id']), MembershipLevel::findOrFail($data['membership_level_id']), $data['type'], $request->user()->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaction created.');
    }

    public function show(MembershipTransaction $transaction)
    {
        $transaction->load('member.category', 'level', 'payments.recorder', 'creator', 'approver');

        return view('membership.transactions.show', compact('transaction'));
    }

    public function approve(Request $request, MembershipTransaction $transaction, MembershipService $service)
    {
        $service->approve($transaction, $request->user()->id);

        return back()->with('success', 'Membership approved.');
    }

    public function cancel(MembershipTransaction $transaction, MembershipService $service)
    {
        $service->cancel($transaction);

        return back()->with('success','Transaction cancelled.');
    }
}
