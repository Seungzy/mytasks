<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\MembershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MembershipSeeder::class);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function data(array $extra = []): array
    {
        return $extra + ['first_name' => 'Ana', 'last_name' => 'Santos', 'email' => 'ana@example.test',
            'membership_category_id' => MembershipCategory::first()->id,
            'membership_level_id' => MembershipLevel::where('name', 'Basic')->first()->id];
    }

    private function register(): MembershipTransaction
    {
        $this->actingAs($this->admin)->post('/members', $this->data())->assertSessionHasNoErrors()->assertRedirect();

        return MembershipTransaction::firstOrFail();
    }

    private function pay(MembershipTransaction $transaction, string $amount = '500.00')
    {
        return $this->post(route('payments.store', $transaction), [
            'amount' => $amount, 'method' => 'cash', 'paid_at' => today()->format('Y-m-d'),
        ]);
    }

    public function test_guests_and_non_admins_cannot_access_or_change_membership_data(): void
    {
        foreach (['/dashboard', '/members', '/categories', '/levels', '/transactions', '/payments'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/members', $this->data())->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
        $this->post('/members', $this->data())->assertForbidden();
        $this->assertDatabaseCount('members', 0);
    }

    public function test_admin_login_logout_and_invalid_password(): void
    {
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'bad'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_and_non_admin_cannot_login(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $this->admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_member_registration_edit_search_validation_and_deactivation(): void
    {
        $transaction = $this->register();
        $member = $transaction->member;
        $this->assertStringStartsWith('MEM-', $member->membership_number);
        $this->assertSame('pending', $transaction->status);
        $this->assertSame('500.00', $transaction->amount);
        $this->get('/members?q=Ana%20Santos')->assertOk()->assertSee('Ana Santos');
        $this->get('/members?category='.$member->membership_category_id.'&level='.$member->membership_level_id)->assertOk()->assertSee('Ana Santos');
        $this->get('/members?q=Unmatched')->assertOk()->assertDontSee('Ana Santos');
        $this->get(route('members.show', $member))->assertOk()->assertSee($transaction->reference_number);
        $this->put(route('members.update', $member), $this->data(['first_name' => 'Anita']))->assertSessionHasNoErrors();
        $this->assertSame('Anita', $member->fresh()->first_name);
        $this->post('/members', $this->data())->assertSessionHasErrors('email');
        $this->post('/members', $this->data(['email' => 'invalid']))->assertSessionHasErrors('email');
        $this->post('/members', $this->data(['email' => 'other@example.test', 'membership_category_id' => 99999]))->assertSessionHasErrors('membership_category_id');
        $other = Member::create($this->data(['email' => 'other@example.test', 'membership_number' => 'MEM-OTHER']));
        $this->put(route('members.update', $member), $this->data(['membership_number' => $other->membership_number]))->assertSessionHasErrors('membership_number');
        $this->delete(route('members.destroy', $member))->assertSessionHasNoErrors();
        $this->assertFalse($member->fresh()->is_active);
        $this->pay($transaction)->assertSessionHasErrors('amount');
        $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id]);
        $this->patch(route('members.reactivate', $member))->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->is_active);
    }

    public function test_categories_and_levels_crud_and_assigned_record_protection(): void
    {
        $transaction = $this->register();
        $this->delete(route('categories.destroy', $transaction->member->category))->assertSessionHasErrors('category');
        $this->delete(route('levels.destroy', $transaction->level))->assertSessionHasErrors('level');
        $this->post('/categories', ['name' => 'Alumni', 'description' => 'Former students'])->assertSessionHasNoErrors();
        $category = MembershipCategory::where('name', 'Alumni')->firstOrFail();
        $this->put(route('categories.update', $category), ['name' => 'Graduates'])->assertSessionHasNoErrors();
        $this->get(route('categories.edit', $category))->assertOk()->assertSee('Graduates');
        $this->post('/categories', ['name' => 'Graduates'])->assertSessionHasErrors('name');
        $this->delete(route('categories.destroy', $category))->assertSessionHasNoErrors();
        $this->assertModelMissing($category);
        $this->post('/levels', ['name' => 'Trial', 'fee' => '12.34', 'validity_months' => 1])->assertSessionHasNoErrors();
        $level = MembershipLevel::where('name', 'Trial')->firstOrFail();
        $this->put(route('levels.update', $level), ['name' => 'Trial Plus', 'fee' => '20.00', 'validity_months' => 2])->assertSessionHasNoErrors();
        $this->get(route('levels.edit', $level))->assertOk()->assertSee('Trial Plus');
        $this->post('/levels', ['name' => 'Invalid', 'fee' => '-1', 'validity_months' => 0])->assertSessionHasErrors(['fee', 'validity_months']);
        $this->delete(route('levels.destroy', $level))->assertSessionHasNoErrors();
        $this->assertModelMissing($level);
    }

    public function test_partial_payments_approval_and_renewal_extend_validity(): void
    {
        $transaction = $this->register();
        $this->patch(route('transactions.approve', $transaction))->assertSessionHasErrors('status');
        $this->pay($transaction, '200.10')->assertSessionHasNoErrors();
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(29990, $transaction->fresh()->balance_cents);
        $this->patch(route('transactions.approve', $transaction))->assertSessionHasErrors('status');
        $this->pay($transaction, '299.90')->assertSessionHasNoErrors();
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame(0, $transaction->fresh()->balance_cents);
        $this->pay($transaction, '1.00')->assertSessionHasErrors('amount');
        $this->patch(route('transactions.approve', $transaction))->assertSessionHasNoErrors();
        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertTrue($transaction->valid_from->equalTo(today()));
        $this->assertTrue($transaction->valid_until->equalTo(today()->addMonthsNoOverflow(12)->subDay()));
        $this->assertSame(1, Member::withActiveMembership()->count());
        $this->patch(route('transactions.approve', $transaction))->assertSessionHasErrors('status');
        $this->post('/transactions', ['member_id' => $transaction->member_id, 'membership_level_id' => $transaction->membership_level_id, 'type' => 'renewal'])->assertSessionHasNoErrors();
        $renewal = MembershipTransaction::latest('id')->first();
        $this->assertNotSame($transaction->reference_number, $renewal->reference_number);
        $this->pay($renewal)->assertSessionHasNoErrors();
        $this->patch(route('transactions.approve', $renewal))->assertSessionHasNoErrors();
        $this->assertTrue($renewal->fresh()->valid_from->equalTo($transaction->valid_until->copy()->addDay()));
        $this->get(route('payments.show', Payment::first()))->assertOk()->assertSee('Payment receipt')->assertSee('200.10');
        $this->get(route('members.show', $transaction->member))->assertOk()->assertSee($renewal->reference_number);
        $this->get('/dashboard')->assertOk()->assertSee('500.00');
    }

    public function test_payment_validation_and_cancellation_preserve_money_history(): void
    {
        $transaction = $this->register();
        foreach (['0', '-1', '501', '1.001', 'invalid'] as $amount) {
            $this->pay($transaction, $amount)->assertSessionHasErrors('amount');
        }
        $this->post(route('payments.store', $transaction), ['amount' => '10', 'method' => 'bank_transfer', 'paid_at' => today()->format('Y-m-d')])->assertSessionHasErrors('external_reference');
        $this->post(route('payments.store', $transaction), ['amount' => '10', 'method' => 'cash', 'paid_at' => today()->addDay()->format('Y-m-d')])->assertSessionHasErrors('paid_at');
        $this->post('/transactions/999999/payments', ['amount' => 1])->assertNotFound();
        $this->patch(route('transactions.cancel', $transaction))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->pay($transaction)->assertSessionHasErrors('amount');
        $this->post('/transactions', ['member_id' => $transaction->member_id, 'membership_level_id' => $transaction->membership_level_id, 'type' => 'application'])->assertSessionHasNoErrors();
        $replacement = MembershipTransaction::latest('id')->first();
        $this->pay($replacement, '1')->assertSessionHasNoErrors();
        $this->patch(route('transactions.cancel', $replacement))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_duplicate_applications_and_invalid_renewals_are_rejected(): void
    {
        $transaction = $this->register();
        foreach (['application', 'renewal'] as $type) {
            $this->post('/transactions', ['member_id' => $transaction->member_id, 'membership_level_id' => $transaction->membership_level_id, 'type' => $type])->assertSessionHasErrors('member_id');
        }
        $this->patch(route('transactions.cancel', $transaction));
        $this->post('/transactions', ['member_id' => $transaction->member_id, 'membership_level_id' => $transaction->membership_level_id, 'type' => 'renewal'])->assertSessionHasErrors('type');
    }

    public function test_expiration_filter_and_snapshot_prices_are_consistent(): void
    {
        $transaction = $this->register();
        $transaction->level->update(['fee' => '999.00', 'validity_months' => 6]);
        $this->assertSame('500.00', $transaction->fresh()->amount);
        $this->assertSame(12, $transaction->validity_months);
        $this->pay($transaction);
        $this->patch(route('transactions.approve', $transaction));
        $this->travel(13)->months();
        $this->assertSame('expired', $transaction->fresh()->status);
        $this->assertSame(0, Member::withActiveMembership()->count());
        $this->get('/transactions?status=expired')->assertOk()->assertSee($transaction->reference_number);
        $this->get('/transactions?status=approved')->assertOk()->assertDontSee($transaction->reference_number);
        $this->travelBack();
    }

    public function test_all_membership_screens_render_and_free_levels_can_be_approved(): void
    {
        $transaction = $this->register();
        foreach (['/dashboard', '/members', '/members/create', '/members/'.$transaction->member_id.'/edit', '/categories', '/levels', '/transactions', '/transactions/create', '/transactions/'.$transaction->id, '/transactions/'.$transaction->id.'/payments/create', '/payments'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->patch(route('transactions.cancel', $transaction));
        $transaction->level->update(['fee' => '0.00']);
        $this->post('/transactions', ['member_id' => $transaction->member_id, 'membership_level_id' => $transaction->membership_level_id, 'type' => 'application'])->assertSessionHasNoErrors();
        $free = MembershipTransaction::latest('id')->first();
        $this->assertSame('paid', $free->status);
        $this->patch(route('transactions.approve', $free))->assertSessionHasNoErrors();
        $this->assertSame('approved', $free->fresh()->status);
    }
}
