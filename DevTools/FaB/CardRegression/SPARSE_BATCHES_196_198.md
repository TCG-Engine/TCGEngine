# Sparse batches 196–198 — 25 October 2026

Nine new four-live representatives are saved, independently reviewed and replayed twice against runtime `6a3fcc98c838e8fb306fb0c526d37f5040cb0c266d0f32c2b3737688d831c5f7`. They cover Brush Off (red), Steelblade Shunt (red), Rally the Rearguard (red), Soul Shield (yellow), Sigil of Suffering (red), Amplify the Arknight (red), Passing Mirage (blue), Mauvrion Skies (red), and Phantasmal Haze (red). Each represents its own previously uncovered group; pitch siblings, support cards and untested branches gain no credit.

The saved inventory is **3,372 scenarios: 3,074 duels and 298 genuine four-live cases**. Recorded primary subjects span **531 / 5,196 exact identities** and **405 / 3,324 pitch cycles or standalone groups**, including 296 groups with a four-live profile. These are breadth counts, not completion percentages. The strict current index qualifies nine new scenarios/groups. The registry retains 3,372 review entries, but the older 3,363 no longer bind the current snapshot bytes after the intervening Git checkout. One investigated example matches the original proof exactly after reversing only TXT CRLF-to-LF normalization; actions and assertions already match. This is a historical hash-binding limitation, not a fresh failure or a claim that all old differences were reviewed. Older review/replay evidence remains unchanged; no blanket reapproval or current-runtime credit was awarded.

Use [cycle-coverage-current.json](cycle-coverage-current.json) for group membership, tested primary identities, exact scenario slugs, four-live profiles and current replay status. Use [card-regression-coverage.json](card-regression-coverage.json) for card text, branches, source specifications, fixture paths, review and replay provenance. [The machine-readable checkpoint](card-regression-sparse196-198-checkpoint.json) names every new scenario and validation report.

Two native gameplay defects were reproduced with correct expectations before repair and passed without changing those expectations afterward:

| Issue | Expected and observed defect | Applied repair |
| --- | --- | --- |
| FAB-REG-043, Mauvrion Skies | Its earned hit ability needs a separate responseable layer; three Runechants were created immediately instead. | Capture each grant's source, controller and count in the existing shared trigger queue; create tokens when that layer resolves. |
| FAB-REG-044, Phantasmal Haze | Destruction needs its own responseable ability; the Shield appeared immediately and Phantasm closure returned to ACTION. | Queue the captured destruction ability and preserve PRIORITY when Phantasm closure leaves pending layers. |

These changes touch `FaBSim/Custom/CRUCards.php`, `EVRCards.php`, `ROSCards.php`, and `GameLogic.php`. No repository Core or generated files changed in this batch. This is migration of two card callbacks into existing shared infrastructure plus a shared Phantasm priority correction, not a claim that all timing issues are solved. [The before/after proof](review-evidence/sparse198-confirmed-trigger-before-after.json) and [issue ledger](card-regression-issues.json) retain the exact limits.

Validation retained:

- 18 targeted replay passes (nine new fixtures, twice each), 301 supported native actions, 264 actual manual passes, and 20,238 persisted assertions.
- Four native causal controls detected; all 4,412 suffix assertions remained intact. They cover Brush's effect, Rally/Sigil defense gain, and Mirage's Phantasm suppression. Amplify's discount, Shunt's retaliation and Soul Shield's destination retain positive physical/economy checks without an isolated mechanism fault.
- Fourteen reusable actual-function component contracts and the directly affected existing CRU and EVR family checks passed. Components are controlled contracts; they add no native pitch-variant coverage.
- **No full integration suite was run in this breadth batch.** The earlier 3,363/3,363 result remains historical at its recorded runtime; intervening shared changes and these fixes prevent treating it as current. The user's broader verification command is `& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim`.

Art of War, Tome of Aetherwind and Reinforce the Line remain selected **source-only, unattempted declaration-timing concerns**, without coverage or confirmed-bug credit. Related inline Coalescence/Miraging/Haze Bending routes were not natively investigated. See [source-only holds](sparse-source-only-holds.json); no passing fixture was fabricated for these branches.

Complete historical diagnostics and verified rollback copies remain outside the repository in `C:\Users\maxim\Documents\Codex\2026-10-19\task\sparse198-trigger-repair-backup`, `sparse198-native-before`, and `sparse196-198-metadata-backup`. Retained compact proofs identify their hashes and scope. No prior tests or user edits were removed. Changes remain local and uncommitted; nothing was staged, pushed or deployed. Batches 199/200 continue separately and do not alter this dated checkpoint.
