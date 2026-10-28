<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipLevel extends Model
{
    protected $fillable = ['name', 'fee', 'validity_months', 'benefits'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2', 'validity_months' => 'integer'];
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function transactions()
    {
        return $this->hasMany(MembershipTransaction::class);
    }
}
