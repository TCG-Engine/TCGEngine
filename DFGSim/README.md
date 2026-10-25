# Deterministic Fighting Game — simulator

Playable human-versus-bot prototype with the 36-card Core Set from the hosted card editor.
No database, hosted editor connection, API token, npm build, or generated engine files are needed
at runtime. This is an isolated PHP prototype in `DFGSim/`; it does not modify other simulators.

## Play

The separate [Cloudbreak Stair roguelike](roguelike/) is playable at
`/TCGEngine/DFGSim/roguelike/`: a short branching run with card rewards, armor,
three mythological opponent types, and a dragon boss. See its [rules and implementation notes](roguelike/README.md).

The main menu has two actions: Learn to play and Play against the bot. Play resumes
an unfinished session match, or opens match setup when there is no active fight.
Returning to the menu or refreshing preserves the current session match. The in-match
menu provides new matches, the card gallery, rules, and a link to the tutorial.
Starting another fight replaces the saved match.

The menu warms all 36 card-art images in the background, two low-priority requests
at a time, starting with the saved or default opening hands. Changing opening hands
moves those colors ahead in the queue. The preloader shares the renderer’s versioned
art URLs and never blocks starting a match. It pauses in hidden tabs and skips
background downloads when the browser reports Save-Data or a 2G connection.
The same queue warms the arena still, hit/block/knockout animations, knockout final
frames, and ambient video. Idle fighters already load through the page’s image tags.
Reduced-motion visitors skip animated downloads. Warmed reaction clips restart from
in-memory data instead of downloading a new cache-busting URL on every hit; the
ambient video also uses its warmed data when ready, and only plays in the arena.
Browsers that reject preloaded MP4 playback retry the normal versioned video URL.

`learn.php` offers an introduction and two guided lessons (14 steps) using the normal match page, card renderer,
arena, hand browser, previews, Commit, Pass reaction, advantage picker, and switch controls.
The floating guide reuses AzukiSim’s popup and spotlight styles, highlights the next
card or control, and shows the full explanation and next action in each popup.
One opening fight teaches bonus and reaction burns together; the introduction explains all loss conditions.
Back, Restart, and Lessons allow replay without collapsible sections. Transitions show loading
feedback and connection errors inside the guide. Requests time out after 12 seconds; failed
responses trigger a read of saved progress before retrying. The server
validates the lesson action and runs it through the real engine with a scripted opponent. Tutorial progress is saved in a
separate session entry and never replaces the regular match. Reload to resume, restart
or select a lesson, or return to the menu to resume your saved fight.

Validate with `php DFGSim/tests/tutorial.php` and
`node DevTools/ui-harness/dfg-menu-tutorial.mjs` (desktop/mobile, Chromium/Firefox/WebKit).

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

Cards use cream fight-poster frames, bold condensed titles, neutral attack/block values and burn
rows, and individually generated monochrome martial-arts illustrations with broad painterly shading and
restrained motion cues. Each scene is tailored to that card's name, rules, and role, using shades of its
printed hand color throughout the figures and background. A centered
switch icon divides the footer above named destination dots. The gallery and in-game inspectors
share the same template; compact tiles,
played cards, burns, and opponent inventory retain the corresponding art. Production WebP
illustrations are in `assets/cards/individual/`; asset mappings are documented in `assets/README.md`.

Hands use compact tiles. Hover or keyboard-focus a tile to see its full card. Click a playable
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

Start a fresh match for rules version 44. Each player starts with no advantage. Tokens are public,
persistent across hand changes, and capped at one of each color. They cannot be spent on their own,
on a bonus burn, or on a reaction. Select a spending card as your main, then choose its optional tokens
in the action panel. Spending choices remain hidden with the
main commitment; tokens leave your inventory only when both commitments reveal. Earned tokens arrive
after final simultaneous damage, so a current-round objective cannot fund a current-round spend.

Eighteen main cards have objectives (stat-based objectives use the opponent's printed stats):

| Main card | Objective after damage | Earn |
|---|---|---|
| Rooted Guard | Deal 4+ damage in the following round; independent of its printed-attack read | Green |
| Glancing Step | Take exactly 1 final damage; outgoing damage is not required | Yellow |
| Feint | Deal damage against printed block 4+ | Yellow |
| Body Lock | Deal damage against printed block 4+ | Orange |
| Sidestep | Take no damage against printed attack 5+ | Green |
| Brace | Take no damage against printed attack 5+ | Blue |
| Watchful Guard | Deal damage against an opponent who committed a bonus burn | Red |
| Haymaker | Deal 4+ damage without committing a bonus burn | Purple |
| Guarded Strike | Deal damage and take no damage | Green |
| Iron Guard | Deal damage | Green |
| Off-Balance Sweep | Deal damage against printed attack 3 or less | Yellow |
| Bait and Slip | Take no damage against an opponent who committed a bonus burn | Blue |
| Measured Advance | Deal damage without your own bonus or reaction burn | Orange |
| Read the Strike | Deal damage against printed attack 5+ | Purple |
| Crushing Blow | Deal damage against printed block 5+ | Red |
| Counter Throw | Deal damage against printed attack 5+ | Orange |
| Grip Break | Deal damage against a defensive bonus, or take no damage against carried attack pressure | Blue |
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
an opposing bonus commitment (5 block total). **Forearm Check (ID 20)** becomes 3 attack /
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

Rules v15 replaces **Punish the Setup (ID 28)** with **Find the Gap**: Blue, attack 3 / block 2.
Against an opposing main with printed block 4+, its main gains +3 attack. Its bonus burn grants
+3 attack against printed block 4+, otherwise +1 attack; its reaction grants +1 attack unconditionally.
It retains Green/Orange/Purple switches. Watchful Guard remains Blue's bonus-commitment counter,
while Find the Gap complements Catch the Opening by targeting strong printed guards.
The pair-analysis outputs below remain historical v14 results and do not include this replacement.

| Specialized main | Spend | Additional payoff |
|---|---|---|
| Rushdown | Red | On hit, +2 attack next round only |
| Driving Tackle | Orange | Ignore 2 block; on hit, choose one opposing legal next color to prohibit if more than one remains |
| Driving Kick | Yellow | On hit, choose the opponent's next hand from their legal destinations |
| Forearm Check | Green | +2 block and permission to remain Green |
| Counterpunch | Blue | +1 attack and +1 block; on hit, prevent the opponent earning advantage this round |
| Charged Fist | Purple | No stat bonus; on hit, permanently burn the opposing main after damage |

Rules v17 revises these four specialized payoffs. Driving Tackle's route cut is chosen after
damage and automatic effects, from the opponent's actual available destinations, including expanded
routes. It removes exactly one color, never the last one. Both players finish any route cuts before
either chooses a next hand; choices cannot exploit a hidden or already-selected destination.
Charged Fist no longer burns itself or receives attack from Purple spending. On a miss neither
main burns, but the token is still spent. On a hit the opposing main completes its simultaneous
damage, main effects, and objective before returning would occur; it is permanently discarded.
Simultaneous Purple hits can burn both mains, and burning a player's final card defeats them.

Rules v16 gives **Driving Palm** an on-hit recovery of 1 life, capped at 40; recovery cannot
prevent a knockout. **Pivot** opens every other available color after taking no damage.
**Exit Strike** opens those routes on a hit; its Yellow spend guarantees the routes and adds block.
**Cross-Step** forces an opponent out of their current color on a clean hit, if another legal
destination remains. **Heavy Feint** ignores only block granted to the opponent by their committed
bonus's resolved branch; printed block, main abilities, and reaction block remain effective.
Stats, bonus/reaction burns, printed destinations, objectives, and Combination Strike are retained.
Rooted Guard's later v24 revision rewards reading high printed attack (see below).

All hit/no-damage triggers inspect final simultaneous damage. Counterpunch's denial applies to
simultaneous objectives before either side earns tokens; it never removes existing tokens.
Advantage is still committed face down and consumed on reveal, even when its hit condition fails.
Rushdown pressure follows the player across hand changes, applies to their next main for one
round, and does not stack. A fresh hit with another Red spend can renew it for the following round.
Route additions are applied before opposing restrictions. Restrictions never remove the final
legal destination. Empty printed destinations stay legal; entering an empty hand loses the fight,
with no fallback to an unrelated hand. Burning the last available card also loses; simultaneous losses are a draw.
These exceptions supersede the original printed-destination-only and main-always-returns rules.

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

**Pro** is an optional, slower search profile in the Bot style selector. It evaluates a
simultaneous payoff matrix of main/bonus plans, rolls the current exchange through the real
rules (including reactions, objectives, burns, relocation and forced routes), then evaluates
legal next-hand combinations and the following round's main-card matchups. Regret matching
produces seeded mixed strategies for hidden commitments and simultaneous hand choices.
Its symmetric evaluation values life, remaining cards, held advantages, carry, and empty hands.
Live reaction choices search three alternating decisions, retaining pass and the two most
promising burns at each step. If that search predicts immediate defeat, a rescue check can
start a defense requiring up to three burns. Pro receives only public observations.

This is bounded lookahead, not a proof of optimal play. Current-round rollouts use Tactical
reactions and follow-up choices. Main candidates retain each available main's best no-bonus
plan and up to four bonus plans, with spend/bonus variants shortlisted by the existing heuristic.
The next-round horizon considers mains and token spends. When either player has 12 or less
life it also shortlists one bonus per main and checks emergency defensive reaction bundles
of up to three burns (from at most eight candidate reactions). It does not solve a full future
reaction game or subsequent routes. Uncertain next-round victories are bounded at 50 points;
an actually resolved current win is worth 1000. Remaining cards are valued more strongly
after loss analysis exposed over-burning. Equivalent immediate flexible-spend variants are
collapsed. Matrix strategies, resource weights and forecasts remain approximate.

Run `php DevTools/DFGSim/pro-bot-tests.php` for solver, information-boundary and tactical
fixtures. `php DevTools/DFGSim/pro-evaluation.php` runs 24 Pro-versus-Tactical games across
six mirror openings, both seats and both priorities; `--suite=all` covers all 36 ordered
openings (144 games). `--suite=balanced` runs 72 games across all ordered openings, both
seats and balanced priorities. `--seed-offset`, `--repeats` and `--runtime-root` support
matched, reproducible experiments. `--opponent=pro-v1` tests against the preserved original
Pro using unchanged v43 game rules. It saves actions, timings and availability-normalized
main counts under `DevTools/DFGSim/results/pro-current/`. The regular balance runner also accepts
`--profiles=pro`; existing default profiles remain unchanged.

The [loss-analysis study](../DevTools/DFGSim/results/pro-improvement/report.md) preserves four
development variants, original/revised validation, direct Pro-versus-Pro games and replay checks.
The [follow-up crossplay study](../DevTools/DFGSim/results/pro-crossplay/report.md) audits the
remaining loss and draws against original Pro, tests candidate-list/history/solver changes,
and records fresh matched results including rejected variants. Experimental runtimes are
isolated from the live profile; more accurate optimization of estimated payoffs need not
translate into stronger real play.

Combat has no random outcomes. Seeded variation is used only to choose between the bot's candidate
moves. A fixed seed plus the same human actions reproduces the bot's choices. A match can stall if
both players indefinitely choose attacks that cannot beat the opposing block; this prototype does
not change your rules to force a finish. All card balance is provisional.

## Card data and source

The switching rules give each color exactly one card that lists its own color (6/36):
Red — Rushdown; Orange — Off-Balance Sweep; Yellow — Darting Strike; Green — Firm Counter;
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
against every other plan. Rules v37 has 201 plans and 40,401 ordered matchups. Outputs are in
`DevTools/DFGSim/results/pairs-v37/`: `explorer.html`, `data.json`, `matchups.csv`, and `report.md`.
With XAMPP, open `http://localhost/TCGEngine/DevTools/DFGSim/results/pairs-v37/explorer.html`.
The v7/v14/v17/v18 matrices and balance trials are preserved as historical reports.
The current matrix records capped recovery and actual legal next colors in addition to damage and
objective rewards. Select Life edge after recovery to include Driving Palm's life recovery;
the default damage rankings and row labels still describe outgoing minus incoming damage.
The one-round matrix does not price next-round pressure or delayed objectives; Rooted Guard's
delayed Green objective therefore has no immediate objective award in that matrix. Full-game
simulations resolve these mechanics across rounds with the live engine and bot.

`php DevTools/DFGSim/spender-analysis.php` separately compares spending versus saving the matching
token on each of the six specialized mains, keeping both players' committed cards identical.
Its 6,633 paired comparisons use 13,266 authoritative engine resolutions. Read `spender-report.md`
or inspect `spender-matchups.csv` and `spender-summary.json` in the same versioned folder. It reports
immediate life-edge changes separately from pressure, route cuts, objective denial, and permanent
main burns; future resource value is not converted to damage. Opponents hold no tokens and do
not react. Combination Strike's flexible combinations remain covered by the functional tests.

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
php DevTools/DFGSim/mechanics-tests.php
node DevTools/DFGSim/mechanics-browser-test.mjs
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

Rules v18 rebalances **Firm Counter (ID 21)** to 2 attack / 2 block. Against an opposing
committed bonus burn, it gains +3 attack and +3 block, reaching 5/5 before other effects.
Reaction burns do not activate this condition. Its +2 block bonus, +1 block reaction, and
Red/Blue switches remain. The v17 pair analysis and play-to-win reports are historical
results from before this change. The full-game simulation has now been rerun for v18:
`DevTools/DFGSim/results/play-win-v18/play-win-report.md` contains the 2,160-game rankings,
and `comparison-v17.md` compares Firm Counter using identical seeds/openings/profiles.
All 472,575 recorded-data checks pass. The v18 pair-analysis matrix has also been regenerated and
all 40,401 outcomes replay-verified. For actual full-game main-plus-bonus win associations, read
`DevTools/DFGSim/results/play-win-v18/pair-play-win-report.md`; these deduplicate repeated pair
uses per player-game and keep draws in the denominator, unlike the isolated round damage matrix.

Rules v19 replaces **Pivot (ID 16)**, **Cross-Step (ID 17)**, and **Exit Strike (ID 18)**:

| Card | Attack / block | Main | Bonus | Reaction | Printed switches |
|---|---|---|---|---|---|
| Outside Step | 3 / 3 | +2 attack against opposing printed block 4+ | +2 attack | +1 block | Red / Blue / Purple |
| Glancing Step | 3 / 4 | Take exactly 1 damage: +2 attack next round and Yellow advantage | +1 attack / +1 block | +1 block | Red / Orange / Purple |
| Driving Kick | 5 / 0 | +1 block against opposing printed block 2 or less; spend Yellow on hit to choose their legal next hand | +2 attack | None | Orange / Green / Purple |

Glancing Step checks final incoming damage after reactions, independently of outgoing damage.
Its pressure lasts the next round only and does not stack. Counterpunch can deny the objective
award but cannot deny pressure. Driving Kick consumes its token on reveal, even on a miss.
All Orange cuts finish first, then both Yellow destination choices, then normal hidden hand
selections. Forced players confirm their sole permitted destination. A target with only one
available destination needs no redundant choice. In v38, empty printed routes remain legal and lethal; cut routes are excluded.
The new `directing` phase, bot choices, and simulation traces preserve this order.
V19 verification: all domain/advantage/mechanic checks pass; 5,625 spending matchups resolve.
Chromium and WebKit pass desktop/mobile control and matrix checks; Firefox cannot launch here.
The 2,160-game batch has 1,746 decisive games, 413 draws, and one 60-round cap, with all 467,474
recorded-data checks passing. Main and exact main/bonus win reports are in
`DevTools/DFGSim/results/play-win-v19/`; matrix and specialized spending reports are in `pairs-v19/`.

## Darting Strike revision (v20)

Darting Strike is now 4 attack / 2 block and gains +3 block against printed attack 6 or more,
reaching 4/5 in that matchup. Its bonus burn and destinations are unchanged.
V20 analysis reran all 40,401 ordered matchups (independently replayed) and 6,633 specialized
spending comparisons. The 2,160-game batch finished with 1,729 decisive games, 430 draws,
and one 60-round cap; all 477,043 recorded-data checks passed. Historical game reports are in
`DevTools/DFGSim/results/play-win-v20/`; historical matrix/spending reports are in `pairs-v20/`.
Darting Strike's pooled main-play win rate rose from 36.9% (N=1,429) to 39.7% (N=1,754),
against an overall v20 win baseline of 40.0% with draws included. Its no-bonus mean damage
edge improved from -2.1940 to -1.2189; with Sidestep it improved from +0.0846 to +0.6915.
Both batches used identical starting scenarios, but card-use cohorts and game paths diverge;
the differences describe these bot policies rather than establish a causal strength estimate.
Historical v19 reports remain in their original folders.

## Catch the Opening revision (v21)

Catch the Opening's printed block increases from 1 to 2. It is 3/2 normally and 6/2 against
printed block 2 or less; its abilities, burns, and destinations are unchanged.
The v20 pair and game simulation reports predate this edit.

## Outside Step revision (v22)

Outside Step is now 3 attack / 2 block and gains +4 attack against printed block 4 or more,
reaching 7/2 in that matchup. Its burns and destinations are unchanged.
The v20 pair and game simulation reports predate this edit.

V22 analysis includes both the Catch the Opening and Outside Step revisions. All 40,401
ordered matchups passed independent engine replay; 6,633 spending comparisons also completed.
The 2,160-game batch produced 1,777 decisive games, 383 draws, and no 60-round caps.
All 473,072 recorded-data checks passed. Current reports are in
`DevTools/DFGSim/results/play-win-v22/`; the matrix/spending reports are in `pairs-v22/`.
Main-play win rates versus v20: Catch the Opening 39.0% (N=1,183) to 42.8% (N=1,668);
Outside Step 40.5% (N=1,389) to 42.1% (N=1,287). The overall win baseline rose from
40.0% to 41.1%, with draws included. Starting scenarios match, but both edits and changing
card-use cohorts mean these are descriptive comparisons, not isolated causal estimates.
Outside Step's no-bonus mean damage edge against printed block 4+ improved from -0.4118
to +0.2745; across all opposing plans it fell from -1.2189 to -1.8955. Its stronger guard
matchup comes with a weaker fallback. Catch the Opening's no-bonus edge improved from
-1.5970 to -0.6418. Exact-pair full-game rankings are in `pair-play-win-report.md`.

## Outside Step revision (v23)

Outside Step is now 1 attack / 3 block, with +6 attack against printed block 4+ (7/3).
Independently, dealing any final damage grants +1 attack next round. The hit does not require
the printed-block read. Pressure follows hand changes, lasts one round, and does not stack.
Burns and destinations are unchanged. V22 analysis predates this revision.

## Rooted Guard revision (v24)

Rooted Guard is now 0 attack / 7 block. Against an opposing main with printed attack 5 or more,
it gains +3 attack this round, reaching 3/7. Modified attack does not satisfy the condition.
Its burns and destinations are unchanged. Existing balance reports predate this revision.

## Rooted Guard delayed objective (v25)

Playing Rooted Guard sets up an independent objective: deal 4+ final damage in the following
round to earn Green advantage. The printed-attack read and a hit during setup are not required.
The objective follows hand changes and expires after that single round. Current-round damage
does not count. Counterpunch can deny the award when it is earned, but does not prevent setup.
Held tokens still cap at one per color. Pending objectives are public in the fighter display.
Both fighters show the source card, requirement, reward color, and whether the objective is
active this round or next round. The display reads objective metadata rather than card-specific
wording. Future damage-threshold objectives share the same parser, public state, resolution,
and bot evaluation with their own threshold and reward color. Existing v25 sessions remain valid.
Existing balance reports predate this revision.

## Driving Kick revision (v26)

Driving Kick remains 5 attack / 0 block and now gains +4 block against opposing printed
block 2 or less, reaching 5/4 on a correct read. Its Yellow spend, burns, and destinations
are unchanged. Existing balance reports predate this revision.

## Counterpunch revision (v27)

Counterpunch is now 4 attack / 2 block and gains +3 block against opposing printed attack
6 or more, reaching 4/5. Spending Blue still adds +1 attack / +1 block and denies opposing
advantage awards on hit; with the read and spend it reaches 5/6. Burns and destinations
are unchanged. Existing balance reports predate this revision.

## Find the Gap revision (v28)

Find the Gap is now 4 attack / 2 block, reaching 7/2 against opposing printed block 4+.
Its conditional +3 attack, burns, and destinations are unchanged.
All 67,770 gameplay checks pass. The v28 analysis reran 40,401 ordered pair matchups and
6,633 spending comparisons, with independent engine verification. The full-game batch ran
2,160 games: 1,803 decisive, 357 draws, no 60-round caps, mean 18.51 rounds. All 482,409
recorded-data checks passed. Reports are in `DevTools/DFGSim/results/play-win-v28/` and
`pairs-v28/`; `revision-comparison-v22.md` compares the five recently revised cards.
Find the Gap now leads main-play win rates at 45.5% (778/1,709 completed player-games).
Driving Kick is 44.0%, Rooted Guard 44.1%, Counterpunch 42.5%, and Outside Step 42.0%.
The overall win baseline with draws included is 41.7%. All changes since v22 are bundled;
these are policy-specific associations, not isolated estimates of each edit's strength.
Full-game simulations include next-round pressure and delayed objectives; the one-round
matrix measures only immediate results.

## Driving Tackle revision (v29)

Driving Tackle's printed block increases from 0 to 1, making it 6 attack / 1 block.
Its Orange spend, burns, and destinations are unchanged. V28 balance reports predate this edit.
V29 reran and independently verified all 40,401 ordered pair matchups, plus 6,633 spending
comparisons. The 2,160-game batch completed with 1,792 decisive games, 368 draws, and no
60-round caps (mean 18.66 rounds). All 486,101 recorded-data checks passed. Current reports
are in `DevTools/DFGSim/results/play-win-v29/` and `pairs-v29/`; `comparison-v28.md` compares
Driving Tackle using identical starting scenarios. Its pooled main-play win rate rose from
37.4% (523/1,400) to 39.2% (707/1,803); tactical exposure increased from 215 to 529 player-games,
while the tactical win rate fell from 44.2% to 39.9%. Card-use cohorts and later paths diverge,
so the rates are descriptive associations. Its no-bonus mean damage edge improved from
-2.3383 to -1.4677; with Body Lock it improved from -0.7363 to +0.1343. The overall v29
win baseline with draws included is 41.5%.

## Rooted Guard revision (v30)

Rooted Guard is now 0 attack / 6 block, reaching 3/6 against opposing printed attack 5+.
Its independent delayed Green objective, burns, and destinations are unchanged.
V29 balance reports predate this edit.
V30 reran and independently verified all 40,401 ordered pair matchups and 6,633 spending
comparisons. The full-game batch completed 2,160 games: 1,798 decisive, 362 draws, and no
60-round caps (mean 18.55 rounds). All 484,625 recorded-data checks passed. Current reports
are in `DevTools/DFGSim/results/play-win-v30/` and `pairs-v30/`; `comparison-v29.md` compares
Rooted Guard using identical starting scenarios. Its pooled main-play win rate changed from
42.7% (N=1,866) to 42.2% (N=1,222), and tactical rate from 42.2% to 40.2%. Total main plays
fell from 2,747 to 1,605. Its no-bonus mean damage edge fell from +0.1592 to -0.1592, and
with Forearm Check or Driving Palm from +0.6269 to +0.2935. These are descriptive bot-policy
associations with changing card-use cohorts; the overall v30 win baseline is 41.6% with draws.

## Guarded Strike revision (v31)

Guarded Strike is now 0 attack / 5 block. If it takes no combat damage after reactions,
it deals 2 direct damage after combat. Direct damage bypasses block; both retaliations
check the same combat exchange and resolve simultaneously without recursion. Total damage
includes combat and direct damage and determines damage-based triggers, objectives, and
knockouts before recovery. The existing clean-counter Green objective remains; mirrored
retaliations both deal damage, so neither player earns it. Burns and destinations are unchanged.
V31 verification: all 67,789 gameplay checks pass, along with 104 Chromium/WebKit UI checks;
Firefox cannot launch here. All 40,401 ordered pair matchups passed independent engine replay,
and 6,633 spending comparisons completed. The 2,160-game batch produced 1,755 decisive games,
405 draws, no 60-round caps, and all 477,365 recorded-data checks passed. Current outputs are
`DevTools/DFGSim/results/play-win-v31/` and `pairs-v31/`. `guarded-strike-analysis.md` details
the comparison: Guarded Strike's no-bonus win rate rose from 41.6% to 45.4%; overall main rate
rose from 43.3% to 45.4%, leading the batch. No-bonus vs no-bonus matchups changed from
18 favorable / 15 even / 3 unfavorable to 23 / 1 / 12; Green objective successes increased
from 11/36 to 23/36. It is more polarized but stronger by these measures, not a demonstrated
nerf. Both batches share starting scenarios; differing card-use cohorts and paths mean
the win rates remain descriptive bot-policy associations.

## Guarded Strike revision (v32)

Guarded Strike is now 0 attack / 4 block. Its 2 direct damage after taking no combat damage,
clean-counter Green objective, burns, and destinations are unchanged.
V32 verification: 67,787 gameplay checks and 104 Chromium/WebKit UI checks pass; Firefox
cannot launch here. All 40,401 ordered pair outcomes passed independent engine replay, plus
6,633 spending comparisons. The 2,160-game batch had 1,766 decisive games, 394 draws, and no
60-round caps (mean 17.81 rounds); all 465,757 recorded-data checks passed. Current outputs
are in `DevTools/DFGSim/results/play-win-v32/` and `pairs-v32/`; `guarded-strike-analysis.md`
compares 0/5 to 0/4. Overall main-play win rate fell from 45.4% to 41.0%, and no-bonus plan
win rate from 45.4% to 43.5%. Main plays fell from 2,740 to 1,305. No-bonus matchups became
18 favorable / 1 even / 17 unfavorable, versus 23 / 1 / 12. The overall v32 win baseline
is 40.9% with draws. These remain descriptive bot-policy associations with changing cohorts.

## Outside Step revision (v33)

Outside Step is now 1 attack / 4 block, reaching 7/4 against opposing printed block 4+.
Its independent on-hit +1 attack next round, burns, and destinations are unchanged.
V33 verification: all 67,787 gameplay checks pass. All 40,401 ordered pair matchups passed
independent engine replay, plus 6,633 spending comparisons. The 2,160-game batch produced
1,743 decisive games, 417 draws, no 60-round caps (mean 17.80 rounds), with all 465,196
recorded-data checks passing. Current reports are in `DevTools/DFGSim/results/play-win-v33/`
and `pairs-v33/`; `outside-step-analysis.md` compares the change. Outside Step's main-play
win rate changed from 41.4% to 40.9%, while the overall baseline changed from 40.9% to 40.3%.
Tactical rate rose from 37.6% to 40.5%, and total main plays from 1,622 to 2,129. No-bonus
matchups changed from 9 favorable / 7 even / 20 unfavorable to 9 / 13 / 14; mean damage
edge across all plans improved from -1.4527 to -1.0348. Printed block 4 also activates opposing
strong-guard reads. Card-use cohorts change, so win rates remain descriptive associations.

## Red identity revision (v34)

Quick Jab is 3/2 and gains +2 attack without a bonus, preserving 5/2 when conserving cards.
Feint is 2/3; against opposing printed block 4+ it gains +2 attack and ignores 3 block,
reaching 4/3 with 3 penetration. Its Yellow objective remains.
Combination Strike is 3/1. Spending any one advantage grants +1 attack (4/1); spending any
two different colors instead grants +4 attack, +2 block, and 2 penetration (7/3). The two-token
payoff replaces the one-token payoff. Color-specific spending and Yellow's extra destination
are removed from this card. Spending is optional, hidden until reveal, and capped at two held
distinct colors. Burns, objectives, and destinations are otherwise unchanged for all three.
All 46,481 gameplay checks pass, including 3,969 full spending matchups and all token-color
pairs. 132 Chromium/WebKit UI checks pass; Firefox cannot launch here. The lower exhaustive
matchup count reflects removal of the old Yellow destination variants. All 40,401 ordered
no-token pair outcomes passed independent replay, plus 6,633 specialized spending comparisons
and 22,110 Combination Strike spend-mode resolutions. The 2,160-game batch produced 1,784
decisive games, 376 draws, no 60-round caps (mean 17.71 rounds); all 462,159 recorded-data
checks passed. Current reports are in `DevTools/DFGSim/results/play-win-v34/` and `pairs-v34/`.
`red-revision-analysis.md` summarizes the comparison: Quick Jab 39.3% to 37.0%, Feint 36.6%
to 37.8%, Combination Strike 41.2% to 48.0%. Its two-token mode is associated with 67.3%
wins in 594 player-games, versus 56.7% in 328 before; the charged payoff is a balance watchpoint.
These are selected-use bot-policy associations, not causal estimates of strength.

## Card gallery

Open `http://localhost/TCGEngine/DFGSim/gallery.php` or use **Card gallery** in the game header.
The gallery reads `DFGCatalog()` on each load and uses `card-renderer.js`, the same detail
renderer used by game previews and opponent card details. Card data and detail markup are
shared; no separate card snapshots or gallery generation step is needed. Search covers names,
abilities, burns, hand styles, and switch colors. Hand filters and full details work on mobile.
The gallery does not create or modify a match. Reload it after a card edit to see updated data.

## Counterplay mechanics revision (v35)

- Brace stays 2/5 with its Blue objective. Its no-bonus +1 block becomes a Green spend:
  suppress opposing carried attack this round, and prevent either fighter gaining attack bonuses
  for next round. Own carried attack and delayed objectives remain.
- Mirror Step stays 3/3. Opposing printed attack exactly 3 gives +2 block; independently,
  printed block exactly 3 gives +2 attack. Spending Blue removes one opposing held token for
  dealing damage and another for taking no damage. Each removal is a public choice after damage,
  before new objective rewards; tokens spent on reveal cannot be removed. Empty inventories
  automatically finish the removal phase, and both players can have pending removals.
- Clinch Throw keeps 4/1 and ignore 2 block. Spending Orange adds an on-hit lock on opposing
  bonus and reaction burns next round. Main play and advantage spending stay legal. The public
  lock follows hand changes and expires after that round.
- Haymaker becomes 8/0 with its existing Purple objective. Dealing no combat damage creates
  -2 final block next round, clamped at 0 after all block bonuses. The public penalty follows
  hand changes and expires after one round. Successful hits do not create it.

The engine, public observations, action UI, bot legal choices and evaluation, and hosted editor
are synchronized. Start a fresh match. The rules popup explains timing; both fighters show burn
locks, Haymaker recovery, and Brace suppression. Mirror Step adds a token-removal chooser.
Darting Strike remains unchanged: its proposed cancel and replacement stats are under discussion.
`darting-analysis.php` exhausts 81 candidate statlines, distinguishing printed-stat reads from
actual damage vulnerability. Results and assumptions: `results/pairs-v35/darting-analysis.md`.

Gameplay checks: 50,960 passing, including 4,356 exhaustive main/spend matchups. UI checks:
180 Chromium/WebKit desktop/mobile mechanics checks and 32 pair-explorer checks. Firefox still
cannot launch on this host. All 40,401 no-token pair outcomes pass independent engine replay;
10,050 specialized-spend comparisons and 22,110 Combination Strike resolutions also pass.
The specialized-spend matrix has no opposing held tokens or incoming pressure, so its zero
immediate gains for Mirror Step and Brace do not measure their resource/pressure utility.
The full-game v35 batch produced 1,737 decisive games, 423 draws, and no 60-round caps
(mean 16.91 rounds). All 443,317 recorded-data checks passed. Reports are in
`results/play-win-v35/`; `counterplay-revision-analysis.md` compares the same 2,160 scenarios
with v34 and records how often the new effects occurred. The main-play win associations are
Clinch Throw 41.4% (N=1,861), Brace 40.0% (N=1,380), Mirror Step 43.1% (N=1,232), and
Haymaker 40.5% (N=2,609). These rates pool token choices and are not causal balance estimates.

## Darting Strike cancel prototype (v36)

Darting Strike is 6/0. Its old conditional +3 block is removed. Its bonus (+2 attack),
no reaction, and Red/Orange/Yellow destinations remain. Declare its optional Yellow spend
with the face-down commitment; consume that token on reveal even when keeping Darting.
Before reactions, either keep it or permanently burn it and choose another available Yellow
main. A committed bonus is already burned and cannot be the replacement. Carried attack and
block penalties stay. The replacement's normal abilities, objective, and destinations apply;
opposing printed-stat reads recalculate. The replacement cannot spend advantage, including
Driving Kick's Yellow payoff. Both replacement decisions remain hidden until both players
choose; then reveal both and start ordinary reaction priority. A Clinch burn lock prevents
cancelling. No available alternatives automatically keeps Darting, without refunding Yellow.

The UI offers Keep and replacement buttons before reactions, shows the cancel burn, and marks
the paid Yellow token as a Darting spend rather than a replacement spend. The bot forecasts
its own cancel against sampled opposing commitments and charges permanent card loss when
choosing a replacement. All choices use public information; it cannot inspect an opponent's
pending replacement.

All 66,953 gameplay checks pass, including 4,489 full main/spend matchups and 4,464 resolved
cancel matchups. An additional 6,231 cancel-analysis outcomes cover every legal own bonus,
all 201 opposing main/bonus plans, and every replacement. All 40,401 no-token pair outcomes,
10,050 specialized-spend comparisons, and 22,110 Combination Strike outcomes pass.
240 Chromium/WebKit mechanics UI checks and 32 pair-explorer checks pass on desktop/mobile;
Firefox cannot launch on this host. Reports: `results/pairs-v36/cancel-report.md` and the
v36 pair explorer. The one-round baseline matrix has no tokens, so it does not exercise cancels.

Full-game traces retain the original face-down commitments in `plans`, each keep/replacement
choice in `cancels`, and the resulting commitments in `combatPlans`. Win-rate and pair reports
attribute a cancelled play to Darting Strike, including damage from the replacement; replacements
are not counted as a second main commitment. Replay checks validate choices, carried effects,
paid tokens, original-main burns, replacement reservation, reactions, and final outcomes.

The v36 batch completes 2,160 games: 1,745 decisive, 415 draws, no 60-round caps, mean
16.15 rounds. All 460,036 recorded-data checks pass. Darting's original-main win association
is 40.5% (699/1,726 player-games), versus 39.3% in v35. Yellow is declared 275 times;
165 actually replace Darting and 110 keep it (19 automatic keeps). Replacement uses associate
with 47.9% wins (79/165), with small profile-specific samples and substantial selection effects.
`results/play-win-v36/darting-cancel-analysis.md` reports these modes separately, alongside
the full main/pair rankings and the same-scenario v35 comparison.

## Haymaker attack correction (v37)

Haymaker is restored to 7/0, retaining the no-combat-damage recovery penalty of -2 final block
next round (minimum 0). Its Purple objective, bonus, no reaction, and destinations stay the same.
The previous 8-attack version missed on only 95 of 4,547 main plays (2.1%), including 61 of
3,124 plays with a bonus burn (2.0%), in the v36 simulated games. The miss penalty is intended
as occasional counterplay rather than compensation for increasing its base attack.

Start a fresh match for v37. Hosted card fields, game metadata, local catalog, and rules popup
are synchronized. All 66,953 gameplay checks pass. The 201-plan matrix has 40,401 ordered
outcomes, independently replayed; 10,050 specialized-spend comparisons, 22,110 Combination
Strike outcomes, and 6,231 cancel-analysis outcomes also pass. Current pair outputs are in
`results/pairs-v37/`; the matching full-game batch is in `results/play-win-v37/`.

The 2,160-game v37 batch finishes with 1,766 decisive games, 394 draws, no caps, and mean
16.90 rounds; all 479,619 recorded-data checks pass. Haymaker's main-play win association
is 38.2% (597/1,564 player-games), versus 41.2% at 8 attack in v36. Its miss rate is 4.4%
without a bonus and 2.2% with one. The same-scenario comparison, hit frequencies, and pair
changes are in `results/play-win-v37/haymaker-correction-analysis.md`. These are bot-policy
associations with changing selected-use cohorts, not causal estimates of strength.

## Bait and Slip and hand relocation (v38)

Bait and Slip (ID 15) remains 3 attack / 2 block, gaining **+4 block** against an opposing
committed bonus burn (3/6 total). Against that commitment, after damage you may return the
surviving opposing main to any different hand of your choice, or keep its current hand.
This requires neither dealing damage nor taking zero damage. Its Blue objective still requires
taking zero final damage. Bonus, reaction, and printed destinations are unchanged.

Relocation resolves after damage, burns, and objective awards, before route cuts and Driving Kick's
forced destinations. Both relocation choices complete before either move applies. A card stays in
its new hand on later rounds, including when used as a main, bonus, or reaction. Printed color,
stats, abilities, and switch destinations remain unchanged. Each player's copy moves independently;
permanently burned cards cannot return. Hand browsing and inventory popups use these current locations.
Orbs cap their normal fill at six and show a bright overfill ring and +N badge above six.

Printed destinations are no longer filtered for empty hands. Driving Kick may force an empty
printed destination, and entering any empty hand loses even with life and cards elsewhere.
Both hidden next-hand choices resolve together, so simultaneous empty-hand losses draw.
The previous exhausted-route fallback is removed. Optional additions specifying an available hand
still require cards in that hand. Start a fresh match to use v38; v37 balance reports are historical.

Verification: `php DevTools/DFGSim/relocation-tests.php` covers persistent inventory, both seats,
optional/simultaneous moves, burn and knockout timing, moved-card play/burn legality, and empty-hand
defeats. `node DevTools/ui-harness/dfg-relocation-xbrowser.mjs` checks desktop/mobile controls,
inventory popups, overfill, and defeat presentation against local PHP fixtures without a server.


## Grip Break: two independent reads (v39)

Grip Break (ID 10) remains Orange, 4 attack / 2 block. Its defensive-bonus read now grants
+2 attack / +1 block. Independently, an opposing attack bonus carried into this round grants
+3 block. It is 6/3 against a block-increasing bonus, 4/5 against carried pressure, and 6/6
when both apply, before other effects. Current-round attack bonuses and pressure earned for
next round do not activate the carried-pressure read.

Its Blue objective succeeds by either dealing final damage against a block-increasing bonus,
or taking zero final damage against an opponent with carried pressure. Both paths together
still award at most one Blue token; Counterpunch can deny the award. Direct damage and reactions
count in final damage. No bonus ability, the +2 block reaction, and Yellow/Green/Blue destinations
are unchanged. Start a fresh match for v39. Historical balance reports predate this revision.

Run `php DevTools/DFGSim/grip-break-tests.php` for independent reads, stacking, both objective
paths, denial, final-damage timing, and next-round pressure from all three existing sources.


## Quick Jab: bypass late defense (v40)

Quick Jab (ID 1) remains 3/2 and gains +2 attack when committing no bonus burn. Under that same
condition, its damage calculation ignores block gained by the opponent from reaction burns.
Printed block, main abilities, bonus block, and advantage block still apply. Reactions retain
their attack effects and remain permanently burned. Quick Jab's own reactions do not disable
conservation; committing a bonus disables both rewards. This is penetration for Jab's damage,
not a reduction of the opponent's actual block. Burn modes and Orange/Yellow routes are unchanged.
Start a fresh match for v40. Run `php DevTools/DFGSim/quick-jab-tests.php` for source-specific
block handling, both seats, conservation, projections, damage resolution, and objective timing.


## Iron Guard: carry the guard forward (v41)

Iron Guard (ID 34) remains 2/5. Taking zero final damage grants +2 block for the following
round only, independently of dealing damage. Its Green objective now requires dealing damage
only, independently of taking damage. Either or both rewards can succeed. Counterpunch can deny
the token; it cannot deny the defensive carry. Reactions and direct damage count toward final damage.

The block bonus follows the fighter across hands, applies to the next main and any Darting Strike
replacement, and stacks with other stat modifiers. Renewal grants +2 again rather than accumulating
extra block. Brace affects attack pressure only; Quick Jab ignores reaction block only; Grip Break
reads carried attack only. None of those treat this defensive carry as attack pressure or a reaction.
The live UI shows whether the guard applies this round or next. Stats, burns, and routes are unchanged.
Start a new match for v41. Run `php DevTools/DFGSim/iron-guard-tests.php` for independent rewards,
carry duration, modifiers, replacements, and damage timing.


## Iron Guard: conservative counterattack (v42)

Iron Guard (ID 34) is now 1 attack / 5 block, gaining +1 attack when it commits no bonus burn.
Its clean-defense payoff is +1 attack next round, replacing v41's carried block. Dealing damage
still independently earns Green advantage. Own reaction burns do not disable conservation.
The printed attack remains 1 for opposing printed-stat reads, even when conservation raises it to 2.

This pressure uses the existing one-round attack system: it follows the fighter across hands,
is preserved by Darting Strike replacements, activates Grip Break's opposing-momentum read,
and can be suppressed or prevented by Brace. Multiple pressure sources use the highest amount
rather than stacking. Base block, bonus/reaction effects, and Green/Blue routes remain unchanged.
The v41 block-carry analysis is historical; start a fresh match for v42.

The matched 2,160-game-per-version comparison and exhaustive pair results are saved in
`DevTools/DFGSim/results/iron-guard-v42/report.md`; the carried-block study is historical in
`DevTools/DFGSim/results/iron-guard-v41/report.md`. These reports distinguish selected-use
win associations from preassigned opening scores.


## Heavy Feint: punish a second resource (v43)

Heavy Feint (ID 36) remains 4/2 and still ignores opposing bonus-burn block for its damage
calculation. Independently, an opponent spending advantage this round grants it +2 attack.
The reward applies once, regardless of how many tokens were spent. Holding, gaining, or losing
a token without spending it does not qualify. Tokens already consumed on reveal still count;
Darting Strike cancelling into another main retains its spent Yellow for this read.

With no other modifiers, Heavy Feint is 6/2 against a spend, while independently retaining
its defensive-bonus penetration if both resources were committed. Printed/main/advantage block
and reaction block are not ignored by its existing bonus-bypass effect. Stats, burns, and routes
are unchanged. Start a new match for v43. `php DevTools/DFGSim/heavy-feint-tests.php` verifies
both seats, independent and overlapping reads, multi-token spends, cancellation, and live resolution.

## Green stay option: Firm Counter (v44)

Driving Palm (ID 23) now switches to Red/Purple. Its Green destination moves to Firm Counter
(ID 21), which now switches to Red/Green/Blue. Green still has exactly one card that can stay
in its own hand. Stats, abilities, and burn effects are unchanged. Start a fresh match for v44.

## El’s Invitation: overworld adventure

The current progression direction is a persistent exploration adventure, available at
`DFGSim/adventure/` and from the main menu. The first playable Sumerian chapter includes
the clay-tablet invitation, keyboard/click/touch walking, NPC tasks, three lesser champions,
the Bull of Heaven’s witnessed-challenge gate, permanent signature techniques, free deck
editing (up to six cards per color), equipment, and sanctuary recovery without losing progress.
Fights use this same polished simulator interface. See [adventure/README.md](adventure/README.md)
for controls, save behavior, validation, and the explicit scope of future regions/tournament.
The earlier roguelike and animation galleries are retained as prototypes and asset studies.
