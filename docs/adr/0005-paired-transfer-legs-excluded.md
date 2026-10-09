# Paired Transfer Legs are excluded from every money figure

When two Transfers are recognised as the two ends of one internal hop — same amount, opposite direction, different Accounts, within three days — both are marked as legs of a pair and then dropped from Spent, Received, and every Budget. Moving one's own money between one's own Accounts is not spending, and counting a leg would inflate a Category the money never left the user for.

## Consequences

- A Transfer that finds no partner stays an External Transfer and is counted in full, because a split bill or a top-up sent to another person is real money moving. Exclusion is the exception, and it has to be earned by an actual pair.
- Pairing is only automatic when each side's Pair Candidates reduce to exactly the other. Ambiguity leaves both rows in the Inbox for the user to pair by hand, since silently excluding the wrong leg hides real spending.
- Rejecting one leg breaks the link and returns the survivor to an External Transfer, so no Transaction stays excluded on the strength of a leg the user has said was wrong.
