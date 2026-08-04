<?php

namespace Modules\WalletManager\Services;

use Modules\WalletManager\Repositories\WalletRepositoryInterface;
use Modules\WalletManager\Repositories\WalletTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class WalletService
{
    protected WalletRepositoryInterface $walletRepository;
    protected WalletTransactionRepositoryInterface $transactionRepository;

    public function __construct(
        WalletRepositoryInterface $walletRepository,
        WalletTransactionRepositoryInterface $transactionRepository
    ) {
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Deposit funds into a person's wallet.
     */
    public function deposit(int $personId, float $amount, ?string $description = 'Deposit'): array
    {
        if ($amount <= 0) {
            throw new Exception(__('Amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($personId, $amount, $description) {
            $wallet = $this->walletRepository->findOrCreateForPerson($personId);

            $this->walletRepository->updateBalance($wallet->id, $amount);

            $transaction = $this->transactionRepository->logTransaction([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
                'description' => $description,
                'created_by' => Auth::id(),
            ]);

            return [
                'wallet' => $wallet->refresh(),
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Pay for a service using the wallet balance.
     */
    public function pay(int $personId, float $amount, ?string $description = 'Payment', ?string $refType = null, ?int $refId = null): array
    {
        if ($amount <= 0) {
            throw new Exception(__('Amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($personId, $amount, $description, $refType, $refId) {
            $wallet = $this->walletRepository->findByPersonIdForUpdate($personId);

            if (!$wallet || $wallet->balance < $amount) {
                throw new Exception(__('Insufficient wallet balance.'));
            }

            if (!$wallet->isActive()) {
                throw new Exception(__('Wallet is inactive.'));
            }

            $this->walletRepository->updateBalance($wallet->id, -$amount);

            $transaction = $this->transactionRepository->logTransaction([
                'wallet_id' => $wallet->id,
                'amount' => -$amount,
                'type' => 'payment',
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => $description,
                'created_by' => Auth::id(),
            ]);

            return [
                'wallet' => $wallet->refresh(),
                'transaction' => $transaction,
            ];
        });
    }
}
