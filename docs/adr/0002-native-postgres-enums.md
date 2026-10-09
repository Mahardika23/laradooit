# Enumerated columns are native Postgres enum types

Every fixed-vocabulary column — transaction kind, direction, status, account type, budget period, category source — is a native Postgres enum type created with a raw statement in its migration and cast to a PHP backed enum on the model. The database is then the last line of defence: a row with a nonsense status cannot exist, whatever writes it, and the values are readable in `psql` without consulting application code.

## Considered options

- **Plain text columns with validation in PHP only.** Rejected: it puts the whole vocabulary in application code, where a bad job, a seeder, or a hand-written SQL fix can quietly write garbage.
- **Text columns with check constraints.** Workable, but the constraint duplicates the vocabulary in a place nothing reads, and altering it is no cheaper than altering a type.

## Consequences

- Adding a value is a migration. That is the point: the vocabulary changes deliberately.
- Renaming or removing a value needs a hand-written migration that creates the new type, rewrites the column, and drops the old one. We accept that cost; it is rare and it is loud.
- The schema is Postgres-specific, which is already true of this project. There is no SQLite fallback, in development or in tests.
