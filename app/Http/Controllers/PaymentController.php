<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        return view('membership.payments.index', ['payments' => Payment::with('transaction.member', 'recorder')->latest('id')->paginate(15)]);
    }

    public function create(MembershipTransaction $transaction)
    {
        $transaction->load('member', 'payments');

        return view('membership.payments.form', compact('transaction'));
    }

    public function store(Request $request, MembershipTransaction $transaction, MembershipService $service)
    {
        $data = $request->validate(['amount' => 'required|numeric|decimal:0,2|gt:0|max:99999999.99', 'method' => ['required', Rule::in(['cash', 'bank_transfer', 'e_wallet'])], 'external_reference' => 'nullable|string|max:255|required_unless:method,cash', 'paid_at' => 'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:'.$transaction->created_at->format('Y-m-d')]);
        $payment = $service->pay($transaction, $data, $request->user()->id);

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded.');
    }

    public function show(Payment $payment)
    {
        $payment->load('transaction.member.category', 'transaction.level', 'recorder');

        return view('membership.payments.receipt', compact('payment'));
    }
}
