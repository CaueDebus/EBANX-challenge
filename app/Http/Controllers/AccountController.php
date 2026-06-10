<?php

namespace App\Http\Controllers;

use App\Http\Requests\BalanceRequest;
use App\Http\Requests\EventRequest;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accountService)
    {
    }

    public function reset(): Response
    {
        $this->accountService->reset();

        return response('OK', 200);
    }

    public function balance(BalanceRequest $request): Response
    {
        $accountId = (string) $request->query('account_id');
        $balance = $this->accountService->getBalance($accountId);

        if ($balance === null) {
            return response('0', 404);
        }

        return response((string) $balance, 200);
    }

    public function event(EventRequest $request): JsonResponse|Response
    {
        $data = $request->validated();

        return match ($data['type']) {
            'deposit' => $this->handleDeposit($data),
            'withdraw' => $this->handleWithdraw($data),
            'transfer' => $this->handleTransfer($data),
        };
    }

    private function handleDeposit(array $data): JsonResponse
    {
        $result = $this->accountService->deposit(
            (string) $data['destination'],
            $data['amount'],
        );

        return response()->json($result, 201);
    }

    private function handleWithdraw(array $data): JsonResponse|Response
    {
        $result = $this->accountService->withdraw(
            (string) $data['origin'],
            $data['amount'],
        );

        if ($result === null) {
            return response('0', 404);
        }

        return response()->json($result, 201);
    }

    private function handleTransfer(array $data): JsonResponse|Response
    {
        $result = $this->accountService->transfer(
            (string) $data['origin'],
            (string) $data['destination'],
            $data['amount'],
        );

        if ($result === null) {
            return response('0', 404);
        }

        return response()->json($result, 201);
    }
}
