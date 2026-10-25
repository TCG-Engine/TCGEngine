# FaBSim repository hygiene and completed cleanup

Authorized recoverable cleanup removed **1,597 review files /167,690,037 bytes** from Git's working tree. The two passes removed333 staging/control-review copies, then1,264 bulk historical snapshots/proposals. **Every removed file was backed up and individually SHA-256 verified before removal.** No regression fixture, gameplay file, reusable tool, current coverage/issue/checkpoint record or required review proof was removed.

`source-review-evidence/` now contains **18 files /62,255 bytes**: five current issue primary reports and thirteen compact apply receipts. These are retained for their actual review/reproducer value. Old snapshots and proposal bundles were not kept solely because generated historical metadata referenced them. All74 current `review-evidence/` files remain available to the strict qualifier.

Recoverable external backups:

- `C:\Users\maxim\Documents\Codex\2026-10-19\task\retained-fab-regression-evidence\review-artifacts-20261020T104722` (333files /17,776,524bytes).
- `C:\Users\maxim\Documents\Codex\2026-10-19\task\retained-fab-regression-evidence\source-review-history-20261020T155901Z` (1,264files /149,913,513bytes).

Each includes `files/` preserving repository-relative names, a complete SHA-256 `manifest.json`, reviewed plan, README and `RESTORE.ps1`. Use PowerShell7 to run that script; it verifies backup hashes and refuses to overwrite different existing files. The first backup also preserves metadata from before the proof-schema alignment. No original historical data was permanently discarded.

[archived-review-artifacts.json](archived-review-artifacts.json) identifies both archives, exact manifest hashes, retained files and restoration semantics. Current metadata labels unavailable historical source paths with `archived:`. Those labels are archive-backed references, not claims the files still exist in the checkout. Immutable captured proof packets keep their original bytes; restore archived files before using their old internal reconstruction paths. The former README reconstruction hyperlink now points to this archive index. Current issue primary evidence paths still exist normally.

The normal runner and strict qualification do not consume historical SOURCE proposal bundles. Runtime/code/fixtures, current source scenario inputs, actual `review-evidence/`, and replay/control/suite results were retained. `spec-history/` and `metadata-history/` outside SOURCE remain available. No ignore rules were added. The earlier whole-Git audit at15:30:18UTC counted18,290 untracked files /534,028,973bytes; final totals after metadata/docs and cleanup are in `final-evidence-index.json`.

Post-cleanup verification rechecks all3,079 scenario folders /15,395 files, all current proof records, gameplay runtime and both complete replay reports. The unchanged strict qualifier confirms3,079 scenarios /239 primary IDs /75 finite baselines /164 partial IDs. External backup files were independently rehashed; no game replay was necessary for data-only removal. The first pre-write cleanup attempt stopped at a cross-language path-sort guard; it made no changes. Corrected ordering then verified every tree before removal.

Each scenario's `initial_gamestate.txt`, `actions.json`, `assertions.json`, `expected_final_gamestate.txt` and `meta.json` form one native test. Removing parts can silently weaken a test, so all five were kept. Future regression work should use one representative per pitch cycle and add materially different or bug-specific cases, while preserving the existing corpus.

The current [coverage guide](COVERAGE_GUIDE.md) lists all114 represented groups and exact tested variants. [scenario-volume-breakdown.json](scenario-volume-breakdown.json) explains the large test count: the biggest eight batches contribute1,242 cases;1,634 extra variant instances have matching within-cycle scenario-name shapes. The shape count is not a semantic equivalence or pruning verdict. No runnable coverage was pruned.

Post-cleanup checks include all 15,395 fixture-file hashes, gameplay runtime, protected tools/manifests, individual backup hashes, retained reference checks and the existing strict metadata qualifier with explicit current suite inputs. See [FINAL_ASSESSMENT.md](FINAL_ASSESSMENT.md) and the final evidence index for qualification results and remaining risks.
