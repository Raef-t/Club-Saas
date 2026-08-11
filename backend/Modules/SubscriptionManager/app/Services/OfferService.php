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
        $query = Offer::with(['plans' => function($q) {
            $q->where('is_active', true);
        }]);

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $offers = $query->latest()->get();

        if (isset($filters['available_only']) && filter_var($filters['available_only'], FILTER_VALIDATE_BOOLEAN)) {
            $offers = $offers->filter(function ($offer) {
                foreach ($offer->plans as $plan) {
                    if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                        return false;
                    }
                }
                return true;
            });
        }

        return $offers;
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
                'price' => $data['price'],
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
        return Offer::with('plans')->findOrFail($id);
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
