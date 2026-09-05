<?php

namespace Modules\WalletManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\WalletManager\Services\WalletService;
use Modules\WalletManager\Repositories\WalletRepositoryInterface;
use Modules\WalletManager\Repositories\WalletTransactionRepositoryInterface;
use OpenApi\Attributes as OA;
use Exception;

class WalletController extends Controller
{
    protected $walletService;
    protected $walletRepository;
    protected $transactionRepository;

    public function __construct(
        WalletService $walletService,
        WalletRepositoryInterface $walletRepository,
        WalletTransactionRepositoryInterface $transactionRepository
    ) {
        $this->walletService = $walletService;
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
    }

    #[OA\Get(
        path: '/v1/people/{person_id}/wallet',
        summary: 'Get wallet details for a person',
        tags: ['WalletManager'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Success')
        ]
    )]
    #[OA\Parameter(name: 'person_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد عناصر الحركات في الصفحة', schema: new OA\Schema(type: 'string', example: '15'))]
    public function show(\Illuminate\Http\Request $request, $personId)
    {
        $wallet = $this->walletRepository->findByPersonId($personId);
        
        if (!$wallet) {
            $wallet = collect(['balance' => 0, 'status' => 'active']);
            $transactions = [];
        } else {
            $perPage = $request->has('per_page') && $request->input('per_page') !== 'all' ? (int) $request->input('per_page') : null;
            $transactions = $this->transactionRepository->getByWalletId($wallet->id, $perPage);
        }

        return response()->json([
            'data' => [
                'wallet' => $wallet,
                'transactions' => $transactions
            ]
        ]);
    }

    #[OA\Post(
        path: '/v1/people/{person_id}/wallet/deposit',
        summary: 'Deposit funds into a person\'s wallet',
        tags: ['WalletManager'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number'),
                    new OA\Property(property: 'description', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success')
        ]
    )]
    #[OA\Parameter(name: 'person_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    public function deposit(Request $request, $personId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string'
        ]);

        try {
            $result = $this->walletService->deposit($personId, $request->amount, $request->description ?? 'Deposit via API');
            
            return response()->json([
                'message' => 'Deposit successful',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
