<?php

namespace App\Http\Controllers;

use App\Domain\Cycles\Cycle;
use App\Domain\Money\Money;
use App\Enums\CategorySource;
use App\Enums\TransactionDirection;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Http\Requests\Transactions\StoreManualEntryRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\CategoryOptions;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $timezone = (string) config('app.timezone');
        $period = Cycle::monthContaining($this->anchorMonth($request, $timezone), $timezone);

        $transactions = Transaction::confirmed()
            ->during($period)
            ->with(['account', 'category'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->get();

        [$spent, $received] = $this->totals($transactions);

        return Inertia::render('transactions/index', [
            'transactions' => $transactions->map(fn (Transaction $transaction): array => $this->presentRow($transaction))->values(),
            'cycle' => [
                'label' => $period->start->translatedFormat('F Y'),
                'param' => $period->start->format('Y-m'),
                'previous' => $period->start->subMonth()->format('Y-m'),
                'next' => $period->start->addMonth()->format('Y-m'),
            ],
            'totals' => [
                'spent' => $spent->format(),
                'received' => $received->format(),
            ],
            'accounts' => Account::active()->orderBy('name')->get()
                ->map(fn (Account $account): array => ['id' => $account->id, 'name' => $account->name])
                ->values(),
            'categories' => CategoryOptions::inTreeOrder(),
            'directions' => array_map(
                fn (TransactionDirection $direction): array => ['value' => $direction->value, 'label' => $direction->label()],
                TransactionDirection::cases(),
            ),
            'kinds' => array_map(
                fn (TransactionKind $kind): array => ['value' => $kind->value, 'label' => $kind->label()],
                TransactionKind::cases(),
            ),
            'today' => CarbonImmutable::now($timezone)->toDateString(),
        ]);
    }

    /**
     * A Manual Entry is trusted on arrival: it is born Confirmed, with the
     * Category source recording that a human chose it, and it never passes
     * through the Inbox.
     */
    public function store(StoreManualEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Transaction::create([
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'],
            'occurred_on' => $data['occurred_on'],
            'amount' => Money::fromDecimal($data['amount'], (string) config('finance.currency')),
            'direction' => TransactionDirection::from($data['direction']),
            'kind' => TransactionKind::from($data['kind']),
            'merchant_raw' => $data['merchant'],
            'status' => TransactionStatus::Confirmed,
            'category_source' => CategorySource::Manual,
        ]);

        return to_route('transactions.index');
    }

    /**
     * The month the page shows: the cycle query parameter when it names a
     * valid month, the current month otherwise. The instance timezone is
     * what decides which month "current" is.
     */
    private function anchorMonth(Request $request, string $timezone): CarbonImmutable
    {
        $cycle = $request->query('cycle');

        if (is_string($cycle) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $cycle) === 1) {
            return CarbonImmutable::parse("{$cycle}-01", $timezone);
        }

        return CarbonImmutable::now($timezone);
    }

    /**
     * Spent and Received over the rows in view. Paired Transfer Legs are
     * excluded — moving one's own money between one's own Accounts is
     * neither — and the two sums never net against each other.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array{Money, Money}
     */
    private function totals(Collection $transactions): array
    {
        $spent = Money::zero((string) config('finance.currency'));
        $received = Money::zero((string) config('finance.currency'));

        foreach ($transactions as $transaction) {
            if ($transaction->isPairedTransferLeg()) {
                continue;
            }

            if ($transaction->direction === TransactionDirection::Out) {
                $spent = $spent->add($transaction->amount);
            } else {
                $received = $received->add($transaction->amount);
            }
        }

        return [$spent, $received];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'occurredOn' => $transaction->occurred_on->toDateString(),
            'merchant' => $transaction->merchant_raw,
            'amount' => $transaction->amount->format(),
            'direction' => $transaction->direction->value,
            'kind' => $transaction->kind->value,
            'kindLabel' => $transaction->kind->label(),
            'category' => $transaction->category?->name,
            'account' => $transaction->account->name,
            'internal' => $transaction->isPairedTransferLeg(),
        ];
    }
}
