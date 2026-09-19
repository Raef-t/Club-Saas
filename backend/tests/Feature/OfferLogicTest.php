<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\Offer;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Services\OfferService;
use Modules\SubscriptionManager\Services\SubscriptionService;

class OfferLogicTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $personUser = Person::create(['full_name' => 'مدير النظام', 'gender' => 'male', 'type' => 'staff']);
        $user = User::create([
            'person_id' => $personUser->id,
            'username' => 'admin_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');

        $club = Club::create([
            'name' => 'نادي الأبطال',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'فرع دمشق الرئيسي',
            'is_active' => true,
        ]);

        $person = Person::create([
            'full_name' => 'سارة أحمد',
            'gender' => 'female',
            'type' => 'player',
        ]);

        $this->member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $person->id,
            'membership_number' => 'MEM-1001',
            'status' => 'active',
        ]);
    }

    public function test_can_create_bundle_and_single_choice_offers()
    {
        $plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'أيروبيك - كوتش سارة',
            'base_price' => 250,
            'max_subscribers' => 10,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        $plan2 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'أيروبيك - كوتش ريم',
            'base_price' => 250,
            'max_subscribers' => 15,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        $offerService = app(OfferService::class);

        // 1. Create single_choice offer
        $singleChoiceOffer = $offerService->createOffer([
            'branch_id' => $this->branch->id,
            'name' => 'عرض فعاليات الأيروبيك',
            'offer_type' => Offer::TYPE_SINGLE_CHOICE,
            'price' => 200,
            'plans' => [$plan1->id, $plan2->id],
        ]);

        $this->assertEquals(Offer::TYPE_SINGLE_CHOICE, $singleChoiceOffer->offer_type);
        $this->assertTrue($singleChoiceOffer->isSingleChoice());
        $this->assertFalse($singleChoiceOffer->isBundle());
        $this->assertTrue($singleChoiceOffer->isDateValid());
    }

    public function test_single_choice_offer_enrolls_player_in_selected_plan_only()
    {
        $plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'أيروبيك - كوتش سارة',
            'base_price' => 250,
            'max_subscribers' => 10,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        $plan2 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'أيروبيك - كوتش ريم',
            'base_price' => 250,
            'max_subscribers' => 15,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        $offer = Offer::create([
            'branch_id' => $this->branch->id,
            'name' => 'عرض فعاليات الأيروبيك',
            'offer_type' => Offer::TYPE_SINGLE_CHOICE,
            'price' => 200,
            'is_active' => true,
        ]);
        $offer->plans()->sync([$plan1->id, $plan2->id]);

        $subscriptionService = app(SubscriptionService::class);

        // Subscribe player to plan1 (Coach Sarah) under the offer
        $result = $subscriptionService->subscribeMemberToOffer(
            $this->member->id,
            $offer->id,
            [
                'plan_id' => $plan1->id,
                'paid_amount' => 200,
            ]
        );

        // Only ONE subscription should be created (for plan1)
        $this->assertCount(1, $result['subscriptions']);
        $subscription = $result['subscriptions']->first();
        $this->assertEquals($plan1->id, $subscription->plan_id);
        $this->assertEquals(200, (float) $subscription->total_amount);
        $this->assertEquals(200, (float) $subscription->paid_amount);

        // Plan1 current subscribers should be incremented to 1
        $plan1->refresh();
        $this->assertEquals(1, $plan1->current_subscribers);

        // Plan2 current subscribers should remain 0
        $plan2->refresh();
        $this->assertEquals(0, $plan2->current_subscribers);
    }

    public function test_dynamic_capacity_and_auto_reactivation_on_offer()
    {
        $plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'أيروبيك - كوتش سارة',
            'base_price' => 250,
            'max_subscribers' => 1,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        $offer = Offer::create([
            'branch_id' => $this->branch->id,
            'name' => 'عرض أيروبيك سارة',
            'offer_type' => Offer::TYPE_SINGLE_CHOICE,
            'price' => 200,
            'is_active' => true,
        ]);
        $offer->plans()->sync([$plan1->id]);

        $subscriptionService = app(SubscriptionService::class);
        $offerService = app(OfferService::class);

        // Initially offer is available
        $availableOffers = $offerService->getAllOffers(['available_only' => true]);
        $this->assertTrue($availableOffers->contains('id', $offer->id));

        // Subscribe member -> fills the capacity (1 / 1)
        $result = $subscriptionService->subscribeMemberToOffer(
            $this->member->id,
            $offer->id,
            [
                'plan_id' => $plan1->id,
                'paid_amount' => 200,
            ]
        );

        $plan1->refresh();
        $this->assertEquals(1, $plan1->current_subscribers);

        // Offer is now full and should NOT appear in available_only
        $availableOffersAfter = $offerService->getAllOffers(['available_only' => true]);
        $this->assertFalse($availableOffersAfter->contains('id', $offer->id));

        // When space opens up (e.g. subscriber finishes and plan count is decremented)
        $result['subscriptions']->first()->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FINISHED]);
        $subscriptionService->decrementPlanSubscribers($plan1);
        $plan1->refresh();
        $this->assertEquals(0, $plan1->current_subscribers);

        // Offer automatically and dynamically re-activates!
        $availableOffersReactivated = $offerService->getAllOffers(['available_only' => true]);
        $this->assertTrue($availableOffersReactivated->contains('id', $offer->id));
    }

    public function test_can_create_offer_with_duration_days_and_subscribe_auto_calculates_end_date()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة فتنس شهرية',
            'base_price' => 300,
            'status' => 'active',
        ]);

        $offerService = app(OfferService::class);
        $subscriptionService = app(SubscriptionService::class);

        // 1. Create an offer with 45 days duration
        $offer = $offerService->createOffer([
            'branch_id' => $this->branch->id,
            'name' => 'عرض الصيف 45 يوم',
            'offer_type' => Offer::TYPE_BUNDLE,
            'price' => 400,
            'duration_days' => 45,
            'plans' => [$plan->id],
        ]);

        $this->assertEquals(45, $offer->duration_days);
        $this->assertEquals(1.5, $offer->duration_months);
        $this->assertEquals('شهر ونصف (45 يوم)', $offer->duration_formatted);

        // 2. Test OfferResource output
        $resource = (new \Modules\SubscriptionManager\Http\Resources\OfferResource($offer))->resolve();
        $this->assertEquals(45, $resource['duration_days']);
        $this->assertEquals(1.5, $resource['duration_months']);
        $this->assertEquals('شهر ونصف (45 يوم)', $resource['duration_formatted']);

        // 3. Subscribe player to the 45-day offer with a specific start date
        $startDate = '2026-10-01';
        $result = $subscriptionService->subscribeMemberToOffer(
            $this->member->id,
            $offer->id,
            [
                'start_date' => $startDate,
                'paid_amount' => 400,
            ]
        );

        $subscription = $result['subscriptions']->first();
        $this->assertEquals('2026-10-01', $subscription->start_date->format('Y-m-d'));
        // 2026-10-01 + 45 days = 2026-11-15
        $this->assertEquals('2026-11-15', $subscription->end_date->format('Y-m-d'));

        // 4. Test PlayerSubscriptionResource output
        $subResource = (new \Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource($subscription->load(['plan', 'offer'])))->resolve();
        $this->assertEquals(45, $subResource['duration_days']);
        $this->assertEquals($offer->id, $subResource['offer_id']);
        $this->assertNotNull($subResource['offer']);
        $this->assertEquals(45, $subResource['offer']['duration_days']);
    }

    public function test_yearly_offer_365_days_auto_calculates_end_date()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة اللياقة السنوية',
            'base_price' => 3000,
            'status' => 'active',
        ]);

        $offer = Offer::create([
            'branch_id' => $this->branch->id,
            'name' => 'عرض الاشتراك السنوي',
            'offer_type' => Offer::TYPE_BUNDLE,
            'price' => 2500,
            'duration_days' => 365,
            'is_active' => true,
        ]);
        $offer->plans()->sync([$plan->id]);

        $subscriptionService = app(SubscriptionService::class);

        $startDate = '2026-01-01';
        $result = $subscriptionService->subscribeMemberToOffer(
            $this->member->id,
            $offer->id,
            [
                'start_date' => $startDate,
                'paid_amount' => 2500,
            ]
        );

        $subscription = $result['subscriptions']->first();
        $this->assertEquals('2026-01-01', $subscription->start_date->format('Y-m-d'));
        // 2026-01-01 + 365 days = 2027-01-01
        $this->assertEquals('2027-01-01', $subscription->end_date->format('Y-m-d'));
    }

    public function test_api_store_and_update_offer_with_duration_days()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة كاراتيه',
            'base_price' => 500,
            'status' => 'active',
        ]);

        // POST /v1/offers
        $response = $this->postJson('/api/v1/offers', [
            'branch_id' => $this->branch->id,
            'name' => 'عرض الكاراتيه 90 يوم',
            'offer_type' => 'bundle',
            'price' => 1200,
            'duration_days' => 90,
            'plans' => [$plan->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.duration_days', 90);
        $response->assertJsonPath('data.duration_months', 3);
        $response->assertJsonPath('data.duration_formatted', '3 أشهر (90 يوم)');

        $offerId = $response->json('data.id');

        // PUT /v1/offers/{id}
        $updateResponse = $this->putJson("/api/v1/offers/{$offerId}", [
            'duration_days' => 45,
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.duration_days', 45);
        $updateResponse->assertJsonPath('data.duration_months', 1.5);
        $updateResponse->assertJsonPath('data.duration_formatted', 'شهر ونصف (45 يوم)');
    }
}
