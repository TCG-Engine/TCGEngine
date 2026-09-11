# FaBSim / FaBDeck generation

FaBSim follows Talishar's import boundary: functional card ids are normalized card name plus
pitch (`whelming_gustwave_red`), while printing ids remain metadata. Source card JSON comes from
`the-fab-cube/flesh-and-blood-cards`; FaBDeck reflects FaBSim's dictionary and art corpus.

```powershell
php zzCardCodeGenerator.php rootName=FaBSim withPreview=1
php zzGameCodeGenerator.php rootName=FaBSim
php zzGameCodeGenerator.php rootName=FaBDeck
php zzTurnGenerator.php rootName=FaBSim
php SharedUI/Render/GenerateSites.php FaBSim
php SharedUI/Render/GenerateSites.php FaBDeck
```

`Schemas/FaBSim/ImportSchema.txt` defaults `downloadImages=false`, because a full 4,900-card art
sync is large. Run `php zzCardCodeGenerator.php rootName=FaBSim downloadImages=1` for an
art-worker/deployment run; subsequent ordinary dictionary
regenerations use `FaBSim/GeneratedCode/cardArrayCache.json` and do not refetch the data.

Fabrary's integration endpoint requires an API key even when the deck itself is public. Configure
the same `$FaBraryKey` used by Talishar in `APIKeys/APIKeys.php`, or set `FABRARY_API_KEY` in the
server environment. FaBSim calls Fabrary's `/prod/v1/decks/{slug}` endpoint with `x-api-key` and
understands its `identifier`, `total`, and `sideboardTotal` fields. Without a configured key the
UI reports that setup requirement instead of incorrectly claiming the deck is private.

The first gameplay slice supports shared deck import, setup, draw, pitch, arsenal, resource
payment, basic play destinations, end-of-turn cleanup, and the FaB arena zones. Card-specific
rules are intentionally added through the normal CardEditor macro workflow rather than copied
from Talishar's GPL game implementation.

## UPF and Welcome to Rathe

The FaBSim menu hosts private four-player UPF rooms through the shared waiting room.
Share the invite link; each player imports a young-hero deck and marks ready. The host
can start after all four players have valid decks and are ready. Validation checks the
40-card starting deck, 52-card pool, pitch-specific copy limits, specializations, classes,
and equipment slots. The board keeps other players' hands and face-down banished cards
private. Attacks follow live-seat adjacency and combat-chain focus.

WTR rules combine generated card abilities with shared combat, cost, activation, and turn
hooks. `wtr_abilities.json` contains the complete 226-identity set snapshot, including
vanilla cards and cards implemented by those shared hooks. Interactive effects use generated
await continuations: optional costs, searches, hand exchanges, reactions, Katsu, Refraction
Bolters, Hope Merchant's Hood, and Spring Tunic. Hero/equipment activations use pitch payment
and the stack. End-of-turn cleanup lets every player order their pitched cards.

Import the snapshot through the configured CardEditor repository, then regenerate:

```powershell
# Set MYSQL_DATABASE_NAME to this installation's card-code database when using local MySQL.
php DevTools/FaB/import_wtr_abilities.php
php zzGameCodeGenerator.php rootName=FaBSim
php zzTurnGenerator.php rootName=FaBSim
php SharedUI/Render/GenerateSites.php FaBSim
```

The importer preserves unrelated macros and rejects conflicting authored bodies. The
compatibility command `mark_wtr_implemented.php` now imports actual authoring instead of
writing blanket implementation flags with direct SQL. After reviewed MCP edits, refresh
the snapshot with `export_wtr_abilities.php`. Never guess a production database mapping.

Run the regression checks after generation:

```powershell
php DevTools/FaB/wtr_test.php
./DevTools/FaB/upf_lobby_test.ps1
```

The PHP suite includes the existing smoke/UPF checks, generated choices for player four,
ability payment, card-family pitch variants, combo/defense/cost modifiers, damage prevention,
pitch ordering, and a deterministic match through three eliminations. The HTTP check needs
the local Apache/APCu server and creates a fresh test lobby/game; it checks readiness gates,
opening draws, and private hands from all four authenticated perspectives.

Browser checks exercised hosting, starting, target selection, Spring Tunic's modal, and
pitching to activate Bravo in Chromium. Firefox and Safari have not been exercised locally.
These regressions cover representative interactions, not every possible ordering of all
WTR card combinations.

Rules references: [UPF](https://rules.fabtcg.com/en/trp/09-special-formats/) and
[combat](https://rules.fabtcg.com/en/cr/07-combat/).
# UPF lobby bots

The host can select **Goldfish bot** in any empty UPF lobby slot and press **Add bot**.
Bots are ready immediately and can be removed with the existing **Remove** control.
A goldfish has 20 health, passes priority, and skips its turns; it needs no deck.

Bot choices come from the optional `LobbyBotAdapter` interface. Add profiles and setup
in `FaBSim/LobbyAdapter.php`, and implement the corresponding runtime behavior in
`FaBSim/CreateGame.php` / custom game logic. Bot profile IDs belong to Player identities,
so removing seats and compacting the table cannot move a bot onto a human's seat.

Checks: `php DevTools/FaB/lobby_bots_test.php`, `php DevTools/FaB/upf_bots_test.php`,
and `DevTools/FaB/upf_bots_lobby_test.ps1` against the local web server.

## UPF board layout

`FaBSim/Custom/MultiplayerLayout.php` owns the multiplayer presentation. The viewer's
field stays below three clockwise opponent summaries. Inspect expands an opponent;
All opponents returns to the overview. Both views use the same absolute seat/zone
references and the server's masked card data.

`FaBSim/Custom/MultiplayerTable.css` supplies the legacy-style table placement:
head/chest/legs down the left, arms beside chest, hero and weapons centered,
arsenal below, and utility piles on the right. Desktop events and chat are mounted
in a dedicated sidebar; narrow screens place activity below the wrapping field.

Stack and combat chain have separate floating panels. Their title bars support
pointer dragging and arrow-key movement, with positions saved locally and clamped
to the viewport. Hide buttons and Escape dismiss a panel; ordinary game updates
do not reopen it until an empty stack/chain becomes active again.

Visual checks: inspect each opponent and return; choose a target from a summary;
play through stack/chain transitions; hide and reopen both panels; move them and
reload; open chat and events at desktop and narrow widths. Confirm only the
viewer's hand is face up and that the viewer remains below the opponents for every seat.

## Fai heuristic bot

Select **Fai · Heuristic bot** in any empty UPF slot. For two-player practice, paste your deck on the main menu
and select **Play Fai bot · 1v1**; this starts immediately with the human in seat 1
and Fai in seat 2. `DevTools/FaB/fai_duel_test.ps1` checks this HTTP entry point.
The pinned deck is defined in
`FaBSim/BotDeck.php`, based on Fabrary `01HXDKBQNQB7TF0GNJN0MQ7CWC`.
The export has 39 main-deck cards; Salt the Wound from its inventory makes 40.
Tenacity remains in inventory. No Fabrary request is needed to start the bot.
Fai starts one Phoenix Flame in the graveyard before drawing; human Fai players
receive the optional setup choice.

`Custom/Bot.php` scores legal actions using its own hand and the public board.
It prefers go-again attacks before finishers, blue pitches, low-cost Kodachi
attacks, discounted Fai recursion, early Stubby/Art of War buffs, and low-life
adjacent targets. Blocking trades weaker cards for damage prevention and becomes
more defensive below nine life. It also resolves equipment and card choices,
sets arsenal, and returns pitch cards in their current order. This is a greedy
heuristic, without multi-action search or opponent-hand knowledge.

The existing shared mode-10017 transport drives one authoritative bot action per
request, supports all four seats, and waits when a human owes a decision.
Bot profiles persist in GameState through turn and chain resets; Goldfish remains
a separate passive profile. `Custom/FaiCards.php` holds shared card mechanics;
interactive abilities and per-card modifiers are authored through CardEditor.
The additive snapshot includes the new deck cards and both inventory cards;
existing WTR cards continue using the WTR implementation.

After deploying source changes, import the snapshot using the configured card-code
database and regenerate (Banish now persists counters and turn effects):

```powershell
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/fai_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/fai_bot_test.php
./DevTools/FaB/fai_lobby_test.ps1
```

Checks cover new card mechanics, generated await continuations, all-seat lobby
configuration, human-decision gating, and a full four-bot match. Browser checks
confirmed Fai setup, bot transport, equipment activation, and attack announcement.
Chain-link last-known properties follow [CR 7.0.3c](https://rules.fabtcg.com/en/cr/07-combat/).
