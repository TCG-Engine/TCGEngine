# Local breadth batch 874 — 22 November 2026

**Completed locally; later batches continue under the renewed user instruction. All work remains uncommitted.** Isolated worktree `C:\Users\maxim\AppData\Local\Temp\fm1122`, based on unchanged head `aa67228188d97c1437e4785e471fc44daece8f17`. Main, both remote review branches and the original dirty checkout were not modified. No merge, staging, commit, push, PR, deployment, authenticated request or configuration change was performed for this batch.

| Metric | Before | After | Added |
| --- | ---: | ---: | ---: |
| Saved and qualified scenarios | 4,202 | 4,212 | 10 |
| Two-live scenarios | 3,075 | 3,075 | 0 |
| Genuine four-live scenarios | 1,127 | 1,137 | 10 |
| Tested primary identities /5,196 | 1,350 | 1,360 | 10 |
| Represented groups /3,324 | 1,224 | 1,234 | 10 |
| Groups with four-live evidence | 1,116 | 1,126 | 10 |

All ten groups were checked against both the frozen cycle index and current additions before selection. Each receives one representative only. Untested pitch siblings, support cards and alternate branches receive no credit. These totals are accumulated qualifications at their recorded runtimes, not a fresh whole-corpus pass.

## Cases

| Tested identity | Named representative branch | Clean replay |
| --- | --- | --- |
| Sap (`sap_red`) | three arcane without surge preserves energy counter | PASS |
| Mind Warp (`mind_warp_yellow`) | two arcane without surge preserves actual hand | PASS |
| Destructive Aethertide (`destructive_aethertide_blue`) | one arcane without surge preserves actual arsenal | PASS |
| Glyph Power Spell (`glyph_power_spell_red`) | real sigil condition and blue pitch pay for six arcane | PASS |
| Foreboding Bolt (`foreboding_bolt_red`) | three arcane then actual opt one bottom | PASS |
| Call to the Grave (`call_to_the_grave_blue`) | choose real deck card to graveyard then shuffle and go again | PASS |
| Soul Food (`soul_food_yellow`) | moves itself and both real hand cards to soul | PASS |
| Cog in the Machine (`cog_in_the_machine_red`) | creates two owned cogs then real tap puts source on deck bottom | PASS |
| Cogwerx Workshop (`cogwerx_workshop_blue`) | creates golden cog and adds steam to two real cogs | PASS |
| Index (`index_red`) | chooses one of five then orders remaining four on bottom | PASS |

The four seats are alive and controlled manually. Every saved case includes actual priority passes by P1, P2, P3 and P4. The spells target P4 where applicable; the other cases exercise P1’s physical zones while the other seats respond. This does not claim exhaustive multiplayer interactions or all possible targets.

## Evidence and limits

**68 native actions / 917 persisted assertions; 10/10 clean targeted replays, once each.** Exact saved actions/assertions, raw five-file hashes, legal young heroes/natural health, printed resource costs, physical UIDs, counters and quiet completion were checked. Independent semantic review covered the ten local printed-text/source specs once, followed only by review of corrected setup/flow deltas. No second independent saved-file review or isolated fault-control run is claimed.

Early no-save diagnostics caught one test-oracle error (Sap originally used STEAM instead of ENERGY) and chooser-flow assumptions. Sap now has an actual ENERGY counter on Tunic; its false-Surge control preserves it. Resolution-choice waits use queue ownership rather than an incidental priority-field value. Call to the Grave uses the supported multichooser, and the Cog cases explicitly decline optional Crank before their own choices. These were corrected before qualification. **No gameplay defect was confirmed; no engine fix or new repair pass was started.** Failed attempts saved no fixture and remain local.

Sap, Mind Warp and Destructive Aethertide test printed base packets with Surge false, not their positive Surge branches. Glyph tests the controlled-Sigil bonus with no arcane barrier; its other condition/prevention routes are untested. Cog Crank is declined; support cards are not independently validated. Index orders all five actual cards; Call searches a real card into graveyard and shuffles the remainder; Soul Food moves itself and both other hand cards into Soul. End-turn and alternate-choice coverage remains sparse.

New qualification runtime: `54409edb1ba1e0691c3beeb648e8ce984a09074b3339b7e303e53c61154e1494`, unchanged through recording/replay. No engine/framework code changed, so unchanged main-refresh 21-case and historical evidence were reused rather than rerun. **No full suite ran.**

Durable local files: [source spec](card-regression-sparse874-20261122.json), [checkpoint](card-regression-sparse874-20261122-qualified-checkpoint.json), [saved semantic proof](review-evidence/sparse874-20261122-qualified-saved.json), [replay receipt with exact commands](card-regression-sparse874-20261122-replay.json), [coverage index](coverage-additions-after218.json) and [review registry](card-regression-review.json). The issue ledger is unchanged because there is no new confirmed engine issue.

Complete diagnostic runs, raw recordings, independent review drafts and metadata backups are outside the proposed patch under `C:\Users\maxim\Documents\Codex\2026-10-19\task\sparse874-local-20261122`. No transient recorder output was added to the repository worktree. The proposed patch contains ten five-file fixtures and compact metadata/documentation, without generated outputs or gameplay source changes.

Actual replay command form, run separately for each exact scenario ID listed in the receipt:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=<exact-scenario-id> --max-failures=1 --compact --log=<new-local-log>
```

Breadth work continued from Batch875 without a routine review stop. Publication remains unauthorized.

## Search replay input amendment

FAB-REG-204 exposed strong-entropy shuffle bypassing deterministic replay. Only inspected local search fixtures now leave one irrelevant remainder, retaining meaningful actual search/choice/movement assertions. Original five-file copies were hash-verified and backed up outside the repository before native replacement. Only changed cases were replayed; unchanged prior passes were reused. No shuffle randomness, corpus-wide determinism or engine fix is claimed. [Boundary evidence](review-evidence/search-strong-entropy-replay-limitation-20261122.json). Current exact hashes and commands are in the linked proof/replay receipt. The prior stop-for-review wording in Batch874 is superseded by user continuation.
