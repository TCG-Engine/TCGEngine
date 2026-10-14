# Deterministic Fighting Game — simulator

Playable human-versus-bot prototype with the 36-card Core Set from the hosted card editor.
No database, hosted editor connection, API token, npm build, or generated engine files are needed
at runtime. This is an isolated PHP prototype in `DFGSim/`; it does not modify other simulators.

## Play

With the existing XAMPP Apache server running, open:

```text
http://localhost/TCGEngine/DFGSim/
```

Alternatively, from the repository root:

```sh
php -S 127.0.0.1:8786 -t DFGSim
```

Then open `http://127.0.0.1:8786/`. The pages and API also support hosted access at the site's
`/TCGEngine/DFGSim/` path. PHP 8.1+ is required.
The match persists in a dedicated PHP session across refreshes; cookies must be enabled. Only one
match is stored per browser session. New match replaces it. Two tabs share the match; stale actions
are rejected rather than silently applied to a different round or reset match.

Choose your opening hand, the bot's opening hand, difficulty, and first reaction priority. You start
at 40 life with all six hands available. Select one main card from your active hand and optionally
mark up to one other card as a bonus burn. Commit to reveal both players' cards. Burn a reaction card or pass
when it is your turn. After damage, choose one of your main card's bottom colors to begin the next
round. Browsing an inactive hand only previews it; it never changes your current active hand.

Hands use compact square tiles. Hover or keyboard-focus a tile to see its full card. Click a playable
card to select your main, then click another eligible card to select a bonus burn. Click either
selected card again to undo; commit explicitly when ready. Clear a selected card before replacing it.
During reactions, clicking an eligible card burns it immediately. Inactive,
reserved, and burned cards can be clicked to pin their preview. Selection badges show each card's role.
Played cards are stacked on the left with effects, totals, and burn thumbnails; compact fighter status
sits on the right. Shared `Core/CardMotion.js` animates staging, undo, burns, and the main's return after
damage, respecting reduced-motion preferences. Animations never change game state.
The fight log and prototype rules can be expanded when needed.

Your existing hand tabs show remaining and burned counts, with burned tiles marked in the card grid.
Six colored orbs beside the opponent's health show public hand inventory. Each starts full with six
cards and drains by one-sixth per permanent burn; a gold ring marks the active hand. Click an orb
to open the shared Core zone popup showing remaining cards and a separate burned section. Hover
or focus a card for shared Core details, or tap to pin them. Escape dismisses details, then the popup.
The layout adjusts across phone, tablet, and desktop widths.
Hidden main/bonus commitments and next-hand choices stay hidden until their normal reveal.

## Rules implemented

- Both players have six color hands, six distinct cards per hand. Both use the same Core Set.
- Damage is simultaneous: `max(0, attack − opponent block)`. Excess block does not heal.
- Bonus burns are committed face down with the main card. Both commitments reveal together.
- Each player may commit at most one bonus burn per round from the active hand. Reaction burns remain unlimited, one per reaction turn. Every burn is permanent.
- A committed main card is reserved, cannot also be burned, and returns after damage resolution.
- Players alternate one reaction burn or pass. Two consecutive passes close the window; any burn
  resets the pass count. First reaction priority alternates each round.
- A next hand must be a main card's listed color. Staying in the current color is legal only if
  that color is listed. Both next-hand selections remain hidden until both are selected.
- If both players reach zero life together, the match is a draw. You may resign any time.

## Advantage prototype

Start a fresh match for rules version 14. Each player starts with no advantage. Tokens are public,
persistent across hand changes, and capped at one of each color. They cannot be spent on their own,
on a bonus burn, or on a reaction. Select a spending card as your main, then choose its optional tokens
in the action panel. Spending and Combination Strike's Yellow destination remain hidden with the
main commitment; tokens leave your inventory only when both commitments reveal. Earned tokens arrive
after final simultaneous damage, so a current-round objective cannot fund a current-round spend.

Sixteen main cards have objectives (stat-based objectives use the opponent's printed stats):

| Main card | Objective after damage | Earn |
|---|---|---|
| Feint | Deal damage against printed block 4+ | Yellow |
| Body Lock | Deal damage against printed block 4+ | Orange |
| Sidestep | Take no damage against printed attack 5+ | Green |
| Brace | Take no damage against printed attack 5+ | Blue |
| Watchful Guard | Deal damage against an opponent who committed a bonus burn | Red |
| Haymaker | Deal 4+ damage without committing a bonus burn | Purple |
| Guarded Strike | Deal damage and take no damage | Green |
| Iron Guard | Deal damage and take no damage | Green |
| Off-Balance Sweep | Deal damage against printed attack 3 or less | Yellow |
| Bait and Slip | Take no damage against an opponent who committed a bonus burn | Blue |
| Measured Advance | Deal damage without your own bonus or reaction burn | Orange |
| Read the Strike | Deal damage against printed attack 5+ | Purple |
| Crushing Blow | Deal damage against printed block 5+ | Red |
| Counter Throw | Deal damage against printed attack 5+ | Orange |
| Grip Break | Deal damage against a bonus that increases its owner's block | Blue |
| Covering Step | Take no damage against printed attack 5+ | Red |

These create a cross-hand route: Feint → Yellow → Sidestep → Green → Brace → Blue → Watchful Guard → Red.
The v7 expansion added one objective per hand and a second source for each token color, preserving
all card IDs, stats, existing effects, spending payoffs, and switch routes in that pass. It rewards clean counters,
punishing committed burns, spotting high/low attack patterns, and breaking heavy defenses.
Existing main effects still apply; each objective is an additional ability. Objectives examine final
damage after all reactions, and holding that color already prevents another token from being awarded.
Measured Advance checks only its owner's burns; an opponent's reaction does not disqualify it.

Rules v8 replaces **Chain Grapple (ID 12)** with **Counter Throw**: Orange, attack 3 / block 3.
Against an opposing main with printed attack 5+, it gains +1 attack and +2 block, and earns Orange
advantage if it deals damage after reactions. Its bonus burn is +2 block; it still has no reaction
and switches to Red/Blue/Purple. This replaces the automatic own-burn attack trigger with an
opponent-dependent read and adds a defensive bonus option. The set remains 36 cards.

Rules v9 reworks **Charged Fist (ID 32)** to reward conserving cards: its 5 attack / 1 block
main gains +1 attack when no bonus burn was committed, instead of +2 attack for committing one.
Its bonus burn is now +2 attack. Spending Purple still grants +2 block, its reaction remains
+1 attack, and its Orange/Purple switches are unchanged. Reaction burns do not disable the
conservation ability. Iron Guard retains its +3 block bonus.

Rules v10 reduces **Haymaker (ID 31)** to 7 attack / 0 block. Its objective, bonus burn,
no reaction, and Red/Yellow routes stay unchanged.

Rules v11 changes **Firm Counter (ID 21)** to 4 attack / 2 block, with +3 block against
an opposing bonus commitment (5 block total). **Shield Bash (ID 20)** becomes 3 attack /
3 block, retaining its low-attack +2 attack condition and Green advantage +2 block spend.
Their burn effects, reactions, and switch destinations stay unchanged.

Rules v12 reworks **Iron Guard (ID 34)** to 2 attack / 5 block, with the objective
“Deal damage and take no damage: gain Green advantage.” It retains its +3 block bonus,
+1 block reaction, and Green/Blue switch destinations. The objective uses final damage
after reactions, requires outgoing damage, and remains capped at one Green token.

Rules v13 strengthens four Red mains: **Rushdown** is 6/1; **Feint** is 4/2 with +2 attack
against printed block 4+ (still 6 triggered attack); **Combination Strike** is 4/2; and
**Covering Step** is 3/3 with +2 block against printed attack 5+ and the objective
“Take no damage against printed attack 5+: gain Red advantage.” Their existing burns,
reactions, spending, and switches remain unchanged.

Rules v14 reworks **Grip Break (ID 10)** to 4 attack / 2 block. If the opponent committed
a bonus that increases their own block, Grip Break gains +1 attack and +1 block. Its objective
earns Blue advantage when it deals final damage against that committed defense. The bonus
is evaluated using its resolved branch; hybrid bonuses count, but reactions and bonuses
that gift block to the other player do not. Grip Break retains no bonus ability, its
+2 block reaction, and Yellow/Green/Blue switches.

| Specialized main | Spend | Additional payoff |
|---|---|---|
| Rushdown | Red | +2 attack |
| Driving Tackle | Orange | Ignore 2 opposing block |
| Exit Strike | Yellow | +2 attack |
| Shield Bash | Green | +2 block |
| Counterpunch | Blue | +1 attack and +1 block |
| Charged Fist | Purple | +2 block |

Follow Through (ID 5) is now **Combination Strike**, with printed attack 3 / block 2. Its existing
bonus and reaction burn effects remain; its printed switches are now Blue/Purple. As a main, spend up to two
different colors: Red/Purple each add 1 attack; Green/Blue each add 1 block; Orange ignores 1 block;
Yellow adds one chosen non-listed, non-exhausted hand different from the active hand to this round's switch options. Spending two
different colors also adds 1 attack. The extra switch expires after this round. All token payoffs
stack with the normal one-bonus-burn and reaction effects. The set remains six cards per hand.

Both token inventories appear under the fighters. Inspect card text to find objectives/payoffs.
The bot enumerates legal spending combinations using public inventories, values attainable objectives,
and charges an opportunity cost for consuming tokens. It still cannot see hidden spending choices.
The first 216-match smoke test exercised all six colors but no two-color bot combos; these are
provisional mechanics, not evidence of balance or optimal planning.

Hosted card text, stats, name, game rules, and template spacing have been synchronized through the
card-editing MCP. The local runtime works immediately. Remote orb/caption rendering still needs the
frontend deployment described in `DevTools/CardEditor/README.md` if it has not already been deployed.

Prototype interpretations needed to make the draft executable:

- Reaction burns reveal immediately, before the next player's reaction turn.
- Normal abilities, bonus burns, and reactions are additive and stack. Printed-stat conditions
  use the opposing main card's unmodified attack/block. Bonus-count conditions use the commitments
  before reveal, never later reaction burns. These conditions cannot change during the window.
- Apply all stat bonuses, then accumulated block reduction to the attacker's damage calculation,
  flooring effective block at zero. Reductions do not change printed block or anyone's attack.
- Both players choose their opening color in match settings; the initial reaction player is also
  a setting. There is no automatic round cap or invented sudden-death rule.

The bot chooses a hidden main card and bonus subset before your commitment. Its decision functions
receive only the public observation: remaining cards, active colors, previous revealed plays, and
its own data. It cannot inspect your face-down commitment or hidden next-hand choice. It evaluates
possible opposing main cards and occasional bonus burns, conserves useful cards, reacts to lethal
threats, and chooses its next hand from the revealed round's legal options. It is a heuristic bot,
not an optimal solver. Casual difficulty considers a wider range of moves.

Combat has no random outcomes. Seeded variation is used only to choose between the bot's candidate
moves. A fixed seed plus the same human actions reproduces the bot's choices. A match can stall if
both players indefinitely choose attacks that cannot beat the opposing block; this prototype does
not change your rules to force a finish. All card balance is provisional.

## Card data and source

The version 6 switching pass gives each color exactly one card that lists its own color (6/36):
Red — Rushdown; Orange — Off-Balance Sweep; Yellow — Darting Strike; Green — Driving Palm;
Blue — Counterpunch; Purple — Charged Fist. The other 30 cards force movement to another hand.
Every card retains two or three destinations. Incoming printed routes are distributed across the
colors (Red/Orange/Purple 14 each, Yellow/Blue 15 each, Green 16), and every hand remains reachable from every opening.
The Feint → Sidestep → Brace → Watchful Guard advantage route remains available. Yellow advantage
cannot restore a same-hand option to Combination Strike. These changes discourage repeated moves;
multi-hand sequences can still cycle. Previous match states require a fresh game for the new routes.

`data/cards.json` is a normalized snapshot exported from the hosted editor on 2026-10-13. Each card
keeps its hosted numeric ID, name, color, stats, effect text, and switch colors. Hosted edits do not
automatically update a running local match. The effect compiler recognizes only the current draft's
small vocabulary of conditional stat gains, block reduction, and the documented advantage objective/spend lines. Unsupported text fails explicitly;
card text is never evaluated as PHP or JavaScript.

The authoritative match state and bot live on the PHP server. `api.php` sends only the human's
observation and checks session token, match ID, and revision on actions. `app.js` renders the board
and submits human choices; it cannot author the bot's actions or supply combat totals.

This prototype intentionally uses a standalone local runtime rather than schema/codegen plumbing.
Its domain code lives in `Custom/` and `Bot/`. Future integration into the shared engine should add
a schema and use the generator, not hand-edit generated files.

## Exhaustive card-pair analysis

`php DevTools/DFGSim/pair-analysis.php` evaluates every legal main plus optional same-hand bonus
against every other plan. Rules v14 has 201 plans and 40,401 ordered matchups. Outputs are in
`DevTools/DFGSim/results/pairs-v14/`: `explorer.html`, `data.json`, `matchups.csv`, and `report.md`.
With XAMPP, open `http://localhost/TCGEngine/DevTools/DFGSim/results/pairs-v14/explorer.html`.
The v7 matrix and balance trials are preserved as historical reports.

The explorer provides an averaged main-card matrix, the full plan matrix, hand and burn filters,
exact-match damage/token results, bonus gains relative to the same main without a burn, and rankings.
Each Y-axis row shows mean damage edge ± population standard deviation over its included bonus
variants and selected opposing plans. These labels update with hand/burn filters and always describe
damage edge, even when another cell measure is selected. SD measures matchup variability, not
uncertainty in an estimated mean. Rows are spaced to keep every label readable.
It compares isolated rounds with no reactions or held tokens, and initially intact hands. Objective
rewards are reported separately; no arbitrary token or permanent-card value is added to damage.
Uniform plan weighting and damage edges are not full-match win probabilities.

`php DevTools/DFGSim/verify-pair-analysis.php` independently verifies all outcomes through actual
commit/pass/damage resolution and checks that all legal plans are included exactly once.
`node DevTools/DFGSim/pair-analysis-browser-test.mjs` checks explorer behavior and responsive layout.

For the historical Chain Grapple trials, run
`php DevTools/DFGSim/balance-opportunities.php DevTools/DFGSim/results/opportunities-v7 DevTools/DFGSim/results/opportunities-v7/baseline/runtime`, then
`python DevTools/DFGSim/summarize-opportunities.py`. These copy the archived custom runtime and card
snapshot into `DevTools/DFGSim/results/opportunities-v7/`, evaluate eight scenario catalogs, and
replay-verify every outcome. They do not edit the live cards or hosted editor. The comparison report
is at `http://localhost/TCGEngine/DevTools/DFGSim/results/opportunities-v7/index.html`; it compares
uniform plan weighting, 50/50 opposing burns, no burns, and always-burn profiles. Trial matrices
are clearly labeled and remain separate from the original report.

## Runtime verification

```sh
php DevTools/DFGSim/tests.php
php DevTools/DFGSim/advantage-tests.php
# Start the local server first. Uses existing DevTools/ui-harness Playwright dependencies.
node DevTools/DFGSim/browser-test.mjs
```

Set `DFG_TEST_URL` for a different serving URL. Set `DFG_SCREENSHOT_DIR` to save browser QA images.
Set `DFG_BROWSERS` to a comma-separated subset such as `chromium,webkit` to select installed browsers;
the default runs all three and reports launch failures rather than treating them as passing checks.
The domain tests cover card effects, conditions, hidden information, legal burns, permanent
discarding, alternating priority, simultaneous knockout, mandatory hand choices, reproducibility,
non-cheating bot decisions, defensive reactions, and three complete seeded self-play matches.
Browser checks exercise Chromium, Firefox, and WebKit, including mobile layout and action guards.

## Card gallery

Open `http://localhost/TCGEngine/DFGSim/gallery.php` or use **Card gallery** in the game header.
The gallery reads `DFGCatalog()` on each load and uses `card-renderer.js`, the same detail
renderer used by game previews and opponent card details. Card data and detail markup are
shared; no separate card snapshots or gallery generation step is needed. Search covers names,
abilities, burns, hand styles, and switch colors. Hand filters and full details work on mobile.
The gallery does not create or modify a match. Reload it after a card edit to see updated data.
