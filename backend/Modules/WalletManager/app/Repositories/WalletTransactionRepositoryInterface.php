<?php

namespace Modules\WalletManager\Repositories;

use Modules\WalletManager\Models\WalletTransaction;

interface WalletTransactionRepositoryInterface
{
    public function logTransaction(array $data): WalletTransaction;
    public function getByWalletId(int $walletId, ?int $perPage = null);
}
