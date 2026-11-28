> **Historical checkpoint below.** The [latest20November continuation](REPAIR_CONTINUATION_20261120.md) has4,180 qualified scenarios,75/93 original records resolved in tested scope and18open. Dates/runtimes/limits below retain their historical scope.

> **Historical checkpoint below.** Superseded by the [20 November repair handoff](REPAIR_CHECKPOINT_20261120.md): 4,176 qualified scenarios;71/93 records resolved in tested scope;22 open. The service input block is resolved; Pox is partially repaired. Earlier runtimes, counts and blockers below describe their stated dates.

# Remaining FaBSim repairs — 17 November 2026

Repair-only pause for review. No new breadth batch was started. **4,173 saved, qualified and registered scenarios: 3,074 duel + 1,099 genuinely four-live; no saved-only pending case.** Coverage is **1,325/5,196 exact primary identities and 1,199/3,324 pitch-cycle/standalone groups**, with four-live evidence in 1,091 groups. Untested pitch siblings and branches get no credit. Counts preserve actual historical qualification runtimes; they are not a whole-suite pass on final `5a8c088126ff8d9c581c7eb97e4b9cc77511e4011c8fd3faed3a5328b7b47379`.

**68 of the original 93 issue records are resolved in tested scope; 25 remain open.** Records overlap engine defects. This is not a count of 68 distinct bugs and is not a completed or merge-ready repair handoff.

## Completed during this triage

- **073/102 Tremor of Iarathael:** one duplicate conditional +2 was removed from `FaBSim/Custom/IARCards.php`; the canonical/emitted MON +2 remains. Original Boost route passed unchanged at runtime `d834`: 56 actions/4,389 assertions, actual power6/damage6/P4HP10/full close. Saved once, independently exact-reviewed and clean-replayed once. Original Nimble route passed 61/4,675 in enforced no-save mode, actual P4HP6/full close; it adds no second fixture/group. The old 073 authorization label was not supported by an actual denied073/102 operation in the inspected denial ledger; this was an independent repair under the latest instruction, not a denied-operation retry. [Qualified proof](review-evidence/tremor073102-d834-qualified-saved.json), [source/saved review bundle](review-evidence/tremor073102-review-bundle-20261117.json).
- **200 Malice, partial only:** premature Zombie death banishment/Corpse creation now earns a synchronous responseable layer, captures controller and exact graveyard entry, and uses existing movement epochs. `GameLogic.php`, `IARRuntime.php`, `ROSCards.php` changed. The separate Malice-only witness passed 32/1,155, clean replay1/1, and existing normal Malice activation compatibility1/1 at final `5a8c`. It proves real lethal6 to a life2 Zombie, public graveyard before four fresh responses, same host banished face-down, one owned Corpse and actual full close. Malice was already represented: no new identity/group credit. **Full original Mark/200 stays held and unchanged**, so this adds no resolved-record count. [Qualified partial proof](review-evidence/malice200-partial-5a8c-qualified-saved.json), [source/components/derivation](review-evidence/malice200-partial-source-bundle-20261117.json).

The two new saved cases total 88 actions/5,544 assertions and two clean creation replays. The separate no-save control is 61/4,675. Tremor25 and Malice19 actual-function component checks passed; candidate/installed syntax checks passed. Components supply no Native sibling/token/reentry/fullcard credit. Aggregate Tremor NoAbilities remains inherited and unqualified. Malice token cessation, ownership/control changes and graveyard reentry have component evidence only.

The Malice witness explicitly removes Mark/public-Red/bind setup and its two-layer chooser branch; it is a separately scoped test, not a weakened replacement for the full original. That original remains diagnostic-only and outside the passing corpus. Metadata append guards stopped on a stale runtime field and the already-known group; final bookkeeping recovered from verified partial state without repeating gameplay, altering tests or granting extra group credit.

## The 25 open records

| Blocker | Records | Action required |
| --- | --- | --- |
| Prior specific automatic-approval denials | 089 Illuminate; 090 Mirage; **094 Blood Vest virtual lifecycle**; 144 Quantum; 176 Hellbound +177 Vexing; 184 Elliptical; 188 Flutter | Eight records/seven denied operations, all known unexecuted. Specific approval of the exact denied goal/files is needed before one guarded retry.094 is awaiting Nate's answer. Some also require generation inputs. No retry was made. |
| Authoring/input migration | 078 Ebon Fold;091 Surface;103 Captain's Call;147 Pox;157 Fluxor;158 Figment;178 Memorial Ground;179 Tip-Off | Restore authorized snapshot reads or provide unmodified raw authored rows, then migrate literal choice/trigger instructions and guard generation.091 has matching normalized canonical bodies but no inspected supported canonical-only emitter route; its mapped local database is missing.078's older authorization wording is retained; no exact denied078 command was identified or attempted. |
| Owned interactive/event continuations | 095 Tear;097 Drowning;101 Bolters;109 Head Leads;125 Burn;192 Read;195 Powder;200 Mark/Malice | Need retained real await frames and captured event adapters, not synchronous substitutes.094 approval alone does not implement Hit, Listener, AttackDeclared, StartTurn or named `iarBound` adapters; its proposal excludes `iarBound`.192 also needs correct automatic beginning-End scheduling.200's Malice-only fix does not validate Mark. |
| Rule-backed declaration contract | 093 High Roller | Early Hero targeting before assets is confirmed. Resolve single/double Intimidate target cardinality, including a qualifying roll during responses, before implementing source-bound target consumption in the existing helper. Native repair need not use generation; no invented extra/late target or source patch was applied. |

[Machine triage](review-evidence/remaining-repair-triage-20261117.json) gives per-record cards, exact denied targets/evidence, correct native probes and smallest next actions. The generation service was **not retried** in this continuation. Last retained configured Card Code result is HTTP403 from `https://soulmastersdb.net/TCGEngine/CardEditor/API/CardCodeService.php`; the local read-only check returned MySQL1049 (missing database). No data/schema/config import or remote authoring write was attempted.

094's prior application remains known-unexecuted. Its [four-function/six-edit proposal](review-evidence/ros094-rebased-after164-awaiting-approval-20261117.json) must be rebound/rebased to final code after specific approval; current proposal tests/Native/replay remain unrun. Historical component results are not current qualification. No generic/named await-lifetime change was smuggled into Malice's synchronous event.

High Roller's primary sources confirm early targeting: [official text](https://judge.fabtcg.com/cards/detail/1281/), [Intimidate8.5.10](https://rules.fabtcg.com/en/cr/08-keywords/), [target effects1.8.5](https://rules.fabtcg.com/en/cr/01-game-concepts/), [declaration5.1.4](https://rules.fabtcg.com/en/cr/05-layers-cards-abilities/). The unresolved design question is declaration count/target reuse when the conditional replacement becomes applicable during responses; the no-roll case cannot certify that untested contract.

## Scope, validation and recovery

This continuation changed only four FaB-native PHP files: `FaBSim/Custom/IARCards.php`, `GameLogic.php`, `IARRuntime.php`, `ROSCards.php`. No repository `Core/`, canonical/builder, schema or generated outputs were written. Existing fixtures and unrelated user/PokeSim work were preserved. Two new five-file fixture sets and one focused input were retained, with compact qualification/triage proofs; proposal/checker/intermediate material remains outside the repository in the task workspace.

Existing runner commands actually used:

```powershell
python -X utf8 -B run_resumed_sparse_recording.py card-regression-batch423-tremor-protocol-v2.json nov17-tremor102-d834-native d8345acccd130cce07e72fc43c1746609ca5860ecb5d1dcc9c2ddab28bf2697c
python -X utf8 -B probe_nov17_tremor073_existing_fix.py
python -X utf8 -B replay_sparse_batch.py card-regression-batch423-tremor-protocol-v2.json nov17-tremor102-d834-replay --targeted-preservation
python -X utf8 -B run_resumed_sparse_recording.py card-regression-repair-malice200-partial-earned-death.json nov17-malice200-5a8c-native 5a8c088126ff8d9c581c7eb97e4b9cc77511e4011c8fd3faed3a5328b7b47379
python -X utf8 -B replay_sparse_batch.py card-regression-repair-malice200-partial-earned-death.json nov17-malice200-5a8c-replay --targeted-preservation
python -X utf8 -B replay_sparse_batch.py card-regression-batch420.json nov17-malice200-5a8c-hero-compat --scenario card-malice-four-live-sparse-real-paid-tapped-hero-grants-zombie-graveyard-play-permission-consumed --targeted-preservation
```

These orchestration scripts are in `C:\Users\maxim\Documents\Codex\2026-10-19\task`; retained specs, fixture sets and replay receipts use the existing repository framework. The component checker's first zero-check failure was a missing isolated GetTurnNumber stub, corrected without changing gameplay candidates; retained failure is not passing evidence.

[Final focused source/worktree receipt](review-evidence/triage-final-source-worktree-check-20261117.json) verifies exact reviewed candidate bytes, no new trailing whitespace, `git diff --check` exit0 and saved/registry name equality. Git only warned about future LF→CRLF conversion. HEAD `b097e43e4de0369d44839fa95d3804aa1454ff1e`, branch main. Pre-final-documentation snapshot: 0 staged,135 unstaged tracked,5,515 untracked normal-mode entries; directory counts DevTools4,883/FaBSim37/PokeSim13/Tests707/tmp10. These are Git entries, including directory placeholders, not raw file counts; final documentation adds a few metadata files afterward. Changes remain local/uncommitted. No staging, commit, push or deployment occurred. This was not a review of the entire dirty worktree or a fresh hash/replay of every old fixture.

Verified recoverable source backups are outside the repository in `C:\Users\maxim\Documents\Codex\2026-10-19\task\nov17-tremor073102-root-application` and `...\nov17-malice200-root-application`; compare later edits before restoring. Pre-handoff docs are in `...\nov17-grouped-handoff-before-docs`. Existing older backups were retained.

No full suite/historical corpus replay was run. User-owned broader validation remains necessary before a merge-readiness claim. Suggested bounded command from the repository root:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact "--log=$env:TEMP\fabsim-review-$(Get-Date -Format yyyyMMdd-HHmmss).log"
```

Next: obtain the pending094 decision and rebase if approved; restore generation inputs for the authoring cohort; then implement/test the captured continuation adapters, prioritizing Powder195 and Tear095. Resolve High Roller's rule contract before patching it. Existing source-only/untested branch caveats remain separate. **Work is paused for review; do not resume breadth automatically.**
