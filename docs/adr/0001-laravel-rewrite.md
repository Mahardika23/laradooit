# Rewrite the tracker in Laravel rather than open up the existing app

laradooit exists because a private Next.js app already runs this domain every day, but it cannot be handed to anyone: it logs in through one chat bot, refuses to boot without a model key, and carries its author's own statements through its schema, tests, and docs. Rather than strip that codebase down in place, we rebuilt the product in Laravel 12 with React kept for the UI, treating the old app's domain rules as reference material and copying none of its code, docs, styling, or fixtures.

## Considered options

- **Open-source the existing app.** Rejected: scrubbing personal data out of a live schema, its fixtures, and its history is slower and less trustworthy than starting clean, and it leaves the author without a private place to try ideas.
- **Port incrementally, module by module.** Rejected: the two stacks share no runtime, so an incremental port is a rewrite with extra coordination.

## Consequences

- The domain rules are proven but the code implementing them is new, so every rule earns its own test here rather than inheriting confidence from the older app.
- There are two codebases. The private app stays the place where new domain ideas are tried; laradooit follows once an idea holds up.
- There is no data migration between them, and none is planned. Moving in means starting from an empty instance or a seeded demo.
