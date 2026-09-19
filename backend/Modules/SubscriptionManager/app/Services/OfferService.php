<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Models\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class OfferService
{
    /**
     * Get all offers based on filters.
     */
    public function getAllOffers(array $filters = [])
    {
        $query = Offer::with([
            'branch:id,name',
            'plans' => function($q) {
                $q->whereIn('status', ['active', 'completed'])
                  ->with(['planActivities.staffActivity.activity.activityType']);
            }
        ]);

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['offer_type']) && in_array($filters['offer_type'], [Offer::TYPE_BUNDLE, Offer::TYPE_SINGLE_CHOICE], true)) {
            $query->where('offer_type', $filters['offer_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['activity_type_id']) && $filters['activity_type_id'] !== '' && $filters['activity_type_id'] !== 'all') {
            $query->whereHas('plans', function($q) use ($filters) {
                $q->forActivityType($filters['activity_type_id']);
            });
        }

        $query->latest();

        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            $offers = $query->get();

            if (isset($filters['available_only']) && filter_var($filters['available_only'], FILTER_VALIDATE_BOOLEAN)) {
                $offers = $offers->filter(function ($offer) {
                    if (!$offer->is_active || !$offer->isDateValid()) {
                        return false;
                    }

                    if ($offer->isBundle()) {
                        foreach ($offer->plans as $plan) {
                            $current = method_exists($plan, 'getCurrentSubscribersCount') ? $plan->getCurrentSubscribersCount() : (int) $plan->current_subscribers;
                            if ($plan->max_subscribers > 0 && $current >= $plan->max_subscribers) {
                                return false;
                            }
                        }
                        return true;
                    } else {
                        // single_choice: at least one plan must have capacity
                        foreach ($offer->plans as $plan) {
                            $current = method_exists($plan, 'getCurrentSubscribersCount') ? $plan->getCurrentSubscribersCount() : (int) $plan->current_subscribers;
                            if ($plan->max_subscribers == 0 || $current < $plan->max_subscribers) {
                                return true;
                            }
                        }
                        return false;
                    }
                });
            }

            return $offers;
        }

        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    /**
     * Create a new offer.
     */
    public function createOffer(array $data)
    {
        return DB::transaction(function () use ($data) {
            $offer = Offer::create([
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'offer_type' => $data['offer_type'] ?? Offer::TYPE_BUNDLE,
                'price' => $data['price'],
                'duration_days' => $data['duration_days'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
            ]);

            $offer->plans()->sync($data['plans']);
            
            return $offer->load('plans');
        });
    }

    /**
     * Get offer by ID.
     */
    public function getOfferById(int $id)
    {
        return Offer::with(['branch:id,name', 'plans.planActivities.staffActivity.activity.activityType'])->findOrFail($id);
    }

    /**
     * Update an offer.
     */
    public function updateOffer(int $id, array $data)
    {
        $offer = Offer::findOrFail($id);

        return DB::transaction(function () use ($offer, $data) {
            $offer->update($data);

            if (isset($data['plans'])) {
                $offer->plans()->sync($data['plans']);
            }

            return $offer->load('plans');
        });
    }

    /**
     * Delete an offer.
     */
    public function deleteOffer(int $id)
    {
        $offer = Offer::findOrFail($id);
        $offer->delete();
        
        return true;
    }
}
