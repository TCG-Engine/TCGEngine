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
- the **style** (`aggro` / `normal` / `control`): which heuristic profile, and later which RL base model, plays it.
- the **flavours**: tags for the flavour profiles, the heuristic nudges on top of a style.

| File | Style | Flavours |
|---|---|---|
| aggro_vader_yellow | aggro | space |
| aggro_ahsoka_yellow | aggro | mixed-space |
| aggro_ahsoka_blue | aggro | ground, combo |
| aggro_boba_lakecountry | aggro | burn |
| aggro_greef | aggro | go-wide, mixed |
| normal_maul_blueforce | normal | tempo, force |
| normal_talzin_force | normal | tempo, force |
| normal_luke_datavault | normal | space, combo, pilot |
| normal_piett_red | normal | capital-ship |
| control_krennic_splash | control | credit-ramp |
| control_lando_blue | control | credit-ramp, tempo |
| control_piett_blue | control | capital-ship |
| control_aurra_red | control | hard |
| control_dedra_colossus | control | hard |

Run a pairing with each deck on its own style's profile:

    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off \
      DevTools/SWUSimBotSelfPlayTest.php --games=8 \
      --deck=SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt --chooser=heuristic-aggro \
      --deck2=SWUSim/Tests/BotFixtures/meta-2026-09/control_krennic_splash.txt --chooser2=heuristic-control

With deterministic bots, a seed plays the same game whichever seat goes first. To get more distinct games,
vary the seed and swap which deck sits in seat 1, rather than swapping the first player.
