# Small differences, different fights

An isolated DFGSim design gallery. Revision 3 merges the 40 refined families with 25 additional families covering current-card mechanics. The gallery has 65 families and 257 entries: 221 proposals/reference placements and 36 exact current-card baselines. See [the complete current-card coverage report](COVERAGE.md).

Open `index.html` directly, or use the existing local web host:

<http://localhost/TCGEngine/DevTools/DFGSim/family-study/>

No build, database, network services, or card-editor connection is required. The page reads a static snapshot of the live catalog and never loads or mutates game sessions, the source catalog, or simulator rules.

## Reading the study

Draft siblings hold their hand and usually their burn modes and destinations constant. Dedicated burn and routing families intentionally change those fields. CURRENT badges reproduce the exact local rules-v44 card, including its own printed color, all main/objective/spend text, burn modes, and routes. They receive no invented tags. DRAFT and REFERENCE badges identify proposed cards and shared proposal baselines. Families can contain current baselines from other hands, and filters retain whole groups containing a matching hand.

Each family has a visible critique, refinement, and next test. The original 40 families retain expandable first-draft comparisons and the historical [revision-2 refinement report](REFINEMENT.md). The 25 new families explain the coverage gap they fill rather than inventing a prior draft.

The expandable coverage index maps all 36 current cards to their primary baseline and related families for every main clause, objective, spend, bonus, reaction, and destination set. There are 175 mapped fields/clauses: 38 main clauses, 18 objectives, 11 spending abilities, and 36 each of bonus, reaction, and routes. Empty burn modes explicitly map to F58, while absent objectives/spends display None. Each family also links its current-card examples, even when the exact card is anchored elsewhere.

The first family preserves the suggested 0/0 heal 4, 0/2 heal 3, and 0/4 heal 2 examples. Three offensive siblings trade one healing for one attack. Reveal-time healing is explicitly experimental; live Driving Palm heals after damage and cannot prevent knockout.

F04 reuses Measured Breath (F01-2) and Steady Return (F01-5) as its immediate recovery references. Delayed alternatives keep matching stats, burns, and routes while offering larger recovery later. A single public pending-recovery queue per fighter prevents accumulating several future heals. Reference definitions are copied from their source cards after all revisions, preventing drift; separate entry IDs identify gallery placement.

“Existing vocabulary” means familiar concepts, not compiler-compatible draft text. “New system” identifies proposed new mechanics. Current conventions are composed by `study-refinement.js` and `study-coverage.js`. No proposed family is claimed balanced. The former F07 redundancy and F14 dominance controls were rebuilt as genuine tradeoffs. The first draft remains historical comparison data.

## Payoff explorer

Selected draft families have a narrow one-exchange model in `study-model.js`. CURRENT cards are excluded because their additional effects are not fully modeled. It supports raw stats, general penetration, printed-stat reads, reveal and conditional post-damage healing, required stamina costs, held-stamina thresholds, and bonus-commitment conditions. Checkboxes change commitment flags only; they do not add burn effects. The model excludes burn stat effects, carry, tokens, direct retaliation, statuses, queues, future effects, opposing life, and routing. Opposing sliders are assumed to be both printed and final values. Damage dealt is uncapped by opposing life because no opposing life is supplied. Damage taken is raw incoming damage; your remaining life floors at zero.

The metric is `your life after - your starting life + damage dealt`. Tied highest values among surviving cards are highlighted; knocked-out cards are never highlighted as leads. The display remains a local comparison rather than a game solver. It does not charge an opportunity cost for stamina. Required-cost cards are unavailable below their threshold.

At starting life 24 and opposing block 2, the first three healing variants produce life changes of:

| Opposing attack | Open Breath | Measured Breath | Sheltered Breath |
|---|---|---|---|
| 0 | +4 | +3 | +2 |
| 2 | +2 | +3 | +2 |
| 4 | 0 | +1 | +2 |
| 6 | -2 | -1 | 0 |

Shortlists are stored under `dfg-family-study-shortlist` in local browser storage, with an in-memory fallback. They contain only family IDs, which remain stable. Search includes displayed entries and current-card connections, not retired entries or critique text. Permalinks and coverage links clear conflicting filters to reveal the target. Download refined data exports composed revision-3 JSON, including critiques, baselines, coverage, and previous entries.

## Files

- `study-data.js`: preserved initial draft; not the final active pool on its own.
- `study-refinement.js`: all 40 critiques, current variants, references, and refined timing conventions. Loaded after the original data.
- `live-catalog.js`: read-only snapshot of the 36 current cards, rules version, and source hash.
- `sync-live-catalog.mjs`: refreshes only that study snapshot from local source files. Never writes game files or requests a remote save.
- `study-coverage.js`: 25 additional families, exact current baselines, per-clause coverage mappings, and merged-study conventions.
- `study-model.js`: the pure, limited payoff calculation shared by the page and numerical audit.
- `study.js`: rendering, filtering, local shortlist, permalinks, and current JSON export.
- `study.css`: independent visual design; no game CSS changes.
- `index.html`: standalone entry point.
- `audit.mjs`: source/snapshot/baseline equality, per-clause coverage, structural checks, explicit payoff-crossing fixtures, and a finite draft-only numerical screen; `--report` writes `COVERAGE.md`. The historical `REFINEMENT.md` remains intact.
- `verify.mjs`: browser interaction, layout, and calculation checks.

Continue with [the mechanics study](../MECHANICS-STUDY.md). Promote a family only after complete card definitions, legality, information boundaries, mixed-strategy matchups, routes, and full-game tests have been reviewed.

## Verification

Run `node DevTools/DFGSim/family-study/verify.mjs` from the repository root, using the existing Playwright installation in `DevTools/ui-harness`. If browser binaries are outside the default location, set `PLAYWRIGHT_BROWSERS_PATH` to their installed directory. No installation or download is performed by the verifier.

The browser verifier covers 257 unique entry IDs, 65 reviews, all 36 rendered current baselines, coverage links, variable family sizes, desktop/mobile overflow, healing/knockout timing, printed-stat thresholds, commitment flags, stamina legality, filtering, shortlist persistence, permalinks, and the exported JSON contents. Optional environment variables: `STUDY_BROWSERS` (comma-separated engine names), `STUDY_URL` (served copy rather than the default local file), `STUDY_SCREENSHOTS` (output directory), and `STUDY_DOWNLOADS` (browser download directory). In restricted environments that cancel browser downloads, run against the local web host with appropriate local process permissions.

The merged study was checked at desktop and mobile sizes in Chromium and WebKit, including the served JSON download. Firefox remains unverified because its installed binary cannot launch on this machine.

Run `node DevTools/DFGSim/family-study/audit.mjs --report` for the independent data and arithmetic checks. The screen has 8,316 possible input contexts and compares 120 pairs with matching hand, burns, routes, and tags. References are excluded as intentional duplicates. Comparisons exit once both directions have a witness, so this is not 120 full matrices or a full-game solver. No unresolved numerical dominance/equivalence flags remain in this restricted screen. The report records what the screen excludes; status/control/future-payoff families still require scenario and complete-game evaluation.

The source audit fails if `DFGSim/data/cards.json` or its rules version differs from the snapshot. To refresh, run `node DevTools/DFGSim/family-study/sync-live-catalog.mjs`, review all affected semantic mappings in `study-coverage.js`, then rerun the audit. Updating a snapshot cannot by itself prove its mappings are still meaningful.
