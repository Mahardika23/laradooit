# Removing a Transaction rejects it rather than deleting it

"Remove" on a Confirmed Transaction, and "reject" on an Inbox row, both mark the Transaction Rejected. Nothing is deleted. A Rejected Transaction is invisible on every page and in every figure, but it stays in the table, because it is what remembers that this exact line — same Account, date, amount, and normalized merchant — has already been seen and judged.

## Consequences

- Re-uploading the same receipt or statement after rejecting its rows produces nothing new, which is the behaviour the user wants: a mistake dismissed once stays dismissed.
- Every query that feeds a page, an export, or a Budget has to filter on status. A query that forgets to will show rejected rows, so this is exercised at the HTTP seam rather than trusted.
- The table grows with rows nobody will ever look at. That is an acceptable price for the deduplication memory.
