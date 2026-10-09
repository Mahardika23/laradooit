# laradooit

A self-hostable personal finance tracker for one person: receipts arrive, get machine-extracted, wait in an inbox until the user clears it, and count against per-category envelope budgets once confirmed.

This file is the glossary and nothing else. It holds only the terms release one implements; later releases add their own terms when the code that needs them lands. Decisions live in [`docs/adr/`](./docs/adr/).

## Language

### Accounts, Categories, and Cycles

**Account**:
A place money sits or moves through — a bank account, an e-wallet, or cash in hand. Every Transaction names exactly one Account.
_Avoid_: Wallet, source, bank

**Category**:
A bucket money is filed under, optionally nested beneath a parent Category so spending can be read at more than one level.
_Avoid_: Tag, label, bucket

**Budget**:
The amount a Category is allowed for one Cycle, set as a monthly or a weekly envelope. A Category without a Budget is still tracked; it simply has no limit to measure against.
_Avoid_: Limit, allowance, target

**Cycle**:
The stretch of time money is measured against: the calendar month, which is what the transactions page shows, or the calendar week for a weekly Budget. Its boundaries follow the instance's timezone and its week start day, so a purchase near midnight lands on the side the user expects.
_Avoid_: Period, billing month, range

### Transactions

**Transaction**:
One movement of money on one Account: a date, an amount, a direction, a merchant, and a kind. Every figure the app reports is a sum of Transactions.
_Avoid_: Entry, record, line

**Manual Entry**:
A Transaction the user typed in. It is trusted on arrival, so it is born Confirmed with a Category the user had to choose, and it never reaches the Inbox.
_Avoid_: Manual upload, quick add

**Ingested Transaction**:
A Transaction machine-extracted from an Upload rather than typed. It is untrusted until a human says otherwise, so it starts pending in the Inbox unless a Merchant Rule claims it first.
_Avoid_: Imported transaction, extracted row

**Confirmed**:
The state of a Transaction the user has vouched for, whether by typing it, accepting it at the Inbox, or letting a Merchant Rule file it. Only Confirmed Transactions reach the transactions page and count toward a Budget.
_Avoid_: Accepted, approved, verified

**Rejected**:
The state of a Transaction the user has dismissed, either by declining it at the Inbox or by removing it after it was Confirmed. It is invisible everywhere, but it is kept, so its Duplicate memory stops the same row from being ingested again.
_Avoid_: Deleted, discarded, archived

**Duplicate**:
A Transaction sharing its Account, date, amount, and normalized merchant with one already recorded. An ingested Duplicate is dropped without a word; a Manual Entry that would be a Duplicate is warned about and still allowed, because only the user knows whether two identical purchases really happened.
_Avoid_: Repeat, double entry, collision

### Capture and review

**Capture**:
One arrival of a file through an ingestion channel: a web upload, or a document or photo sent to the Telegram bot. The Capture is the arrival; the Upload is what it leaves behind.
_Avoid_: Import, ingest, submission

**Upload**:
The stored record of a Capture — the file itself, kept so the user can always go back to the source, together with what extracting it produced or why it failed. Every Ingested Transaction traces back to one Upload.
_Avoid_: File, attachment, document

**Merchant Rule**:
A user-written pattern on a merchant name that files matching Ingested Transactions into a Category as Confirmed, skipping the Inbox entirely. Rules carry a priority, and the first match wins.
_Avoid_: Auto-rule, mapping, filter

**Inbox**:
The queue of pending Ingested Transactions waiting on human judgement. Nothing else passes through it: a Manual Entry and a rule-filed row never appear there.
_Avoid_: Pending list, review queue, drafts

**Weekly Review**:
The user's dominant workflow, run about once a week: batch-enter the week's cash and untracked spending as Manual Entries, then clear the Inbox to empty. It is the loop every release-one screen is designed around, which is why the review screens are driven from the keyboard.
_Avoid_: Triage, review session, cleanup

### Transfers

**Transfer**:
A Transaction that moves money without buying anything — between two of the user's own Accounts, or to and from another person.
_Avoid_: Move, payment, send

**Pair Candidate**:
A pending Transfer that could plausibly be the other leg of a given Transfer: the same amount, the opposite direction, a different Account, and a date within three days either way. Pairing happens on its own only when each side's Candidates reduce to exactly the other; zero or several on either side leaves both legs pending, because the app never guesses at where money went.
_Avoid_: Match, suggestion, sibling

**Paired Transfer Leg**:
A Transfer linked to the opposite leg of the same internal hop. Together the two legs are money that never left the user, so each leg is excluded from Spent, Received, and every Budget.
_Avoid_: Internal transfer, matched transfer

**External Transfer**:
A Transfer with no paired leg, usually because the counterparty is another person rather than another Account of the user's. It is money genuinely leaving or arriving, so it counts in Spent, Received, and Budgets like any other Transaction.
_Avoid_: Unpaired transfer, one-sided transfer

### Figures

**Spent**:
The sum of Confirmed outflows over a range, of every kind, including outgoing External Transfers and excluding Paired Transfer Legs. Inflows never net against it.
_Avoid_: Total, outgoings, expenses

**Received**:
The sum of Confirmed inflows over a range, on the same terms as Spent: incoming External Transfers count, Paired Transfer Legs do not, and outflows never net against it.
_Avoid_: Income, earnings, credits

**Expense Export**:
A CSV download of the Confirmed outflows currently in view on the transactions page — same filters, same rows, outflows only, uncapped. Its amount total equals the Spent figure on screen at the moment of export, and an inflow is never in it.
_Avoid_: CSV dump, transaction export, report
