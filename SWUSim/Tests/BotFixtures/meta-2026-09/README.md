# Real-deck self-play fixtures — Premier, September 2026

Twenty-two tournament decklists — the best-finishing list of each archetype the owner labelled, across four
melee.gg Premier events: 445115 Sector Open Dallas, and Planetary Qualifiers 437735, 438966 and 439947. All
four fall after the Cad Bane ASH leader ban and before HMW/IC27. They exist so the heuristic and RL bots can
be checked against how real decks actually perform.
The research behind them (records, matchups, labels) is in `docs/superpowers/research/2026-09-premier-meta/`,
and the style mapping is in the RL bots spec (`docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md`,
"Archetype vocabulary → base style").

⚠ **A tournament list is not automatically a list the bot can be judged on.** Measured 2026-09-29:
`director-krennic_law_blue-splash`'s cheapest space unit costs SEVEN, so against the three opponents whose
units are >50% space it cannot contest that arena at all and loses 8-14%, while beating midrange and control
35-63%. That made "the bot pilots Krennic badly" indistinguishable from "that list cannot contest space" in
every Krennic measurement up to that date. **When a fixture's result looks like a bot defect, check the
deck's CURVE first.** The owner's own Krennic list, which does contest space, is in `../force-fam/`.

Each file header records:
- the **source** — event, final placement and the melee decklist URL. Lists were converted to SET_NNN by
  `APIs/MeleeLinkToJson.php`; sideboards are dropped.
- the owner's **archetype label**.
- the **style**: which heuristic profile, and later which RL base model, plays it.
- the **flavours**: tags for the flavour profiles, the heuristic nudges on top of a style.

⚠ **Filenames were RENAMED on 2026-09-29** to `<leader-title>_<leader-set>_<base-name-or-archetype>`, which
the Bot Arena picker shows as "Leader Title (SET) Base" — `director-krennic_law_blue-splash` displays as
"Director Krennic (LAW) Blue Splash". A single `-` is a word break and a DOUBLE `--` is a literal hyphen, so
`obi--wan-kenobi_lof_vergence-temple` round-trips to "Obi-Wan Kenobi". The base half is the colour for a
30-HP common, "<Colour> Force" for a 28-HP LOF common, "<Colour> Splash" for a 27-HP LAW common, and the
printed title for anything rarer. Implemented in `SWUSim/Custom/BotDeckStyle.php`
(`SWUBotFixtureFileName` / `SWUBotDeckDisplayName`), pinned by
`SWUSim/DevTools/tests/bot_fixture_naming_test.php`. The earlier `aggro_` / `normal_` / `control_` prefixes
are gone; nothing infers style from a filename.
The five archetypes (`SWUSim/Custom/BotArchetypes.php`, `SWU_BOT_ARCHETYPES`) replaced the old three-style
scheme on 2026-09-17: `hyperaggro`, `softaggro`, `midrange`, `softcontrol`, `hardcontrol`, ordered aggro ->
control. The old three names (`aggro` / `normal` / `control`) remain permanent aliases to `softaggro` /
`midrange` / `softcontrol` for backward compatibility, but every fixture below now carries an explicit,
non-aliased `# Style:` line — that line is what `sweep_fixtures.sh` reads (`grep -m1 '^# Style:'`) and is
authoritative; the filename prefix is now only a historical label and must not be used to infer style.

| File | Style | Flavours |
|---|---|---|
| darth-vader_jtl_yellow | hyperaggro | space |
| ahsoka-tano_ash_yellow | softaggro | mixed-space |
| ahsoka-tano_ash_blue | softaggro | ground, combo |
| boba-fett_jtl_lake-country | softaggro | burn |
| greef-karga_ash_data-vault | softaggro | go-wide, mixed |
| darth-maul_lof_blue-force | midrange | tempo, force |
| mother-talzin_lof_yellow-force | midrange | tempo, force |
| luke-skywalker_jtl_data-vault | softaggro | space, combo, pilot |
| admiral-piett_jtl_red-splash | midrange | capital-ship |
| director-krennic_law_blue-splash | softcontrol | credit-ramp |
| lando-calrissian_law_blue | softcontrol | credit-ramp, tempo |
| admiral-piett_jtl_blue | softcontrol | capital-ship |
| aurra-sing_law_red | hardcontrol | hard |
| dedra-meero_sec_colossus | hardcontrol | hard |

**Second batch (2026-09-15)** — eight owner-supplied lists (a ninth, Mother Talzin on Crystal Caves, turned out to be the SAME decklist as `talzin_force` and was dropped): B-tier decks and newer lists, several from events
outside the research data (each header says which). They are not "best of archetype" picks. RL run 3 never
saw them (the trainer lists its decks at startup), so they double as a held-out set for the learned layer.

| File | Style | Flavours |
|---|---|---|
| the-armorer_ash_nabat-village | midrange | go-tall, upgrades |
| obi--wan-kenobi_lof_vergence-temple | midrange | go-wide, force, high-hp |
| chewbacca_law_alliance-outpost | hyperaggro | hyper, credit |
| grand-admiral-thrawn_jtl_yellow | softcontrol | combo, when-defeated |
| grand-admiral-thrawn_jtl_data-vault | softcontrol | bombs |
| the-mandalorian_ash_colossus | hardcontrol | hard, combo |
| aurra-sing_law_data-vault | softcontrol | setup |

**Third batch (2026-09-16)** — one deck, promoted from the field set at the owner's request because the gate set
covered no **defensive / heal** deck at all. Every other fixture either races, trades, or stalls behind Sentinels;
none of them heals to win.

| File | Style | Flavours |
|---|---|---|
| luke-skywalker_ash_data-vault | midrange | none — see its header |

⚠ **Two DIFFERENT Luke decks both play Green Data Vault** — the 2026-09-29 naming convention is what finally
tells them apart, because it carries the leader's SET:
- `luke-skywalker_ash_data-vault` — ASH_005 *I Can Save Him*: grindy midrange blue-green Hero, defensive, heal-based.
  35 entries, 46.4% over 207 matches.
- `luke-skywalker_jtl_data-vault` — JTL_012 *Hero of Yavin*: soft aggro / midrange SPACE, the Plot Cinta Kaz → Luke pilot
  deck. 94 entries, 52.5% over 575 matches.

They differ by 6 points and play nothing alike, so "Luke DV" unqualified is always ambiguous — say Luke (ASH)
or Luke (JTL), which is now exactly what the filenames and the picker labels do. The same trap applies to every leader with two printings in this meta (Thrawn, Vader, Leia, Jabba,
Lando, Ahsoka). ⚠ The gate is now **22 decks** (greef_datavault was merged into greef-karga_ash_data-vault, and the owner's
krennic_ninin moved to `../force-fam/`, both on 2026-09-29), so a full `strength_test.sh` run is
22 x 21 x seeds x 2 = **9,240 games** at 10 seeds.

Run a pairing with each deck on its own style's profile:

    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off \
      DevTools/SWUSimBotSelfPlayTest.php --games=8 \
      --deck=SWUSim/Tests/BotFixtures/meta-2026-09/darth-vader_jtl_yellow.txt --chooser=heuristic-hyperaggro \
      --deck2=SWUSim/Tests/BotFixtures/meta-2026-09/director-krennic_law_blue-splash.txt --chooser2=heuristic-softcontrol

With deterministic bots, a seed plays the same game whichever seat goes first. To get more distinct games,
vary the seed and swap which deck sits in seat 1, rather than swapping the first player.
