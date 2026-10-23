# Focused source components

These tests extract actual named functions without loading a game or database. External zones, metadata, draw/move, payment and await boundaries are instrumented doubles. They do not replace supported four-live action recordings, serializer checks, clean replays or independent saved-fixture review.

From repository root in PowerShell:

```powershell
$repo = (Get-Location).Path
php DevTools/FaB/printed_keyword_test.php
php DevTools/FaB/CardRegression/components/gorganian_resolution_component.php $repo
php DevTools/FaB/CardRegression/components/declared_reaction_target_component.php $repo
$gameSHA = (Get-FileHash FaBSim/Custom/GameLogic.php -Algorithm SHA256).Hash.ToLowerInvariant()
$choicesSHA = (Get-FileHash FaBSim/Custom/CardChoices.php -Algorithm SHA256).Hash.ToLowerInvariant()
$arcSHA = (Get-FileHash FaBSim/Custom/ARCAbilities.php -Algorithm SHA256).Hash.ToLowerInvariant()
php DevTools/FaB/CardRegression/components/prepared_payment_component.php "repo=$repo" "gameSHA=$gameSHA" "choicesSHA=$choicesSHA" "arcSHA=$arcSHA"
```

Observed contracts:32 keyword cases;36 Gorganian profiles/541 assertions;30 reaction profiles/150 assertions;19 payment contracts/116 assertions. These are different component scopes, not card-coverage totals. The payment helper requires current whole-file hashes and reports selected function hashes.

Optional in-memory faults append `missing-addend` or `foreign-owner-excluded` to the Gorganian command, or `ignore-bound-link` or `lose-snag-tag` to the reaction command. Each must fail at the named intended assertion; source files are never mutated. The retained component copies differ from executed scratch copies only in portable default repository paths/comment text. Explicit repository arguments retain the executed behavior.

See ../REPAIR_HANDOFF_20261022.md for the HTTP521 generation blocker, known failing ELE check and missing native qualification. Do not record incorrect behavior as passing or run the full suite automatically.
