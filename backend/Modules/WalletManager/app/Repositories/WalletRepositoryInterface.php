<?php

namespace Modules\WalletManager\Repositories;

use Modules\WalletManager\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findByPersonId(int $personId): ?Wallet;
    public function findOrCreateForPerson(int $personId): Wallet;
    public function updateBalance(int $walletId, float $amount): Wallet;
}
