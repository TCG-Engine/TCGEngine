# TODO — "Fill Seat with Bot" for Twin Suns rooms

Status: **TODO / not started.** Researched 2026-10-01: a read-only sweep of the bot engine and the room/live-game
wiring, with the key claims spot-checked in code. Owner decisions are recorded under **Decisions**.

Line numbers are from 2026-10-01 and will drift. Grep the named function before trusting them.

## Why

Players asked for a way to fill a Twin Suns room when activity is low and only two people have joined. A room
needs at least 3 players to start.

## Decisions (owner, 2026-10-01)

1. **Rule 1 (take the kill):** take a kill only if, after the eliminator's 5-heal (CR 12.6.2), the bot would have
   the most remaining base HP. Ties share the victory (CR 12.7.3), so "most" means ≥ every other live seat.
2. **Host-only.** Only the room host can add a bot. No extra login gate beyond what hosting already needs.
3. **Deck choice:** clicking **"Fill Seat with Bot"** opens a popup. The host either pastes a decklist or picks one
   of the pre-cons.
4. **Play strength:** reuse Arenabot's existing play scoring. Don't write a deliberately weaker or new bot.
5. **When a bot can be added (owner, 2026-10-01, session 132):**
   - **Private room:** immediately. It's invite-only, so there is nobody to wait for.
   - **Public room:** only once **30s have passed since the last HUMAN joined** (60s until the owner shortened it,
     2026-10-09). The wait restarts on every human join, so bots are offered only after the room has gone quiet.
   - Mechanics: `max(joinedAt)` over the non-bot seats; the room creator's `joinedAt` counts as a join. Bot
     seats never restart the wait. The "Fill Seat with Bot" control counts down in place, like the Remove
     button. Enforce it server-side in `LobbyAddBot`/the adapter as well, not only in the UI.
   - There is no such timer today. The only existing timer is the 60s Remove timer (`SWUSeatKickableIn`),
     which is a separate thing.
6. **Bots are placeholders: a human who joins takes a bot's seat (owner, 2026-10-01, session 132).**
   Example: the host fills seats 3 and 4 with bots, and friends who follow the invite before Start each
   take a seat from a bot.
   - Today that join is **refused**: bots count toward `numPlayers`, so the join says "That room is
     full" (`JoinQueue.php` ~431, plus the re-check inside the room lock).
   - Rule: the joining human takes an **empty** seat if there is one. Otherwise they **replace a bot, FIFO**:
     the bot that was added FIRST goes first (owner, 2026-10-01). Order by the bot seat's `joinedAt`;
     the seat number doesn't matter. A room counts as "full" for a join only when every seat is human.
   - Applies to **every** join path, in both private and public rooms: the invite/Copy Link, public
     matchmaking into a waiting room, and the in-lock re-check. Before Start only; a started game's seats
     are fixed.
   - A replacement is a human join, so it restarts Decision 5's public wait.
   - Lobby listings (`GetLobbies.php`) and the public queue should count only **human** seats when
     deciding whether a room has space, or no one could ever find a bot-filled room.
   - Tests: invite join into a bot-filled private room replaces the OLDEST bot (add the bots in seat order 4 then 3, so FIFO and seat order disagree); two
     friends replace both;
     join with an empty seat AND a bot takes the empty seat; join into an all-human full room is still
     refused; join after Start is still "already started".

## Viability

**Viable without writing a new bot.**

The engine already handles 3–4 seats, and the bot plumbing is seat-agnostic:

- `SWUBotLegalActions` always offers a legal move: a counter, or Pass when allowed (`BotLegalActions.php` ~513–536).
- It offers deploy/ability actions for **both** leader slots (~448–455).
- It finds the owing seat by looping over all seats (~29–38).
- No prompt type with zero legal answers was found, so no stalls.
- Attack targets cover every opponent and are addressed per seat as `p{n}<Zone>`. Sentinel is checked **per
  opponent** (`SWUGetAllValidAttackTargets` → `SWUGetValidAttackTargets`, CombatLogic.php ~3780–3902).

**The gap is judgement.** `SWUBotOpponent($seat)` (BotEvaluator.php:17) is `$seat === 1 ? 2 : 1`, with ~32 call
sites. A bot in seat 3 or 4 therefore:

- reads only seat 1 as its opponent (board read, clock, threat);
- holds removal aimed at seat 3/4 as a "dud": the dud/gift reads see no enemy change (BotFallback.php ~1057/1090/1104);
- **targets itself** on "choose a player". ✅ Verified: `SWUPlayerPickerLabels` lists `You&P2&P3…` with "You" first,
  and the fallback scorer takes the first legal option (BotFallback.php ~396);
- treats every base as equal at the attack-target prompt (first listed wins), and misreads `p{n}…` targets because
  the "enemy" checks match `^their`;
- keeps choosing "take the kill" once a seat is eliminated: the dead seat's base reads 0 HP, so `SWUBotLethalNow`
  stays true.

**Cheapest route:** a thin Twin Suns policy layer on top of Arenabot's enumerator, prompt handling and play scoring.
A brand-new minimal bot wouldn't be much smaller, because it would still need answers for ~1,600 cards' prompts.

**Rough size:**

- bot layer: ~150–250 lines, plus an N-seat self-play harness and tests;
- room and game plumbing: ~50–100 lines;
- popup UI.

## Bot policy

Attacks, in priority order, each read **per live opponent**:

1. **Take the kill — when it wins.** If an attack can bring a live opponent's base to 0, take it, but only if
   `myBaseRemaining + 5 >= every other live seat's remaining base HP` (Decision 1). Otherwise treat that base like
   any other.
2. **Sentinels and Shields first.**
   - Prefer attacking a Sentinel, at any opponent. A Sentinel only forces attacks aimed at its own controller
     (CR 7.11.b), so this is a preference, not a constraint.
   - Break Shields with your own Saboteurs first (Saboteur defeats the defender's Shields). Otherwise use the
     weakest ready unit (lowest power): the "weenie" eats the Shield, and the strong units are kept for the real hit.
3. **Otherwise the healthiest base:** the live opponent with the most remaining base HP. This also matches the win
   condition: the first elimination ends the game at the end of that phase, and the highest base HP wins (CR 12.7).

Everything else (defaults):

- **Which unit attacks:** Saboteurs and weak units into Shields and Sentinels; the strongest attacker into the
  target base.
- **What to play:** Arenabot's play scoring, with "the opponent" generalised to all live opponents (Decision 4).
- **Counters:** Twin Suns has no free Pass, and taking Initiative/Blast/Plan ends your round (CR 12.5–12.6). Take a
  counter only when nothing else scores above it (that's today's behaviour, ~0 score). Prefer **Blast** when ahead
  (1 damage to every opponent's base), otherwise **Initiative**.
- **"Choose an opponent" / "choose a player":**
  - harmful effect → the opponent with the most base HP;
  - beneficial effect → yourself.

  Fixes the self-targeting bug above.
- **Eliminated seats:** never an opponent or a target.

## Bot changes (by file)

1. **`SWUBotOpponent`** → the live opponent that is the current focus (the most base HP) via `OpponentsOf()`. Keep
   a single-opponent read for Arenabot's scoring. This one change fixes the dead-seat lethal bug and most reads.
2. **New rule ahead of the style filter**, for the attack-target prompt and attacker choice: the three rules above.
   It must run **before** `BotStyles.php`'s filter, which uses one opponent's targets and would hide the others.
   Read targets via `SWUMzOwner()` + `SWUBotViewForMz`.
3. **Enemy tests:** replace `^their` / `^(my|their)` prefix checks with "owner seat is an enemy"
   (`SWUIsEnemySeat`). Known sites:
   - BotFallback.php ~79 (it scores `p{n}Base` as MY base), ~277, ~344, ~442, ~969–990;
   - BotRules.php ~247, ~773.
4. **Player/opponent pickers**, as above. Also "Resolve_Which_Player_First?" (GameLogic.php ~10212).
5. **Dud/gift reads** count a change on **any** opponent.

## Room and game plumbing

What already exists:

- `APIs/Lobbies/AddBot.php` → `LobbyAddBot()` (host-only, under the room lock, respects room size).
- The waiting room's "Add bot" picker/button and bot labels on the roster (`WaitingRoom.php` ~471–487; shown only
  when `botProfiles` is non-empty).
- `Player::botProfile`.
- Bot exemptions from AWAY, the Remove timer and the Kick Host vote.
- The client bot driver (seats 1–4) and endpoint 10017.

To build:

1. **`SWULobbyAdapter implements LobbyBotAdapter`** (`SWUSim/LobbyAdapter.php`; only FaB implements it today):
   - `botProfiles()`;
   - `configureBot()` setting the deck link, **deckOk and Ready** (`SWURoomStartBlockers` checks both; FaB's
     version doesn't set Ready).

   Twin Suns seats aren't fixed-seat, so `LobbyAddBot`'s seat-assignment branch doesn't apply.
2. **"Fill Seat with Bot" popup** (Decision 3), host-only, on an empty seat:
   - paste a decklist (validated by `SWUCheckFormat('twinsuns')` exactly like a human's), **or**
   - pick a pre-con.

   Replaces or extends the generic "Add bot" control for SWUSim Twin Suns rooms. Must pass the Chromium + Firefox
   + WebKit check, desktop and phone width.
3. **Pre-con decks: ALREADY EXIST.** Use `SWUSim/Custom/TwinSunsPreCons.json`, the four official TS26 pre-cons:
   - Aggressive Negotiations (TS26_02 + TS26_04);
   - Master and Apprentice (TS26_01 + TS26_07);
   - Against the Odds (TS26_08 + TS26_06);
   - Blood Brothers (TS26_03 + TS26_05).

   Load them with `SWUSetupTwinSunsPreCons()` (`SWUSim/Custom/SetupPanels.php`). Each row carries `key`, `name`,
   `leaders`, `base`, `count` and an `input` string that `SWUResolveDeckInput()` accepts as-is, so the bot's deck
   link is that `input` (local, no network on the start path).
   - Already guarded: `DevTools/tdd-regression/test_swusim_setup_panels.php` resolves every row.
   - The main menu already renders these in its Twin Suns setup modal (`SharedUI/Sites/SWUSim/MainMenu.php`,
     `$swuSetupTSPre`). Reuse that row markup/data for the popup's pre-con list rather than building a second one.
   - Store the chosen pre-con by its `key` on the bot seat, or store the resolved `input` directly.
4. **Start path:** the SWUSim room start (`SWUCreateMatchFromLobby` → `SWUResolveLobbyDecks`, MatchHooks.php
   ~19–23) reads `getBotProfile()`, loads the bot's deck, and records the bot's **actual seat**. Today a bot seat
   with an empty deck link makes the whole start fail.
5. **"Has bot seats" flag, separate from `botpractice` mode.**
   - Today `GetSWUBotPlayers()` (GameLogic.php ~27633), `BotControllerPendingPlayerForClient` and
     `ProcessBotControllerStep` (BotController.php ~136–164) only work when `SWUGameMode() === 'botpractice'`.
   - **Any** game mode switches off the inactivity clock (`InactivityClock.php` ~27) and undo consent.
   - ✅ Verified. Reusing that mode would disable both for the humans.
6. **Server-side driver lock:** every human's browser polls and drives the bot, guarded only per browser. With 2–3
   humans that needs a server lock (or a single elected driver).
7. **Game-flow exclusions for bot seats:**
   - **Kick votes:** exclude bots from `SWUVoterSeatsFor` (InactivityClock.php ~258–276). At 3 live seats the vote
     is unanimous, so a bot that never votes blocks every kick.
   - **Undo consent:** exclude bots, or auto-answer. Any opponent may answer an undo request.
   - **Eliminated bot seats:** skip them in `GetSWUBotPlayers` (no explicit guard today).
   - Whispers to a bot are harmless; no change.

## Tests

- **Self-play harness for 3 and 4 seats.** `DevTools/SWUSimBotSelfPlayTest.php` hardcodes 2 players, `[1,2]` bot
  seats and `GetBase(1)/(2)`. A 2-seat harness never exercises "opponent 3".
- **Policy tests per rule, with the near-miss on the board:**
  - a Sentinel at a *different* opponent;
  - a lethal base whose kill would leave the bot **not** highest after the 5-heal (must be skipped) next to one
    that leaves it highest (must be taken);
  - a Shield popped by a Saboteur vs a weenie.
- Run them on 3- **and** 4-seat boards, plus an elimination mid-phase. Assert the bot never targets the dead seat.
- **"Choose a player":** a harmful effect never picks "You".
- **Room flow:** host-only add, popup decklist validation, pre-con pick, Start with a bot seat, game created with
  the bot on its real seat.
- **Game flow:** kick vote and undo consent with a bot seat present; clock still active for the humans.
- **UI:** the popup in Chromium, Firefox and WebKit, desktop and phone.
- Mutation-check every guard.

## Deep research (2026-10-01, session 132)

Three read-only sweeps, with the load-bearing claims spot-checked in code. They **sharpen** the verdict
rather than change it: still viable, still no new bot. They add one prerequisite the plan above underweights
(**a gamestate write lock**) and correct the size of the enemy-test fix.

### A. The bot's two-player reads, in full

- **33** direct `SWUBotOpponent()` calls, plus **12** `$ctx['opp']` readers. `$ctx['opp']` is set once in
  `_SWUBotHeuristicChooseStack` and inherited by every rule.
- **Live Arenabot runs with every `SWUBotProposalOn` OFF.** The heuristic registers variant `''`, so
  RL and value layers are off too. About **8** B sites and **6** C sites are inert in live play and can wait.
- Classification:

  | Class | Meaning | Sites |
  |---|---|---|
  | B | the "focus" opponent is enough | 21 (13 free with a generalised `SWUBotOpponent`; the 5 pickers + the `BotRules` base pick need code) |
  | C | needs every live opponent (sum, min or any) | 26 |
  | D | enemy test by mz prefix → owner-seat test | 18 (15 on live paths) |

- **⚠ D is worse than the plan says.** Above 2 seats `ZoneSearch` turns `their<Zone>` into one
  `p{n}<Zone>-i` per opponent (`GameLogic.php` ~26754). **Every** `their` test is therefore false for
  **every** real enemy. Two regexes go further and drop `p{n}` candidates entirely:
  - `BotFallback.php` ~277, `$onBoard = /^(my|their)(GroundArena|SpaceArena|Base)-/`. Every `p{n}` target
    skips hostile/beneficial/attach scoring, so the bot takes the **first legal option**. Highest impact.
  - `BotFallback.php` ~1434, the buffs guard `/^(my|their)\w*Arena-\d+$/`. A mixed `my…`+`p3…` list reads
    as "all mine", so the bot **refuses hostile Actions that do have enemy targets**.
- Missing from the "Bot changes" list above:
  - `BotFallback.php` ~1434–1438 (above);
  - `BotLegalActions.php` ~148, a `^(my|their)…\.u\d+` subcard-expansion regex. Not an enemy test, but
    `p{n}` subcards are never expanded;
  - `BotRules.php` ~44, which takes the FIRST `Base-` candidate rather than the focus seat's base;
  - `BotFallback.php` ~298 (indirect-damage player pick) and ~326 (attack-target prompt), where every base
    scores the same.
- **Board read is me-vs-one** (`_SWUBotBoardRead`, `_SWUBotBoardSignature`, `SWUBotAttackTargets`). The
  building blocks take seats as arguments, so they can be reused per opponent: `SWUBotClock`,
  `SWUBotBasePotential`, `SWUBotLethalNow`.
- **Helpers to add (~30 lines):**
  - `SWUBotOpponents($seat)`, wrapping `OpponentsOf`;
  - a generalised `SWUBotOpponent`: the live opponent with the most base HP, ties going to the lowest seat;
  - `SWUBotIsEnemyMz($seat,$mz)`, built from `SWUMzOwner` and `SWUIsEnemySeat`;
  - `SWUBotEnemyUnits($seat)`;
  - `SWUBotMinClockOnMe($seat)`.
- **Revised size for B+C+D: ~140–190 lines**, before the attack-policy rule and tests.

### B. Elimination

- **A dead seat keeps the bot in permanent lethal/race mode.** ✅ Verified:
  - `SWUBaseRemainingHp` returns 0 for a removed base;
  - `SWUBotLethalNow` is `potential >= 0`, which is always true;
  - `SWUBotRuleLethalNow` runs before the style filter and then takes the first `Base-` option, whoever's
    it is.
- The fix is the generalised `SWUBotOpponent` (live seats only), plus running lethal per live enemy.
- The driver won't act for a dead bot seat, but only **indirectly**: a dead seat can't owe a decision or be
  the turn player. `GetSWUBotPlayers` still lists it. Add the explicit guard anyway.
- The engine never offers a dead seat's units or base as targets: `OpponentsOf` returns live seats only.

### C. Team Suns (2v2): out of scope for v1, and here is why

- **Not seatable today.** Bot mode needs format `botpractice`; teams need a team format; a lobby has one
  format. The "has bot seats" flag lifts the first half of that.
- **A seat-3 bot would read seat 1, its TEAMMATE, as "the opponent".** Teams are seat parity (`SWUTeamOf`:
  1/3 Red, 2/4 Blue).
- **Attacks on a teammate are never offered.** The engine builds attack targets from `OpponentsOf`, which
  excludes teammates.
- **Unqualified card prompts DO include a teammate's units**, and they are `p{n}` too. So the owner-seat
  enemy test must be `SWUIsEnemySeat`, not just "not mine". A teammate's units must never be used as fodder
  or a sacrifice.
- **The kill rule doesn't apply.** Team Suns has no HP scoring; the game ends the moment a team has no live
  seats (✅ verified, `_SWUTeamWipedOut`). A same-team kill heals nobody. The team policy is:
  - always take an enemy kill;
  - the kill that wipes the team comes first;
  - otherwise focus one enemy;
  - defend the teammate's base too.
- **Kick votes:** the whole opposing team must vote yes. A bot on that team never votes, so **no human
  facing a bot could ever be kicked**. Exclude bot seats from the voters.
- **Undo:** the answer comes from a popup variable, not a decision, so a bot can't answer. If both enemies
  are bots, an undo request **waits forever**. Auto-allow, or skip bots as answerers.
- Team-only extra work: ~100–150 lines plus 4-seat team tests. **Recommend shipping free-for-all Twin
  Suns first.**

### D. Live driving: the gap the plan underweights

- **How it works today:** every non-spectator browser runs `MaybeRunBotControllerStep` (`Core/jsInclude.js`
  ~1165) after each render and POSTs mode 10017. The server picks the bot seat itself. The only guards are
  **per browser**. FaB's 4-seat UPF rooms already mix humans and bots on exactly this path, unlocked (✅
  verified, `FaBSim/Custom/Bot.php`).
- **There is no gamestate lock anywhere.** ✅ Verified: there is no `flock`, `apcu_add` or `apcu_cas` in
  `ProcessInput.php`, `EngineActionRunner.php`, `GameAuth.php` or `GamestateParser.php`. The write path is a
  plain `apcu_store` + `file_put_contents`.
- **The real risk is a lost update against a HUMAN, not a double bot move.** A bot step reads state, spends
  tens of ms to seconds choosing, then overwrites a human's action committed in between. N human browsers
  multiply the number of step requests. (Two overlapping bot steps mostly collapse to one move; two
  back-to-back steps make a second, legitimate but unpaced move.) The human-vs-human version of this race
  already exists in every multiplayer game.
- **Nobody watching = the game stalls.** There is no server-side runner. A backgrounded tab is throttled to
  about one move per minute after 5 minutes. The inactivity clock can't rescue it: bots are exempt, and a
  kick needs another human.
- **Recommended design:** a per-game write mutex in `ProcessInput.php` around Parse → Execute → Write, for
  **every** mode (`apcu_add` with a TTL, or `flock` on `Games/<id>/.lock`).
  - The 10017 request also sends the client's `lastUpdate`; a stale step returns `applied:false` and makes
    no move.
  - A busy lock returns "retry later", so the other browsers back off.
  - Size: ~60–100 lines.
  - ⚠ This is **Core**: it changes FaB and every other sim, so it needs their regression runs. It is also a
    "blast radius" change.
  - Rejected alternatives:
    - an elected client driver: a backgrounded driver stalls the game, and it doesn't fix lost updates;
    - stepping the bot server-side inside each human submission: the bot runs inside that request's 1 s
      limit, its moves lose pacing and animation, and it doesn't help when nobody submits.
- **Plumbing details the plan above didn't have:**
  - The mode is the GlobalEffect `SWU_MODE_BOTPRACTICE`, set from `$lobby->format` (`CreateGame.php`
    ~45). `CreateGame.php` ~55–71 reads `$lobby->botPlayers` only under `botpractice`, and the room path
    never sets it.
  - **Bot seats must be derived after `StartRoom` compacts and re-sorts the seats** (`StartRoom.php`
    ~58–68), from `getBotProfile()`, at the same point `SWUResolveLobbyDecks` maps seats by array order.
  - `_SWUBotPracticeUndoTarget` (`GameLogic.php` ~21860) makes an undo skip back past the bot's replies.
    Without it, the bot re-plays within about 100 ms of a human's undo. Extend it to the new flag.
  - Keep bot games out of ranked stats (`SWUSubmitMatchResults`). `SWURecordDeckStatsForGame` already skips
    games with more than 2 seats.
  - `BotDataRecorder.php` ~125 records only seats `[1, 2]`.
- **Timing is not a concern:** about 7 ms per enumeration, 0.5 ms per lookahead, capped at 32 lookaheads.
  A typical step takes tens to hundreds of ms, well inside 10017's 15 s limit.

### Revised verdict and size

**Viable, free-for-all Twin Suns first.** About **4–6 dev-days**:

| Work | Size |
|---|---|
| Write lock + stale token (Core) | ~60–100 lines |
| Bot-seat flag, adapter, start path | ~150 lines |
| Bot judgement: B/C/D + the 3-rule attack policy | ~250–300 lines |
| N-seat harness + tests | ~200–300 lines |
| Popup | — |

Team Suns is a follow-up: ~100–150 lines plus team tests.

## Suggested order

0. ✅ **Gamestate write lock + stale-step token** (Core, see Deep research D). **Built 2026-10-01 (session 132).**
   - `Core/GameWriteLock.php` holds a `flock` on `<root>/Games/<id>/.writelock`. It fails OPEN, waits at
     most 10s, and the kernel releases the lock if the request dies.
   - `ProcessInput.php` holds the lock from before the parse until after the write. Spectators skip it. On
     timeout the reply is "busy", flagged retryable.
   - The 10017 request carries `lastUpdate`, and `EngineActionRunner` refuses a step whose token is behind
     the game (`botStepStale`). A client with no token gets no check.
   - `jsInclude.js` sends the token. On a stale reply it re-fetches the board, with no backoff.
   - Tests:
     - `DevTools/tdd-regression/test_core_game_write_lock.php` (15 checks, RED before the change,
       mutation-checked both ways);
     - `DevTools/ui-harness/swusim-botstep-lock-xbrowser.mjs` (a live Arenabot game in all three browsers,
       one tab and two tabs; with two tabs exactly half the steps come back stale and none errors).
   - ⚠ Not yet run live: FaB, whose generated files aren't built in this checkout. It shares the same
     client loop and the same `$updateNumber` echo (generator line ~2119), so it should behave the same.
   - `botpractice-menu-xbrowser.mjs` is already red, for an unrelated reason: its menu checks look for
     `#swu-second-label`, which the menu revamp removed.
1. ✅ **Plumbing — built 2026-10-01 (session 132).** What landed:
   - **Game:**
     - `SWUHasBotSeats()`, `SWUBotSeatsActive()` and `SWUMarkBotSeats([seat => profile])` in `GameLogic.php`.
       The flag is `SWU_HAS_BOT_SEATS` on P1; `SWUGameMode()` stays `''`.
     - `GetSWUBotPlayers` drops dead seats. The BotController gates read `SWUBotSeatsActive()`.
     - Per-seat profile `SWUBotProfile_<seat>`, read by `SWUBotActiveChooserProfile`.
     - Kick voters exclude bots, on the loaded path and the cheap one.
     - An undo whose only possible answerers are bots is free.
     - The step-undo skip-back now applies to room games with bots too.
     - `SWUSetupGame` marks lobby bot seats after compaction. Every room bot plays `heuristic-normal`.
   - **Lobby:**
     - New optional interface `LobbyBotPolicyAdapter`: `botAddWaitSeconds`, `botsYieldToHumans`,
       `botDeckInput`.
     - `LobbyAddBot` gains a `$deck` argument, which `AddBot.php` resolves and validates OUTSIDE the lock.
     - `LobbyHasRoomForHuman` / `LobbyYieldBotSeatIfFull` are applied at all four `JoinQueue.php` capacity
       checks (invite pre-scan + lock, matchmaking scan + lock).
     - `SWULobbyAdapter` implements both bot interfaces: profiles `precon:ts1..4` + `custom`, free-for-all
       Twin Suns rooms only, plus a start blocker for a stale bot profile.
     - FaB opts into nothing, so its behaviour is unchanged.
   - **Already true, no change:**
     - ranked stats skip any match with more than 2 players;
     - the write lock (step 0) covers the bot driver.
   - **Tests:**
     - `SWUSim/DevTools/tests/twinsuns_bot_seats_test.php` (CLI, 21 checks, 6 guards mutation-checked);
     - `SWUSim/DevTools/tests/lobby_room_bots_test.php` (web SAPI, 30 checks, 9 guards mutation-checked; it
       removes its own leftover public rooms).
   - **⚠ Interim UI until step 4:** the waiting room's GENERIC "Add bot" picker now shows for Twin Suns rooms,
     because `botProfiles` is non-empty.
     - The pre-con entries work.
     - `custom` fails with "Paste a decklist for this bot", because the generic picker has no deck field.
     - In a public room the picker refuses during the wait, with the seconds left in the message.
     - Don't ship step 1 to players without step 4, or hide `custom` first.
   - Not done:
     - `GetLobbies.php` still reports `numPlayers` including bots. Matchmaking is what finds rooms, and it
       now treats bots as free seats, so the listing is display-only.
     - The poll sends no `botAddableIn` field yet; that belongs with the step 4 popup.

   The original step 1 list:
   - the "has bot seats" flag;
   - the adapter;
   - the start path (bot seats derived after compaction);
   - voter/undo exclusions, including `_SWUBotPracticeUndoTarget`;
   - the dead-seat guard in `GetSWUBotPlayers`;
   - keeping bot games out of ranked stats;
   - Decision 5 (public-room wait, enforced server-side) and Decision 6 (a joining human replaces a bot,
     on every join path).

   A bot that plays legally on any seat, using Arenabot as-is.
2. ✅ **The N-seat self-play harness — built 2026-10-01 (session 132).**
   - **Harness:** `DevTools/SWUSimTwinSunsSelfPlay.php` (`--seats=3|4 --games=N --seed=S`, `--trace` for one line
     per step, one child process per game with a 600s cap).
     - It creates the game the ROOM way: a twinsuns lobby whose seats all have a botProfile goes through
       `SWUSetupGame`, so it also proves step 1's start path.
     - It uses the four TS26 pre-cons, rotated per game.
     - It checks that the controller never owes a move to a dead seat.
   - **Result:** 20/20 games finish at 3 seats and 20/20 at 4 seats (rounds 3–5; 5 shared wins at 4 seats).
   - **⚠ Not reproducible per seed:** the bot's lookahead is TIME-budgeted, so the same seed can take a
     different line under CPU load. Seed ts-19 at 3 seats hit the self-elimination line in 5 of 6 runs.
   - **Smoke test:** `SWUSim/DevTools/tests/twinsuns_selfplay_smoke_test.php` (3 pinned games, about 25s).
   - **Three ENGINE bugs found and fixed** — none of them is bot-only:
     1. **The decision bridge looked only at seats 1–2.** `BridgeEnumerateLegalActionsLoaded` and the
        static-queue drain (`DevTools/TestAutomationBridge.php`) now use `BridgeDecisionSeats()` (the seat
        order). Before this, seat 3's mulligan YES/NO enumerated the turn player's free-play actions, and every
        one was a no-op. Test: the seat-3 YESNO section in `twinsuns_bot_seats_test.php`.
     2. **`AllQueuesEmpty()` checked seats 1..(LIVE COUNT)** (`Core/DecisionQueueController.php`, now
        `SeatIDs()`).
        - With live seats 1,3,4 it never saw seat 4.
        - With live seats 3,4 it checked only the two dead seats.
        - So the phase engine ran past later seats' prompts. In a finished game this looped
          RGS→DRAW→RES→READY→RGS forever, a hang.
        - **Live impact without bots:** Team Suns plays on after an elimination, so with seat 2 out, seat 4's
          regroup prompts were skipped.
        - Test: `SWUSim/DevTools/tests/dq_all_queues_empty_seat_gap_test.php`.
     3. **A seat eliminated during its own action kept the turn.** Example: TWI_146 Steela's "deal 2 to your
        base" at 1 HP. Elimination empties that seat's queue, the action's close included, so the table froze on
        a dead seat. A human can trigger this too.
        - Fix: `_SWUMoveTurnOffEliminatedSeat()` at the end of `ProcessGoldfishAutomation`. Once every queue has
          settled, it moves the turn on and resets PASS. `GameTestAdapter::_mirrorProductionPostAction` calls
          the same function.
        - Test: `core/TwinSuns_EliminatedOnOwnAction_TurnMovesOn.md` (3 sections).
   - **Bot judgement noted for step 3:** the bot took that self-eliminating "may" at 1 HP. The may-damage-own-base
     cost should be priced against its own remaining HP.
3. ✅ **Bot judgement — built 2026-10-01 (session 132).**
   - **Helpers** (`BotEvaluator.php`):
     - `SWUBotOpponents($seat)`: every live enemy, from `OpponentsOf`.
     - `SWUBotOpponent($seat)`: the live enemy with the MOST base HP, ties to the lowest seat. At 2 seats it is
       still the other seat.
     - `SWUBotIsEnemyMz($seat, $mz)`: reads the owning seat; a teammate is not an enemy.
     - `SWUBotEnemyUnits($seat)`.
     - `SWUBotMostDangerousOpponent($seat)`: the lowest clock on me.
   - **Enemy tests → owner seat:** split damage, `$onBoard` (+`p\d+`), upgrade picks, `_SWUBotTargetScore`,
     host policy/attach, the buffs guard (+`p\d+`), `BotLegalActions` subcards (+`p\d+`), and `BotRules` FreeKill
     and ExhaustReadyEnemies.
   - **All-opponent reads:**
     - board read, gift read and board signature are summed or listed per enemy;
     - `SWUBotAttackTargets` is the union of every enemy's targets, Sentinel per enemy, and gains `bases`;
     - flavours, Force, ability enemies, WD payout, wipe losses/arenas and aggro-leader read across all enemies;
     - "am I dying" reads use the most dangerous opponent: dud, notDying, stabilised, BreakLethal.
   - **The owner's attack policy** (`BotRules.php`, rule `ts-attack` before the filter):
     - `SWUBotKillWins` (FFA: my HP+5 ≥ every other live seat; Team Suns: any enemy kill, team-wipe first);
     - `SWUBotRuleTwinSunsLethal` (free play; never a dead seat);
     - a weak attacker or Saboteur breaks a Shield;
     - `SWUBotLosingKillMzs` is dropped by the style filter, including in its "never empty" fallback;
     - healthiest-base tie-break in the fallback.
   - **Pickers:** a "choose a player" at 3-4 seats sends harmful effects to the focus enemy and beneficial ones
     to self.
   - **Self-damage:** "deal N to your base" is declined when it would defeat my base. This one applies at 2
     seats too.
   - **2 seats provably unchanged:**
     - 48 seeded games across 3 configurations are byte-identical to the pre-change code;
     - built-in decks, midrange vs hyperaggro, 24 games against a recorded baseline;
     - the Piett-blue vs Ahsoka-yellow meta decks (softcontrol vs midrange) and the modern pair (hardcontrol vs
       softaggro), 12 games each against a pre-change copy at `.claude/tmp/ctl3`.
   - **Tests:** `SWUSim/DevTools/tests/twinsuns_bot_judgement_test.php` (production chooser, near-miss per rule;
     20 mutation probes, all caught).
   - **Left alone (inert in live play, or fine with the focus opponent):** the proposal-only sites (Lando,
     Krennic, holdanswers, mgtrade, …), ControlWipe / ShrinkFirst unit reads, `_SWUBotResourcing2Tiers`, and
     Rl/ value features.
   - **Team Suns defence** (valuing the teammate's base) is NOT done; Team Suns rooms don't offer bots in v1.
4. ✅ **The popup — built 2026-10-01 (session 132).**
   - **Page** (`SharedUI/Render/WaitingRoom.php`): a sim whose bot profiles carry a `deck` kind gets a "Fill Seat
     with Bot" button on each empty seat (host only).
     - The popup lists the 4 TS26 pre-cons with their leader/base art, plus a paste box.
     - A refusal (e.g. an unreadable list) shows INSIDE the popup, which stays open.
     - Escape or a backdrop click closes it.
     - In a public room the button counts down the quiet minute in place.
     - The panel is OPAQUE on purpose: the Petranaki theme's surfaces are translucent "glass", and the first
       version showed the roster through itself.
     - FaB's profile picker is unchanged.
   - **Server (additive):**
     - `botProfiles` entries gain `deck` (`precon`|`paste`), `deckName`, and `cards` (identity thumbs built from
       CardIDs, no deck resolve per poll);
     - `PollLobbyUpdates` gains `botAddableIn` for policy adapters.
   - **Verified in Chromium, Firefox and WebKit**, desktop and 400px phone:
     - `DevTools/ui-harness/swusim-fill-bot-xbrowser.mjs` (popup, pre-con add, paste add, unreadable-list
       refusal, fit, public countdown; it leaves its public room so reruns are clean);
     - `DevTools/ui-harness/swusim-fill-bot-game-xbrowser.mjs` (END TO END: a room with 2 pre-con bots is started
       and played; the bots move on their own with no step errors).
   - **Noted, not fixed:** a "choose 2 players" BENEFICIAL pick that must name an enemy (TS26 Count Dooku's
     Battle Droids) takes the first enemy listed. It should be the least threatening one.

5. ✅ **Targeting rules — owner, 2026-10-01 (session 132), built the same day.** Every rule applies at 3-4 seats
   only, and 2-seat Arenabot is byte-identical. The code is `_SWUBotTSPlayerPick` / `_SWUBotTSUnitPick` in
   `BotFallback.php`.
   1. **Discard from a hand** → the opponent with the MOST cards in hand.
   2. **A ping to a base** (a base-target prompt or a "which opponent's base" pick) → the opponent with the most
      remaining HP. A ping that DEFEATS a base follows the kill rule: taken only if it wins, never if it hands
      someone else the game.
   3. **Exhaust** → the highest-power READY enemy unit; exhausted units are ignored.
   4. **Bounce** → the highest-HP enemy Sentinel first, else the highest-power enemy unit (upgrades count).
      Leaders are never targeted. Tokens ARE legal: they leave play, so a Voltron'd token is a good target.
   5. **Capture / take control** → the most valuable enemy unit.
   6. **Defeat** → the highest threat (power, then value).
   7. **Heal** → my base when it is the lowest remaining HP at the table; otherwise my most-damaged unit.
   8. **Shield / Experience / Advantage / buffs** → my strongest READY attacker.
   9. **A benefit that must name an enemy** (TS26 Count Dooku's second pick) → the WEAKEST enemy: fewest units,
      then least HP.
   10. **Mill / discard from a deck** → a pseudo-random opponent (owner: decking is very unlikely in Twin Suns).
   11. **Look at / reveal a hand or deck** → a pseudo-random opponent.

   "Pseudo-random" means derived from the game, the round and the prompt (crc32), never the game RNG.

   Tests: 30 new checks in `twinsuns_bot_judgement_test.php`, each rule with a near-miss. 15 mutations: 14 caught;
   the 15th (R10's "never self" guard) is an equivalent mutant, because the pseudo-random pick is always an
   opponent and always outscores "You".

6. ✅ **Bot names (owner, 2026-10-01).** Each bot added to a room takes the NEXT Greek letter, in the order bots are
   added: "Arenabot Alpha", "Arenabot Beta", …
   - The room keeps a counter that only ever goes up (`$lobby->swuBotNameIndex`), so a removed bot never hands its
     letter back.
   - It covers the full 24-letter alphabet and wraps to Alpha after Omega.
   - The name lives on the bot seat's `username` and is carried into the game as `SWUBotName_<seat>`, which
     `SWUBotSeatDisplayName` reads for the board and the game log.
   - Bot Practice's single bot stays plain "Arenabot".
   - Tests in `lobby_room_bots_test.php`: order, wrap, no reuse after removal, and the name on the board. 3
     mutations, all caught.

**All four steps are built.** Before players see it:
- **Commit and deploy.** The changes touch Core (`ProcessInput`, `EngineActionRunner`,
  `DecisionQueueController`, `jsInclude.js`), so run the blast-radius check.
- **After deploy, check a FaB room with a bot** (the write lock and stale token are shared; not run live on FaB).
- **Team Suns rooms** still offer no bots (v1 scope).
