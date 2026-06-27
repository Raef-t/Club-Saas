<?php

namespace Modules\MemberManager\Services;

use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class PlayerRegistrationService
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function registerPlayer(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate Capacity for all requested plans first
            foreach ($data['plans'] as $planData) {
                $plan = SubscriptionPlan::findOrFail($planData['plan_id']);

                if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                    throw ValidationException::withMessages([
                        'plans' => ["خطة الاشتراك '{$plan->name}' مكتملة العدد ولا يمكن التسجيل بها."]
                    ]);
                }
            }

            // 2. Handle Photo Upload
            $photoUrl = null;
            if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                $photoUrl = $data['photo']->store('people/photos', 'public');
            }

            // 3. Create Person
            $person = Person::create([
                'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                'mobile_1_country_code' => $data['mobile_country_code'] ?? null,
                'mobile_1' => $data['mobile'],
                'gender' => $data['gender'],
                'dob' => $data['dob'] ?? null,
                'photo_url' => $photoUrl,
                'type' => 'player',
            ]);

            // 4. Create Additional Contacts
            if (!empty($data['additional_contacts']) && is_array($data['additional_contacts'])) {
                foreach ($data['additional_contacts'] as $contactData) {
                    $person->contacts()->create([
                        'name' => $contactData['name'],
                        'country_code' => $contactData['country_code'] ?? null,
                        'phone_number' => $contactData['phone_number'],
                        'relation' => $contactData['relation'] ?? null,
                    ]);
                }
            }

            // 5. Create Member
            $member = Member::create([
                'person_id' => $person->id,
                'branch_id' => $data['branch_id'] ?? 1, // Default to branch 1 if not provided
                'member_number' => $data['member_number'] ?? $this->generateMemberNumber(),
                'membership_status' => 'active',
                'join_date' => now(),
            ]);

            // 6. Create User Account automatically
            $user = User::create([
                'person_id' => $person->id,
                'username' => $member->member_number,
                'password' => Hash::make('password123'),
                'is_active' => true,
                'role' => 'player',
            ]);

            // Assign spatie role if needed
            $role = \Spatie\Permission\Models\Role::where('name', 'player')->first();
            if ($role) {
                $user->assignRole($role);
            }

            // 7. Create Subscriptions
            $subscriptions = [];
            foreach ($data['plans'] as $planData) {
                $options = [
                    'paid_amount' => $planData['paid_amount'] ?? 0,
                    // Note: start_date is intentionally omitted here to default to today, 
                    // and end_date is calculated automatically inside subscribeMember
                ];

                $subscription = $this->subscriptionService->subscribeMember(
                    $member->id,
                    $planData['plan_id'],
                    $options
                );

                $subscriptions[] = $subscription;

                // Update plan subscribers count
                $plan = SubscriptionPlan::find($planData['plan_id']);
                if ($plan) {
                    $plan->increment('current_subscribers');
                }
            }

            // Return the created member along with their subscriptions, person data, and contacts
            $member->load('person.contacts', 'measurements', 'healthProfile');
            return [
                'member' => $member,
                'subscriptions' => $subscriptions
            ];
        });
    }

    private function generateMemberNumber(): string
    {
        $lastMember = Member::latest('id')->first();
        $nextId = $lastMember ? $lastMember->id + 1 : 1;
        return 'MEM-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
