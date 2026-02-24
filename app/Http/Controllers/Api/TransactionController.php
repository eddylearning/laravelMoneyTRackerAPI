<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function store(Request $request, Wallet $wallet): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction = $wallet->transactions()->create($validated);

        return response()->json([
            'message' => 'Transaction created successfully.',
            'data' => [
                'id' => $transaction->id,
                'wallet_id' => $transaction->wallet_id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'description' => $transaction->description,
            ],
        ], 201);
    }
}
