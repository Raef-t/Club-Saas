<?php

namespace Modules\WalletManager\Repositories;

use Modules\WalletManager\Models\WalletTransaction;

class EloquentWalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    public function logTransaction(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function getByWalletId(int $walletId, ?int $perPage = null)
    {
        $query = WalletTransaction::where('wallet_id', $walletId)
            ->with(['reference', 'createdBy'])
            ->latest();

        if ($perPage === null) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }
}
