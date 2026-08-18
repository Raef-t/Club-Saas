<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Facility;
use Modules\ClubManager\Models\Locker;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;

class CascadingSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cascading_soft_delete_and_restore(): void
    {
        // 1. Arrange: Create Club -> Branch -> Person -> Member -> Subscription -> Invoice -> Payment
        $club = Club::create(['name' => 'Test Club', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Test Branch', 'is_active' => true]);
        $person = Person::create(['full_name' => 'John Doe', 'gender' => 'male', 'type' => 'player']);
        
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $person->id,
            'member_number' => 'MEM-1001',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Plan M',
            'base_price' => 100.00,
            'status' => 'active',
        ]);

        $subscription = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $invoice = Invoice::create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'player_subscription_id' => $subscription->id,
            'total' => 100.00,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'cash',
        ]);

        // 2. Act: Delete Member
        $member->delete();

        // 3. Assert: Member and all children are soft deleted
        $this->assertSoftDeleted('members', ['id' => $member->id]);
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);

        // 4. Act: Restore Member
        $member->restore();

        // 5. Assert: Member and all children are restored
        $this->assertNotSoftDeleted('members', ['id' => $member->id]);
        $this->assertNotSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment->id]);
    }

    public function test_branch_cascading_soft_delete_and_restore(): void
    {
        // 1. Arrange: Create Club -> Branch -> Facility & Locker & Member
        $club = Club::create(['name' => 'Main Club', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch Alpha', 'is_active' => true]);
        $facility = Facility::create(['branch_id' => $branch->id, 'name' => 'Gym Floor']);
        $locker = Locker::create(['branch_id' => $branch->id, 'locker_number' => 'L-01', 'status' => 'available']);
        
        $person = Person::create(['full_name' => 'Jane Smith', 'gender' => 'female', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $person->id,
            'member_number' => 'MEM-2002',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        // 2. Act: Delete Branch
        $branch->delete();

        // 3. Assert: Branch and all child entities (Facility, Locker, Member) are soft deleted
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertSoftDeleted('facilities', ['id' => $facility->id]);
        $this->assertSoftDeleted('lockers', ['id' => $locker->id]);
        $this->assertSoftDeleted('members', ['id' => $member->id]);

        // 4. Act: Restore Branch
        $branch->restore();

        // 5. Assert: Branch and all child entities are restored
        $this->assertNotSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertNotSoftDeleted('facilities', ['id' => $facility->id]);
        $this->assertNotSoftDeleted('lockers', ['id' => $locker->id]);
        $this->assertNotSoftDeleted('members', ['id' => $member->id]);
    }

    public function test_player_subscription_cascading_soft_delete_and_restore(): void
    {
        // 1. Arrange
        $club = Club::create(['name' => 'Club X', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch X', 'is_active' => true]);
        $person = Person::create(['full_name' => 'Player One', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $person->id,
            'member_number' => 'MEM-3001',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $planX = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Plan X',
            'base_price' => 300.00,
            'status' => 'active',
        ]);

        $subscription = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $planX->id,
            'months_count' => 3,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
        ]);

        $item = \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::create([
            'player_subscription_id' => $subscription->id,
            'sessions_allocated' => 12,
            'sessions_consumed' => 2,
            'is_unlimited' => false,
        ]);

        $freeze = \Modules\SubscriptionManager\Models\SubscriptionFreeze::create([
            'player_subscription_id' => $subscription->id,
            'freeze_start_date' => now(),
            'freeze_end_date' => now()->addDays(7),
        ]);

        $invoice1 = Invoice::create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'player_subscription_id' => $subscription->id,
            'total' => 200.00,
            'status' => 'paid',
        ]);

        $payment1 = Payment::create([
            'invoice_id' => $invoice1->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
        ]);

        $invoice2 = Invoice::create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'player_subscription_id' => $subscription->id,
            'total' => 100.00,
            'status' => 'paid',
        ]);

        $payment2 = Payment::create([
            'invoice_id' => $invoice2->id,
            'amount' => 100.00,
            'payment_method' => 'visa',
        ]);

        // 2. Act: Soft delete subscription
        $subscription->delete();

        // 3. Assert: Subscription, items, freezes, invoices, and payments are soft-deleted
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertSoftDeleted('player_subscription_items', ['id' => $item->id]);
        $this->assertSoftDeleted('subscription_freezes', ['id' => $freeze->id]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice1->id]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice2->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment1->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment2->id]);

        // 4. Act: Restore subscription
        $subscription->restore();

        // 5. Assert: Subscription and all cascaded children are restored
        $this->assertNotSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertNotSoftDeleted('player_subscription_items', ['id' => $item->id]);
        $this->assertNotSoftDeleted('subscription_freezes', ['id' => $freeze->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice1->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice2->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment1->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment2->id]);
    }

    public function test_invoice_cascading_soft_delete_and_restore(): void
    {
        $club = Club::create(['name' => 'Club Y', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch Y', 'is_active' => true]);
        $person = Person::create(['full_name' => 'Player Two', 'gender' => 'female', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $person->id,
            'member_number' => 'MEM-4001',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $invoice = Invoice::create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'total' => 250.00,
            'status' => 'paid',
        ]);

        $payment1 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
        ]);

        $payment2 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'bank_transfer',
        ]);

        // Act: Delete Invoice
        $invoice->delete();

        // Assert: Invoice and its payments are soft deleted
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment1->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment2->id]);

        // Act: Restore Invoice
        $invoice->restore();

        // Assert: Invoice and its payments are restored
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment1->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment2->id]);
    }

    public function test_player_subscription_api_endpoints_cascade(): void
    {
        $adminPerson = Person::create(['full_name' => 'Admin User', 'gender' => 'male', 'type' => 'staff']);
        $user = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_test_user',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');

        $club = Club::create(['name' => 'Club Z', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch Z', 'is_active' => true]);
        $person = Person::create(['full_name' => 'Player Three', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $person->id,
            'member_number' => 'MEM-5001',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $planZ = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Plan Z',
            'base_price' => 150.00,
            'status' => 'active',
        ]);

        $subscription = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $planZ->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'member_id' => $member->id,
            'branch_id' => $branch->id,
            'player_subscription_id' => $subscription->id,
            'total' => 150.00,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
        ]);

        // Call API Delete
        $response = $this->deleteJson("/api/v1/player-subscriptions/{$subscription->id}");
        $response->assertStatus(200);

        $this->assertSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);

        // Call API Restore
        $restoreResponse = $this->postJson("/api/v1/player-subscriptions/{$subscription->id}/restore");
        $restoreResponse->assertStatus(200);

        $this->assertNotSoftDeleted('player_subscriptions', ['id' => $subscription->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertNotSoftDeleted('payments', ['id' => $payment->id]);
    }
}
