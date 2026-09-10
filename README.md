# laradooit

A self-hostable personal finance tracker for one person. Receipts and statements go in, get machine-extracted, and land in an inbox you clear once a week. Confirmed spending is counted against per-category envelope budgets.

laradooit is a Laravel rewrite of a private Next.js app that has been in daily use for a while. The domain rules are proven; the code here is new.

## Status

Pre-release. Nothing runs yet. The release-one spec is [issue #1](https://github.com/Mahardika23/laradooit/issues/1) and the work is broken into [ready-for-agent issues](https://github.com/Mahardika23/laradooit/issues?q=is%3Aissue+is%3Aopen+label%3Aready-for-agent).

## What release one will do

- Accounts, categories, and monthly or weekly budgets
- Manual entry of cash spending, built for batch entry once a week
- Upload a receipt image or PDF from the web, or send it to a Telegram bot, and have it extracted into pending transactions
- An inbox to confirm, recategorize, or reject what was extracted, keyboard first
- Merchant rules so recurring merchants skip the inbox
- Transfer pairing so internal moves between your own accounts never count as spending
- CSV export of confirmed spending

Statement import and reconciliation come in release two. Delivery-platform order tracking comes in release three.

## Stack

- Laravel 12 on PHP 8.4
- Inertia 2 with React and TypeScript, Tailwind 4, shadcn/ui
- PostgreSQL only
- OpenRouter for extraction, optional
- Telegram via Nutgram, optional
- Docker Compose for self-hosting

## Principles

- One instance, one user. No tenants.
- Money is integer minor units. No floats.
- Removing a transaction rejects it. Nothing is deleted, so the same statement line is never re-ingested.
- Every test fixture is synthetic and generated in this repo. No real statement, receipt, or token is ever committed.

## License

MIT.
