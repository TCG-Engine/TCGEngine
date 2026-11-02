# Meta baselines

Each subfolder is one real tournament set, frozen for the SWUSim bots: every player's actual list (`fixtures.tar.gz`, sideboards
included), every real match with its game score (`matches.tsv.gz`), and the frozen bot run all later runs are compared against
(`baseline/`). Run and read them with the skill `.claude/skills/swusim-meta-bot-run/SKILL.md`; the tools are in
`SWUSim/DevTools/rl/meta_baseline/`.

- `hmw/` — **the current meta** (owner 2026-10-26). melee 480807 Nexus Open: Homeworlds, 463642 Datacup II, 480819 Nexus Invitational:
  Homeworlds — 104 decks, 273 matches with both lists public (269 pairs, 538 games, ~3–4 min per arm). Baseline v2 frozen 2026-10-19
  (`e5841b2d`, 196 matches — it predates 480819). Small: thresholds in its `meta.json` (`analyzeArgs`, `matrixArgs`, `pairedMin`).
  Archetypes keyed by the fixture-style name (Homeworlds trait read off main + sideboard).
- `ash/` — RETIRED 2026-10-26 (kept as history). 53 ASH Premier events, 2026-09-05 to 2026-09-28: 3,207 lists, 9,933 matches; the ASH set
  kept the DB's leader + base-bucket keys.

The fixtures are archived rather than loose `.txt` files on purpose: tools that glob `BotFixtures/*/*.txt` (check_fixture_sizes.php,
the curated-fixture loaders) must not pick up thousands of tournament lists.
