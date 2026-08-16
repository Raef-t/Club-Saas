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
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function registerPlayer(array $data)
    {
        return DB::transaction(function () use ($data) {
            $branchId = $data['branch_id'] ?? null;
            if (!$branchId) {
                /** @var \Modules\Authentication\Models\User|null $user */
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && $user->person) {
                    $staff = \Modules\StaffManager\Models\Staff::where('person_id', $user->person_id)->first();
                    if ($staff && $staff->branches()->exists()) {
                        $branchId = $staff->branches()->first()->id;
                    }
                }
            }
            if (!$branchId) {
                throw new Exception(__('Branch is required. Please specify a branch.'));
            }
            $branch = \Modules\ClubManager\Models\Branch::find($branchId);
            
            if ($branch && $branch->gender_restriction !== 'mixed' && $branch->gender_restriction !== $data['gender']) {
                throw ValidationException::withMessages([
                    'gender' => 'لا يمكن إضافة هذا اللاعب/ة في هذا الفرع بسبب قيود الجنس الخاصة بالفرع.'
                ]);
            }

            // 2. Handle Photo Upload
            $photoUrl = null;
            if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
                $photoUrl = $data['photo']->store('people/photos', 'public');
            }

            $age = $data['age'] ?? null;
            if (!$age && !empty($data['dob'])) {
                $age = \Carbon\Carbon::parse($data['dob'])->age;
            }

            // 3. Create Person
            $person = Person::create([
                'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                'gender' => $data['gender'],
                'age' => $age,
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
                'branch_id' => $branchId,
                'member_number' => $data['member_number'] ?? $this->generateMemberNumber(),
                'membership_status' => 'active',
                'join_date' => now(),
            ]);

            // 6. Create User Account automatically
            $username = \Modules\Authentication\Services\UsernameGeneratorService::generateForRole('player');
            $password = '12345678';
            $user = User::create([
                'person_id' => $person->id,
                'username' => $username,
                'password' => Hash::make($password),
                'is_active' => true,
                'role' => 'player',
            ]);
            
            $member->generated_username = $username;
            $member->generated_password = $password;

            // Assign spatie role if needed
            $user->assignRole('player');

            // Generate 7 QR codes for the new player (one for each day of the week)
            app(\Modules\Authentication\Services\PersonQrCodeService::class)->generateForPerson($person->id);

            // Return the created member along with their person data, and contacts
            $member->load('person.contacts', 'measurements', 'healthProfile');
            return [
                'member' => $member
            ];
        });
    }

    public function updatePlayer(int $memberId, array $data)
    {
        return DB::transaction(function () use ($memberId, $data) {
            $member = Member::findOrFail($memberId);
            $person = $member->person()->first();

            if (!$person) {
                abort(404, 'لا يمكن تعديل العضو: لا يوجد سجل بيانات شخصية مرتبط بهذا العضو.');
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
            if (array_key_exists('age', $data)) $personData['age'] = $data['age'];
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
            $memberUpdateData = [];
            if (isset($data['branch_id'])) {
                $memberUpdateData['branch_id'] = $data['branch_id'];
            }
            if (isset($data['reason'])) {
                $memberUpdateData['reason'] = $data['reason'];
            }
            if (!empty($memberUpdateData)) {
                $member->update($memberUpdateData);
            }

            $member->load('person.contacts', 'measurements', 'healthProfile');
            return $member;
        });
    }

    public function updateMemberPhoto(int $memberId, \Illuminate\Http\UploadedFile $photo)
    {
        return DB::transaction(function () use ($memberId, $photo) {
            $member = Member::findOrFail($memberId);
            $person = $member->person()->first();

            if (!$person) {
                abort(404, 'لا يمكن تعديل العضو: لا يوجد سجل بيانات شخصية مرتبط بهذا العضو.');
            }

            // Delete old photo if exists
            if ($person->photo_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($person->photo_url);
            }

            $person->update([
                'photo_url' => $photo->store('people/photos', 'public'),
            ]);

            $member->load('person.contacts', 'measurements', 'healthProfile');
            return $member;
        });
    }

    private function generateMemberNumber(): string
    {
        $year = date('Y');
        $prefix = "MEM-{$year}-";

        $lastMember = Member::withTrashed()
            ->where('member_number', 'like', "{$prefix}%")
            ->orderBy('member_number', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastMember) {
            $lastSeq = (int) \Illuminate\Support\Str::afterLast($lastMember->member_number, '-');
            $sequence = $lastSeq + 1;
        }

        do {
            $candidateNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = Member::withTrashed()->where('member_number', $candidateNumber)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $candidateNumber;
    }
}
