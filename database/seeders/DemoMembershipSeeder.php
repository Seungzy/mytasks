<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Optional fictional presentation data. Existing members are never overwritten. */
class DemoMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->firstOrFail();
        $service = app(MembershipService::class);
        foreach ([['Maya', 'Reyes', 'Student', 'Basic', 'approved'], ['Daniel', 'Cruz', 'Regular', 'Silver', 'pending'], ['Elena', 'Santos', 'Senior Citizen', 'Gold', 'paid'], ['Alex', 'Rivera', 'Corporate', 'Platinum', 'expired'], ['Nina', 'Garcia', 'Regular', 'Basic', 'cancelled']] as [$first,$last,$category,$level,$status]) {
            $email = strtolower($first).'.demo@example.test';
            if (Member::where('email', $email)->exists()) {
                continue;
            }
            DB::transaction(function () use ($admin, $service, $first, $last, $email, $category, $level, $status) {
                $member = Member::create(['membership_number' => MembershipService::reference('MEM'), 'first_name' => $first.' (Demo)', 'last_name' => $last, 'email' => $email, 'address' => 'Fictional member for classroom demonstration.', 'membership_category_id' => MembershipCategory::where('name', $category)->firstOrFail()->id, 'membership_level_id' => MembershipLevel::where('name', $level)->firstOrFail()->id]);
                $transaction = $service->create($member, $member->level, 'application', $admin->id);
                if (in_array($status, ['paid', 'approved', 'expired'])) {
                    $service->pay($transaction, ['amount' => $transaction->amount, 'method' => 'cash', 'paid_at' => today()->format('Y-m-d')], $admin->id);
                }
                if (in_array($status, ['approved', 'expired'])) {
                    $service->approve($transaction, $admin->id);
                }
                if ($status === 'expired') {
                    $transaction->update(['valid_from' => today()->subMonthsNoOverflow(13), 'valid_until' => today()->subMonthNoOverflow()]);
                }
                if ($status === 'cancelled') {
                    $service->cancel($transaction);
                }
            });
        }
    }
}
