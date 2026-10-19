<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\Cycles\CyclePeriod;
use App\Domain\Money\Money;
use App\Domain\Transactions\DuplicateHash;
use App\Domain\Transactions\Merchant;
use App\Enums\CategorySource;
use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $account_id
 * @property string|null $category_id
 * @property CarbonImmutable $occurred_on
 * @property Money $amount
 * @property TransactionDirection $direction
 * @property TransactionKind $kind
 * @property TransactionStatus $status
 * @property CategorySource $category_source
 * @property string $merchant_raw
 * @property string $merchant_normalized
 * @property string $dedup_hash
 * @property string|null $paired_transfer_id
 * @property string|null $upload_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Account $account
 * @property Category|null $category
 * @property Transaction|null $pairedTransfer
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable. The normalized merchant and
     * the dedup hash are not among them: they are derived from the raw
     * fields on every write, so no path can ever let them drift.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'category_id',
        'occurred_on',
        'amount',
        'direction',
        'kind',
        'status',
        'category_source',
        'merchant_raw',
        'paired_transfer_id',
        'upload_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'immutable_date',
            'amount' => MoneyCast::class,
            'direction' => TransactionDirection::class,
            'kind' => TransactionKind::class,
            'status' => TransactionStatus::class,
            'category_source' => CategorySource::class,
        ];
    }

    /**
     * The normalized merchant and the Duplicate hash are pure functions of
     * the raw merchant, the Account, the date, and the amount. They are
     * recomputed here whenever any of those change — on entry, on edit —
     * so a stale hash can never be written by any path.
     */
    protected static function booted(): void
    {
        static::saving(function (Transaction $transaction): void {
            if (! $transaction->isDirty(['account_id', 'occurred_on', 'amount', 'merchant_raw'])) {
                return;
            }

            $transaction->merchant_normalized = Merchant::normalize($transaction->merchant_raw);
            $transaction->dedup_hash = DuplicateHash::for(
                $transaction->account_id,
                $transaction->occurred_on->toDateString(),
                $transaction->amount,
                $transaction->merchant_normalized,
            );
        });
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The other leg, when this Transfer is one half of an internal hop.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function pairedTransfer(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'paired_transfer_id');
    }

    /**
     * Only Confirmed Transactions reach the transactions page, the figures,
     * and every Budget; pending rows wait in the Inbox and Rejected rows
     * are invisible everywhere.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Confirmed);
    }

    /**
     * The rows whose occurred-on date falls inside the Cycle: on or after
     * its start, before its end.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeDuring(Builder $query, CyclePeriod $period): Builder
    {
        return $query
            ->whereDate('occurred_on', '>=', $period->startDate())
            ->whereDate('occurred_on', '<', $period->endDate());
    }

    /**
     * A Transfer linked to the opposite leg of the same internal hop is
     * money that never left the user, so it is excluded from Spent,
     * Received, and every Budget (see ADR 0005).
     */
    public function isPairedTransferLeg(): bool
    {
        return $this->kind === TransactionKind::Transfer && $this->paired_transfer_id !== null;
    }
}
