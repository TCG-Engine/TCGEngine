<?php
require_once __DIR__ . "/BotDecisions.php";
// Legal-action enumeration for the GA heuristic bot. Reuses the exact functions that already
// drive the client's card-highlight colors (CanActivateCardForSelection) and the exact function
// that is simultaneously the check AND the action for attacks (BeginCombatPhase has no
// side-effect-free predicate — see GABotLegalActions below). Returns raw ProcessInput.php-shaped
// {mode, cardID, playerID} action pairs, mirroring AzukiRlBotLegalActions's shape
// (AzukiSim/Custom/GameLogic.php:578-602) so a future model-backed chooser can consume the same
// data a heuristic chooser does.

// Whichever seat has a non-empty DecisionQueue must respond before anything else can happen —
// mirrors AzukiPendingDecisionPlayer() gating AzukiRlBotLegalActions.
function GABotPendingDecisionPlayer() {
    foreach ([1, 2] as $seat) {
        if (!function_exists('GetDecisionQueue')) return 0;
        $dq = GetDecisionQueue($seat);
        foreach ($dq as $entry) {
            if ($entry !== null && empty($entry->removed)) return $seat;
        }
    }
    return 0;
}

function GABotFrontDecision($seat) {
    $dq = GetDecisionQueue($seat);
    foreach ($dq as $entry) {
        if ($entry !== null && empty($entry->removed)) return $entry;
    }
    return null;
}

function GABotChooseCombatTarget($player, array $candidates) {
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = intval($player);
    $attackerMZ = DecisionQueueController::GetVariable('CombatAttacker');
    $attacker = $attackerMZ !== null ? GetZoneObject($attackerMZ) : null;
    $attackPower = $attacker !== null ? max(0, intval(ObjectCurrentPower($attacker))) : 0;
    $attackHP = $attacker !== null ? max(0, intval(ObjectCurrentHP($attacker)) - intval($attacker->Damage ?? 0)) : 0;
    $best = null; $bestScore = PHP_INT_MIN;
    foreach ($candidates as $candidate) {
        $target = GetZoneObject($candidate);
        if ($target === null || !empty($target->removed)) continue;
        $type = EffectiveCardType($target);
        $targetPower = max(0, intval(ObjectCurrentPower($target)));
        $targetHP = max(0, intval(ObjectCurrentHP($target)) - intval($target->Damage ?? 0));
        $score = 0;
        if (PropertyContains($type, 'CHAMPION')) {
            $score = 500 + $attackPower * 8;
            if ($attackPower >= $targetHP && $attackPower > 0) $score += 10000;
        } else {
            // Favor killing a unit without losing the attacker; avoid suicidal trades unless
            // the target is substantially more powerful.
            if ($attackPower >= $targetHP && $attackPower > 0) $score += 180;
            $score += $targetPower * 10;
            if ($attackHP > $targetPower) $score += 80;
            else $score -= max(0, $attackPower * 5 - $targetPower * 2);
        }
        if ($score > $bestScore) { $bestScore = $score; $best = $candidate; }
    }
    $playerID = $savedPlayerID;
    return $best ?? (reset($candidates) ?: 'PASS');
}

// A '&'-joined MZCHOOSE candidate list can mix fully-qualified mzIDs (e.g. "myField-1") with a
// bare zone spec (e.g. "myHand"), per GameValidateDecisionAnswer's own reading of Param
// (GrandArchiveSim/Custom/GameLogic.php:39-51): a bare zone spec means "any live object in that
// zone is a legal answer," not that the literal zone name is itself a submittable mzID. Resolve
// each token to a concrete, currently-live mzID (first live object in the zone for a bare spec)
// so every returned candidate is something the engine will actually accept.
function GABotResolveChoiceToken($player, $token) {
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = intval($player);
    $resolved = null;
    if (preg_match('/^(.+)-(\d+)(\.u\d+)?$/', $token)) {
        $object = GetZoneObject($token);
        if ($object !== null && empty($object->removed)) $resolved = $token;
    } elseif (preg_match('/^(my|their)[A-Za-z]+$/', $token) && function_exists('GetZone')) {
        $zone = GetZone($token);
        foreach ((array)$zone as $i => $object) {
            if ($object !== null && empty($object->removed)) { $resolved = $token . '-' . $i; break; }
        }
    }
    $playerID = $savedPlayerID;
    return $resolved;
}

// {playerID, kind: 'decision'|'free-play', decisionType?, actions: [{playerID, mode, cardID}]}
function GABotLegalActions($gameName, $player) {
    $pending = GABotPendingDecisionPlayer();
    if ($pending !== 0) {
        $front = GABotFrontDecision($pending);
        $type = $front !== null ? strval($front->Type ?? '') : '';
        $param = $front !== null ? strval($front->Param ?? '') : '';
        $actions = [];
        $context = GABotDecisionContext($pending, $front);
        if (in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE'], true)) {
            foreach (GABotChoiceCandidates($pending, $param) as $candidate) {
                $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $candidate];
            }
            if ($type === 'MZMAYCHOOSE' || empty($actions)) {
                $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => 'PASS'];
            }
        } elseif ($type === 'YESNO') {
            foreach (['NO', 'YES'] as $answer) $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $answer];
        } elseif ($type === 'NAMECARD') {
            // Use the same deterministic chooser as goldfish automation: prefer an explicit
            // preview candidate, then a card visible in the bot's own zones. NAMECARD expects
            // the display name as the decision response, not an mzID.
            $name = function_exists('GoldfishChooseCardName')
                ? GoldfishChooseCardName($pending, $param)
                : 'Fireball';
            $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $name];
        } elseif ($type === 'MZREARRANGE') {
            // The engine's rearrange protocol already encodes the complete desired ordering.
            // Preserve it verbatim, matching GoldfishResolveDecisionInput().
            $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $param];
        } elseif ($type === 'MZMULTICHOOSE') {
            // Keep the candidate pool in context; the chooser builds a bounded valid subset.
            $parts = explode('|', $param, 3);
            $context['minimum'] = max(0, intval($parts[0] ?? 0));
            $context['maximum'] = max($context['minimum'], intval($parts[1] ?? 0));
            $context['candidates'] = GABotChoiceCandidates($pending, $parts[2] ?? '');
            $minimum = $context['minimum'];
            $answer = $minimum > 0 ? implode('&', array_slice($context['candidates'], 0, $minimum)) : '-';
            $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $answer ?: '-'];
        } elseif (in_array($type, ['MZMODAL', 'NUMBERCHOOSE', 'TWOSIDEDSLIDER', 'MZSPLITASSIGN', 'CHOOSEZONE', 'ICONCHOICE'], true)) {
            // Goldfish already has a deterministic, engine-compatible response encoder for these
            // non-card decision formats. Reuse it so bot and goldfish agree on wire syntax.
            $input = function_exists('GoldfishResolveDecisionInput') ? GoldfishResolveDecisionInput($pending, $front) : null;
            $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => $input !== null ? $input : '-'];
        } else {
            $actions[] = ['playerID' => $pending, 'mode' => 100, 'cardID' => 'PASS'];
            error_log("GABot: unrecognized decision type '$type' (param='$param') for seat $pending — defaulting to PASS.");
            // Structured mirror of the error_log above — lets callers (the self-play test harness in
            // particular) assert "no unhandled decision gap occurred" without scraping log text.
            $GLOBALS['GABotUnrecognizedDecisions'][] = ['type' => $type, 'param' => $param, 'seat' => $pending];
        }
        return array_merge($context, ['playerID' => $pending, 'kind' => 'decision', 'decisionType' => $type, 'actions' => $actions]);
    }

    // Free play: legal hand materializes + legal attack candidates + always-available end turn.
    $actions = [];
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $player; // CanActivateCardForSelection's mzID derivation depends on this perspective global

    $hand = GetHand($player);
    foreach ($hand as $i => $obj) {
        if ($obj === null || !empty($obj->removed)) continue;
        if (function_exists('CanActivateCardForSelection') && CanActivateCardForSelection($player, $obj, false)) {
            // The engine refuses a hand click it cannot start or pay for (ActionMap -> ActivationRefusedBeforeStart:
            // pre-announcement gates, mandatory additional costs, unaffordable reserve cost). Offering such a
            // click would only produce a no-op the controller then has to exclude and retry, so skip it here
            // (the flash message the check sets is preserved).
            if (function_exists('ActivationRefusedBeforeStart') && ActivationRefusedBeforeStart($player, $obj, false, true)) continue;
            $actions[] = ['playerID' => $player, 'mode' => 10002, 'cardID' => "myHand-$i!FSM!"];
        }
    }

    $field = GetField($player);
    foreach ($field as $i => $obj) {
        if ($obj === null || !empty($obj->removed)) continue;
        if (intval($obj->Status ?? 0) !== 2) continue; // must be awake/rested-ready
        $cardType = function_exists('EffectiveCardType') ? EffectiveCardType($obj) : CardType($obj->CardID);
        if (!PropertyContains($cardType, 'ALLY') && !PropertyContains($cardType, 'CHAMPION')) continue;
        // Power > 0 is one of BeginCombatPhase's own checks and, unlike the rest of that chain
        // (weapon taxes, valid-target search, etc.), is a cheap, side-effect-free read — worth
        // pre-filtering here. Without it, a 0-power champion with no weapon gets offered every
        // step, BeginCombatPhase silently rejects it every time, and the bot loops forever
        // re-choosing the same doomed attack instead of ever reaching materialize/end-turn.
        // This does mean a 0-power unit that could still attack via an equipped weapon is missed
        // — an accepted gap, not a correctness issue (BeginCombatPhase remains the real authority
        // for every candidate that DOES pass this filter).
        $power = function_exists('ObjectCurrentPower') ? intval(ObjectCurrentPower($obj)) : 0;
        if ($power <= 0) continue;
        $actions[] = ['playerID' => $player, 'mode' => 10002, 'cardID' => "myField-$i!FSM!"];
    }

    $playerID = $savedPlayerID;

    $actions[] = ['playerID' => $player, 'mode' => 10001, 'cardID' => 'myHealth-0!CustomInput!Pass'];
    return ['playerID' => $player, 'kind' => 'free-play', 'actions' => $actions];
}
