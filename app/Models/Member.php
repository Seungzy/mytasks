<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = ['membership_number', 'first_name', 'last_name', 'email', 'phone', 'address', 'membership_category_id', 'membership_level_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level()
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function transactions()
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function scopeWithActiveMembership($query)
    {
        return $query->where('is_active', true)->whereHas('transactions', fn ($q) => $q->where('status', 'approved')->whereDate('valid_from', '<=', today())->whereDate('valid_until', '>=', today()));
    }
}
