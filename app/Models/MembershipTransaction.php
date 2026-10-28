<?php

namespace App\Models;

use App\Services\MembershipService;
use Illuminate\Database\Eloquent\Model;

class MembershipTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'valid_from' => 'date', 'valid_until' => 'date', 'approved_at' => 'datetime'];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function level()
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusAttribute($value): string
    {
        return $value === 'approved' && $this->valid_until?->lt(today()) ? 'expired' : $value;
    }

    public function scopeStatusIs($query, string $status)
    {
        if ($status === 'expired') {
            return $query->where('status', 'approved')->whereDate('valid_until', '<', today());
        }
        $query->where('status', $status);
        if ($status === 'approved') {
            $query->whereDate('valid_until', '>=', today());
        }

        return $query;
    }

    public function getPaidCentsAttribute(): int
    {
        return $this->payments->sum(fn ($payment) => MembershipService::cents($payment->amount));
    }

    public function getBalanceCentsAttribute(): int
    {
        return max(0, MembershipService::cents($this->amount) - $this->paid_cents);
    }
}
