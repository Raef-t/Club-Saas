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
                'gender' => $data['gender'],
                'age' => $data['age'] ?? null,
                'dob' => $data['dob'] ?? null,
                'address' => $data['address'] ?? null,
                'photo_url' => $photoUrl,
                'type' => 'player',
            ]);

            // 3.5 Create Primary Contact
            $person->contacts()->create([
                'name' => 'Personal',
                'relation' => 'self',
                'country_code' => $data['mobile_country_code'] ?? null,
                'phone_number' => $data['mobile'],
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

    public function updatePlayer($memberId, array $data)
    {
        return DB::transaction(function () use ($memberId, $data) {
            $member = Member::findOrFail($memberId);
            $person = $member->person()->first();

            if (!$person) {
                abort(404, 'لا يمكن تعديل العضو: لا يوجد سجل بيانات شخصية مرتبط بهذا العضو.');
            }
            // Handle Photo Upload
            if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                if ($person->photo_url) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($person->photo_url);
                }
                $data['photo_url'] = $data['photo']->store('people/photos', 'public');
            }

            // Update Person
            $personData = [];
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $parts = explode(' ', $person->full_name);
                $firstName = $data['first_name'] ?? ($parts[0] ?? '');
                $lastName = $data['last_name'] ?? ($parts[1] ?? '');
                $personData['full_name'] = trim($firstName . ' ' . $lastName);
            }
            if (isset($data['gender'])) $personData['gender'] = $data['gender'];
            if (array_key_exists('dob', $data)) $personData['dob'] = $data['dob'];
            if (array_key_exists('address', $data)) $personData['address'] = $data['address'];
            if (isset($data['photo_url'])) $personData['photo_url'] = $data['photo_url'];

            if (!empty($personData)) {
                $person->update($personData);
            }

            // Update Additional Contacts
            // If main mobile changed, update primary contact
            if (isset($data['mobile'])) {
                $person->contacts()->updateOrCreate(
                    ['name' => 'Personal', 'relation' => 'self'],
                    [
                        'phone_number' => $data['mobile'],
                        'country_code' => array_key_exists('mobile_country_code', $data) ? $data['mobile_country_code'] : null,
                    ]
                );
            }

            if (isset($data['additional_contacts']) && is_array($data['additional_contacts'])) {
                $person->contacts()->where('name', '!=', 'Personal')->delete();
                foreach ($data['additional_contacts'] as $contactData) {
                    $person->contacts()->create([
                        'name' => $contactData['name'],
                        'country_code' => $contactData['country_code'] ?? null,
                        'phone_number' => $contactData['phone_number'],
                        'relation' => $contactData['relation'] ?? null,
                    ]);
                }
            }

            // Update Member
            if (isset($data['branch_id'])) {
                $member->update(['branch_id' => $data['branch_id']]);
            }

            $member->load('person.contacts', 'measurements', 'healthProfile');
            return $member;
        });
    }

    private function generateMemberNumber(): string
    {
        $lastMember = Member::latest('id')->first();
        $nextId = $lastMember ? $lastMember->id + 1 : 1;
        return 'MEM-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
