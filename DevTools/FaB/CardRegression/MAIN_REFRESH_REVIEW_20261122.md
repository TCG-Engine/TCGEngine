# FaBSim review branch refreshed onto main — 22 November 2026

Base: `a7a86adc5c8f560265e6e6eefb44896b0084c5e7`. Original FaBSim commit: `9daf4fb79163a47d8118ba1dc144d11d3ef208d8`. Its isolated cherry-pick is `27c01491529fae73e2b64478604c9c888c89bffe` on `codex/fabsim-regressions-main-20261122`.

The 52 newer main commits had no overlapping paths with the 6,422-file FaBSim patch. The cherry-pick applied with **zero conflicts**. Main already contains 3,521 earlier FaBSim fixtures, the capped integration runner and shared native recording infrastructure; these remain inherited. No duplicate repair was reapplied separately. The refreshed branch adds the same 681 fixtures and intended later repairs/metadata, with no new coverage expansion.

Current main’s changes to the action runner, four-board authorization, transport, match handling and generator were preserved. Shared source files stay byte-identical to main; no Core or other-product source was patched by this refresh.

## Verification and one portability correction

**21/21 targeted replays passed**, with fixture initial states, actions, assertions, final snapshots and metadata unchanged. This includes all three High Roller controls (the responding Instant’s real roll, false bonus, and sole adjacent Hero repeated across separate instances), Illuminate, Mirage, Tear, Quantum Processor, Hellbound Assault, Vexing Malice, Elliptical Conflux, Fluttersteps, Read the Ripples, Pox, Burn, Vest, Glisten and Reincarnate, plus Smash Instinct, Pack Hunt and two Razor Reflex compatibility cases. Exact names, commands and log hashes are in [focused evidence](review-evidence/main-refresh-focused-20261122.json).

Seven PHP syntax checks passed on current shared/native files. The smoke/UPF/WTR continuation/card-family/timing/property checks passed. Seven synthetic lobby checks verified ordinary four-seat key independence, explicit one-board defaults, main’s intended two-board key sharing and existing hotseat semantics. They used no live authentication store or network.

A clean Windows checkout exposed an existing publication portability problem: automatic CRLF conversion changed an embedded authoring prefix while its strict consumer normalized input to LF, so regeneration stopped at the Aethersling prefix guard. The guard was **not weakened**. A narrowly scoped `.gitattributes` rule now keeps `FaBSim/Custom/CodeGeneration.php` at canonical LF. Its committed code content is unchanged. Actual Git checkout obeyed the rule, guarded offline emission passed, and the tested runtime stayed unchanged. Macro code/count output differs from the prior native output only by timestamps after newline normalization.

The isolated setup initially needed Windows long-path support and materialization of the existing lobby-class dependency for the component test. Those setup failures were retained locally and corrected; they were not gameplay failures or passing test evidence. No assertion or expected snapshot was changed.

## Scope and limits

Qualified inventory remains **4,202 scenarios: 3,075 two-live + 1,127 four-live**, 1,350 exact tested primary identities and 1,224 representative groups. This update adds **zero** scenarios or coverage credit. New test runtime: `54409edb1ba1e0691c3beeb648e8ce984a09074b3339b7e303e53c61154e1494`. Historical proof hashes/runtimes remain historical; the new receipt supplements only the selected 21 cases.

No full suite was run. No claim is made for browser transport rendering or live Card Code integration. Public dictionary/schema-parser artifacts were bootstrapped with hashes from the established runtime because the schema/parser regions were unchanged; current-main macro generation used the trusted local public snapshot with network disabled. Generated outputs, source cache, credentials and configuration remain uncommitted.

The original dirty laptop checkout, original index, local main and original review branch were verified unchanged. Work ran in a separate short-path linked worktree. Only the fresh review branch is to be pushed; no force push, merge, PR or deployment. [Prior repair scope and limitations](REPAIR_REVIEW_20261122.md) and [publication scope](PUBLISH_REVIEW_20261122.md) still apply. Pause for Nate’s review after publication.
