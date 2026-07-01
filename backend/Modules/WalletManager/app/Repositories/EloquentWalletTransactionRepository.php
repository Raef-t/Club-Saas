<?php

namespace Modules\WalletManager\Repositories;

use Modules\WalletManager\Models\WalletTransaction;

class EloquentWalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    public function logTransaction(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function getByWalletId(int $walletId, int $perPage = 15)
    {
        return WalletTransaction::where('wallet_id', $walletId)
            ->with(['reference', 'createdBy'])
            ->latest()
            ->paginate($perPage);
    }
}
