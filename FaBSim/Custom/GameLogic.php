<?php

include_once __DIR__ . '/../../Core/ShortcutPreferences.php';
include_once __DIR__ . '/../../Core/DeterministicRNG.php';
include_once __DIR__ . '/WTRCards.php';

$customDQHandlers = [];
$additionalActivationCosts = [];

/*
 * FaB runtime invariants
 * ----------------------
 * - Card identity is a persistent UniqueID. mzIDs are short-lived render addresses.
 * - Seat order is data, never an implicit 1 <-> 2 toggle.
 * - A card may only be pitched while paying an announced cost.
 * - Stack, priority and combat timing are shared game state.
 */

function FaBSeatOrder(): array {
    $raw = preg_replace('/[^1-4]/', '', (string)GetSeatOrder());
    return $raw === '' ? [1, 2] : array_values(array_unique(array_map('intval', str_split($raw))));
}

function FaBLiveSeats(): array {
    $raw = preg_replace('/[^1-4]/', '', (string)GetLiveSeats());
    $seats = $raw === '' ? FaBSeatOrder() : array_values(array_unique(array_map('intval', str_split($raw))));
    return array_values(array_intersect(FaBSeatOrder(), $seats));
}

function FaBSeatCount(): int { return count(FaBSeatOrder()); }
function FaBSeatIsLive(int $seat): bool { return in_array($seat, FaBLiveSeats(), true); }

function FaBNextSeat(int $seat, bool $liveOnly = true): int {
    $order = $liveOnly ? FaBLiveSeats() : FaBSeatOrder();
    if (empty($order)) return $seat;
    $idx = array_search($seat, $order, true);
    return $idx === false ? $order[0] : $order[($idx + 1) % count($order)];
}

function FaBPassiveSeats(): array {
    $decoded = json_decode((string)GetGameState(), true);
    if (is_array($decoded) && array_key_exists('passiveSeats', $decoded)) {
        $seats = array_map('intval', is_array($decoded['passiveSeats']) ? $decoded['passiveSeats'] : []);
        return array_values(array_intersect(FaBLiveSeats(), array_values(array_unique($seats))));
    }

    // Compatibility for goldfish games created before passive-seat metadata was
    // persisted. A real player cannot enter a game without a hero/deck, while the
    // deliberately empty goldfish opponent has neither.
    $passive = [];
    foreach (FaBLiveSeats() as $seat) {
        if (empty(GetHero($seat)) && empty(GetDeck($seat)) && empty(GetHand($seat))
            && empty(GetWeapons($seat)) && empty(GetEquipment($seat))) {
            $passive[] = $seat;
        }
    }
    return $passive;
}

function FaBIsPassiveSeat(int $seat): bool {
    return in_array($seat, FaBPassiveSeats(), true);
}

function FaBEnsureGoldfishOpponent(int $seat): void {
    if ($seat < 1 || $seat > 4 || !empty(GetHero($seat))) return;
    $heroID = 'ira_crimson_haze';
    AddHero($seat, CardID:$heroID, Owner:$seat, Controller:$seat, Status:2);
    AddHealth($seat, max(1, intval(CardHealth($heroID)) ?: 20));
    AddResources($seat, 0); AddActionPoints($seat, 0);
}

function FaBEnsureGoldfishOpponents(array $state): void {
    foreach ((array)($state['passiveSeats'] ?? []) as $seat) FaBEnsureGoldfishOpponent(intval($seat));
}

function FaBNextInteractiveSeat(int $seat): int {
    $candidate = $seat;
    for ($guard = 0; $guard < max(1, count(FaBLiveSeats())); ++$guard) {
        $candidate = FaBNextSeat($candidate);
        if (!FaBIsPassiveSeat($candidate)) return $candidate;
    }
    return $seat;
}

function FaBOpponents(int $seat): array {
    return array_values(array_filter(FaBLiveSeats(), fn($candidate) => $candidate !== $seat));
}

function FaBDefaultDefender(int $attacker): int {
    $next = FaBNextSeat($attacker);
    return $next === $attacker ? 0 : $next;
}

function FaBStateDefaults(): array {
    return [
        'window' => 'ACTION',
        'combatOpen' => false,
        'combatStep' => 'NONE',
        'chainLink' => 0,
        'attacker' => 0,
        'defender' => 0,
        'attackUID' => 0,
        'attackTarget' => null,
        'pendingAttackTarget' => null,
        'previousAttackCardID' => '',
        'handBlockUIDs' => [],
        'intimidated' => [],
        'pendingPayment' => null,
        'lastAttackName' => '',
        'attackHit' => false,
        'attackPower' => 0,
        'defenseValue' => 0,
        'damageDealt' => 0,
        'declaredBlockUIDs' => [],
        'passiveSeats' => [],
        'gameMode' => '',
        'turnEffects' => [],
        'nextTurnEffects' => [],
        'hitsThisTurn' => [],
        'cardsPlayedThisTurn' => [],
    ];
}

function FaBResetWindowState(): array {
    $previous = FaBGetState();
    $passiveSeats = FaBPassiveSeats();
    $state = FaBStateDefaults();
    $state['passiveSeats'] = $passiveSeats;
    $state['gameMode'] = (string)($previous['gameMode'] ?? '');
    if ($state['gameMode'] === '' && !empty($passiveSeats)) $state['gameMode'] = 'GOLDFISH';
    foreach (['turnEffects', 'nextTurnEffects', 'hitsThisTurn', 'cardsPlayedThisTurn'] as $key) {
        $state[$key] = is_array($previous[$key] ?? null) ? $previous[$key] : [];
    }
    return $state;
}

function FaBGetState(): array {
    $decoded = json_decode((string)GetGameState(), true);
    return array_replace(FaBStateDefaults(), is_array($decoded) ? $decoded : []);
}

function FaBSetState(array $state): void {
    SetGameState(json_encode(array_replace(FaBStateDefaults(), $state), JSON_UNESCAPED_SLASHES));
}

function FaBTypes($cardID): array {
    $types = CardTypes($cardID);
    return is_array($types) ? $types : [];
}

function FaBHasType($cardID, $type): bool {
    foreach (FaBTypes($cardID) as $candidate) if (strcasecmp((string)$candidate, (string)$type) === 0) return true;
    return false;
}

function FaBKeywords($cardID): array {
    $keywords = function_exists('CardCard_keywords') ? CardCard_keywords($cardID) : [];
    return is_array($keywords) ? $keywords : [];
}

function FaBHasKeyword($cardID, string $keyword): bool {
    foreach (FaBKeywords($cardID) as $candidate) {
        if (strcasecmp(trim((string)$candidate), trim($keyword)) === 0) return true;
        if (str_starts_with(strtolower(trim((string)$candidate)), strtolower(trim($keyword)) . ' ')) return true;
    }
    return false;
}

function FaBObjectCounters(object $obj): array {
    if (is_array($obj->Counters ?? null)) return $obj->Counters;
    $decoded = json_decode((string)($obj->Counters ?? ''), true);
    return is_array($decoded) ? $decoded : [];
}

function FaBSetObjectCounter(object $obj, string $name, int $value): void {
    $counters = FaBObjectCounters($obj);
    if ($value === 0) unset($counters[$name]); else $counters[$name] = $value;
    $obj->Counters = $counters;
}

function FaBCurrentDefense(object $obj, int $player): int {
    $base = max(0, intval(CardDefense($obj->CardID)));
    if (FaBHasType($obj->CardID, 'Equipment')) $base = max(0, $base - intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0));
    $delta = function_exists('EvaluateDefenseModifier') ? intval(EvaluateDefenseModifier($obj->CardID, $player, $obj, $base, $obj)) : 0;
    if (function_exists('FaBWTRDefenseModifier')) $delta += FaBWTRDefenseModifier($player, $obj);
    return max(0, $base + $delta);
}

function FaBCurrentAttackHasKeyword(array $state, string $keyword): bool {
    $attack = FaBFindUID(intval($state['attackUID'] ?? 0));
    if ($attack === null) return false;
    $effects = is_array($attack['object']->TurnEffects ?? null) ? $attack['object']->TurnEffects : [];
    if (in_array(strtoupper($keyword), array_map('strtoupper', $effects), true)) return true;
    $base = FaBPrintedKeywordIsActive($attack['object']->CardID, $keyword) ? 1 : 0;
    $evaluator = 'Evaluate' . str_replace(' ', '', ucwords(strtolower($keyword))) . 'Modifier';
    $delta = function_exists($evaluator) ? intval($evaluator($attack['object']->CardID, intval($state['attacker']), $attack['object'], $base, $attack['object'])) : 0;
    return max(0, min(1, $base + $delta)) === 1;
}

function FaBPrintedKeywordIsActive(string $cardID, string $keyword): bool {
    if (!FaBHasKeyword($cardID, $keyword)) return false;
    $text = trim((string)(function_exists('CardFunctional_text_plain') ? CardFunctional_text_plain($cardID) : ''));
    $needle = strtolower($keyword);
    foreach (preg_split('/\R+/', $text) as $line) {
        if (strcasecmp(trim($line), $keyword) === 0) return true;
    }
    // Keyword-only cards sometimes have no text in older source records.
    return $text === '' && in_array($needle, ['go again', 'dominate'], true);
}

function FaBComboActive(array $state, string $requiredCardID): bool {
    return strcasecmp((string)($state['previousAttackCardID'] ?? ''), $requiredCardID) === 0;
}

function FaBAttackHasGoAgain(array $state, object $attack): bool {
    $effects = is_array($attack->TurnEffects ?? null) ? $attack->TurnEffects : [];
    if (in_array('GO_AGAIN', $effects, true)) return true;
    $base = FaBPrintedKeywordIsActive($attack->CardID, 'Go again') ? 1 : 0;
    $delta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($attack->CardID, intval($state['attacker']), $attack, $base, $attack)) : 0;
    if (function_exists('FaBWTRAttackHasGoAgain') && FaBWTRAttackHasGoAgain($state, $attack)) return true;
    return max(0, min(1, $base + $delta)) === 1;
}

function FaBIntimidate(int $sourcePlayer, int $targetPlayer, int $amount = 1): array {
    $state = FaBGetState(); $banished = [];
    for ($n = 0; $n < max(0, $amount); ++$n) {
        $choices = [];
        foreach (GetHand($targetPlayer) as $obj) if (is_object($obj) && empty($obj->removed)) $choices[] = intval($obj->UniqueID ?? 0);
        $choices = array_values(array_filter($choices)); if (empty($choices)) break;
        $uid = $choices[EngineRandomInt(0, count($choices) - 1)];
        $moved = FaBMoveUID($uid, 'Banish', $targetPlayer);
        if ($moved !== null) { $moved->FaceDown = 1; $moved->ReturnAtEndTurn = 1; $banished[] = $uid; }
    }
    $state['intimidated'] = array_values(array_unique(array_merge($state['intimidated'] ?? [], $banished)));
    FaBSetState($state); return $banished;
}

function FaBRandomHandUID(int $player, array $excludedUIDs = []): int {
    $choices = [];
    foreach (GetHand($player) as $obj) {
        if (!is_object($obj) || !empty($obj->removed)) continue;
        $uid = intval($obj->UniqueID ?? 0);
        if ($uid > 0 && !in_array($uid, $excludedUIDs, true)) $choices[] = $uid;
    }
    return empty($choices) ? 0 : $choices[EngineRandomInt(0, count($choices) - 1)];
}

function FaBDiscardRandom(int $player, int $amount = 1): array {
    $discarded = [];
    for ($i = 0; $i < max(0, $amount); ++$i) {
        $uid = FaBRandomHandUID($player); if ($uid <= 0) break;
        $found = FaBFindUID($uid); $cardID = $found['object']->CardID ?? '';
        if (FaBMoveUID($uid, 'Graveyard', $player) !== null) {
            $discarded[] = $uid;
            if (function_exists('FaBWTRCardDiscarded')) FaBWTRCardDiscarded($player, (string)$cardID);
        }
    }
    return $discarded;
}

function FaBHandCount(int $player): int {
    $count = 0; foreach (GetHand($player) as $obj) if (is_object($obj) && empty($obj->removed)) ++$count; return $count;
}

function FaBReturnIntimidatedCards(): void {
    $state = FaBGetState();
    foreach (($state['intimidated'] ?? []) as $uid) {
        $found = FaBFindUID(intval($uid));
        if ($found !== null && $found['zone'] === 'Banish' && intval($found['object']->ReturnAtEndTurn ?? 0) === 1) {
            FaBMoveUID(intval($uid), 'Hand', intval($found['object']->Owner ?? $found['player']));
        }
    }
    $state['intimidated'] = []; FaBSetState($state);
}

function ParseModifierResult($result): array {
    if (is_array($result)) {
        $delta = intval($result['delta'] ?? 0);
        return ['delta' => $delta, 'consume' => !empty($result['consume']),
            'applied' => array_key_exists('applied', $result) ? !empty($result['applied']) : $delta !== 0];
    }
    $delta = intval($result);
    return ['delta' => $delta, 'consume' => false, 'applied' => $delta !== 0];
}

function ConsumeModifierSource($sourceObj): bool {
    // FaB modifier consumption is explicit until a generated replacement effect supplies a consumable source.
    return false;
}

function FaBZoneGet(string $zone, int $player = 0): array {
    return match ($zone) {
        'Hero' => GetHero($player), 'Weapons' => GetWeapons($player), 'Equipment' => GetEquipment($player),
        'Arena' => GetArena($player), 'CombatChain' => GetCombatChain($player), 'Deck' => GetDeck($player),
        'Hand' => GetHand($player), 'Arsenal' => GetArsenal($player), 'Graveyard' => GetGraveyard($player),
        'Banish' => GetBanish($player), 'Pitch' => GetPitch($player), 'Stack' => GetStack(),
        default => [],
    };
}

function FaBIdentityZones(): array {
    return ['Hero', 'Weapons', 'Equipment', 'Arena', 'CombatChain', 'Deck', 'Hand', 'Arsenal', 'Graveyard', 'Banish', 'Pitch'];
}

/** Resolve a persistent identity at the last responsible moment. */
function FaBFindUID(int $uid): ?array {
    if ($uid <= 0) return null;
    foreach (FaBSeatOrder() as $seat) {
        foreach (FaBIdentityZones() as $zoneName) {
            $zone = FaBZoneGet($zoneName, $seat);
            foreach ($zone as $index => $obj) {
                if (!is_object($obj) || !empty($obj->removed) || intval($obj->UniqueID ?? 0) !== $uid) continue;
                return ['player' => $seat, 'zone' => $zoneName, 'index' => $index,
                    'mzID' => 'p' . $seat . $zoneName . '-' . $index, 'object' => $obj];
            }
        }
    }
    foreach (GetStack() as $index => $obj) {
        if (is_object($obj) && empty($obj->removed) && intval($obj->UniqueID ?? 0) === $uid) {
            return ['player' => intval($obj->Controller ?? 0), 'zone' => 'Stack', 'index' => $index,
                'mzID' => 'Stack-' . $index, 'object' => $obj];
        }
    }
    return null;
}

function FaBIdentityFromMZ(string $mzID): ?array {
    $obj = GetZoneObject($mzID);
    if (!is_object($obj) || !empty($obj->removed)) return null;
    $uid = intval($obj->UniqueID ?? 0);
    return $uid > 0 ? FaBFindUID($uid) : null;
}

function FaBObjectCanBeAttacked(object $obj): bool {
    if (FaBHasType($obj->CardID, 'Ally') || FaBHasKeyword($obj->CardID, 'Spectra')) return true;
    $effects = array_map('strtoupper', is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : []);
    return in_array('ATTACKABLE', $effects, true);
}

function FaBAttackTargetDescriptor(array $found): array {
    return [
        'type' => $found['zone'] === 'Hero' ? 'HERO' : 'PERMANENT',
        'player' => intval($found['player']),
        'uid' => intval($found['object']->UniqueID ?? 0),
        'zone' => (string)$found['zone'],
    ];
}

function FaBLegalAttackTargets(int $attacker): array {
    $targets = [];
    foreach (FaBOpponents($attacker) as $seat) {
        foreach (GetHero($seat) as $index => $hero) {
            if (!is_object($hero) || !empty($hero->removed)) continue;
            $targets[] = FaBAttackTargetDescriptor(['player'=>$seat,'zone'=>'Hero','index'=>$index,'object'=>$hero]);
        }
        foreach (GetArena($seat) as $index => $permanent) {
            if (!is_object($permanent) || !empty($permanent->removed) || !FaBObjectCanBeAttacked($permanent)) continue;
            $targets[] = FaBAttackTargetDescriptor(['player'=>$seat,'zone'=>'Arena','index'=>$index,'object'=>$permanent]);
        }
    }
    return $targets;
}

function FaBResolveAttackTarget(array $descriptor, int $attacker): ?array {
    $found = FaBFindUID(intval($descriptor['uid'] ?? 0));
    if ($found === null || intval($found['player']) === $attacker || !FaBSeatIsLive(intval($found['player']))) return null;
    if ($found['zone'] !== 'Hero' && ($found['zone'] !== 'Arena' || !FaBObjectCanBeAttacked($found['object']))) return null;
    return FaBAttackTargetDescriptor($found);
}

function FaBAttackTargetMZ(array $descriptor): string {
    $found = FaBFindUID(intval($descriptor['uid'] ?? 0));
    return $found === null ? '' : (string)$found['mzID'];
}

function FaBAttackTargetChoiceMZ(array $descriptor, int $viewer): string {
    $found = FaBFindUID(intval($descriptor['uid'] ?? 0));
    if ($found === null) return '';
    if (FaBSeatCount() === 2) {
        $prefix = intval($found['player']) === $viewer ? 'my' : 'their';
        return $prefix . $found['zone'] . '-' . intval($found['index']);
    }
    return (string)$found['mzID'];
}

/** Returns a target, null while a chooser is pending, or false when no target exists. */
function FaBClaimOrRequestAttackTarget(int $player, int $sourceUID, string $sourceKind) {
    $state = FaBGetState();
    $pending = $state['pendingAttackTarget'] ?? null;
    if (is_array($pending) && intval($pending['sourceUID'] ?? 0) === $sourceUID) {
        $target = FaBResolveAttackTarget((array)($pending['target'] ?? []), $player);
        $state['pendingAttackTarget'] = null; FaBSetState($state);
        return $target ?? false;
    }
    $targets = FaBLegalAttackTargets($player);
    if (empty($targets)) return false;
    if (count($targets) === 1) return $targets[0];
    $specs = [];
    foreach ($targets as $target) {
        $mzID = FaBAttackTargetChoiceMZ($target, $player);
        if ($mzID !== '') $specs[] = $mzID;
    }
    if (count($specs) === 1) return $targets[0];
    if (empty($specs)) return false;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $specs), 1, 'Choose_attack_target');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_ATTACK_TARGET|' . $sourceUID . '|' . rawurlencode($sourceKind), 1);
    return null;
}

function FaBAddToZone(string $zone, int $player, object $source): ?object {
    return match ($zone) {
        'Hero' => AddHero($player, CardID:$source->CardID, sourceObject:$source),
        'Weapons' => AddWeapons($player, CardID:$source->CardID, sourceObject:$source),
        'Equipment' => AddEquipment($player, CardID:$source->CardID, sourceObject:$source),
        'Arena' => AddArena($player, CardID:$source->CardID, sourceObject:$source),
        'CombatChain' => AddCombatChain($player, CardID:$source->CardID, sourceObject:$source),
        'Deck' => AddDeck($player, CardID:$source->CardID, sourceObject:$source),
        'Hand' => AddHand($player, CardID:$source->CardID, sourceObject:$source),
        'Arsenal' => AddArsenal($player, CardID:$source->CardID, sourceObject:$source),
        'Graveyard' => AddGraveyard($player, CardID:$source->CardID, sourceObject:$source),
        'Banish' => AddBanish($player, CardID:$source->CardID, sourceObject:$source),
        'Pitch' => AddPitch($player, CardID:$source->CardID, sourceObject:$source),
        default => null,
    };
}

function FaBMoveUID(int $uid, string $toZone, ?int $targetPlayer = null, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] === 'Stack') return null;
    if ($toZone === 'Graveyard' && function_exists('FaBWTRBase') && FaBWTRBase((string)$found['object']->CardID) === 'drone_of_brutality') $toZone = 'Deck';
    $source = $found['object'];
    $targetPlayer ??= intval($source->Owner ?? $found['player']);
    if ($targetPlayer < 1) $targetPlayer = $found['player'];
    $source->removed = true;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        $newIndex = intval($newObj->mzIndex ?? 0);
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . $newIndex, 360, true, $uid, $uid);
    }
    return $newObj;
}

function FaBMoveStackUID(int $uid, string $toZone, int $targetPlayer, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] !== 'Stack') return null;
    $source = $found['object'];
    $source->removed = true;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . intval($newObj->mzIndex ?? 0), 360, true, $uid, $uid);
    }
    return $newObj;
}

function SaveUndoVersion($targetPlayerID, $name = ''): void {
    // FaBSim intentionally keeps one reversible snapshot. Replacing the array
    // atomically avoids walking every historical Versions object in PHP, then
    // delegates serialization/numbering to the same generated SaveVersion()
    // path used by the other engine apps.
    $versions = &GetVersions(intval($targetPlayerID));
    $versions = [];
    SaveVersion(intval($targetPlayerID), $name);
}

function FaBCardCost(object $obj, int $player): int {
    $base = max(0, intval(CardCost($obj->CardID)));
    $delta = function_exists('EvaluateCostModifier') ? intval(EvaluateCostModifier($obj->CardID, $player, $obj, $base, $obj)) : 0;
    if (function_exists('FaBWTRCostModifier')) $delta += FaBWTRCostModifier($player, $obj);
    return max(0, $base + $delta);
}

function FaBAvailablePitch(int $player, int $excludedUID = 0): int {
    $total = max(0, intval(GetResources($player)));
    foreach (GetHand($player) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->UniqueID ?? 0) === $excludedUID) continue;
        $total += max(0, intval(CardPitch($obj->CardID)));
    }
    return $total;
}

function FaBStackTop(): ?object {
    $stack = GetStack();
    for ($i = count($stack) - 1; $i >= 0; --$i) if (is_object($stack[$i]) && empty($stack[$i]->removed)) return $stack[$i];
    return null;
}

function FaBStackCount(): int {
    $count = 0; foreach (GetStack() as $obj) if (is_object($obj) && empty($obj->removed)) ++$count; return $count;
}

function CanPlayCard($player, $mzID): bool {
    $player = intval($player);
    if (!FaBSeatIsLive($player) || intval(GetWinner()) !== 0 || intval(GetPriorityPlayer()) !== $player) return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || $found['player'] !== $player || !in_array($found['zone'], ['Hand', 'Arsenal', 'Banish'], true)) return false;
    if ($found['zone'] === 'Banish' && empty($found['object']->PlayableFromBanish)) return false;
    $obj = $found['object']; $state = FaBGetState();
    if ($state['pendingPayment'] !== null) return false;
    $isAttackReaction = FaBHasType($obj->CardID, 'Attack Reaction');
    $isDefenseReaction = FaBHasType($obj->CardID, 'Defense Reaction');
    $isInstant = FaBHasType($obj->CardID, 'Instant');
    $isAction = FaBHasType($obj->CardID, 'Action');
    $isAttack = FaBHasType($obj->CardID, 'Attack');
    $actionWindow = $state['window'] === 'ACTION'
        || ($state['window'] === 'RESOLUTION' && $isAttack);
    $timingLegal = ($isAction && $player === intval(GetTurnPlayer()) && $actionWindow && intval(GetActionPoints($player)) > 0)
        || ($isAttackReaction && $state['window'] === 'REACTION' && $player === intval($state['attacker']))
        || ($isDefenseReaction && $state['window'] === 'REACTION' && $player === intval($state['defender']) && ($state['attackTarget']['type'] ?? 'HERO') === 'HERO')
        || ($isInstant && !in_array($state['window'], ['PITCH', 'DEFEND_DECLARE'], true));
    if (function_exists('FaBWTRCanPlay') && !FaBWTRCanPlay($player, $found, $state)) return false;
    return $timingLegal && FaBAvailablePitch($player, intval($obj->UniqueID ?? 0)) >= FaBCardCost($obj, $player);
}

function DoPlayCard($player, $mzID) {
    $player = intval($player);
    if (!CanPlayCard($player, $mzID)) return false;
    $found = FaBIdentityFromMZ((string)$mzID); if ($found === null) return false;
    $isAttack = FaBHasType($found['object']->CardID, 'Attack');
    $attackTarget = null;
    if ($isAttack) {
        $attackTarget = FaBClaimOrRequestAttackTarget($player, intval($found['object']->UniqueID), 'PLAY');
        if ($attackTarget === null) return true;
        if ($attackTarget === false) return false;
    }
    SaveUndoVersion($player, 'Before playing ' . (CardName($found['object']->CardID) ?: $found['object']->CardID));
    $source = $found['object']; $uid = intval($source->UniqueID); $fromZone = $found['zone'];
    $kind = FaBHasType($source->CardID, 'Attack') ? 'ATTACK'
        : (FaBHasType($source->CardID, 'Defense Reaction') ? 'DEFENSE_REACTION'
        : (FaBHasType($source->CardID, 'Attack Reaction') ? 'ATTACK_REACTION'
        : (FaBHasType($source->CardID, 'Instant') ? 'INSTANT' : 'ACTION')));
    $source->removed = true;
    $stackObj = AddStack(CardID:$source->CardID, Controller:$player, Kind:$kind, SourceZone:$fromZone,
        SourceUniqueID:$uid, Params:$attackTarget === null ? [] : ['attackTarget'=>$attackTarget], sourceObject:$source);
    $stackObj->Controller = $player; $stackObj->Kind = $kind; $stackObj->SourceZone = $fromZone; $stackObj->SourceUniqueID = $uid;
    if (function_exists('FaBWTRPayAdditionalCosts')) FaBWTRPayAdditionalCosts($player, $stackObj);
    if (function_exists('QueueZoneMoveAnimation')) QueueZoneMoveAnimation($found['mzID'], 'Stack-' . intval($stackObj->mzIndex), 360, true, $uid, $uid);
    $state = FaBGetState();
    $state['pendingPayment'] = ['player' => $player, 'uid' => $uid, 'cost' => FaBCardCost($stackObj, $player), 'fromZone' => $fromZone,
        'kind' => $kind, 'returnWindow' => (string)$state['window'], 'returnCombatStep' => (string)$state['combatStep']];
    $state['window'] = 'PITCH'; FaBSetState($state);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
    return FaBTryCompletePayment();
}

function CanPitchCard($player, $mzID): bool {
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== intval($player) || $state['window'] !== 'PITCH') return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    return $found !== null && $found['player'] === intval($player) && $found['zone'] === 'Hand'
        && max(0, intval(CardPitch($found['object']->CardID))) > 0;
}

function DoPitchCard($player, $mzID) {
    if (!CanPitchCard($player, $mzID)) return false;
    $found = FaBIdentityFromMZ((string)$mzID); if ($found === null) return false;
    $pitch = max(0, intval(CardPitch($found['object']->CardID)));
    $pitchedCardID = (string)$found['object']->CardID;
    $uid = intval($found['object']->UniqueID); FaBMoveUID($uid, 'Pitch', intval($player));
    AddResources(intval($player), intval(GetResources(intval($player))) + $pitch);
    if (function_exists('FaBWTRCardPitched')) FaBWTRCardPitched(intval($player), $pitchedCardID);
    return FaBTryCompletePayment();
}

function FaBTryCompletePayment(): bool {
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending)) return false;
    $player = intval($pending['player']); $cost = max(0, intval($pending['cost']));
    if (intval(GetResources($player)) < $cost) return true;
    AddResources($player, intval(GetResources($player)) - $cost);
    if (in_array((string)$pending['kind'], ['ACTION', 'ATTACK'], true)) AddActionPoints($player, max(0, intval(GetActionPoints($player)) - 1));
    $weaponUID = intval($pending['weaponUID'] ?? 0);
    if ($weaponUID > 0) {
        $weapon = FaBFindUID($weaponUID);
        if ($weapon !== null && $weapon['zone'] === 'Weapons') $weapon['object']->Status = 1;
    }
    $returnWindow = (string)($pending['returnWindow'] ?? 'ACTION');
    $returnCombatStep = (string)($pending['returnCombatStep'] ?? $state['combatStep']);
    $state['pendingPayment'] = null;
    $state['combatStep'] = $returnCombatStep;
    if ((string)$pending['kind'] === 'ATTACK' && !empty($state['combatOpen'])) {
        $state['combatStep'] = 'LAYER';
        $state['window'] = 'PRIORITY';
    } elseif (in_array($returnWindow, ['ATTACK', 'DEFEND_PRIORITY', 'REACTION', 'DAMAGE', 'RESOLUTION'], true)) {
        $state['window'] = $returnWindow;
    } else {
        $state['window'] = 'PRIORITY';
    }
    FaBSetState($state);
    $found = FaBFindUID(intval($pending['uid']));
    if (empty($pending['isWeaponAttack']) && $found !== null) OnCardPlayed($player, $found['mzID'], $found['object']->CardID, (string)$pending['fromZone']);
    SetPriorityPlayer(FaBNextSeat($player)); SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
    return true;
}

function FaBReactionWindowForState(array $state): bool { return !empty($state['combatOpen']) && $state['combatStep'] === 'REACTION'; }

function OnCardPlayed($player, $mzID, $cardID, $fromZone) {
    $params = ['mzID' => $mzID, 'cardID' => $cardID, 'fromZone' => $fromZone];
    if (function_exists('FaBWTRCardPlayed')) FaBWTRCardPlayed(intval($player), (string)$mzID, (string)$cardID, (string)$fromZone);
    $count = FaBRunSourceMacro('CardPlayed', intval($player), (string)$cardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('CardPlayed', intval($player), $params) : 0);
}

function OnAttackDeclared($player, $mzID, $attacker, $defender) {
    $params = compact('mzID', 'attacker', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found !== null && function_exists('FaBWTRAttackDeclared')) FaBWTRAttackDeclared(intval($player), $found['object'], intval($defender));
    $count = $found === null ? 0 : FaBRunSourceMacro('AttackDeclared', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('AttackDeclared', intval($player), $params) : 0);
}

function OnDefended($player, $mzID, $defender) {
    $params = compact('mzID', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found !== null && function_exists('FaBWTRDefended')) FaBWTRDefended(intval($player), $found['object']);
    $count = $found === null ? 0 : FaBRunSourceMacro('Defended', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Defended', intval($player), $params) : 0);
}

function OnHit($player, $mzID, $amount) {
    $params = compact('mzID', 'amount'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found !== null && function_exists('FaBWTRHit')) FaBWTRHit(intval($player), $found['object'], intval($amount));
    $count = $found === null ? 0 : FaBRunSourceMacro('Hit', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Hit', intval($player), $params) : 0);
}

function OnChainLinkResolved($player, $mzID) {
    $params = compact('mzID'); $found = FaBIdentityFromMZ((string)$mzID);
    $count = $found === null ? 0 : FaBRunSourceMacro('ChainLinkResolved', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('ChainLinkResolved', intval($player), $params) : 0);
}

function OnCombatChainClosed($player) {
    return function_exists('DispatchMacroListeners') ? DispatchMacroListeners('CombatChainClosed', intval($player), []) : 0;
}

function FaBRunSourceMacro(string $macroName, int $player, string $cardID, array $params): int {
    $countFn = 'Card' . $macroName . 'Count';
    $base = lcfirst($macroName);
    $abilities = $GLOBALS[$base . 'Abilities'] ?? [];
    $prereqs = $GLOBALS[$base . 'Prereqs'] ?? [];
    if (!function_exists($countFn) || !is_array($abilities)) return 0;
    foreach ($params as $name => $value) DecisionQueueController::StoreVariable($name, $value);
    $ran = 0;
    for ($i = 0; $i < intval($countFn($cardID)); ++$i) {
        $key = $cardID . ':' . $i;
        if (!isset($abilities[$key])) continue;
        if (isset($prereqs[$key]) && !$prereqs[$key](...array_merge([$player], array_values($params)))) continue;
        $abilities[$key]($player); ++$ran;
    }
    return $ran;
}

function DoResolveCard($player, $mzID) {
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || $found['zone'] !== 'Stack') return false;
    $obj = $found['object']; $controller = intval($obj->Controller ?? $player); $uid = intval($obj->UniqueID);
    $kind = (string)($obj->Kind ?? 'ACTION'); $state = FaBGetState();
    if ($kind === 'ATTACK') {
        $attackTarget = FaBResolveAttackTarget((array)($obj->Params['attackTarget'] ?? []), $controller);
        if ($attackTarget === null) return false;
        $defender = intval($attackTarget['player']);
        $chain = FaBMoveStackUID($uid, 'CombatChain', $controller);
        if ($chain === null || $defender === 0) return false;
        $state = FaBGetState();
        $prior = FaBFindUID(intval($state['attackUID'] ?? 0));
        $state['previousAttackCardID'] = $prior !== null ? (string)$prior['object']->CardID : (string)($state['previousAttackCardID'] ?? '');
        $state['combatOpen'] = true; $state['combatStep'] = 'ATTACK'; $state['window'] = 'ATTACK';
        $state['chainLink'] = intval($state['chainLink']) + 1; $state['attacker'] = $controller; $state['defender'] = $defender;
        $state['attackUID'] = $uid; $state['lastAttackName'] = CardName($chain->CardID) ?: $chain->CardID; $state['attackHit'] = false;
        $state['attackTarget'] = $attackTarget;
        $state['attackPower'] = 0; $state['defenseValue'] = 0; $state['damageDealt'] = 0;
        $state['handBlockUIDs'] = []; $state['declaredBlockUIDs'] = [];
        $chain->Role = 'ATTACK'; $chain->ChainLink = $state['chainLink']; $chain->FromZone = (string)($obj->SourceZone ?? 'Hand');
        FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
        OnAttackDeclared($controller, FaBFindUID($uid)['mzID'], $controller, $defender);
    } elseif (in_array($kind, ['ATTACK_REACTION', 'DEFENSE_REACTION'], true)) {
        $owner = $kind === 'DEFENSE_REACTION' ? intval($state['defender']) : intval($state['attacker']);
        $chain = FaBMoveStackUID($uid, 'CombatChain', $owner);
        if ($chain !== null) { $chain->Role = $kind; $chain->ChainLink = intval($state['chainLink']); }
        if (function_exists('FaBWTRResolveCard')) FaBWTRResolveCard($controller, $obj, $chain);
        SetPriorityPlayer(intval($state['attacker'])); SetConsecutivePasses(0);
    } else {
        $persistent = FaBHasType($obj->CardID, 'Aura') || FaBHasType($obj->CardID, 'Item') || FaBHasType($obj->CardID, 'Ally');
        $resolved = FaBMoveStackUID($uid, $persistent ? 'Arena' : 'Graveyard', intval($obj->Owner ?? $controller));
        if (function_exists('FaBWTRResolveCard')) FaBWTRResolveCard($controller, $obj, $resolved);
        $baseGoAgain = FaBPrintedKeywordIsActive($obj->CardID, 'Go again') ? 1 : 0;
        $goAgainDelta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($obj->CardID, $controller, $obj, $baseGoAgain, $obj)) : 0;
        if ($kind === 'ACTION' && max(0, min(1, $baseGoAgain + $goAgainDelta)) === 1) AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);
        $state = FaBGetState();
        if (!empty($state['combatOpen']) && in_array($state['combatStep'], ['ATTACK', 'DEFEND', 'REACTION', 'DAMAGE', 'RESOLUTION'], true)) {
            $state['window'] = $state['combatStep'] === 'DEFEND' ? 'DEFEND_PRIORITY' : $state['combatStep'];
        } else {
            $state['window'] = 'ACTION';
        }
        FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    }
    FaBAutoPassShortcuts();
    return true;
}

function FaBCanBlock(int $player, string $mzID): bool {
    $state = FaBGetState(); if ($state['window'] !== 'DEFEND_DECLARE' || intval($state['defender']) !== $player) return false;
    if (($state['attackTarget']['type'] ?? 'HERO') !== 'HERO') return false;
    $found = FaBIdentityFromMZ($mzID);
    if ($found === null || $found['player'] !== $player || !in_array($found['zone'], ['Hand', 'Equipment'], true)) return false;
    if (FaBCurrentDefense($found['object'], $player) <= 0) return false;
    if ($found['zone'] === 'Hand' && FaBCurrentAttackHasKeyword($state, 'Dominate') && count($state['handBlockUIDs'] ?? []) >= 1) return false;
    return true;
}

function FaBDeclareBlock(int $player, string $mzID): bool {
    if (!FaBCanBlock($player, $mzID)) return false;
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return false;
    SaveUndoVersion($player, 'Before blocking with ' . (CardName($found['object']->CardID) ?: $found['object']->CardID));
    $uid = intval($found['object']->UniqueID); $from = $found['zone'];
    $chain = FaBMoveUID($uid, 'CombatChain', $player);
    if ($chain === null) return false;
    $state = FaBGetState(); $chain->Role = 'DEFENSE'; $chain->ChainLink = intval($state['chainLink']); $chain->FromZone = $from;
    if ($from === 'Hand') {
        $state['handBlockUIDs'][] = $uid;
        $state['handBlockUIDs'] = array_values(array_unique(array_map('intval', $state['handBlockUIDs'])));
    }
    $state['declaredBlockUIDs'][] = $uid;
    $state['declaredBlockUIDs'] = array_values(array_unique(array_map('intval', $state['declaredBlockUIDs'])));
    $state['defenseValue'] = FaBDefenseValue($state);
    FaBSetState($state);
    return true;
}

function FaBFinishDefendDeclaration(array $state): void {
    foreach ((array)($state['declaredBlockUIDs'] ?? []) as $uid) {
        $found = FaBFindUID(intval($uid));
        if ($found !== null && $found['zone'] === 'CombatChain') OnDefended(intval($state['defender']), $found['mzID'], intval($state['defender']));
    }
    $state = FaBGetState();
    $state['combatStep'] = 'DEFEND'; $state['window'] = 'DEFEND_PRIORITY';
    $state['defenseValue'] = FaBDefenseValue($state);
    FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
}

function FaBAttackPower(array $state): int {
    $attack = FaBFindUID(intval($state['attackUID']));
    if ($attack === null) return 0;
    $base = max(0, intval(CardPower($attack['object']->CardID)));
    $delta = function_exists('EvaluateAttackPowerModifier') ? intval(EvaluateAttackPowerModifier($attack['object']->CardID, intval($state['attacker']), $attack['object'], $base, $attack['object'])) : 0;
    if (function_exists('FaBWTRAttackPowerModifier')) $delta += FaBWTRAttackPowerModifier(intval($state['attacker']), $attack['object'], $state);
    return max(0, $base + $delta);
}

function FaBDefenseValue(array $state): int {
    $total = 0;
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (!in_array((string)($obj->Role ?? ''), ['DEFENSE', 'DEFENSE_REACTION'], true)) continue;
        $total += FaBCurrentDefense($obj, intval($state['defender']));
    }
    return $total;
}

function DoDamage($player, $sourceMZ, $targetPlayer, $amount, $damageType = 'PHYSICAL') {
    $targetPlayer = intval($targetPlayer); $amount = max(0, intval($amount));
    if ($amount <= 0 || !FaBSeatIsLive($targetPlayer)) return 0;
    if (function_exists('FaBWTRPreventDamage')) $amount = FaBWTRPreventDamage($targetPlayer, $amount, (string)$damageType);
    if ($amount <= 0) return 0;
    AddHealth($targetPlayer, max(0, intval(GetHealth($targetPlayer)) - $amount));
    $hero = null; foreach (GetHero($targetPlayer) as $candidate) if (is_object($candidate) && empty($candidate->removed)) { $hero = $candidate; break; }
    if (function_exists('QueueDamageAnimation')) QueueDamageAnimation('p' . $targetPlayer . 'Hero-0', $amount, 500, true, intval($hero->UniqueID ?? 0));
    if (intval(GetHealth($targetPlayer)) <= 0) FaBEliminateSeat($targetPlayer, intval($player));
    return $amount;
}

function FaBEliminateSeat(int $seat, int $sourcePlayer = 0): void {
    $live = array_values(array_filter(FaBLiveSeats(), fn($candidate) => $candidate !== $seat));
    SetLiveSeats(implode('', $live));
    if (count($live) <= 1) SetWinner(intval($live[0] ?? $sourcePlayer));
}

function FaBBeginDamageStep(): void {
    $state = FaBGetState(); $power = FaBAttackPower($state); $defense = FaBDefenseValue($state);
    $amount = max(0, $power - $defense); $attack = FaBFindUID(intval($state['attackUID']));
    $state['combatStep'] = 'DAMAGE'; $state['window'] = 'DAMAGE';
    $state['attackPower'] = $power; $state['defenseValue'] = $defense; $state['damageDealt'] = $amount;
    FaBSetState($state);
    $target = FaBResolveAttackTarget((array)($state['attackTarget'] ?? []), intval($state['attacker']));
    $targetMZ = $target === null ? '' : FaBAttackTargetMZ($target);
    if ($attack !== null && $targetMZ !== '' && function_exists('QueueCardLungeAnimation')) QueueCardLungeAnimation($attack['mzID'], $targetMZ, 360, true, intval($state['attackUID']), intval($target['uid'] ?? 0));
    if ($amount > 0) {
        if (($target['type'] ?? 'HERO') === 'HERO') {
            DoDamage(intval($state['attacker']), $attack['mzID'] ?? '', intval($state['defender']), $amount, 'PHYSICAL');
        } elseif ($target !== null) {
            $targetFound = FaBFindUID(intval($target['uid']));
            if ($targetFound !== null) {
                $targetFound['object']->Damage = intval($targetFound['object']->Damage ?? 0) + $amount;
                if (function_exists('QueueDamageAnimation')) QueueDamageAnimation($targetFound['mzID'], $amount, 500, true, intval($target['uid']));
                $health = max(0, intval(CardHealth($targetFound['object']->CardID)));
                if ($health > 0 && intval($targetFound['object']->Damage) >= $health) FaBMoveUID(intval($target['uid']), 'Graveyard', intval($targetFound['object']->Owner ?? $targetFound['player']));
            }
        }
        $state['attackHit'] = true;
        FaBSetState($state);
        if ($attack !== null) OnHit(intval($state['attacker']), $attack['mzID'], $amount);
    }
    $state = FaBGetState();
    if ($amount > 0) $state['attackHit'] = true;
    $state['attackPower'] = $power; $state['defenseValue'] = $defense; $state['damageDealt'] = $amount;
    FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
}

function FaBBeginResolutionStep(): void {
    $state = FaBGetState(); $attack = FaBFindUID(intval($state['attackUID']));
    $state['combatStep'] = 'RESOLUTION'; $state['window'] = 'RESOLUTION'; FaBSetState($state);
    if ($attack !== null) OnChainLinkResolved(intval($state['attacker']), $attack['mzID']);
    $state = FaBGetState(); $attack = FaBFindUID(intval($state['attackUID']));
    if ($attack !== null && FaBAttackHasGoAgain($state, $attack['object'])) AddActionPoints(intval($state['attacker']), intval(GetActionPoints(intval($state['attacker']))) + 1);
    FaBCleanupResolvedLink($state);
    $state = FaBGetState(); $state['combatStep'] = 'RESOLUTION'; $state['window'] = 'RESOLUTION'; $state['handBlockUIDs'] = []; FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
}

function FaBCleanupResolvedLink(array $state): void {
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (($obj->Role ?? '') !== 'DEFENSE' || ($obj->FromZone ?? '') !== 'Equipment') continue;
        if (FaBHasKeyword($obj->CardID, 'Blade Break')) {
            $obj->TurnEffects = array_values(array_unique(array_merge(is_array($obj->TurnEffects) ? $obj->TurnEffects : [], ['DESTROY_ON_CHAIN_CLOSE'])));
        } elseif (FaBHasKeyword($obj->CardID, 'Battleworn')) {
            FaBSetObjectCounter($obj, 'DEFENSE', intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0) + 1);
        }
    }
}

function FaBCloseCombatChain(): void {
    $state = FaBGetState(); if (empty($state['combatOpen'])) return;
    foreach (FaBSeatOrder() as $seat) {
        $chain = GetCombatChain($seat);
        foreach ($chain as $obj) {
            if (!is_object($obj) || !empty($obj->removed)) continue;
            $uid = intval($obj->UniqueID ?? 0); $role = (string)($obj->Role ?? ''); $from = (string)($obj->FromZone ?? '');
            $effects = is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [];
            if ($role === 'DEFENSE' && $from === 'Equipment' && !in_array('DESTROY_ON_CHAIN_CLOSE', $effects, true)) FaBMoveUID($uid, 'Equipment', intval($obj->Owner ?? $seat));
            elseif ($role === 'ATTACK' && $from === 'Weapons') $obj->removed = true;
            elseif (function_exists('FaBWTRMoveReplacement') && FaBWTRMoveReplacement($obj, 'Graveyard', intval($obj->Owner ?? $seat))) continue;
            else FaBMoveUID($uid, 'Graveyard', intval($obj->Owner ?? $seat));
        }
    }
    OnCombatChainClosed(intval(GetTurnPlayer()));
    $state = FaBResetWindowState(); FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
}

function FaBPassPriority(int $player, bool $automatic = false): bool {
    if (intval(GetWinner()) !== 0 || intval(GetPriorityPlayer()) !== $player) return false;
    $state = FaBGetState();
    if ($state['window'] === 'PITCH') return false;
    if (!$automatic) SaveUndoVersion($player, 'Before passing priority');
    if ($state['window'] === 'DEFEND_DECLARE') {
        if ($player !== intval($state['defender'])) return false;
        FaBFinishDefendDeclaration($state); FaBAutoPassShortcuts(); return true;
    }
    $passes = intval(GetConsecutivePasses()) + 1; SetConsecutivePasses($passes);
    $needed = max(1, count(FaBLiveSeats()));
    if ($passes < $needed) { SetPriorityPlayer(FaBNextSeat($player)); FaBAutoPassShortcuts(); return true; }
    SetConsecutivePasses(0);
    $top = FaBStackTop();
    if ($top !== null) return DoResolveCard(intval($top->Controller ?? GetTurnPlayer()), 'Stack-' . intval($top->mzIndex ?? 0));
    if ($state['window'] === 'ATTACK') {
        $state['combatStep'] = 'DEFEND'; $state['window'] = 'DEFEND_DECLARE'; FaBSetState($state);
        SetPriorityPlayer(intval($state['defender'])); FaBAutoPassShortcuts(); return true;
    }
    if ($state['window'] === 'DEFEND_PRIORITY') {
        $state['combatStep'] = 'REACTION'; $state['window'] = 'REACTION'; FaBSetState($state);
        SetPriorityPlayer(intval(GetTurnPlayer())); FaBAutoPassShortcuts(); return true;
    }
    if ($state['window'] === 'REACTION') { FaBBeginDamageStep(); return true; }
    if ($state['window'] === 'DAMAGE') { FaBBeginResolutionStep(); return true; }
    if ($state['window'] === 'RESOLUTION') { FaBCloseCombatChain(); return true; }
    if ($state['window'] === 'ACTION') {
        $turnPlayer = intval(GetTurnPlayer());
        $state['window'] = 'END_PHASE'; FaBSetState($state); SetPriorityPlayer($turnPlayer); return true;
    }
    if ($state['window'] === 'END_PHASE') { FaBEndTurn(intval(GetTurnPlayer())); return true; }
    SetPriorityPlayer(intval(GetTurnPlayer())); return true;
}

function FaBPlayerHasPriorityAction(int $player): bool {
    $state = FaBGetState();
    foreach (GetHand($player) as $index => $obj) {
        if (!is_object($obj) || !empty($obj->removed)) continue;
        if (CanPlayCard($player, 'p' . $player . 'Hand-' . $index)) return true;
    }
    return false;
}

/**
 * Schema-backed client highlight metadata for the context actions offered by
 * ActionMap(). Building the mzID from the object's live zone identity keeps
 * legality aligned with the exact unique object that will be submitted.
 */
function FaBSelectionMetadata($obj): string {
    if (!is_object($obj) || !empty($obj->removed) || intval(GetWinner()) !== 0) {
        return json_encode(['highlight' => false]);
    }

    $owner = intval($obj->PlayerID ?? ($obj->Controller ?? ($obj->Owner ?? 0)));
    $location = (string)($obj->Location ?? '');
    $index = intval($obj->mzIndex ?? -1);
    if ($owner < 1 || $index < 0 || $location === '' || intval(GetPriorityPlayer()) !== $owner) {
        return json_encode(['highlight' => false]);
    }

    $mzID = 'p' . $owner . $location . '-' . $index;
    $legal = CanPitchCard($owner, $mzID)
        || FaBCanBlock($owner, $mzID)
        || CanPlayCard($owner, $mzID)
        || (function_exists('FaBWTRCanActivate') && FaBWTRCanActivate($owner, $mzID))
        || FaBCanArsenal($owner, $mzID);

    return $legal
        ? json_encode(['color' => 'rgba(86, 255, 126, 0.92)'])
        : json_encode(['highlight' => false]);
}

function FaBAutoPassShortcuts(): void {
    static $running = false; if ($running) return; $running = true;
    for ($guard = 0; $guard < 16 && intval(GetWinner()) === 0; ++$guard) {
        $player = intval(GetPriorityPlayer()); $state = FaBGetState(); $window = '';
        if (FaBIsPassiveSeat($player)) {
            if (!FaBPassPriority($player, true)) break;
            continue;
        }
        if ($state['window'] === 'DEFEND_DECLARE') $window = 'BLOCK';
        elseif ($state['window'] === 'REACTION') $window = $player === intval($state['attacker']) ? 'ATTACK_REACTION' : 'DEFENSE_REACTION';
        elseif (in_array($state['window'], ['ACTION', 'PRIORITY', 'ATTACK', 'DEFEND_PRIORITY', 'DAMAGE', 'RESOLUTION'], true)) $window = 'INSTANT_PRIORITY';
        if ($window === '' || !ShouldAutoPassShortcutWindow($player, $window)) break;
        if ($window === 'INSTANT_PRIORITY' && FaBPlayerHasPriorityAction($player)) break;
        $wasInCombat = !empty($state['combatOpen']);
        if (!FaBPassPriority($player, true)) break;
        if ($wasInCombat && empty(FaBGetState()['combatOpen'])) break;
    }
    $running = false;
}

function ActionMap($actionCard) {
    global $playerID; $player = intval($playerID); $mzID = (string)$actionCard;
    $actions = [];
    if (CanPitchCard($player, $mzID)) $actions['PITCH'] = 'Pitch';
    if (FaBCanBlock($player, $mzID)) $actions['BLOCK'] = 'Block';
    if (CanPlayCard($player, $mzID)) $actions['PLAY'] = FaBHasType(FaBIdentityFromMZ($mzID)['object']->CardID ?? '', 'Defense Reaction') ? 'Play defense reaction' : 'Play';
    if (function_exists('FaBWTRCanActivate') && FaBWTRCanActivate($player, $mzID)) $actions['ACTIVATE'] = 'Activate';
    if (FaBCanArsenal($player, $mzID)) $actions['ARSENAL'] = 'Put in arsenal';
    if (count($actions) === 1) return FaBExecuteContextAction($player, $mzID, array_key_first($actions));
    if (count($actions) > 1) {
        $labels = []; foreach ($actions as $key => $label) $labels[] = $key . ':_' . str_replace(' ', '_', $label);
        DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 1, 'Choose_card_action');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_CONTEXT_ACTION|' . rawurlencode($mzID) . '|' . implode(',', array_keys($actions)), 1);
        return true;
    }
    if (function_exists('SetFlashMessage')) SetFlashMessage('That card has no legal action in the current window.');
    return false;
}

function FaBExecuteContextAction(int $player, string $mzID, string $action): bool {
    return match ($action) {
        'PITCH' => (bool)PitchCard($player, $mzID),
        'BLOCK' => FaBDeclareBlock($player, $mzID),
        'PLAY' => (bool)PlayCard($player, $mzID),
        'ARSENAL' => FaBArsenalCard($player, $mzID),
        'ACTIVATE' => (bool)DoActivatedAbility($player, $mzID, 0),
        default => false,
    };
}

$customDQHandlers['FAB_CONTEXT_ACTION'] = function($player, $parts, $lastDecision) {
    $mzID = rawurldecode((string)($parts[0] ?? ''));
    $allowed = array_values(array_filter(explode(',', (string)($parts[1] ?? ''))));
    $index = intval(explode(',', (string)$lastDecision)[0] ?? -1);
    if ($index < 0 || $index >= count($allowed)) return;
    FaBExecuteContextAction(intval($player), $mzID, $allowed[$index]);
};

$customDQHandlers['FAB_ATTACK_TARGET'] = function($player, $parts, $lastDecision) {
    $player = intval($player); $sourceUID = intval($parts[0] ?? 0); $sourceKind = rawurldecode((string)($parts[1] ?? ''));
    if ($sourceUID <= 0 || !in_array($sourceKind, ['PLAY', 'ACTIVATE'], true)) return;
    $targetFound = FaBIdentityFromMZ((string)$lastDecision);
    if ($targetFound === null) return;
    $target = FaBResolveAttackTarget(FaBAttackTargetDescriptor($targetFound), $player);
    $source = FaBFindUID($sourceUID);
    if ($target === null || $source === null) return;
    $state = FaBGetState();
    $state['pendingAttackTarget'] = ['sourceUID'=>$sourceUID, 'target'=>$target]; FaBSetState($state);
    if ($sourceKind === 'PLAY') DoPlayCard($player, $source['mzID']);
    elseif (function_exists('FaBWTRActivate')) FaBWTRActivate($player, $source['mzID']);
};

function FaBCanArsenal(int $player, string $mzID): bool {
    if ($player !== intval(GetTurnPlayer()) || intval(GetPriorityPlayer()) !== $player) return false;
    $state = FaBGetState(); if ($state['window'] !== 'END_PHASE' || $state['pendingPayment'] !== null) return false;
    $found = FaBIdentityFromMZ($mzID);
    if ($found === null || $found['player'] !== $player || $found['zone'] !== 'Hand') return false;
    foreach (GetArsenal($player) as $obj) if (is_object($obj) && empty($obj->removed)) return false;
    return true;
}

function FaBArsenalCard(int $player, string $mzID): bool {
    if (!FaBCanArsenal($player, $mzID)) return false;
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return false;
    SaveUndoVersion($player, 'Before putting a card in arsenal');
    return FaBMoveUID(intval($found['object']->UniqueID), 'Arsenal', $player) !== null;
}

function DoActivatedAbility($player, $mzID, $abilityIndex = 0) {
    return function_exists('FaBWTRActivate') ? FaBWTRActivate(intval($player), (string)$mzID, intval($abilityIndex)) : false;
}

function DoDrawCard($player, $amount) {
    if (function_exists('FaBWTRCanDraw') && !FaBWTRCanDraw(intval($player))) return false;
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < intval($amount); ++$i) {
        $top = null;
        foreach ($deck as $obj) if (is_object($obj) && empty($obj->removed)) { $top = $obj; break; }
        if ($top === null) break;
        FaBMoveUID(intval($top->UniqueID), 'Hand', intval($player));
    }
    return true;
}

function StartOfTurnPhase() {
    $player = intval(GetTurnPlayer()); AddResources($player, 0); AddActionPoints($player, 1);
    foreach (['Weapons', 'Equipment', 'Arena'] as $zoneName) foreach (FaBZoneGet($zoneName, $player) as $obj) if (is_object($obj) && empty($obj->removed)) $obj->Status = 2;
    $state = FaBResetWindowState();
    $state['turnEffects'][(string)$player] = $state['nextTurnEffects'][(string)$player] ?? [];
    unset($state['nextTurnEffects'][(string)$player]);
    $state['hitsThisTurn'][(string)$player] = [];
    $state['cardsPlayedThisTurn'][(string)$player] = [];
    FaBSetState($state);
    FaBEnsureGoldfishOpponents($state);
    if (function_exists('FaBWTRStartTurn')) FaBWTRStartTurn($player);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
}

function MainPhase() {}

function EndOfTurnPhase() { FaBEndTurn(intval(GetTurnPlayer())); }

function FaBEndTurn(int $player): bool {
    if ($player !== intval(GetTurnPlayer())) return false;
    SetCurrentPhase('END');
    FaBCloseCombatChain();
    FaBReturnIntimidatedCards();
    if (function_exists('FaBWTREndTurn')) FaBWTREndTurn($player);
    $pitch = GetPitch($player);
    foreach ($pitch as $obj) if (is_object($obj) && empty($obj->removed)) FaBMoveUID(intval($obj->UniqueID), 'Deck', $player);
    $hero = GetHero($player); $intellect = !empty($hero) ? max(0, intval(CardIntelligence($hero[0]->CardID))) : 4;
    if (function_exists('FaBWTRIntellectModifier')) $intellect += FaBWTRIntellectModifier($player);
    $hand = GetHand($player); $count = 0; foreach ($hand as $obj) if (is_object($obj) && empty($obj->removed)) ++$count;
    if ($count < $intellect) DoDrawCard($player, $intellect - $count);
    AddResources($player, 0);
    $next = FaBNextInteractiveSeat($player); SetTurnPlayer($next); SetTurnNumber(intval(GetTurnNumber()) + 1);
    StartOfTurnPhase(); SetCurrentPhase('MAIN'); SaveUndoVersion($next, 'Start of turn');
    return true;
}

function FaBPassTurn($player) { return FaBPassPriority(intval($player)); }

?>
