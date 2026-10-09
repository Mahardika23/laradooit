# Money is a project-owned value object of integer minor units

Money is a small value object holding an integer count of minor units plus a currency code, persisted through an Eloquent cast over a bigint column. Arithmetic is integer-only, floats are refused rather than rounded, and amounts are stored positive with direction kept as a separate field, so a sign error can never turn spending into income. Nothing in a money path ever sees a float.

## Considered options

- **A decimal or float column read as a PHP number.** Rejected: float arithmetic loses cents, and `decimal` still arrives in PHP as a string or float that invites arithmetic nobody audited.
- **A third-party money library.** Rejected for release one: the object we need is add, subtract, compare, zero, and format against one instance currency. A dependency for that buys API surface we would have to constrain anyway.

## Consequences

- The instance has one base currency, set in configuration. Multi-currency and FX are out of scope; the currency code rides along so that adding them later is a change to arithmetic, not a change to storage.
- Every money-bearing feature depends on this object, so it is worth unit tests at the pure-domain seam rather than only through HTTP.
