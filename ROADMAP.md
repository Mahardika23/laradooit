# Roadmap

Release one is the weekly loop: accounts, categories and budgets; manual entry of cash spending; receipts captured from the web or from Telegram and machine-extracted into an inbox; merchant rules; transfer pairing; and a CSV export of confirmed spending. It is what the repository is being built toward now, broken into issues and tracked in GitHub.

What follows is the order the rest is expected to arrive in. It is here so contributors know what not to build yet, not as a promise of dates.

## Release two: statements and reconciliation

Release one treats an uploaded statement as just another receipt: rows are extracted, deduplicated, and reviewed, and the parsed lines are thrown away afterwards. Release two keeps them. Statement import retains a statement's lines as first-class records so that a month of them can be compared against what the app actually recorded, producing the two lists that matter: lines the app has never seen, which become inbox rows with one click, and recorded transactions that no statement backs up, which usually means the money was attributed to the wrong account. That comparison is reconciliation, and it is the feature that turns the tracker from a record of what was captured into a record that can be trusted.

Alongside it come balance checkpoints. Every statement closes with a balance, and two consecutive closing balances imply exactly how much moved in between. When the confirmed transactions for that stretch do not add up to the same number, something is missing, and the app should say so rather than quietly show a tidy budget built on an incomplete month.

Release two is also where bank-specific parsers arrive: a per-institution reader that turns a known statement format into lines directly, at no model cost and with no extraction risk. Release one deliberately has none, because a parser is only worth writing once there is something to keep the parsed rows for. The parsers are the natural first contribution for someone whose bank is not yet covered, so the interface they implement is documented when it lands.

## Release three: orders and delivery insights

Release three adds the platform side of an online purchase. A delivery order carries detail a bank line never will: which vendor actually cooked the food, what each item cost, what the delivery fee was, and what a promotion took off the total. Release three captures that as an order, links it to the transaction that paid for it, and leaves the transaction as the money truth: an order enriches a transaction, and on its own it never counts toward spending or a budget.

Orders arrive through a channel release one does not have. Platform confirmation emails are the natural source, so release three opens an email ingestion channel, which also broadens capture beyond a web upload and a chat message.

With item-level detail recorded, the budgets page gains a section that reads it: what each vendor cost over a cycle, what was paid in fees and saved in discounts, and which items quietly cost more than the last time they were bought. Those are the delivery insights, and like everything else built on orders, the section reports on spending without ever adding to it.

## Not planned

Multi-user and multi-tenant support are out of scope permanently: one instance serves one person, which is what lets the schema stay free of tenant columns and the app ship without an invitation flow. A plugin system and support for any database other than PostgreSQL are out too, for the same reason: each would cost more in complexity everywhere than it returns for the person this is built for. Several currencies inside one instance are not on any release above, either; an instance holds one base currency, and the stored shape of money leaves the door open without anybody having to walk through it.
