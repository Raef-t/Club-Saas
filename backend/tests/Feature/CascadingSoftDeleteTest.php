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
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\Authentication\Models\Person;

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

        $subscription = PlayerSubscription::create([
            'member_id' => $member->id,
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
}
