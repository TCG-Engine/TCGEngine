# Meta baselines

Each subfolder is one real tournament set, frozen for the SWUSim bots: every player's actual list (`fixtures.tar.gz`, sideboards
included), every real match with its game score (`matches.tsv.gz`), and the frozen bot run all later runs are compared against
(`baseline/`). Run and read them with the skill `.claude/skills/swusim-ash-meta-bot-run/SKILL.md`; the tools are in
`SWUSim/DevTools/rl/meta_baseline/`.

- `ash/` — 53 ASH Premier events, 2026-09-05 to 2026-09-28: 3,207 lists, 9,933 matches. Baseline v1 frozen 2026-10-11 (HEAD 2c43fbfe).
- `hmw/` — melee 480807 Nexus Open: Homeworlds (2026-10-10, 39 players): 24 lists, 42 matches with both lists public. Baseline v1 frozen
  2026-10-11 (84 games). Small: analyze with `--min 3 --cell-min 2`, matrix with `--min 1`. Archetypes keyed by the fixture-style name
  (Homeworlds trait read off main + sideboard); the ASH set kept the DB's leader + base-bucket keys.

The fixtures are archived rather than loose `.txt` files on purpose: tools that glob `BotFixtures/*/*.txt` (check_fixture_sizes.php,
the curated-fixture loaders) must not pick up thousands of tournament lists.
