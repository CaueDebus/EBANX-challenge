<?php

namespace App\Services;

class AccountService extends Service
{
    /** @var array<string, int|float> */
    private static array $accounts = [];

    public function reset(): void
    {
        self::$accounts = [];
    }

    public function getBalance(string $accountId): int|float|null
    {
        return self::$accounts[$accountId] ?? null;
    }

    public function deposit(string $destinationId, int|float $amount): array
    {
        self::$accounts[$destinationId] = (self::$accounts[$destinationId] ?? 0) + $amount;

        return [
            'destination' => [
                'id' => $destinationId,
                'balance' => self::$accounts[$destinationId],
            ],
        ];
    }

    public function withdraw(string $originId, int|float $amount): array|null
    {
        // Rejeita se a conta não existe ou se o saldo é insuficiente para evitar saldo negativo
        if (!isset(self::$accounts[$originId]) || self::$accounts[$originId] < $amount) {
            return null;
        }

        self::$accounts[$originId] -= $amount;

        return [
            'origin' => [
                'id' => $originId,
                'balance' => self::$accounts[$originId],
            ],
        ];
    }

    public function transfer(string $originId, string $destinationId, int|float $amount): array|null
    {
        // Rejeita a transferência inteira se a origem não existe ou tem saldo insuficiente, ou se a conta de destino não existe
        // garantindo que nenhuma das contas seja alterada em caso de falha
        if (!isset(self::$accounts[$originId]) || self::$accounts[$originId] < $amount || !isset(self::$accounts[$destinationId])) {
            return null;
        }

        self::$accounts[$originId] -= $amount;
        self::$accounts[$destinationId] = (self::$accounts[$destinationId] ?? 0) + $amount;

        return [
            'origin' => [
                'id' => $originId,
                'balance' => self::$accounts[$originId],
            ],
            'destination' => [
                'id' => $destinationId,
                'balance' => self::$accounts[$destinationId],
            ],
        ];
    }
}
