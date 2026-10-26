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

Intimidate checks retained after qualification:10 marker contracts/15 checks and20 beginning-end-phase group contracts/29 checks. The copies differ from executed scratch scripts only in their portable default repository path; explicit-argument execution is unchanged. Lint passed after retention.

```powershell
$rosSHA = (Get-FileHash FaBSim/Custom/ROSCards.php -Algorithm SHA256).Hash.ToLowerInvariant()
php DevTools/FaB/CardRegression/components/intimidate_marker_component.php $repo $gameSHA
php DevTools/FaB/CardRegression/components/intimidate_return_component.php $repo $gameSHA $rosSHA
```

See [current repair handoff](../REPAIR_HANDOFF_20261024.md). Generation and focused qualification passed; broad compatibility remains unrun by request. Older22October failure receipts are historical.

## Captured card triggers

Fourteen contracts exercise actual extracted queue, dispatch, token-creation and Phantasm-resolution functions with controlled doubles. They cover per-grant capture, event-time suppression, source independence, creation-time modifiers and pending-layer priority. Actual physical movement, all-seat responses and token ownership are covered by the separate four-live regressions.

```powershell
$repo = (Get-Location).Path
$triggerHashes = @('GameLogic.php','CRUCards.php','EVRCards.php','ROSCards.php','ARCCards.php') | ForEach-Object { (Get-FileHash -LiteralPath "FaBSim/Custom/$_" -Algorithm SHA256).Hash.ToLowerInvariant() }
& 'C:\xampp\php\php.exe' DevTools/FaB/CardRegression/components/queued_card_trigger_component.php $repo @triggerHashes
```

The explicit hashes bind the actual current files; the existing shared component extractor is reused. No external proposal manifest, engine bootstrap or game storage is needed.

## Briar first attack-action damage trigger

Thirteen controlled contracts exercise actual extracted ELE damage/marker and ROS queue/dispatch functions. They check first-event eligibility, no early output, source capture, duplicate suppression and earned-source independence. Controlled dependencies do not add native variant, suppression-implementation or arcane-driver coverage. The unfused Stir four-live fixture separately tests real damage and all-seat responses.

```powershell
$repo = (Get-Location).Path
$briarHashes = @('ELECards.php','ROSCards.php') | ForEach-Object { (Get-FileHash -LiteralPath "FaBSim/Custom/$_" -Algorithm SHA256).Hash.ToLowerInvariant() }
& 'C:\xampp\php\php.exe' DevTools/FaB/CardRegression/components/briar_first_damage_trigger_component.php $repo @briarHashes
```

Supply the reviewed source hashes deliberately; the pure shared extractor is reused, with no engine bootstrap or game storage. See the batches 199–200 checkpoint for the qualified source versions and limits.

## Native activated-ability lifetime

Fifteen contracts extract the actual admission, await-frame tracker and work/pending gates. Three in-memory mutants must fail at contracts01 (admission),05 (frame ownership) and12 (frame-only work). External state and queues are controlled; actual AP/refund and source completion are covered by the separate Dreadbore/Opal/Deja/Rune and synchronous-item native witnesses.

```powershell
$gamePath = Join-Path (Get-Location).Path 'FaBSim/Custom/GameLogic.php'
& 'C:\xampp\php\php.exe' DevTools/FaB/CardRegression/components/native_ability_frame_component.php $gamePath none
```

Append `classifier-allowlist`, `frame-allowlist` or `ignore-frame-only` instead of `none` for the mutation controls. Each must exit1 at its named contract; the ordinary run exits0. Source SHA82a10388 and the existing shared tokenizer hash are guarded. The retained bytes are identical to the executed portable copy; no duplicate validation run was needed.
