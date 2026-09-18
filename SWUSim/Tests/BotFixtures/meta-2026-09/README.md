# Real-deck self-play fixtures — Premier, September 2026

Fourteen tournament decklists — one per archetype the owner labelled. Each is the best-finishing list of its
archetype across four melee.gg Premier events: 445115 Sector Open Dallas, and Planetary Qualifiers 437735,
438966 and 439947. All four fall after the Cad Bane ASH leader ban and before HMW/IC27. They exist so the
heuristic and RL bots can be checked against how real decks actually perform.
The research behind them (records, matchups, labels) is in `docs/superpowers/research/2026-09-premier-meta/`,
and the style mapping is in the RL bots spec (`docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md`,
"Archetype vocabulary → base style").

Each file header records:
- the **source** — event, final placement and the melee decklist URL. Lists were converted to SET_NNN by
  `APIs/MeleeLinkToJson.php`; sideboards are dropped.
- the owner's **archetype label**.
- the **style**: which heuristic profile, and later which RL base model, plays it.
- the **flavours**: tags for the flavour profiles, the heuristic nudges on top of a style.

⚠ **Filenames keep their historical `aggro_` / `normal_` / `control_` prefixes — they were never renamed.**
The five archetypes (`SWUSim/Custom/BotArchetypes.php`, `SWU_BOT_ARCHETYPES`) replaced the old three-style
scheme on 2026-09-17: `hyperaggro`, `softaggro`, `midrange`, `softcontrol`, `hardcontrol`, ordered aggro ->
control. The old three names (`aggro` / `normal` / `control`) remain permanent aliases to `softaggro` /
`midrange` / `softcontrol` for backward compatibility, but every fixture below now carries an explicit,
non-aliased `# Style:` line — that line is what `sweep_fixtures.sh` reads (`grep -m1 '^# Style:'`) and is
authoritative; the filename prefix is now only a historical label and must not be used to infer style.

| File | Style | Flavours |
|---|---|---|
| aggro_vader_yellow | hyperaggro | space |
| aggro_ahsoka_yellow | softaggro | mixed-space |
| aggro_ahsoka_blue | softaggro | ground, combo |
| aggro_boba_lakecountry | softaggro | burn |
| aggro_greef | softaggro | go-wide, mixed |
| normal_maul_blueforce | midrange | tempo, force |
| normal_talzin_force | midrange | tempo, force |
| normal_luke_datavault | softaggro | space, combo, pilot |
| normal_piett_red | midrange | capital-ship |
| control_krennic_splash | softcontrol | credit-ramp |
| control_lando_blue | softcontrol | credit-ramp, tempo |
| control_piett_blue | softcontrol | capital-ship |
| control_aurra_red | hardcontrol | hard |
| control_dedra_colossus | hardcontrol | hard |

**Second batch (2026-09-15)** — eight owner-supplied lists (a ninth, Mother Talzin on Crystal Caves, turned out to be the SAME decklist as `normal_talzin_force` and was dropped): B-tier decks and newer lists, several from events
outside the research data (each header says which). They are not "best of archetype" picks. RL run 3 never
saw them (the trainer lists its decks at startup), so they double as a held-out set for the learned layer.

| File | Style | Flavours |
|---|---|---|
| normal_armorer_nabat | midrange | go-tall, upgrades |
| normal_obiwan_vergence | midrange | go-wide, force, high-hp |
| normal_greef_datavault | midrange | go-wide, mixed |
| aggro_chewbacca_outpost | hyperaggro | hyper, credit |
| control_thrawn_yellow | softcontrol | combo, when-defeated |
| control_thrawn_datavault | softcontrol | bombs |
| control_mando_colossus | hardcontrol | hard, combo |
| control_aurra_datavault | softcontrol | setup |

**Third batch (2026-09-16)** — one deck, promoted from the field set at the owner's request because the gate set
covered no **defensive / heal** deck at all. Every other fixture either races, trades, or stalls behind Sentinels;
none of them heals to win.

| File | Style | Flavours |
|---|---|---|
| normal_lukeash_datavault | midrange | none — see its header |

⚠ **`normal_lukeash_datavault` and `normal_luke_datavault` are DIFFERENT DECKS that share an archetype key.**
Two Luke leaders both play Green Data Vault:
- `normal_lukeash_datavault` — ASH_005 *I Can Save Him*: grindy midrange blue-green Hero, defensive, heal-based.
  35 entries, 46.4% over 207 matches.
- `normal_luke_datavault` — JTL_012 *Hero of Yavin*: soft aggro / midrange SPACE, the Plot Cinta Kaz → Luke pilot
  deck. 94 entries, 52.5% over 575 matches.

They differ by 6 points and play nothing alike, so "Luke DV" unqualified is always ambiguous — say Luke (ASH) or
Luke (JTL). The same trap applies to every leader with two printings in this meta (Thrawn, Vader, Leia, Jabba,
Lando, Ahsoka). ⚠ The gate is now **23 decks**, so a full `strength_test.sh` run is 23 x 22 x seeds x 2 =
**10,120 games** at 10 seeds, not 9,240.

Run a pairing with each deck on its own style's profile:

    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off \
      DevTools/SWUSimBotSelfPlayTest.php --games=8 \
      --deck=SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt --chooser=heuristic-aggro \
      --deck2=SWUSim/Tests/BotFixtures/meta-2026-09/control_krennic_splash.txt --chooser2=heuristic-control

With deterministic bots, a seed plays the same game whichever seat goes first. To get more distinct games,
vary the seed and swap which deck sits in seat 1, rather than swapping the first player.
