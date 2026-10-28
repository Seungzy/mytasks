<?php

namespace Database\Seeders;

use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Student', 'Regular', 'Senior Citizen', 'Corporate'] as $name) {
            MembershipCategory::firstOrCreate(['name' => $name]);
        }
        foreach ([['Basic', '500.00', 'Member directory and community updates.'], ['Silver', '1000.00', 'Basic benefits plus event discounts.'], ['Gold', '2000.00', 'Silver benefits plus priority event registration.'], ['Platinum', '3500.00', 'Gold benefits plus premium member assistance.']] as [$name,$fee,$benefits]) {
            MembershipLevel::firstOrCreate(['name' => $name], ['fee' => $fee, 'validity_months' => 12, 'benefits' => $benefits]);
        }
    }
}
