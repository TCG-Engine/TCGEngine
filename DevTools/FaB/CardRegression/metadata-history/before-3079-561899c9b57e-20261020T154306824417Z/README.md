# FaBSim card regression coverage

This work uses `Core/RegressionTestFramework.php` and `Core/EngineActionRunner.php`.
The PHP authoring driver creates a unique local game, starts the existing recorder,
executes supported card clicks, decision answers and priority passes, checks explicit
card-text expectations, stops recording, and saves only successful scenarios.
It uses the existing recorder. Current user games are not used. Rule defects found
by independently checked reproducers are fixed in native Custom sources under the
user's authorization; generated files and databases are unchanged.

Run from the repository root:

```powershell
php DevTools/FaB/wtr_test.php
php DevTools/RunIntegrationTests.php --root=FaBSim
python DevTools/FaB/CardRegression/run_card_regression_replays.py <unique-run-name>
python DevTools/FaB/CardRegression/card_regression_negative_controls.py
python DevTools/FaB/CardRegression/build_card_regression_coverage.py
```

`card-regression-coverage.json` inventories all 5,196 functional identities and
17,188 printing records. Pitch variants are separate identities. Reprints/foilings
are metadata, not extra rule implementations. Candidates include ability snapshots,
compiled macros and native literal/family references. Identities without any such
evidence remain in the manifest for review; they are not excluded from the catalog.

Counts distinguish inventory, implementation candidates, recorded scenarios,
independently reviewed scenarios and clean replay passes. Replay evidence is matched
to the SHA-256 of the exact current fixture files and gameplay runtime. Historical
reports without runtime hashes cannot qualify the current implementation. Every tested identity remains
`partial`: named branches are covered, not every timing, target or cross-card interaction.
Legacy PHP test mentions and generic play/pitch checks do not count as ability coverage.

Independent review includes family specifications and representative saved pilots;
it does not imply that every saved fixture was manually inspected. The early 185
review entries retain their reviewer, source specification, exact fixture hash and
partial-coverage verdict. Their original records did not state the extent of
saved-fixture inspection; the added review-basis text preserves that limitation.

To author a reviewed batch:

```powershell
php DevTools/FaB/CardRegression/record_card_regressions.php DevTools/FaB/CardRegression/card-regression-batch01.json
```

Existing fixtures are skipped. `--rerecord-authored` permits replacing only fixtures
whose metadata identifies this authoring workflow; validation completes before save.
The driver never updates expected snapshots on replay. Failed scenarios are reported
and not saved as passing regressions. See `card-regression-issues.json` for rule bugs.

The initial independent review corrected arrow setups to include a bow, pinned
Goliath destruction before resolution, added meaningful Kodachi reuse rejection,
and persisted Sink exchange checkpoints. Negative controls mutate only disposable
fixture copies (or their isolated initial state). They must fail at an expected
semantic assertion, rather than merely because a card identity changed in a snapshot.

The shared runner evaluates globals before the next serialized state reload, so
removed card tombstones can remain in array indexes. Current hand/deck checkpoints
use live membership/count assertions; exact final snapshots also pin ordering.
The generic membership converter does not prove duplicate-card multiplicity by
itself. Authoring checks exact lists, and future duplicate-sensitive cases need
additional suitable persisted checks or demonstrable snapshot negative controls.

Reviewed batches include vanilla/go-again attacks, plain arcane spells, life gain,
optional hand exchange, opt, cost/power buffs, equipment/hero activations, arrows,
and hit/play-trigger response windows. Each manifest entry names only its exercised
branches. All catalog identities still need a full branch audit; comprehensive
coverage remains in progress.

`card-regression-issues.json` separates before-fix evidence from verified fixes and
remaining limitations. Current fixes address arrow/bow legality, end-turn AP
cleanup, the runner's initial-state loading, Snatch's draw trigger, Scar/Wounded
play triggers, native activation suppression, attack-reaction resolution/awaits,
Flock asset-before-reveal payment and choice references, queued Quicken/Flock
triggers, and token removal at arena boundaries. Explicit checkpoints precede and follow responses; current output
alone is not an oracle. Failed probes are never saved as passing fixtures.

The runner initial-state-loading fix was observed failing during the working
session, but no failing-before trace was preserved in the repository. Its current
step-0 assertion and replay evidence proves the repaired behavior; it is not a
repository-persisted failing-before/passing-after evidence pair.

The original hand-only `NO_ABILITIES` probes were invalid because Hand does not
serialize that dynamic property. Replacement suppression interactions use a known
active Blinding duration during Guardian Bravo's turn. They do not establish
Blinding's crush, generation, duration, or expiry coverage. Similarly, a requested
removed-bow tombstone is removed by initial serialization; that case is only an
absent-bow compatibility duplicate.

All work is local. No generated code, databases, user save data, commits, pushes,
deployments, or publications are part of this work.

The latest qualified checkpoint is `card-regression-checkpoint.json`:629 saved
scenarios passed clean replay under runtime a727c65555adb9d1dcb55119b906c03411990f1f36c466ec1058daae763243a0;
507 passed at least twice under that same runtime. There are122 identities with
verified partial branches, zero fully covered identities, and45 semantic negative
controls. These are a dated checkpoint; later runtime changes require a new
qualified replay before those results describe the changed implementation.

Each authoring attempt now retains `recording-runs/<input>-<time>-<id>.json` before
refreshing the latest-result views. An early retry overwrote the original full
Flock/Quicken failure report; its independent audit summary is explicitly labeled.
Incorrect early Quicken attempts are preserved only under
`held-unverified-token-attempts`, outside the passing fixture tree.

Active-attack power checks now also persist the existing native
`computed_power_equals` assertion. Native power/go-again hooks reject missing,
removed, unrelated, nonattacking or inactive objects rather than returning zero.
The source lifecycle, source roundtrips, effect-granted ability provenance,
elimination and trigger-ordering limitations in the issue/checkpoint files remain
open even when a related ordinary scenario passes.


### Persisted game-state checkpoints

`game_state_field` reads the optional root-owned `GameRegressionState($rootName)`
hook. The hook must return a JSON object snapshot without changing game state,
running actions, evaluating card legality, or consuming effects. FaBSim supplies a
JSON snapshot of `FaBGetState()` only when the requested root is `FaBSim`.
This is the normalized state view, including defaults and PHP associative-array
conversion; it does not validate the original raw `GameState` JSON representation.
Unsupported roots, absent hooks, hook exceptions and invalid snapshots fail the
assertion, including assertions that expect null or a missing field.

The assertion schema is `game-state-field-assertion.schema.json`. Paths contain
literal field names or nonnegative integer indexes, with no wildcard, expression
or implicit traversal. Integer segments can also address numeric object keys;
array indexes require integer segments. Equality requires the field to exist.
`valueJson` contains the expected JSON encoded as a string so the existing
associative JSON loaders preserve empty objects, empty arrays, null and scalar
types. Object member order is immaterial; array order, members and scalar types
are exact, including integer versus fractional-number representation.

```json
{"type":"game_state_field","rootName":"FaBSim","path":["arcaneDealt","1","2"],"comparison":"equals","valueJson":"4"}
{"type":"game_state_field","rootName":"FaBSim","path":["pendingPayment"],"comparison":"equals","valueJson":"null"}
{"type":"game_state_field","rootName":"FaBSim","path":["resolvingReaction"],"comparison":"missing_or_null"}
```

`missing_or_null` explicitly accepts either an absent path or an existing null
value. It rejects false, zero, empty arrays, empty objects and live markers.
It must omit `valueJson`. This is useful for resolution-marker cleanup, where the
runtime can remove the key. Strict equality to JSON null remains available when
field presence itself matters.

The authoring recorder now persists `kind=state` checks using this assertion.
Existing null checks emit `missing_or_null` explicitly, matching their original
`$state[$key] ?? null` semantics. Set `comparison: "equals"` on a null state check
to require presence. New recordings persist these checks; old fixtures retain
their exact saved assertions and historical state diagnostics stay author-only
until a reviewed rerecord. Snapshot comparison remains complementary evidence.
Legality diagnostics (`can_play`, `can_activate`) and defense diagnostics remain
author-only; this assertion does not call those predicates.

The pure contract test loads only extracted function definitions with stub state
and an assertion sink; it does not load an engine runtime or create games:

```powershell
php DevTools/FaB/CardRegression/state_assertion_contract_test.php
```


Qualified captured milestone 2026-10-19T23:53:18.085607+00:00: 659 exact saved scenarios passed two same-runtime clean replays under `d85229659e75730de2649374a6182852fc19b0a2f5e080c4b1fa7c00bbf02062`; 52 semantic controls detected their intended native failures, and WTR/ARC native suites exited0. See `card-regression-replay-spell-lifecycle-state-pilots-run2.json` and `card-regression-native-suite-spell-lifecycle-state-pilots-results.json`. These are captured-runtime facts; later source changes require fresh qualification.

The manifest records 130 identities with saved partial branches. Complete-card counts now come from explicit reviewed finite baseline scopes, required native oracles and qualified replay evidence. Muscle Mutt has two saved representative cases against a draft16-obligation baseline; its extra partial-damage defense case and completion-scope approval remain pending. Separate interaction holds are not concealed by baseline completion.

The failed652 replay, original wrong-attribution Ice Bolt bytes, immutable before-fix attempts and fixture-history beforeimages remain preserved. Fresh36 spell recordings retained initial state/actions and prior assertions, added native credit/cleanup checks, and were independently compared against the diagnostic source-reference/order changes. No snapshot-update flag was used. Lead's two unsaved native failures are tracked separately as023.


Qualified captured milestone 2026-10-20T00:51:39.033604+00:00: 1452 exact saved scenarios passed 2 same-runtime full clean replays under `8ef84003d127806df61aa7bb0eb577df5783846f088fcb063d3fcec5b9a83f87`; 57 semantic controls detected their intended native failures, and WTR/ARC native suites exited0. See `card-regression-replay-expanded1452-persisted-run2.json` and `card-regression-native-suite-expanded1452-persisted-results.json`. These are captured-runtime facts; later source changes require fresh qualification.

The manifest records 185 identities with saved branches and 1 complete within explicit reviewed finite baseline scopes. Completion requires every declared obligation, required native oracle and qualified replay evidence; per-card assessments list remaining obligations and separate interaction holds. Family/spec approval and representative saved-fixture review do not mean every fixture was manually inspected.

Immutable before-fix attempts, rejected held snapshots and earlier qualified metadata remain preserved. Issue status is bounded by its exact evidence and remaining-work fields; passing ordinary pilots alone cannot clear a broader suspended source approval.

The historical659/d852 checkpoint can be reconstructed from eight source overrides and14 fixture beforeimages, with all659 behavior hashes checked against its original two clean reports. See `source-review-evidence/historical659-reproducibility-overlay8-8ef84003d127/reconstruction-proof.json`; matching base manifests are required.


Qualified captured milestone 2026-10-20T03:02:34.083369+00:00: 2743 exact saved scenarios passed 2 same-runtime full clean replays under `8ec89bc9f6616ede8d9ed45fdd6ff8c9ccecb9cc74f349328f6742ce85ea8330`; 71 semantic controls detected their intended native failures, and WTR/ARC native suites exited0. See `card-regression-replay-full2743-8ec-run2.json` and `card-regression-native-suite-checkpoint2743-8ec-final-results.json`. These are captured-runtime facts; later source changes require fresh qualification.

The manifest records 214 identities with saved branches and 75 complete within explicit reviewed finite baseline scopes. Completion requires every declared obligation, required native oracle and qualified replay evidence; per-card assessments list remaining obligations and separate interaction holds. Family/spec approval and representative saved-fixture review do not mean every fixture was manually inspected.

Immutable before-fix attempts, rejected held snapshots and earlier qualified metadata remain preserved. Issue status is bounded by its exact evidence and remaining-work fields; passing ordinary pilots alone cannot clear a broader suspended source approval.
