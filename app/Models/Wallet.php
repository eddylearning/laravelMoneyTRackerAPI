<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    protected $appends = [
        'balance',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getBalanceAttribute(): float
    {
        if ($this->relationLoaded('transactions')) {
            return $this->calculateBalance($this->transactions);
        }

        $totals = $this->transactions()
            ->selectRaw("type, COALESCE(SUM(amount), 0) as total")
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = (float) ($totals['income'] ?? 0);
        $expense = (float) ($totals['expense'] ?? 0);

        return $income - $expense;
    }

    /**
     * @param Collection<int, Transaction> $transactions
     */
    private function calculateBalance(Collection $transactions): float
    {
        $income = $transactions
            ->where('type', 'income')
            ->sum(fn (Transaction $transaction) => (float) $transaction->amount);

        $expense = $transactions
            ->where('type', 'expense')
            ->sum(fn (Transaction $transaction) => (float) $transaction->amount);

        return $income - $expense;
    }
}
