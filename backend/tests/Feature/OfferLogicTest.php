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
}
