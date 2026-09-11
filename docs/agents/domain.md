# Domain docs

How to consume this repository's domain documentation before changing anything in it.

## Read first

- **`CONTEXT.md`** at the root: the glossary. One context, one file. It defines what each term *is*, never how it is implemented, and lists the synonyms to avoid for each.
- **`docs/adr/`**: the decision records. Read the ones covering the area you are about to touch. They are short on purpose; the value is in recording that a choice was made and why.

## Use the glossary's vocabulary

When your output names a domain concept — an issue title, a test name, a class, a page heading, a commit message — use the term exactly as `CONTEXT.md` defines it, and never a synonym the entry tells you to avoid. The words in the glossary are the words in the code, which is what makes both searchable.

If the concept you need is not in the glossary, stop and look at why. Usually you are inventing language the project does not use and a defined term already covers it. Occasionally there is a real gap, in which case the term gets defined before the code that needs it is written.

## Keep the glossary clean

`CONTEXT.md` is a glossary and nothing else: not a spec, not a scratch pad, not a home for implementation notes. It also carries only the terms the shipped release actually implements. A term belonging to a later release is added when the code that needs it lands, not in advance — `ROADMAP.md` is where unbuilt work is described.

## Flag ADR conflicts

If what you are about to do contradicts an ADR, surface it explicitly instead of quietly overriding it:

> _This contradicts ADR-0004 (remove means reject), but it may be worth reopening because…_

Add a new ADR only when the decision is hard to reverse, surprising without context, and the result of a real trade-off. If any of the three is missing, there is nothing worth recording. Number it one above the highest in `docs/adr/`.
