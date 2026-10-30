<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/GameSetupRules.php';   // SWUCardPoolFor — the card pool a game records
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$ttl = 600;

/**
 * Full game setup. Returns the new $gameName.
 *   $opts['forcedFirstPlayer'] : 1|2 to override the coin flip (null = random)
 *   $opts['forcedFirstPlayerChooses'] : true = that seat CHOOSES who opens instead of opening (the prior
 *                                game's loser in a Bo3 / rematch — MatchSpawnNextGameWithDecks)
 *   $opts['resolvedDecks']     : [seat => SWUResolveDeckInput-shaped array] to inject
 *                                already-resolved decks (e.g. sideboarded Bo3 games)
 */
function SWUSetupGame($lobby, $opts = []) {
    global $gameName;
    // ⚠ SWUSim ALWAYS needs its game directory, even though it is apcu-mode. Unlike the other sims,
    // SWUSim's generated WriteGamestate normally does a durable WRITE-THROUGH: it stores to APCu *and*
    // writes Games/<id>/Gamestate.txt, because the APCu entry has a 600s TTL and ParseGamestate falls
    // back to that file — so a game idle for >10 minutes (or an FPM restart) would otherwise be lost.
    // `createGameDirectory: !GamestateUsesMemoryStorage()` is correct for GrandArchiveSim/AzukiSim
    // (their generator writes a file only when NOT apcu), but for SWUSim it skipped the directory that
    // the write-through still targets — every new game then failed with
    // "file_put_contents(.../Games/<id>/Gamestate.txt): No such file or directory" and the warning text
    // corrupted the JoinQueue JSON response ("Unexpected server response while joining queue").
    $gameName = GetGameCounter(__DIR__ . '/Games');
    InitializeGamestate();
    WriteGamestate(__DIR__ . "/");
    ParseGamestate(__DIR__ . "/");

    // Persist the game mode (goldfish/hotseat) as a never-cleared GlobalEffect on P1 so all
    // runtime branching (SWUGameMode()) can read it. Normal games leave $mode '' and write nothing.
    $mode = '';
    if (isset($lobby->format)) {
        $f = strtolower((string)$lobby->format);
        if ($f === 'goldfish' || $f === 'hotseat' || $f === 'botpractice') $mode = $f;
    }
    if ($mode === 'goldfish')    AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');
    if ($mode === 'hotseat')     AddGlobalEffects(1, 'SWU_MODE_HOTSEAT');
    if ($mode === 'botpractice') AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');

    // Which seats the bot drives. Seat 2 by default; the lobby may override.
    if ($mode === 'botpractice') {
        $botSeats = [];
        if (isset($lobby->botPlayers) && is_array($lobby->botPlayers)) {
            foreach ($lobby->botPlayers as $seat) {
                $seat = intval($seat);
                if ($seat > 0) $botSeats[] = $seat;
            }
        }
        SetSWUBotPlayers(empty($botSeats) ? [2] : $botSeats);
        // The live bot's chooser (SWUBotActiveChooserProfile reads SWUBotProfile): the heuristic stack for
        // the Play Style the menu sent (APIs/Lobbies/JoinQueue.php → $lobby->botStyle). Missing or unknown
        // means Normal — never the first-legal fallback. The bot's flavours come from its own leader + base.
        // The five archetypes plus the three legacy names (see APIs/Lobbies/JoinQueue.php) — each is a registered
        // chooser, "heuristic-<style>" (SWUSim/BotHeuristic.php).
        $botStyle = strtolower(strval($lobby->botStyle ?? ''));
        if (!in_array($botStyle, ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol', 'aggro', 'normal', 'control'], true)) $botStyle = 'normal';
        DecisionQueueController::StoreVariable('SWUBotProfile', 'heuristic-' . $botStyle);
    }
    // Room games with BOT SEATS (Twin Suns "Fill Seat with Bot", SWUSim/docs/todo-twinsuns-fill-bot.md): a lobby seat
    // with a botProfile is driven by the bot. Its GAME seat is its array position, the same mapping the deck loop
    // below uses, and that position is final here: StartRoom has already compacted and sorted the seats.
    // Not a game mode (SWUMarkBotSeats): the humans keep the inactivity clock and undo consent.
    // Every room bot plays Arenabot's Normal stack for now; the lobby profile only chooses its deck.
    if ($mode === '') {
        $botSeats = []; $botNames = [];
        $seat = 1;
        foreach (($lobby->players ?? []) as $p) {
            if (is_object($p) && method_exists($p, 'getBotProfile') && $p->getBotProfile() !== '') {
                $botSeats[$seat] = 'heuristic-normal';
                $botNames[$seat] = method_exists($p, 'getUsername') ? strval($p->getUsername()) : '';   // "Arenabot Gamma"
            }
            ++$seat;
        }
        if (!empty($botSeats)) SWUMarkBotSeats($botSeats);
        foreach ($botNames as $s => $name) if ($name !== '') DecisionQueueController::StoreVariable('SWUBotName_' . $s, $name);
    }
    // Team rules (2v2). A separate never-cleared flag rather than a value of $mode, because it is
    // orthogonal: SWUGameMode() answers "goldfish/hotseat/normal" and must keep returning '' here.
    // ⚠ Follows the format's CONFIGURATION ('teams' in AppCore/SWU/Formats.php), never the literal id: the old
    // `=== 'teamsuns'` would load every Team Suns Preview game as a four-player free-for-all (2026-09-16).
    if (isset($lobby->format) && SWUFormatIsTeamFormat(strtolower((string)$lobby->format))) {
        AddGlobalEffects(1, 'SWU_MODE_TEAMS');
    }
    // Every game records its card pool (docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2): an Arenabot game
    // its chosen pool, Goldfish and Hotseat 'open', everything else its own format. Read through SWUGameCardPool().
    DecisionQueueController::StoreVariable('SWUCardPool', SWUCardPoolFor(strval($lobby->format ?? ''),
        isset($lobby->cardPool) ? strval($lobby->cardPool) : null));

    // Rematch inputs (spec 2026-09-23 §3). ⚠ An Arenabot game sets isGoldfish and so creates NO Match
    // record — the deck links exist only on this lobby, right now. Persist them here or the end-game
    // menu has nothing to offer a rematch from. Inputs only: no authKey, no userId (JoinQueue.php mints
    // fresh credentials for the new game).
    if ($mode === 'botpractice') {
        require_once __DIR__ . '/BotDataRematch.php';
        $links = [];
        foreach (($lobby->players ?? []) as $p) {
            $links[] = method_exists($p, 'getDeckLink') ? strval($p->getDeckLink()) : '';
        }
        SWUBotDataWriteRematch(__DIR__ . '/Games/' . $gameName, [
            'deckLink'  => strval($links[0] ?? ''),
            'deckLink2' => strval($links[1] ?? ''),
            'botStyle'  => strval($botStyle ?? ''),
            'cardPool'  => SWUCardPoolFor(strval($lobby->format ?? ''),
                               isset($lobby->cardPool) ? strval($lobby->cardPool) : null),
        ]);
    }

    // Optional fixed per-game RNG seed. Normally LoadPlayerDeck() mints one from random_bytes() the
    // first time it runs, which makes every game's shuffle unpredictable (the point) but also makes a
    // headless self-play run unreproducible — a bot stall found on one run could not be re-entered to
    // diagnose it. Setting it here, before the first LoadPlayerDeck() call, wins that function's
    // `if (GetSWUVar('RNG_SEED','') === '')` guard. Opt-in only: absent this option nothing changes.
    if (!empty($opts['rngSeed'])) SetSWUVar('RNG_SEED', strval($opts['rngSeed']));

    $resolvedDecks = isset($opts['resolvedDecks']) && is_array($opts['resolvedDecks']) ? $opts['resolvedDecks'] : [];

    // ─── Step 1–2: Load decks (leader, base, main deck) for each player ───────────
    $playerCounter = 1;
    $deckLoadOk = true;
    foreach ($lobby->players as $player) {
        $player->setGamePlayerID($playerCounter);
        $injected = $resolvedDecks[$playerCounter] ?? null;
        if (!LoadPlayerDeck($playerCounter, $player->getDeckLink(), $player->getPreconstructedDeck(), $injected)) {
            // Goldfish P2 is an intentionally empty passive seat — its failed load must NOT block
            // the pregame (which is what left P1 unable to act). Every other failure still gates.
            if (!($mode === 'goldfish' && $playerCounter === 2)) $deckLoadOk = false;
        }
        ++$playerCounter;
    }

    // ─── Twin Suns: seed the seat order + live-seats from the lobby's player count ──
    // Seats were assigned 1..$seatCount above; clockwise turn order = assignment order.
    // Seats are single digits (1..4) so the list is a plain concatenation ("123").
    $seatCount = $playerCounter - 1;
    $seatList  = implode('', range(1, max(1, $seatCount)));
    SetSeatOrder($seatList);
    SetLiveSeats($seatList);

    // ─── Goldfish practice sponge ────────────────────────────────────────────────
    // The passive P2 seat loads no deck, so it has no base — nothing to attack. Seed an Echo Base
    // (SOR_024) as a practice damage sponge: it's attackable through the normal combat path (your
    // units exhaust as usual), and its Damage counter is the "generic number" that climbs on each
    // hit. The base-defeat win-con is suppressed for goldfish P2 (see CombatLogic base sweep), so
    // it survives past its 30 HP and the counter just keeps rising.
    if ($mode === 'goldfish') {
        $p2Base = &GetBase(2);
        if (empty($p2Base)) {
            $sponge = new Base('SOR_024');
            $sponge->Damage = 0;   // constructor leaves the -1 fallback; force undamaged like real bases
            array_push($p2Base, $sponge);
        }
        unset($p2Base);
    }

    // ─── Step 3: Determine first player ───────────────────────────────────────────
    // Forced (Bo3 / loser's choice) or random pick among seats; first player holds initiative.
    // CR 5.2.1c: in a 1v1 game the random pick only wins the ROLL — that player then chooses who starts with the
    // initiative (InitiativeChoice, queued below in place of the pregame). Twin Suns keeps the random first player.
    // A Bo3 game 2/3 or a rematch forces the prior game's LOSER, who likewise chooses rather than simply opening.
    $forced = $opts['forcedFirstPlayer'] ?? null;
    if ($mode === 'goldfish') $forced = 1;   // solo: the human seat always opens
    $firstPlayer = &GetFirstPlayer();
    $seatArr = GetSeatOrderArray();
    $initiativeChoiceReason = '';   // '' = no choice; 'roll' = coin-flip winner; 'loser' = prior game's loser
    if (in_array($forced, $seatArr, true)) {
        $firstPlayer = $forced;
        if (!empty($opts['forcedFirstPlayerChooses']) && count($seatArr) === 2 && $mode !== 'goldfish') $initiativeChoiceReason = 'loser';
    } else {
        if (count($seatArr) === 2) $initiativeChoiceReason = 'roll';
        // random_int() and NOT EngineRandomInt() — deliberately, and this is the ONLY live unseeded
        // random call left in SWUSim. The rest of the engine must use Core/DeterministicRNG.php so undo
        // and replay reproduce a random outcome, but this pick happens ONCE at game creation, before any
        // undo stack exists, and its result is written straight into gamestate ($firstPlayer) — which undo
        // then restores like any other zone. So there is nothing here for a deterministic stream to protect,
        // and true entropy is what you want for the coin flip that decides who opens.
        $firstPlayer = $seatArr[random_int(0, count($seatArr) - 1)];
    }

    $turnPlayer = &GetTurnPlayer();
    $turnPlayer = $firstPlayer;

    $currentTurn = &GetTurnNumber();
    $currentTurn = 1;

    // Initiative starts with the first player, not yet taken this round.
    SetInitiativeCounter("P{$firstPlayer}_UNCLAIMED");

    if ($deckLoadOk) SetFlashMessage('');
    $currentPhase = &GetCurrentPhase();
    $currentPhase = 'APS';
    SetPhaseParameters("-");

    // SWU-PGN writer: start recording a human 2-seat PvP game (a no-op otherwise). The decks are shuffled, so this
    // captures their order; the setup frame it opens is committed just before the WriteGamestate below.
    SwuPgnRecStart($lobby, $opts, $deckLoadOk);

    // ─── Steps 4–6: Draw opening hands, queue mulligan decisions, resource 2 ──────
    if ($deckLoadOk) {
        if ($initiativeChoiceReason !== '') QueueInitiativeChoice($firstPlayer, $initiativeChoiceReason);
        else QueuePregameSetup($firstPlayer);
        AdvanceAndExecute("PASS");
        AutoAdvanceAndExecute();
        // A seat whose base forbids the mulligan (JTL_028 Nabat Village) has no YESNO in front, so its
        // pregame statics (PushPregameSnapshot, ChooseStartingResource) ARE its front decision — and
        // GetNextTurn never runs statics, so the game stalled in setup. Drain them now so that seat opens
        // on its resource pick; a seat with a mulligan stops at its YESNO, unchanged.
        // DevTools/tdd-regression/test_nabat_pregame_front_decision.php.
        if (function_exists('ProcessGoldfishAutomation')) ProcessGoldfishAutomation();
        SaveUndoVersion($firstPlayer, "Start of Game");
    }

    // Stamp the client-visible lobby facts NOW, not only on the first SWUAfterAction: the Undo menu
    // reads them to decide whether to offer "Bookmark Gamestate", so without a value here a brand-new
    // game renders the wrong menu until someone takes an action.
    // Privacy is read from the LOBBY, not SimGameIsPrivateGame — the auth record that backs that lookup
    // is only written below, AFTER this WriteGamestate, so the lookup would answer "public" for every
    // game. $lobby->isPrivate is the same field SimGameBuildAuthKeysFromLobby persists. A one-player
    // mode counts as private, mirroring SWUGameIsPrivate.
    SetSWUVar('GAME_IS_PRIVATE',
        ((is_object($lobby) && !empty($lobby->isPrivate)) || $mode !== '') ? 'true' : 'false');
    SetSWUVar('GAME_SEAT_COUNT', strval(SeatCountForGame()));
    // Twin Suns house rules the room agreed on (StartRoom resolves them; a rematch re-reads them from the match).
    if (is_object($lobby) && is_array($lobby->houseRulesActive ?? null) && !empty($lobby->houseRulesActive)) {
        SWUApplyHouseRules($lobby->houseRulesActive);
    }

    SwuPgnRecFrameCommit();   // the setup frame SwuPgnRecStart opened (no-op for an unrecorded game)
    WriteGamestate(__DIR__ . "/");
    $lobby->gameName = $gameName;
    if (!SimGameWriteAuthKeysFromLobby('SWUSim', $gameName, $lobby)) {
        throw new RuntimeException('Unable to store game authentication metadata in APCu.');
    }
    return $gameName;
}

// Backward-compatible entrypoint: the queue still does `include '.../CreateGame.php'`
// with an ambient $lobby in scope. Only auto-run when that ambient lobby exists.
if (isset($lobby) && is_object($lobby)) {
    SWUSetupGame($lobby);
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Resolve and load a deck for one player.
 * Returns true on success, false on failure (caller should abort game setup).
 */
// Per-game secret RNG seed — established ONCE (idempotent across the per-player calls), before any
// shuffle, so the deterministic shuffles (Task 3) are unpredictable yet reproducible for undo.
// RNG_V 2: the RNG material leaves out $updateNumber (Core/DeterministicRNG.php), which Undo cannot roll
// back — without it, mulligan → Undo → mulligan redrew a different hand. Versioned so games created
// before it, and their input replays, keep the old formula.
function SWUEnsureRandomnessSeed(): void
{
    if (GetSWUVar('RNG_SEED', '') === '') {
        SetSWUVar('RNG_SEED', bin2hex(random_bytes(16)));
    }
    if (GetSWUVar('RNG_V', '') === '') SetSWUVar('RNG_V', '2');
}

function LoadPlayerDeck($playerID, $deckLink, $preconstructedDeck = '', $resolved = null) {
    if ($resolved === null) {
        if (empty($deckLink)) {
            SetFlashMessage("Player $playerID has no deck link. Please provide a SWUDeck or SWUDB deck URL.");
            return false;
        }
        $resolved = SWUResolveDeckInput($deckLink);
    }

    if (!is_array($resolved) || empty($resolved['success'])) {
        $msg = is_array($resolved) ? ($resolved['message'] ?? 'Unknown error') : 'Unknown error';
        SetFlashMessage("Could not load deck for Player $playerID: $msg");
        return false;
    }

    if (!empty($resolved['unresolved'])) {
        $missing = implode(', ', array_slice($resolved['unresolved'], 0, 5));
        $extra = count($resolved['unresolved']) > 5 ? ' (and more)' : '';
        SetFlashMessage("Warning: some cards for Player $playerID could not be resolved: $missing$extra");
    }

    // Leader → Leader zone (one or two — Twin Suns decks resolve `leader` to an array).
    if (!empty($resolved['leader'])) {
        $leaderIds = is_array($resolved['leader']) ? $resolved['leader'] : [$resolved['leader']];
        $leaderZone = &GetLeader($playerID);
        foreach ($leaderIds as $leaderId) {
            $newLeader = new Leader($leaderId);
            $newLeader->Ready = true;
            // The generated zone constructor defaults absent numeric fields to -1 (not the schema's
            // Damage:number=0), and `new Leader($cardID)` passes only the CardID — so Damage would
            // start at -1. Force it to 0 so the leader begins undamaged.
            $newLeader->Damage = 0;
            array_push($leaderZone, $newLeader);
        }
        unset($leaderZone);
    }

    // Base → Base zone
    if (!empty($resolved['base'])) {
        $baseZone = &GetBase($playerID);
        $newBase  = new Base($resolved['base']);
        // Same constructor-default gap as the leader above: `new Base($cardID)` would leave
        // Damage at the -1 fallback instead of the schema default 0, making every base start at
        // -1 damage (so a base attack renders one counter short). Force it undamaged.
        $newBase->Damage = 0;
        // Seed the per-game use budget for repeatable base Actions (e.g. LOF_022 Mystic Monastery).
        // These track remaining uses in NumUses and are exempt from the per-round refill.
        global $baseActionNumUses;
        if (isset($baseActionNumUses[$resolved['base']])) {
            $newBase->NumUses = intval($baseActionNumUses[$resolved['base']]);
        }
        array_push($baseZone, $newBase);
    }

    SWUEnsureRandomnessSeed();

    // Main deck → Deck zone (shuffled)
    if (!empty($resolved['mainDeck'])) {
        $gameDeck = &GetDeck($playerID);
        foreach ($resolved['mainDeck'] as $cardID) {
            array_push($gameDeck, new Deck($cardID));
        }
        // Deterministic (counter-based) so an undo that restores the counter+seed replays the same
        // shuffle (Task 3, undo redesign). Unpredictable to players via the per-game secret seed.
        EngineShuffle($gameDeck, false);
    }

    return true;
}
