<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class MyInvoicesEmployeeNameTest extends TestCase
{
    use DatabaseTransactions;

    protected $branch;
    protected $member;
    protected $memberUser;
    protected $employee1;
    protected $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'club_saas',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $club = Club::create([
            'name' => 'Test Club ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Main Branch',
            'status' => 'active',
        ]);

        // Employee 1
        $person1 = Person::create([
            'full_name' => 'سامر الخطيب',
            'gender' => 'male',
            'type' => 'staff',
        ]);
        $this->employee1 = User::create([
            'person_id' => $person1->id,
            'username' => 'employee_samer_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Employee 2
        $person2 = Person::create([
            'full_name' => 'نور الهدى',
            'gender' => 'female',
            'type' => 'staff',
        ]);
        $this->employee2 = User::create([
            'person_id' => $person2->id,
            'username' => 'employee_nour_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create([
            'full_name' => 'عضو التجربة',
            'gender' => 'male',
            'type' => 'player',
        ]);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->memberUser = User::create([
            'person_id' => $memberPerson->id,
            'username' => 'member_user_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->memberUser->assignRole($role);
    }

    public function test_my_invoices_returns_employee_name_for_payments_and_invoice(): void
    {
        Sanctum::actingAs($this->memberUser, ['*']);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'currency' => 'SYP',
            'total' => 50000.00,
            'status' => 'partially_paid',
            'created_by' => $this->employee1->id,
        ]);

        // First payment received by Employee 1
        $payment1 = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-001',
            'currency' => 'SYP',
            'amount' => 30000.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_by' => $this->employee1->id,
        ]);

        // Second payment received by Employee 2
        $payment2 = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-002',
            'currency' => 'SYP',
            'amount' => 20000.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_by' => $this->employee2->id,
        ]);

        $response = $this->getJson('/api/v1/my-invoices');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $invoices = $response->json('data.invoices');
        $this->assertNotEmpty($invoices);

        $firstInvoice = $invoices[0];
        // The invoice employee name should match the latest payment employee or invoice creator
        $this->assertArrayHasKey('employee_name', $firstInvoice);
        $this->assertEquals('نور الهدى', $firstInvoice['employee_name']);

        // Check payments list inside the invoice
        $payments = $firstInvoice['payments'];
        $this->assertCount(2, $payments);

        $this->assertArrayHasKey('employee_name', $payments[0]);
        $this->assertEquals('سامر الخطيب', $payments[0]['employee_name']);

        $this->assertArrayHasKey('employee_name', $payments[1]);
        $this->assertEquals('نور الهدى', $payments[1]['employee_name']);
    }

    public function test_my_invoices_handles_null_employee_name_gracefully(): void
    {
        Sanctum::actingAs($this->memberUser, ['*']);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'currency' => 'USD',
            'total' => 100.00,
            'status' => 'unpaid',
            'created_by' => null,
        ]);

        $response = $this->getJson('/api/v1/my-invoices');

        $response->assertStatus(200);
        $invoices = $response->json('data.invoices');
        $this->assertNotEmpty($invoices);
        $this->assertNull($invoices[0]['employee_name']);
        $this->assertEmpty($invoices[0]['payments']);
    }
}
