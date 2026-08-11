<?php

namespace Modules\WalletManager\Repositories;

use Modules\WalletManager\Models\Wallet;

class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function findByPersonId(int $personId): ?Wallet
    {
        return Wallet::where('person_id', $personId)->first();
    }

    public function findByPersonIdForUpdate(int $personId): ?Wallet
    {
        return Wallet::where('person_id', $personId)->lockForUpdate()->first();
    }

    public function findOrCreateForPerson(int $personId): Wallet
    {
        return Wallet::firstOrCreate(
            ['person_id' => $personId],
            ['balance' => 0, 'status' => 'active']
        );
    }

    public function updateBalance(int $walletId, float $amount): Wallet
    {
        if ($amount > 0) {
            Wallet::where('id', $walletId)->increment('balance', $amount);
        } elseif ($amount < 0) {
            Wallet::where('id', $walletId)->decrement('balance', abs($amount));
        }

        return Wallet::findOrFail($walletId);
    }
}
