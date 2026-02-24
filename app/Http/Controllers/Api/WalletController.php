<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $wallet = Wallet::create($validated);

        return response()->json([
            'message' => 'Wallet created successfully.',
            'data' => $wallet,
        ], 201);
    }

    public function show(Wallet $wallet): JsonResponse
    {
        $wallet->load('transactions');

        return response()->json([
            'data' => [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'name' => $wallet->name,
                'balance' => $wallet->balance,
                'transactions' => $wallet->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at,
                    ];
                }),
            ],
        ]);
    }
}
