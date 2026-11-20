# Sparse batches 199–200 — 25 October 2026

Eight new four-live representatives are independently reviewed, registered and replayed twice at runtime `6e2fff7d7f0245b78544d19d50606796383c4af0154265966517e0460e8ed067`:

| Primary representative | Exercised mechanic |
| --- | --- |
| Arcanite Skullcap | Actual conditional defense and physical damage, stopping before chain closure. |
| Pyroglyphic Protection (red) | An owned active Aura prevents real arcane damage. |
| Enchanting Melody (red) | An owned active Aura is consumed while preventing real physical damage. |
| Yendurai | An owned invoked Dragon loses its endurance counter and takes one of four actual damage. |
| Rain Razors (yellow) | A real arrow gains two power and deals six damage. |
| Talishar | Paid first weapon attack adds a rust counter and deals four damage. |
| Induction Chamber (red) | Paid action charges the uncharged Item after four responses. |
| Stir the Wildwood (red) | Actual Runechant damage earns the unfused attack's +2; real attack damage earns Briar's responseable Earth trigger. |

The [checkpoint](card-regression-sparse199-200-checkpoint.json) maps every primary ID to its exact scenario slug and source specification. Each fixture is `Tests/Integration/FaBSim/<slug>/` with its existing five-file set. No support-card, hero, sibling-pitch or full-card credit is inherited.

Saved inventory is **3,380: 3,074 duels +306 four-live cases**. Recorded primary subjects now span **539/5,196 exact IDs and 413/3,324 groups**, including 304 groups with a four-live profile. The detailed [card index](card-regression-coverage.json) and [cycle index](cycle-coverage-current.json) now map all 3,380 fixtures. Their strict diagnostics retain 17 exact semantic reviews and eight cases with two current-runtime replays; previous batch replay contexts remain historical. Historical exact review hash mismatches remain a separate limitation: only one inspected example was proven to differ solely in TXT line endings. No whole-corpus equivalence or requalification is claimed.

**FAB-REG-045 is fixed:** Briar created Earth inline on first attack-action damage. The unchanged correct native probe failed unsaved at 25 actions (target 12 health, DAMAGE, empty stack, early Earth), then passed after a two-file repair to `ELECards.php` and `ROSCards.php`. The existing trigger queue captures the hero and controller; token creation waits for resolution. See [before/after evidence](review-evidence/sparse200-briar-confirmed-before-after.json) and [issues](card-regression-issues.json). This grants no additional primary Briar identity credit.

Validation: **16 targeted replay passes**, 184 native actions, 162 actual manual passes and 10,722 persisted assertions. Four causal controls detected the expected failures with all 3,140 suffix oracles retained. Thirteen new controlled function contracts, 14 existing dispatcher contracts and two PHP syntax checks passed. Yendurai/Talishar/Induction lack isolated native counter-mechanism controls; positive physical/economy evidence remains explicit. No full-suite run or repeat replay of the prior nine cases occurred.

Two Yendurai recipe issues were corrected without changing gameplay: a draft counter alias before execution, then the post-removal JSON representation (the existing setter omits zero keys). Initial endurance one, actual damage one and survival assertions remain. No incorrect fixture was saved. Two slug casing corrections were also made before execution.

Unattempted branch concerns remain explicit: Stir's actual fusion cost ordering, already-charged Induction activation, Talishar's three-rust end-phase timing, and Briar's second-NAA/ordinal-recovery branches. Active-Aura/Dragon cases do not claim casting, invocation or expiry coverage.

Source and oracle rollback copies are verified outside the repository under `C:\Users\maxim\Documents\Codex\2026-10-19\task\sparse200-briar-trigger-repair-backup`, `sparse199-counter-oracle-backup` and `sparse199-200-metadata-backup`. Complete historical diagnostic receipts stay there; compact useful proof and reusable components remain in the repository. Changes remain local and uncommitted. No user edits were removed or staged, and nothing was pushed or deployed. Further uncovered groups continue in separate batches.
