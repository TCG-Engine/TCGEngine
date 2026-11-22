> **Historical checkpoint below.** The [latest20November continuation](REPAIR_CONTINUATION_20261120.md) has4,180 qualified scenarios,75/93 original records resolved in tested scope and18open. Dates/runtimes/limits below retain their historical scope.

# FaBSim repair checkpoint — 20 November 2026

Paused for review after four narrow local repairs. **Three are qualified saved regressions; Bloodrot Pox has a verified type correction but remains open and unsaved.** This is a checkpoint, not completion of all known repairs or a merge-ready claim. No new breadth batch, full suite, historical corpus replay, stage, commit, push, deployment or remote card-source write occurred.

**4,176 saved = qualified/registered scenarios: 3,074 duel + 1,102 genuinely four-live.** Exact primary evidence covers **1,328/5,196 identities** and representative evidence covers **1,202/3,324 pitch-cycle or standalone groups**, with four-live evidence in 1,094 groups. These are named branches at their actual historical runtimes; untested pitch siblings receive no identity credit. Scenario volume is not percent completion. The [small additions index](coverage-additions-after218.json) augments frozen through218 indexes; [review registry](card-regression-review.json) maps scenario IDs to proofs and fixture hashes. The new three scenarios add three identities/groups. **71/93 original issue records are resolved in tested scope; 22 remain open.** Records overlap shared defects and are not 71 distinct engine bugs.

## Service recovered; remote source unchanged

The actual configured PHP/cURL client read `https://soulmastersdb.net/TCGEngine/CardEditor/API/CardCodeService.php`, workspace `FaBSim`, with the existing token. The single snapshot GET returned **HTTP200**, valid application JSON, on 20 November 15:05:27–29 UTC: 6,261 ability rows, 5,333 card IDs, and all eight selected repair entries present. No redirects, retries, config changes or credential output. This service count does not replace the frozen 5,196 functional-identity denominator. [Sanitized receipt](review-evidence/cardcode-snapshot-read-20261120.json). The earlier403 cause remains unknown; successful reads do not explain it.

Four subsequent guarded local MacroCode emissions succeeded, each limited by exact source/body hashes and verified generated scope. Canonical inputs, builders and local generation bridges are consistent; hosted authored source was not modified. Raw snapshot, large logs and recoverable beforeimages remain outside the repository in `C:\Users\maxim\Documents\Codex\2026-10-19\task`; no bulk review dump was added.

## Applied fixes and focused results

| Record | Local behavior corrected | Native evidence / clean replay | Limits |
| --- | --- | --- | --- |
| 091 Surface Shaking Blue | Actual Arena entry earns a responseable captured entry trigger, before three Seismic tokens; source go again completes once. | Original unchanged 10 actions /933 assertions; replay1/1 at `ad185f26`. Direct Surface compatibility1/1 also passes final `7514ce8e`. [Proof](review-evidence/surface091-ad18-qualified-saved.json). | Paid Blue entry only. Owner-start, other entry and siblings untested natively. |
| 157 Volatile Fluxor Blue | Pitch scalar is Red+3/Yellow+2/Blue+1, instead of all+3. Red and Hit bodies preserved. | 56 actions /4,346 assertions; replay1/1 at `6d4e73c8`. Actual Instant, Blue power1 vs real defense2 for zero damage, then actual go-again-funded Critical hit5/full close. [Proof](review-evidence/fluxor157-6d4e-qualified-saved.json). | Only Blue has native credit. Yellow has source/component evidence. Critical follow-through proves go again use and close, not later-link Fluxor expiry; that predicate's negative checks are components only. |
| 158 Figment of Erudition Yellow | Paid Instant enters Arena, then a separately responseable entry WHEN creates one Ponder. Explicit Instant provenance avoids Action-only Ripple handling. | Original7-action/602-query prefix preserved; four more manual responses complete 11 actions /1,248 assertions; replay1/1 at final `7514ce8e`. [Proof](review-evidence/figment158-7514-qualified-saved.json). | Paid Hand entry/immediate Ponder only. Transform, activation, material, End and other entry untested. |
| 147 Bloodrot Pox | Both authored damage calls and emitted prevention packets now GENERIC, removing physical-only damage history. | Unchanged original enforced-no-save diagnostic: 10 actions /1,095 assertions pass at `f5b53e63`; first declined token deals2 to HP18. [Partial proof](review-evidence/pox147-generic-type-partial-20261120.json). | **No fixture, replay, qualification or coverage credit.** Stops with second-token choice pending. Exact token source UID/LKI is unobserved; HNT Hero bookkeeping is not source proof. Full End, second token, pay3, prevention and ResolveAbility native branches remain unqualified. |

Saved witnesses total77 actions/6,527 assertions, three creation replays, plus one directly affected compatibility replay. Original normative assertions were preserved; save successors add completion rather than bless incorrect behavior. Original diagnostic inputs remain unchanged. Independent source/saved semantic review and actual isolated components are retained in one [compact review/application bundle](review-evidence/repair-source-review-bundle-20261120.json): Pox14 actual bridge checks; Surface22 contracts/61 assertions; Fluxor36 actual bridge/predicate checks; Figment17 contracts/36 assertions, with relevant syntax/builder checks passing. Units are deliberately not summed into a misleading test count.

Final runtime: `7514ce8e6cf71bf22ce5f023d22b2c9543390e4bb3e00f5fe2ecc59968ea30e6`. Old evidence has not been relabeled to this runtime. Pox/Fluxor were not natively rerun after unrelated later emissions; exact guarded scopes preserve their bodies. The inventory total is not a final-runtime full-suite pass.

## Source and worktree scope

Repository Core, FaB `GameLogic.php`, client/configuration, schemas and remote authored data were not changed by this batch. The ten authored files are:

- `FaBSim/Custom/ROSCards.php`: native named synchronous entry-trigger dispatch/capture for Surface and Figment.
- `FaBSim/Custom/CodeGeneration.php`: exact-card/body bridges for only the selected legacy source bodies.
- `DevTools/FaB/out_abilities.json`, `sea_abilities.json`, `omn_abilities.json`, `dtd_abilities.json`, and their four matching `build_*_abilities.py` files.

The generator changed `FaBSim/GeneratedCode/GeneratedMacroCode.php` and `GeneratedMacroCount.js`; neither was hand-edited. All three new five-file fixture sets remain under `Tests/Integration/FaBSim/`. Coverage/issue/index documentation and compact proof files are retained; no fixture reorganization or cleanup deletion occurred.

HEAD remains `b097e43e4de0369d44839fa95d3804aa1454ff1e`, branch `main`. The status snapshot before these final documentation additions had **0 staged, 144 unstaged tracked and5,543 untracked normal-mode entries**. Directory placeholders mean these are not raw file counts. Existing unrelated PokeSim and Schema edits were preserved. Everything from this batch remains uncommitted. [Focused source/worktree proof](review-evidence/focused-source-worktree-check-20261120.json).

`git diff --check` exited2 with15 inherited CodeGeneration whitespace warnings. Comparison with the pre-session file proves no newly introduced trailing whitespace; older exact DSL text was left untouched. Syntax and focused checks passed. No complete audit of unrelated dirty files or broad validation is claimed.

## Remaining22 records and next decisions

| Category | Record IDs | Next action |
| --- | --- | --- |
| Eight records /seven prior denied operations, untouched | 089 Illuminate;090 Mirage;094 Blood Splattered Vest;144 Quantum;176/177 Hellbound/Vexing;184 Elliptical;188 Flutter | Specific approval before retry. Vest/094 was explicitly excluded this session. Old denial evidence remains in the [historical triage](review-evidence/remaining-repair-triage-20261117.json). |
| Eight event/interactive continuations | 095 Tear;097 Drowning;101 Bolters;109 Head Leads;125 Burn;192 Read;195 Powder;200 Mark/Malice | Event-specific awaited callback ownership/capture/phase design. The094 change alone does not supply all Hit/Listener/AttackDeclared/StartTurn adapters. Read also needs automatic begin-End flow. Malice-only remains separately qualified; full Mark stays held. |
| Four feasible authoring migrations, not applied | 078 Ebon;103 Captain;178 Memorial;179 Tip Off | Inputs are available. Use inspected ARC Prepare and bound completion paths: move Ebon effect after payment; declare Captain mode early; declare/revalidate Memorial graveyard target; admit Tip target before paid discard. No generic094 change is needed for those scoped routes. [Read-only feasibility](review-evidence/authoring-feasibility-20261120.json). Older permission labels are preserved, not treated as proof of a newly denied078 operation. |
| One rule/design question | 093 High Roller | Resolve conditional one/two-target cardinality when a qualifying roll occurs during responses, then bind early targets. No patch attempted. |
| One partially repaired record | 147 Pox | Finish a meaningful durable witness and resolve source-observation scope before saving/closing. Damage type is already corrected; no current service block. |

Further independent repairs are technically possible; this is a safe review checkpoint, not a claim that all22 require user action or are blocked by094. Do not resume breadth automatically. Review feasible078/103/178/179 work and the independent continuation design next; separately decide any denied-operation approval. Preserve untested Smashing Item-choice and older hold limitations in their existing records.

## Reproduction and broader-validation limit

From repository root, the three focused replay selections are:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=card-surface-shaking-blue-four-live-correct-probe-real-aura-entry-trigger-before-three-seismic-tokens
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=card-volatile-fluxor-blue-four-live-instant-link-plus-one-fully-blocked-and-followthrough
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=card-figment-of-erudition-yellow-four-live-paid-instant-entry-when-then-ponder
```

Pox remains an enforced no-save diagnostic:

```powershell
& 'C:\xampp\php\php.exe' DevTools/FaB/CardRegression/record_card_regressions.php DevTools/FaB/CardRegression/card-regression-batch618-pox-probe-v3.json --probe-only
```

No full suite was run. The optional user-owned broader check below was **not executed** here:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact "--log=$env:TEMP\fabsim-review-$(Get-Date -Format yyyyMMdd-HHmmss).log"
```

Recoverable source/generated beforeimages are in the four workspace `nov20-*-guarded-application-and-emission` directories. Final metadata beforeimages and a manifest are in `nov20-final-handoff-metadata-before`. Compare intervening edits before restoring individual files; do not bulk-reset the worktree. The [machine checkpoint](review-evidence/repair-checkpoint-20261120.json) supplies exact counts, issue groups, evidence contexts and paths.
