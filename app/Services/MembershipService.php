<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public static function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function reference(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid());
    }

    public function create(Member $member, MembershipLevel $level, string $type, int $adminId): MembershipTransaction
    {
        return DB::transaction(function () use ($member, $level, $type, $adminId) {
            $member = Member::lockForUpdate()->findOrFail($member->id);
            if (! $member->is_active) {
                $this->fail('member_id', 'Reactivate this member before creating a transaction.');
            }
            if ($member->transactions()->whereIn('status', ['pending', 'paid'])->exists()) {
                $this->fail('member_id', 'This member already has an unfinished transaction.');
            }
            $hasApproved = $member->transactions()->where('status', 'approved')->exists();
            if (($type === 'application' && $hasApproved) || ($type === 'renewal' && ! $hasApproved)) {
                $this->fail('type', $hasApproved ? 'Use a renewal for an existing membership.' : 'Create an application before a renewal.');
            }

            return MembershipTransaction::create(['reference_number' => self::reference('TXN'), 'member_id' => $member->id, 'membership_level_id' => $level->id, 'type' => $type, 'amount' => $level->fee, 'validity_months' => $level->validity_months, 'status' => self::cents($level->fee) === 0 ? 'paid' : 'pending', 'created_by' => $adminId]);
        });
    }

    public function pay(MembershipTransaction $transaction, array $data, int $adminId): Payment
    {
        return DB::transaction(function () use ($transaction, $data, $adminId) {
            Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            if (! $transaction->member->is_active || $transaction->status !== 'pending') {
                $this->fail('amount', 'Payments are allowed only for pending transactions of enabled members.');
            }
            $cents = self::cents((string) $data['amount']);
            $balance = $transaction->balance_cents;
            if ($cents <= 0 || $cents > $balance) {
                $this->fail('amount', 'Payment must be greater than zero and cannot exceed the remaining balance.');
            }
            $payment = $transaction->payments()->create($data + ['receipt_number' => self::reference('RCT'), 'recorded_by' => $adminId]);
            if ($cents === $balance) {
                $transaction->update(['status' => 'paid']);
            }

            return $payment;
        });
    }

    public function approve(MembershipTransaction $transaction, int $adminId): void
    {
        DB::transaction(function () use ($transaction, $adminId) {
            $member = Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            if (! $member->is_active || $transaction->status !== 'paid' || $transaction->balance_cents !== 0) {
                $this->fail('status', 'Only fully paid transactions of enabled members may be approved.');
            }
            $lastEnd = $member->transactions()->where('status', 'approved')->max('valid_until');
            $start = $lastEnd && Carbon::parse($lastEnd)->gte(today()) ? Carbon::parse($lastEnd)->addDay() : today();
            $transaction->update(['status' => 'approved', 'approved_by' => $adminId, 'approved_at' => now(), 'valid_from' => $start, 'valid_until' => $start->copy()->addMonthsNoOverflow($transaction->validity_months)->subDay()]);
            $member->update(['membership_level_id' => $transaction->membership_level_id]);
        });
    }

    public function cancel(MembershipTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status !== 'pending' || $transaction->payments()->exists()) {
                $this->fail('status', 'Only unpaid pending transactions can be cancelled. Payments remain in the audit history.');
            }
            $transaction->update(['status' => 'cancelled']);
        });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
