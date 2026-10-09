# Contributing to laradooit

Thanks for looking. laradooit is a single-user, self-hostable finance tracker, and it is built to be extended by people who want it to understand their own bank, their own receipts, and their own way of reviewing a week. This guide covers the three things you need before your first pull request: the rule about data, the two seams tests are written at, and the branch workflow.

Read [`CONTEXT.md`](./CONTEXT.md) before touching domain logic. It defines every term the code and the issues use, and using a synonym it tells you to avoid is the fastest way to make a review awkward. Decisions that would otherwise look surprising are recorded in [`docs/adr/`](./docs/adr/); if your change contradicts one, say so in the pull request rather than quietly reversing it.

## Every fixture is synthetic and generated in this repository

No real statement, receipt, bank export, API token, or chat id may ever be committed. Not redacted, not "just for a moment", not in a branch you intend to rebase away. This is the project's one absolute rule, and it exists because a finance tracker's test data is, by definition, somebody's spending history.

Test data is produced inside the repository by a factory, a seeder, or a generator script, so that any contributor can regenerate it and anyone reading a fixture can tell at a glance that nobody lived it. That includes the receipt images and PDFs that ingestion tests need, and the model responses those tests replay: generate the file, commit the generator alongside it.

If you are debugging against your own statement, keep it outside the working tree and make sure it is not something `git add -A` could ever sweep up.

## Two test seams

Tests drive the app from the outside and assert on what a user or an operator could observe: the props an Inertia page received, a row in the database, the effect of a queued job, a reply the bot sent. They do not mock repositories, services, or models, and they do not assert on how a result was computed. If a test has to reach inside to know whether the code worked, the code is asking for a different shape.

**The HTTP seam is primary.** Pest feature tests run through Laravel's test kernel against a real PostgreSQL database with the queue running synchronously. Outbound calls to a language model or to the Telegram Bot API are faked at the HTTP boundary with responses shaped like the real ones. Controllers, jobs, Eloquent, enums, console commands, and the ingestion pipeline are all covered here, end to end, and most new behaviour belongs here.

**The pure domain seam is secondary.** Framework-free unit tests cover the rules that deserve combinatorial cases rather than a request: money arithmetic, Cycle boundaries across timezone and week start, the duplicate hash and merchant normalization, merchant rule matching and priority, Pair Candidate mutual uniqueness, the hallucination guard, and the rows and total of an Expense Export. These need no database and should stay fast enough to run on every save.

There is no third seam. React components have no tests of their own in release one; their behaviour is asserted through the props the server hands them.

PostgreSQL is the only supported database, in development and in tests alike. There is no SQLite fallback to fall back to, so point the test connection at a real server before running the suite.

## Running the checks

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Point the `DB_*` values at your PostgreSQL server, create the test database, then:

```sh
composer test        # pint --test, phpstan, pest
npm run format:check # prettier
npm run lint:check   # eslint
npm run types        # tsc --noEmit
```

`composer lint` and `npm run format` fix what the check commands complain about. CI runs all of the above plus a Docker image build on every pull request, so a green pull request is one you can expect to be mergeable.

No paid model call may ever be made from a test. The ingestion work carries an offline guard that refuses every outbound model request regardless of whether a key is configured, and the guard itself is covered by a test, so a green pull request cannot have spent money.

## Branches and pull requests

Nothing is committed directly to `master`. Every change, however small, goes on a feature branch and arrives through a pull request; the maintainer merges it.

- Branch from `master`, named for the work: `feat/telegram-pairing`, `fix/cycle-week-start`.
- Keep the pull request to one subject. Two unrelated fixes are two pull requests.
- Write the commit message and the pull request body for someone reading them in a year, and reference the issue the work came from.
- Let CI finish. If a check fails, fix it on the branch rather than asking for the merge anyway.

Work is tracked in this repository's GitHub Issues. An issue labelled `ready-for-agent` is fully specified and safe to pick up as written; one labelled `needs-triage` has not been evaluated yet, so ask before building it. The full label vocabulary is in [`docs/agents/triage-labels.md`](./docs/agents/triage-labels.md).

Release one's scope is fixed. If your idea belongs to a later release, check [`ROADMAP.md`](./ROADMAP.md) first and open an issue rather than a pull request.
