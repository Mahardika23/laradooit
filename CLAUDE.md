# laradooit

A self-hostable personal finance tracker for one user: Laravel 12 on PHP 8.4 with an Inertia 2 / React / TypeScript frontend, PostgreSQL only, receipts machine-extracted into an inbox and counted against per-category envelope budgets.

## Read before writing domain code

- **`CONTEXT.md`** at the repo root is the glossary. Every domain term the code, the issues, and the pull requests use is defined there. Use those words and avoid the synonyms each entry lists.
- **`docs/adr/`** holds the decisions a reader would otherwise find surprising. Read the ones touching the area you are about to change. If your work contradicts one, say so out loud rather than reversing it silently.
- **`CONTRIBUTING.md`** states the fixture rule and the two test seams. Both are binding on anything you write.

If a concept you need is not in the glossary, that is a signal: either you are inventing vocabulary this project does not use, or there is a genuine gap worth filling deliberately.

## Git workflow

Never commit directly to `master`. For any change, create a feature branch, commit there, and open a pull request; the user merges it themselves.

## Data rule

Every fixture is synthetic and generated in this repository. No real statement, receipt, bank export, API token, or chat id is ever committed, in any form, on any branch. Generate test data with a factory, a seeder, or a script that lives here.

## Testing

Two seams and no others. HTTP feature tests through Laravel's test kernel against a real PostgreSQL, with outbound model and Telegram calls faked at the HTTP boundary, are the primary seam. Framework-free unit tests cover pure domain rules with combinatorial cases. Never mock a repository, a service, or a model, and never assert on how a result was computed. `CONTRIBUTING.md` has the detail.

Run `composer test` for Pint, Larastan, and Pest; `npm run format:check`, `npm run lint:check`, and `npm run types` for the frontend.

## Agent conventions

### Issue tracker

Work lives in this repository's GitHub Issues (`Mahardika23/laradooit`), driven with the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

The label vocabulary is `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single context: `CONTEXT.md` at the root plus ADRs in `docs/adr/`. See `docs/agents/domain.md`.
