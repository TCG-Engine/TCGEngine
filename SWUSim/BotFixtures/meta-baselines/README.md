# Meta baselines

Each subfolder is one real tournament set, frozen for the SWUSim bots: every player's actual list (`fixtures.tar.gz`, sideboards
included), every real match with its game score (`matches.tsv.gz`), and the frozen bot run all later runs are compared against
(`baseline/`). Run and read them with the skill `.claude/skills/swusim-meta-bot-run/SKILL.md`; the tools are in
`SWUSim/DevTools/rl/meta_baseline/`.

- `hmw/` — **the current meta** (owner 2026-10-26). Rebuilt 2026-11-02 from ONE event, melee 492213 Regional Championship Utrecht
  (2026-11-01): 766 lists, 3,046 matches with both lists public. It supersedes the earlier 5-event set (480807, 463642, 480819,
  483483, 489470 — 342 matches; in git history with its baseline v3), whose baseline does not pair with this set, so there is no
  frozen baseline until the owner asks for one. Thresholds in its `meta.json` (`analyzeArgs`, `matrixArgs`, `pairedMin`).
  Archetypes keyed by the fixture-style name (Homeworlds trait read off main + sideboard).
- `ash/` — RETIRED 2026-10-26 (kept as history). 53 ASH Premier events, 2026-09-05 to 2026-09-28: 3,207 lists, 9,933 matches; the ASH set
  kept the DB's leader + base-bucket keys.

The fixtures are archived rather than loose `.txt` files on purpose: tools that glob `BotFixtures/*/*.txt` (check_fixture_sizes.php,
the curated-fixture loaders) must not pick up thousands of tournament lists.
