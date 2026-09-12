<?php

include_once __DIR__ . '/../../Core/ShortcutPreferences.php';
include_once __DIR__ . '/../../Core/DeterministicRNG.php';
include_once __DIR__ . '/WTRCards.php';
include_once __DIR__ . '/CardChoices.php';
include_once __DIR__ . '/WTRAbilities.php';
include_once __DIR__ . '/FaiCards.php';
include_once __DIR__ . '/ARCCards.php';
include_once __DIR__ . '/CRUCards.php';
include_once __DIR__ . '/ARCAbilities.php';
include_once __DIR__ . '/ProfessorCards.php';
include_once __DIR__ . '/MultiTargetCombat.php';
include_once __DIR__ . '/ProfessorBot.php';
include_once __DIR__ . '/IraCards.php';
include_once __DIR__ . '/IraBot.php';
include_once __DIR__ . '/Bot.php';

$customDQHandlers = [];
$additionalActivationCosts = [];
$customDQHandlers['FAB_FAI_SETUP']=function($player,$parts,$lastDecision){FaBFaiSetup(intval($player),(string)$lastDecision==='0');};

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
function GameMacroListenerSeats(): array { return FaBLiveSeats(); }
function FaBSeatIsLive(int $seat): bool { return in_array($seat, FaBLiveSeats(), true); }

function FaBNextSeat(int $seat, bool $liveOnly = true): int {
    $order = $liveOnly ? FaBLiveSeats() : FaBSeatOrder();
    if (empty($order)) return $seat;
    $table = FaBSeatOrder();
    $idx = array_search($seat, $table, true);
    if ($idx === false) return $order[0];
    for ($offset = 1; $offset <= count($table); ++$offset) {
        $next = $table[($idx + $offset) % count($table)];
        if (in_array($next, $order, true)) return $next;
    }
    return $seat;
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

function FaBAdjacentOpponents(int $seat): array {
    $live = FaBLiveSeats();
    $index = array_search($seat, $live, true);
    if ($index === false || count($live) < 2) return [];
    return array_values(array_unique([$live[($index + 1) % count($live)], $live[($index + count($live) - 1) % count($live)]]));
}

function FaBAttackableSeats(int $seat): array {
    $state = FaBGetState();
    $opponents = $state['gameMode'] === 'UPF' ? FaBAdjacentOpponents($seat) : FaBOpponents($seat);
    if ($state['gameMode'] === 'UPF' && !empty($state['combatOpen']) && intval($state['defender']) > 0) {
        $opponents = array_values(array_intersect($opponents, [intval($state['defender'])]));
    }
    return $opponents;
}

function FaBDefaultDefender(int $attacker): int {
    $next = FaBNextSeat($attacker);
    return $next === $attacker ? 0 : $next;
}

function FaBRequestIntimidate(int $player, int $amount = 1): void {
    $seats = FaBGetState()['gameMode'] === 'UPF' ? FaBAdjacentOpponents($player) : FaBOpponents($player);
    if (count($seats) === 1) { FaBIntimidate($player, $seats[0], $amount); return; }
    $targets = [];
    foreach ($seats as $seat) foreach (GetHero($seat) as $hero) {
        if (!is_object($hero) || !empty($hero->removed)) continue;
        $targets[] = FaBAttackTargetChoiceMZ(['uid'=>intval($hero->UniqueID)], $player);
    }
    if (!$targets) return;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_hero_to_intimidate');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_INTIMIDATE|' . max(1,$amount), 1);
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
    foreach (['botProfiles', 'turnEffects', 'nextTurnEffects', 'hitsThisTurn', 'cardsPlayedThisTurn', 'weaponHits', 'attackActionHits', 'arcNames', 'arcaneDealt', 'arcCards', 'arcActions'] as $key) {
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
    if(is_object($cardID))return EffectiveCardType($cardID);
    $types = CardTypes($cardID);
    return is_array($types) ? $types : [];
}

// FaB's imported type line includes class, card type, subtype and equipment slot.
function EffectiveCardType($obj): array {
    $override=FaBObjectCounters($obj)['_overrides']['type']??null;
    if($override!==null)return is_array($override)?$override:array_values(array_filter(array_map('trim',explode(',',$override))));
    $types=(array)CardTypes($obj->CardID);
    if(in_array('FAI_DRACONIC',(array)($obj->TurnEffects??[]),true))$types[]='Draconic';
    $p=intval($obj->Controller??$obj->Owner??0);
    if(!$p&&intval($obj->UniqueID??0))$p=intval(FaBFindUID(intval($obj->UniqueID))['player']??0);
    if($p>0&&!in_array('Hero',$types,true)&&FaBWTRHeroActive($p))foreach(GetHero($p) as $hero)if(is_object($hero)&&empty($hero->removed)&&!empty(FaBObjectCounters($hero)['CRU_SHIYANA']))$types=array_merge($types,array_intersect((array)CardTypes($hero->CardID),['Brute','Guardian','Ninja','Warrior','Mechanologist','Ranger','Runeblade','Wizard','Merchant','Shapeshifter']));
    return array_values(array_unique($types));
}

function HasNoAbilities($obj): bool {
    if(in_array('NO_ABILITIES',(array)($obj->TurnEffects??[]),true)||!empty(FaBObjectCounters($obj)['_overrides']['NO_ABILITIES']))return true;
    if(in_array('Hero',EffectiveCardType($obj),true))return !FaBWTRHeroActive(intval($obj->Controller??$obj->Owner??0));
    return false;
}

function FaBHasType($cardID, $type): bool {
    foreach (FaBTypes($cardID) as $candidate) if (strcasecmp((string)$candidate, (string)$type) === 0) return true;
    return false;
}

function FaBKeywords($cardID): array {
    if(is_object($cardID)){
        if(HasNoAbilities($cardID))return [];
        return array_merge((array)CardCard_keywords($cardID->CardID),(array)(FaBObjectCounters($cardID)['_overrides']['granted_keywords']??[]));
    }
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
    if (FaBHasType($obj, 'Equipment')) $base -= intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0);
    $delta = function_exists('EvaluateDefenseModifier') ? intval(EvaluateDefenseModifier($obj->CardID, $player, $obj, $base, $obj)) : 0;
    if (function_exists('FaBWTRDefenseModifier')) $delta += FaBWTRDefenseModifier($player, $obj);
    if ($obj->CardID==='arcanite_skullcap'&&FaBARCLowerLife($player)) ++$delta;
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
    foreach (FaBWTREffects(intval($state['attacker'])) as $effect) if (($effect['type'] ?? '') === 'NO_GO_AGAIN') return false;
    $effects = is_array($attack->TurnEffects ?? null) ? $attack->TurnEffects : [];
    if($attack->CardID==='teklo_blaster'&&FaBEvoActive(intval($state['attacker']),'evo_rapid_fire_blue'))return true;
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
        'Banish' => GetBanish($player), 'Pitch' => GetPitch($player), 'Temp' => GetTemp($player), 'Stack' => GetStack(),
        default => [],
    };
}

function FaBIdentityZones(): array {
    return ['Hero', 'Weapons', 'Equipment', 'Arena', 'CombatChain', 'Deck', 'Hand', 'Arsenal', 'Graveyard', 'Banish', 'Pitch', 'Temp'];
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
    if (FaBHasType($obj, 'Ally') || FaBHasKeyword($obj, 'Spectra')) return true;
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
    foreach (FaBAttackableSeats($attacker) as $seat) {
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
    if ($found === null || !in_array(intval($found['player']), !empty($descriptor['anyHero'])?FaBOpponents($attacker):FaBAttackableSeats($attacker), true)) return null;
    if ($found['zone'] !== 'Hero' && ($found['zone'] !== 'Arena' || !FaBObjectCanBeAttacked($found['object']))) return null;
    return FaBAttackTargetDescriptor($found) + (!empty($descriptor['anyHero'])?['anyHero'=>true]:[]);
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
    $targets = FaBProfessorAttackTargets($player,$sourceUID);
    $source=FaBFindUID($sourceUID);
    if(($source['object']->CardID??'')==='apocalypse_automaton_red')return $targets[0]??false;
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
    $added = match ($zone) {
        'Hero' => AddHero($player, CardID:$source->CardID, sourceObject:$source),
        'Temp' => AddTemp($player, CardID:$source->CardID, sourceObject:$source),
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
    if($added!==null){
        if(property_exists($added,'Owner')&&intval($added->Owner)<1)$added->Owner=$player;
        if(property_exists($added,'Controller')&&intval($added->Controller)<1)$added->Controller=$player;
    }
    return $added;
}

function FaBMoveUID(int $uid, string $toZone, ?int $targetPlayer = null, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] === 'Stack') return null;
    if ($toZone === 'Graveyard' && function_exists('FaBWTRBase') && FaBWTRBase((string)$found['object']->CardID) === 'drone_of_brutality') $toZone = 'Deck';
    $source = $found['object'];
    $targetPlayer ??= intval($source->Owner ?? $found['player']);
    if ($targetPlayer < 1) $targetPlayer = $found['player'];
    if($found['zone']==='CombatChain'&&($source->Role??'')==='ATTACK'){
        // Chain-link properties survive their active attack leaving (CR 7.0.3c).
        $s=FaBGetState();$s['departedChainTypes'][(string)$found['player']][(string)$source->ChainLink]=EffectiveCardType($source);FaBSetState($s);
    }
    if(!in_array($toZone,['Equipment','CombatChain'],true)&&!empty(FaBObjectCounters($source)['SUBCARDS'])){foreach(FaBObjectCounters($source)['SUBCARDS'] as $under)AddGraveyard($targetPlayer,CardID:$under);unset($source->Counters['SUBCARDS']);}
    $source->removed = true;
    if($toZone==='Graveyard'&&FaBHasKeyword($source,'Ephemeral'))return null;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        $newIndex = intval($newObj->mzIndex ?? 0);
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . $newIndex, 360, true, $uid, $uid);
    }
    if ($newObj !== null) FaBCRUAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    return $newObj;
}

function FaBMoveStackUID(int $uid, string $toZone, int $targetPlayer, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] !== 'Stack') return null;
    $source = $found['object'];
    if($toZone==='Graveyard'&&FaBWTRBase($source->CardID)==='drone_of_brutality')$toZone='Deck';
    if(!in_array($toZone,['Equipment','CombatChain'],true)&&!empty(FaBObjectCounters($source)['SUBCARDS'])){foreach(FaBObjectCounters($source)['SUBCARDS'] as $under)AddGraveyard($targetPlayer,CardID:$under);unset($source->Counters['SUBCARDS']);}
    $source->removed = true;
    if($toZone==='Graveyard'&&FaBHasKeyword($source,'Ephemeral'))return null;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . intval($newObj->mzIndex ?? 0), 360, true, $uid, $uid);
    }
    if ($newObj !== null) FaBCRUAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
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
    $delta += FaBARCCostModifier($player, $obj) + FaBProfessorCost($player,$obj) + FaBCRUCost($player,$obj);
    return max(0, $base + $delta);
}

function FaBAvailablePitch(int $player, int $excludedUID = 0): int {
    $total = max(0, intval(GetResources($player)));
    foreach (GetHand($player) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->UniqueID ?? 0) === $excludedUID) continue;
        if(!FaBARCNamedProhibited($obj->CardID))$total += max(0, intval(CardPitch($obj->CardID)));
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
    if (FaBHasPendingDecision()) return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || $found['player'] !== $player || !in_array($found['zone'], ['Hand', 'Arsenal', 'Banish'], true)) return false;
    if ($found['zone'] === 'Banish' && empty($found['object']->PlayableFromBanish) && !(FaBProfessorActive($player)&&FaBHasType($found['object'],'Evo')&&empty($found['object']->FaceDown))) return false;
    if ($found['zone'] === 'Banish' && intval($found['object']->PlayableChainLink ?? 0) > 0 && intval($found['object']->PlayableChainLink) !== intval(FaBGetState()['chainLink'])) return false;
    $obj = $found['object']; $state = FaBGetState();
    if ($state['pendingPayment'] !== null) return false;
    $isAttackReaction = FaBHasType($obj, 'Attack Reaction');
    $isDefenseReaction = FaBHasType($obj, 'Defense Reaction');
    $isInstant = FaBHasType($obj, 'Instant') || FaBARCAsInstant($player,$obj);
    $isAction = FaBHasType($obj, 'Action');
    $isAttack = FaBHasType($obj, 'Attack');
    $actionWindow = $state['window'] === 'ACTION'
        || ($state['window'] === 'RESOLUTION' && $isAttack);
    $timingLegal = ($isAction && $player === intval(GetTurnPlayer()) && $actionWindow && intval(GetActionPoints($player)) > 0)
        || ($isAttackReaction && $state['window'] === 'REACTION' && $player === intval($state['attacker']))
        || ($isDefenseReaction && $state['window'] === 'REACTION' && FaBIsDefendingHero($player,$state))
        || ($isInstant && !in_array($state['window'], ['PITCH', 'DEFEND_DECLARE'], true));
    if (function_exists('FaBWTRCanPlay') && !FaBWTRCanPlay($player, $found, $state)) return false;
    if (!FaBARCCanPlay($player, $found) || !FaBCRUCanPlay($player,$found)) return false;
    if ($obj->CardID==='apocalypse_automaton_red'&&FaBEvoCount($player)<1)return false;
    return $timingLegal && ((($obj->CardID==='cash_in_yellow'&&FaBCRUCashOptions($player)!=='Pay_resources')) || (FaBWTRBase($obj->CardID)==='moon_wish' && FaBHandCount($player)>($found['zone']==='Hand'?1:0)) || FaBAvailablePitch($player, intval($obj->UniqueID ?? 0)) >= FaBCardCost($obj, $player));
}

function DoPlayCard($player, $mzID) {
    $player = intval($player);
    if (!CanPlayCard($player, $mzID)) return false;
    $found = FaBIdentityFromMZ((string)$mzID); if ($found === null) return false;
    $isAttack = FaBHasType($found['object'], 'Attack');
    $attackTarget = null;
    if ($isAttack) {
        $attackTarget = FaBClaimOrRequestAttackTarget($player, intval($found['object']->UniqueID), 'PLAY');
        if ($attackTarget === null) return true;
        if ($attackTarget === false) return false;
    }
    SaveUndoVersion($player, 'Before playing ' . (CardName($found['object']->CardID) ?: $found['object']->CardID));
    $source = $found['object']; $uid = intval($source->UniqueID); $fromZone = $found['zone'];
    if($fromZone==='Arsenal')FaBARCSetCard($uid,'faceUp',intval($source->FaceDown??1)===0);
    $kind = FaBHasType($source, 'Attack') ? 'ATTACK'
        : (FaBHasType($source, 'Defense Reaction') ? 'DEFENSE_REACTION'
        : (FaBHasType($source, 'Attack Reaction') ? 'ATTACK_REACTION'
        : (FaBHasType($source, 'Instant') || FaBARCAsInstant($player,$source) ? 'INSTANT' : 'ACTION')));
    $source->removed = true;
    $stackObj = AddStack(CardID:$source->CardID, Controller:$player, Kind:$kind, SourceZone:$fromZone,
        SourceUniqueID:$uid, Params:$attackTarget === null ? [] : ['attackTarget'=>$attackTarget], sourceObject:$source);
    $stackObj->Controller = $player; $stackObj->Kind = $kind; $stackObj->SourceZone = $fromZone; $stackObj->SourceUniqueID = $uid;
    if (function_exists('QueueZoneMoveAnimation')) QueueZoneMoveAnimation($found['mzID'], 'Stack-' . intval($stackObj->mzIndex), 360, true, $uid, $uid);
    $state = FaBGetState();
    $state['pendingPayment'] = ['player' => $player, 'uid' => $uid, 'cost' => FaBCardCost($stackObj, $player), 'fromZone' => $fromZone,
        'kind' => $kind, 'returnWindow' => (string)$state['window'], 'returnCombatStep' => (string)$state['combatStep']];
    $state['window'] = 'PITCH'; FaBSetState($state);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
    if (FaBRunSourceMacro('PrepareCard', $player, $stackObj->CardID, ['mzID'=>'Stack-'.intval($stackObj->mzIndex)]) > 0) return true;
    return FaBTryCompletePayment();
}

function FaBEnergyCounters($obj): int { return intval(FaBObjectCounters($obj)['ENERGY']??0); }
function FaBSteamCounters($obj): int { return intval(FaBObjectCounters($obj)['STEAM']??0); }
function FaBDefenseCounters($obj): int { return intval(FaBObjectCounters($obj)['DEFENSE']??0); }
function FaBPowerCounters($obj): int { return intval(FaBObjectCounters($obj)['POWER']??0); }
function FaBDisplayCombatPower($obj): int {
    $state=FaBGetState();
    return ($obj->Role??'')==='ATTACK' && intval($obj->UniqueID)===intval($state['attackUID']) ? FaBAttackPower($state) : -1;
}
function FaBDisplayCombatDefense($obj): int {
    return in_array($obj->Role??'',['DEFENSE','DEFENSE_REACTION'],true) ? FaBCurrentDefense($obj,intval($obj->Controller??$obj->Owner??0)) : -1;
}

function FaBHasPendingDecision(): bool {
    // Static continuations need no player input. Generated macro bookkeeping
    // can still follow an attack-target continuation while it resumes payment.
    $staticTypes = ['CUSTOM', 'SYSTEM', 'PASSPARAMETER', 'MZMOVE'];
    foreach (FaBLiveSeats() as $seat) foreach(GetDecisionQueue($seat)as$decision) if(!in_array($decision->Type, $staticTypes, true)) return true;
    return false;
}

function CanPitchCard($player, $mzID): bool {
    if (FaBHasPendingDecision()) return false;
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== intval($player) || $state['window'] !== 'PITCH') return false;
    $source=FaBFindUID(intval($pending['uid']));
    if($source!==null&&FaBWTRNeedsDiscard($source['object']->CardID)&&FaBHandCount(intval($player))<=1)return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    return $found !== null && $found['player'] === intval($player) && $found['zone'] === 'Hand'
        && !FaBARCNamedProhibited($found['object']->CardID)
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
    FaBRunSourceMacro('CardPitched',intval($player),$pitchedCardID,['mzID'=>FaBFindUID($uid)['mzID']]);
    if(FaBHasPendingDecision())return true;
    return FaBTryCompletePayment();
}

function FaBTryCompletePayment(): bool {
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending)) return false;
    $player = intval($pending['player']); $cost = max(0, intval($pending['cost']));
    if (intval(GetResources($player)) < $cost) return true;
    AddResources($player, intval(GetResources($player)) - $cost);
    if (in_array((string)$pending['kind'], ['ACTION', 'ATTACK'], true) || !empty($pending['abilityAction'])) AddActionPoints($player, max(0, intval(GetActionPoints($player)) - 1));
    $weaponUID = intval($pending['weaponUID'] ?? 0);
    if ($weaponUID > 0) {
        $weapon = FaBFindUID($weaponUID);
        if ($weapon !== null && $weapon['zone'] === 'Weapons') FaBCRUUseWeapon($weapon['object']);
        if ($weapon !== null && in_array($weapon['object']->CardID,['teklo_plasma_pistol','plasma_barrel_shot'],true)) FaBARCSteam($weapon['object'],-1);
        if($weapon!==null&&$weapon['object']->CardID==='talishar_the_lost_prince')FaBSetObjectCounter($weapon['object'],'RUST',intval(FaBObjectCounters($weapon['object'])['RUST']??0)+1);
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
    if ($found !== null) {
        if (!empty($pending['isWeaponAttack'])) {
            FaBSetObjectCounter($found['object'],'WEAPON_UID',$weaponUID);
            FaBWTRCardPlayed($player,$found['mzID'],$found['object']->CardID,'Weapons');
        } elseif (!empty($pending['isAbility'])) {
            FaBWTRPayAbilityCosts($player,$found['object']);
        } else {
            FaBWTRPayAdditionalCosts($player, $found['object']);
            OnCardPlayed($player, $found['mzID'], $found['object']->CardID, (string)$pending['fromZone']);
        }
    }
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
    if($found!==null)FaBCRUAttack(intval($player),$found['object']);
    $count = $found === null ? 0 : FaBRunSourceMacro('AttackDeclared', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('AttackDeclared', intval($player), $params) : 0);
}

function OnDefended($player, $mzID, $defender) {
    $params = compact('mzID', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found !== null && function_exists('FaBWTRDefended')) FaBWTRDefended(intval($player), $found['object']);
    if($found!==null)FaBCRUDefended(intval($player),$found['object']);
    $count = $found === null ? 0 : FaBRunSourceMacro('Defended', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Defended', intval($player), $params) : 0);
}

function OnHit($player, $mzID, $amount) {
    $params = compact('mzID', 'amount'); $found = FaBIdentityFromMZ((string)$mzID);
    if($found!==null&&FaBHasType($found['object'],'Dagger')){$s=FaBGetState();$s['daggerHits']=intval($s['daggerHits']??0)+1;FaBSetState($s);}
    if ($found !== null && function_exists('FaBWTRHit')) FaBWTRHit(intval($player), $found['object'], intval($amount));
    if($found!==null&&FaBCRUHitSuppressed($found['object'],true))return 0;
    if($found!==null)FaBCRUHit(intval($player),$found['object'],intval($amount));
    $count = $found === null || FaBCRUHitSuppressed($found['object']) ? 0 : FaBRunSourceMacro('Hit', intval($player), $found['object']->CardID, $params);
    $count += function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Hit', intval($player), $params) : 0;
    if ($found !== null) FaBARCAfterHit(intval($player), $found['object'], intval($amount));
    if($found!==null&&in_array('WTR_RETURN_HAND',(array)$found['object']->TurnEffects,true)){
        $state=FaBGetState();$state['attackGoAgain']=FaBAttackHasGoAgain($state,$found['object']);FaBSetState($state);
        FaBMoveUID(intval($found['object']->UniqueID),'Hand',intval($player));
    }
    return $count;
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
    if ($kind === 'ABILITY') {
        $obj->removed=true;
        FaBWTRResolveAbility($controller,$obj);
        $state=FaBGetState();
        $state['window']=(string)($obj->Params['returnWindow']??'ACTION');
        $state['combatStep']=(string)($obj->Params['returnCombatStep']??'NONE');
        FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    } elseif ($kind === 'ATTACK') {
        $attackTargets=[];
        foreach(($obj->Params['attackTargets']??[$obj->Params['attackTarget']??[]]) as $descriptor){$t=FaBResolveAttackTarget((array)$descriptor,$controller);if($t!==null)$attackTargets[]=$t;}
        $attackTarget=$attackTargets[0]??null;
        if ($attackTarget === null) {
            // A target can leave the game while players respond. Resolve the failed
            // attack off the stack instead of leaving everyone passing forever.
            FaBMoveStackUID($uid, 'Graveyard', $controller);
            if($obj->CardID==='apocalypse_automaton_red'){FaBCloseCombatChain();$state=FaBGetState();}
            $state['window'] = !empty($state['combatOpen']) ? 'RESOLUTION' : 'ACTION';
            FaBSetState($state); SetPriorityPlayer(FaBNextSeat($controller, false));
            if (FaBSeatIsLive($controller)) SetPriorityPlayer($controller);
            return true;
        }
        $defender = intval($attackTarget['player']);
        $chain = FaBMoveStackUID($uid, 'CombatChain', $controller);
        if ($chain === null || $defender === 0) return false;
        $state = FaBGetState();
        $prior = FaBFindUID(intval($state['attackUID'] ?? 0));
        $state['previousAttackCardID'] = $prior !== null ? (string)$prior['object']->CardID : (string)($state['lastAttackCardID'] ?? '');
        $state['lastAttackCardID'] = $chain->CardID;
        $state['combatOpen'] = true; $state['combatStep'] = 'ATTACK'; $state['window'] = 'ATTACK';
        $state['chainLink'] = intval($state['chainLink']) + 1; $state['attacker'] = $controller; $state['defender'] = $defender;
        $state['attackUID'] = $uid; $state['lastAttackName'] = CardName($chain->CardID) ?: $chain->CardID; $state['attackHit'] = false;
        $state['attackTarget'] = $attackTarget;
        $state['attackTargets'] = $attackTargets; $state['defendIndex']=0;
        $state['attackGoAgain'] = false;
        $state['attackPower'] = 0; $state['defenseValue'] = 0; $state['damageDealt'] = 0;
        $state['handBlockUIDs'] = []; $state['declaredBlockUIDs'] = [];
        $chain->Role = 'ATTACK'; $chain->ChainLink = $state['chainLink']; $chain->FromZone = (string)($obj->SourceZone ?? 'Hand');
        FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
        OnAttackDeclared($controller, FaBFindUID($uid)['mzID'], $controller, $defender);
        FaBResolveRules($controller, $obj, $chain);
    } elseif (in_array($kind, ['ATTACK_REACTION', 'DEFENSE_REACTION'], true)) {
        $owner = $kind === 'DEFENSE_REACTION' ? $controller : intval($state['attacker']);
        if($kind==='DEFENSE_REACTION'&&$obj->SourceZone==='Hand'&&FaBCurrentAttackHasKeyword($state,'Dominate')&&FaBHandDefendingCount($state,$controller)>=1){
            FaBMoveStackUID($uid,'Graveyard',$owner);SetPriorityPlayer(intval($state['attacker']));SetConsecutivePasses(0);return true;
        }
        $chain = FaBMoveStackUID($uid, 'CombatChain', $owner);
        if($kind==='DEFENSE_REACTION'&&$obj->SourceZone==='Hand'){$state['handBlockUIDs'][]=$uid;FaBSetState($state);}
        if ($chain !== null) { $chain->Role = $kind; $chain->ChainLink = intval($state['chainLink']); $chain->FromZone=(string)$obj->SourceZone; if($kind==='DEFENSE_REACTION')FaBWTRApplyNextDefense($controller,$chain); }
        FaBResolveRules($controller, $obj, $chain);
        if ($kind === 'DEFENSE_REACTION' && $chain !== null) OnDefended($controller, FaBFindUID($uid)['mzID'], $controller);
        SetPriorityPlayer(intval($state['attacker'])); SetConsecutivePasses(0);
    } else {
        $persistent = FaBHasType($obj, 'Aura') || FaBHasType($obj, 'Item') || FaBHasType($obj, 'Ally');
        $resolved = FaBMoveStackUID($uid, $persistent ? 'Arena' : 'Graveyard', intval($obj->Owner ?? $controller));
        FaBResolveRules($controller, $obj, $resolved);
        $baseGoAgain = FaBPrintedKeywordIsActive($obj->CardID, 'Go again') || in_array('GO_AGAIN',(array)($obj->TurnEffects??[]),true) ? 1 : 0;
        $goAgainDelta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($obj->CardID, $controller, $obj, $baseGoAgain, $obj)) : 0;
        if ($kind === 'ACTION' && FaBWTRMayGoAgain($controller) && max(0, min(1, $baseGoAgain + $goAgainDelta)) === 1) AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);
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

function FaBResolveRules(int $player, object $source, ?object $resolved): void {
    if ($resolved === null) return;
    $found = FaBFindUID(intval($resolved->UniqueID));
    if ($found === null) return;
    DecisionQueueController::StoreVariable('fabSourceZone', (string)($source->SourceZone ?? ''));
    if (FaBRunSourceMacro('ResolveCard', $player, $source->CardID, ['mzID'=>$found['mzID']]) === 0) {
        FaBWTRResolveCard($player, $source, $resolved);
    }
}

function FaBCanBlock(int $player, string $mzID): bool {
    $state = FaBGetState(); if ($state['window'] !== 'DEFEND_DECLARE' || intval($state['defender']) !== $player) return false;
    if (($state['attackTarget']['type'] ?? 'HERO') !== 'HERO') return false;
    $found = FaBIdentityFromMZ($mzID);
    if ($found === null || $found['player'] !== $player) return false;
    if (!in_array($found['zone'], ['Hand', 'Equipment'], true) && !($found['zone']==='Arsenal'&&(FaBHasKeyword($found['object'],'Ambush')||(FaBFaiEffect($player,'AOW_ARSENAL')>0&&FaBWTRIsAttackAction($found['object']))))) return false;
    if (!is_numeric(CardDefense($found['object']->CardID))) return false;
    if (FaBARCNamedProhibited($found['object']->CardID) || !FaBCRUBlockLegal($player,$found)) return false;
    if ($found['zone']==='Hand' && FaBHasType($found['object'],'Defense Reaction')) return false;
    if ($found['zone'] === 'Hand' && FaBCurrentAttackHasKeyword($state, 'Dominate') && FaBHandDefendingCount($state,$player) >= 1) return false;
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
    FaBWTRApplyNextDefense($player,$chain); $state=FaBGetState();
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
    if(FaBCRUMustEquip(intval($state['defender'])))return;
    foreach ((array)($state['declaredBlockUIDs'] ?? []) as $uid) {
        $found = FaBFindUID(intval($uid));
        if ($found !== null && $found['zone'] === 'CombatChain' && $found['player']===intval($state['defender'])) OnDefended(intval($state['defender']), $found['mzID'], intval($state['defender']));
    }
    $state = FaBGetState();
    if(FaBNextDefendTarget($state))return;
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
    if(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'SNAG'))$delta-=max(0,intval(EvaluateAttackPowerModifier($attack['object']->CardID,intval($state['attacker']),$attack['object'],$base,$attack['object'])));
    $base=FaBCRUBasePower($attack['object'],$base);
    if(FaBCRUCount(intval($state['attacker']),'CHOKESLAM')&&FaBWTRIsAttackAction($attack['object']))$delta=min(0,$delta);
    $delta+=FaBCRUPower(intval($state['attacker']),$attack['object']);
    $result=max(0,$base+$delta+(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'SNAG')?0:FaBProfessorPower(intval($state['attacker']),$attack['object'])));
    if(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'CHOKESLAM'))$result=min($base,$result);
    return $result;
}

function FaBDefenseValue(array $state, ?int $defender=null): int {
    $defender ??= intval($state['defender']);
    $total = 0;
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (!in_array((string)($obj->Role ?? ''), ['DEFENSE', 'DEFENSE_REACTION'], true)) continue;
        if($seat===$defender)$total += FaBCurrentDefense($obj, $defender);
    }
    return $total;
}

function DoDamage($player, $sourceMZ, $targetPlayer, $amount, $damageType = 'PHYSICAL') {
    $targetPlayer = intval($targetPlayer); $amount = max(0, intval($amount));
    if ($amount <= 0 || !FaBSeatIsLive($targetPlayer)) return 0;
    if (function_exists('FaBWTRPreventDamage')) $amount = FaBWTRPreventDamage($targetPlayer, $amount, (string)$damageType);
    $amount = FaBARCPreventDamage($targetPlayer, $amount, (string)$damageType);
    $amount = FaBCRUPrevent($targetPlayer,$amount,(string)$damageType);
    if($amount>0)FaBCRUAdd($targetPlayer,'DAMAGED',$amount);
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

/** Finish elimination after the current effect has finished using its source. */
function GameAfterEngineAction($action, $result): void {
    foreach (FaBSeatOrder() as $seat) {
        if (FaBSeatIsLive($seat)) continue;
        $queue = &GetDecisionQueue($seat); $queue = [];
        foreach (FaBIdentityZones() as $zone) {
            if ($zone === 'Hero') continue;
            foreach (FaBZoneGet($zone, $seat) as $obj) if (is_object($obj)) $obj->removed = true;
        }
        foreach (GetStack() as $obj) if (is_object($obj) && intval($obj->Controller ?? 0) === $seat) $obj->removed = true;
    }
    if (intval(GetWinner()) !== 0) return;
    $state = FaBGetState();
    if(!empty($state['endingTurn'])&&!FaBHasPendingDecision()){
        FaBFinishEndTurn(intval($state['endingTurn']));
        return;
    }
    FaBRepairMultiTargetDefender();$state=FaBGetState();
    if (!empty($state['combatOpen']) && (!FaBSeatIsLive(intval($state['attacker'])) || !FaBSeatIsLive(intval($state['defender'])))) FaBCloseCombatChain();
    if (!FaBSeatIsLive(intval(GetTurnPlayer()))) {
        SetTurnPlayer(FaBNextInteractiveSeat(intval(GetTurnPlayer())));
        SetTurnNumber(intval(GetTurnNumber()) + 1);
        StartOfTurnPhase(); SetCurrentPhase('MAIN');
    } elseif (!FaBSeatIsLive(intval(GetPriorityPlayer()))) {
        SetPriorityPlayer(FaBNextSeat(intval(GetPriorityPlayer()))); SetConsecutivePasses(0);
    }
    FaBAutoPassShortcuts();
}

function FaBBeginDamageStep(): void {
    $state = FaBGetState();
    if(count($state['attackTargets']??[])>1){FaBMultiTargetDamage($state);return;}
    $power = FaBAttackPower($state); $defense = FaBDefenseValue($state);
    $amount = max(0, $power - $defense); $attack = FaBFindUID(intval($state['attackUID']));
    $state['combatStep'] = 'DAMAGE'; $state['window'] = 'DAMAGE';
    $state['attackPower'] = $power; $state['defenseValue'] = $defense; $state['damageDealt'] = $amount;
    FaBSetState($state);
    $target = FaBResolveAttackTarget((array)($state['attackTarget'] ?? []), intval($state['attacker']));
    $targetMZ = $target === null ? '' : FaBAttackTargetMZ($target);
    if ($attack !== null && $targetMZ !== '' && function_exists('QueueCardLungeAnimation')) QueueCardLungeAnimation($attack['mzID'], $targetMZ, 360, true, intval($state['attackUID']), intval($target['uid'] ?? 0));
    if ($amount > 0) {
        if (($target['type'] ?? 'HERO') === 'HERO') {
            if ($attack !== null && in_array('WTR_DOUBLE_DAMAGE', (array)$attack['object']->TurnEffects, true)) $amount *= 2;
            $amount = $target === null ? 0 : DoDamage(intval($state['attacker']), $attack['mzID'] ?? '', intval($state['defender']), $amount, 'PHYSICAL');
        } elseif ($target !== null) {
            $targetFound = FaBFindUID(intval($target['uid']));
            if ($targetFound !== null) {
                $targetFound['object']->Damage = intval($targetFound['object']->Damage ?? 0) + $amount;
                if (function_exists('QueueDamageAnimation')) QueueDamageAnimation($targetFound['mzID'], $amount, 500, true, intval($target['uid']));
                $health = max(0, intval(CardHealth($targetFound['object']->CardID)));
                if ($health > 0 && intval($targetFound['object']->Damage) >= $health) FaBMoveUID(intval($target['uid']), 'Graveyard', intval($targetFound['object']->Owner ?? $targetFound['player']));
            }
        }
        $state = FaBGetState();
        $state['attackHit'] = $amount > 0;
        FaBSetState($state);
        if ($attack !== null && $amount > 0) OnHit(intval($state['attacker']), $attack['mzID'], $amount);
    }
    $state = FaBGetState();
    if ($amount > 0) $state['attackHit'] = true;
    if ($amount === 0) $state['consecutiveHits'] = 0;
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
    if (($attack !== null && $attack['zone']==='CombatChain' && FaBAttackHasGoAgain($state, $attack['object'])) || !empty($state['attackGoAgain'])) AddActionPoints(intval($state['attacker']), intval(GetActionPoints(intval($state['attacker']))) + 1);
    FaBCleanupResolvedLink($state);
    $state = FaBGetState(); $state['combatStep'] = 'RESOLUTION'; $state['window'] = 'RESOLUTION'; $state['handBlockUIDs'] = []; FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
}

function FaBCleanupResolvedLink(array $state): void {
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (($obj->Role ?? '') !== 'DEFENSE' || ($obj->FromZone ?? '') !== 'Equipment') continue;
        if (FaBHasKeyword($obj, 'Blade Break')) {
            $obj->TurnEffects = array_values(array_unique(array_merge(is_array($obj->TurnEffects) ? $obj->TurnEffects : [], ['DESTROY_ON_CHAIN_CLOSE'])));
        } elseif (FaBHasKeyword($obj, 'Battleworn')) {
            FaBSetObjectCounter($obj, 'DEFENSE', intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0) + 1);
        }
    }
}

function FaBCloseCombatChain(): void {
    FaBCRUClose();
    $state = FaBGetState(); if (empty($state['combatOpen'])) return;
    foreach (FaBSeatOrder() as $seat) {
        FaBWTRSetEffects($seat,array_values(array_filter(FaBWTREffects($seat),fn($e)=>($e['type']??'')!=='FAI_BRAND')));
        foreach(GetBanish($seat) as $card)if(is_object($card)&&!empty(FaBObjectCounters($card)['FAI_CHAIN_PLAY']))$card->PlayableFromBanish=0;
        foreach(GetBanish($seat)as$card)if(is_object($card)&&intval($card->PlayableChainLink??0)>0){$card->PlayableFromBanish=0;$card->PlayableChainLink=0;}
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
    if (!FaBSeatIsLive($player)) return false;
    foreach (FaBLiveSeats() as $seat) if (count(GetDecisionQueue($seat)) > 0) return false;
    $state = FaBGetState();
    if ($state['window'] === 'PITCH') return false;
    if (!$automatic) SaveUndoVersion($player, 'Before passing priority');
    if ($state['window'] === 'DEFEND_DECLARE') {
        if ($player !== intval($state['defender']) || FaBCRUMustEquip($player)) return false;
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
    foreach (['Hand','Arsenal','Banish','Hero','Weapons','Equipment','Arena','CombatChain'] as $zone) foreach (FaBZoneGet($zone,$player) as $index => $obj) {
        if (!is_object($obj) || !empty($obj->removed)) continue;
        $mzID='p'.$player.$zone.'-'.$index;
        if (CanPlayCard($player,$mzID) || FaBWTRCanActivate($player,$mzID)) return true;
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
        foreach (FaBLiveSeats() as $seat) if (count(GetDecisionQueue($seat)) > 0) { $running = false; return; }
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
    $f=FaBIdentityFromMZ($mzID);
    $arc=$f===null?[]:FaBARCAbilityActions($player,$f);
    foreach($arc as $index=>$spec)$actions['ARC_'.$index]=$spec['label'];
    if (!$arc && function_exists('FaBWTRCanActivate') && FaBWTRCanActivate($player, $mzID)) $actions['ACTIVATE'] = 'Activate';
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
    if(str_starts_with($action,'ARC_')){$f=FaBIdentityFromMZ($mzID);return $f!==null&&FaBARCActivate($player,$f,intval(substr($action,4)));}
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
    $target=null;
    foreach(FaBProfessorAttackTargets($player,$sourceUID) as $candidate)if(intval($candidate['uid'])===intval($targetFound['object']->UniqueID))$target=FaBResolveAttackTarget($candidate,$player);
    $source = FaBFindUID($sourceUID);
    if ($target === null || $source === null) return;
    $state = FaBGetState();
    $state['pendingAttackTarget'] = ['sourceUID'=>$sourceUID, 'target'=>$target]; FaBSetState($state);
    if ($sourceKind === 'PLAY') DoPlayCard($player, $source['mzID']);
    elseif (function_exists('FaBWTRActivate')) FaBWTRActivate($player, $source['mzID']);
    // A failed continuation must not leave a target cached for a later click.
    $state = FaBGetState(); $state['pendingAttackTarget'] = null; FaBSetState($state);
};

$customDQHandlers['FAB_INTIMIDATE'] = function($player, $parts, $lastDecision) {
    $target = FaBIdentityFromMZ((string)$lastDecision);
    $seats = FaBGetState()['gameMode'] === 'UPF' ? FaBAdjacentOpponents(intval($player)) : FaBOpponents(intval($player));
    if ($target === null || $target['zone'] !== 'Hero' || !in_array($target['player'], $seats, true)) return;
    FaBIntimidate(intval($player), $target['player'], max(1, intval($parts[0] ?? 1)));
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
    SetCurrentPhase('MAIN');
    foreach (['Weapons', 'Equipment', 'Arena'] as $zoneName) foreach (FaBZoneGet($zoneName, $player) as $obj) if (is_object($obj) && empty($obj->removed)) $obj->Status = 2;
    $state = FaBResetWindowState();
    $state['turnEffects'][(string)$player] = array_merge($state['turnEffects'][(string)$player]??[], $state['nextTurnEffects'][(string)$player] ?? []);
    unset($state['nextTurnEffects'][(string)$player]);
    $state['hitsThisTurn'][(string)$player] = [];
    $state['cardsPlayedThisTurn'] = [];
    $state['weaponHits'] = []; $state['attackActionHits'] = [];
    $state['arcaneDealt'] = []; $state['arcActions'] = [];
    FaBSetState($state);
    FaBEnsureGoldfishOpponents($state);
    if (function_exists('FaBWTRStartTurn')) FaBWTRStartTurn($player);
    FaBARCStartTurn($player);
    FaBCRUStart($player);
    FaBIraStartTurn($player);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
}

function MainPhase() {}

$customDQHandlers['FAB_ARC_SETUP']=function($player,$parts,$lastDecision){
    foreach(FaBChoiceRefs(intval($player),'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];FaBRunSourceMacro('StartTurn',intval($player),$o->CardID,['mzID'=>$ref]);}
};

function EndOfTurnPhase() { FaBEndTurn(intval(GetTurnPlayer())); }

function FaBEndTurn(int $player): bool {
    if ($player !== intval(GetTurnPlayer())) return false;
    if(!empty(FaBGetState()['endingTurn']))return true;
    SetCurrentPhase('END');
    FaBCloseCombatChain();
    FaBReturnIntimidatedCards();
    if (function_exists('FaBWTREndTurn')) FaBWTREndTurn($player);
    FaBARCEndTurn($player);
    FaBCRUEnd($player);
    $state=FaBGetState();$state['endingTurn']=$player;FaBSetState($state);
    foreach(FaBLiveSeats()as$seat){
        $cards=[];foreach(GetPitch($seat)as$obj)if(is_object($obj)&&empty($obj->removed))$cards[]=$obj->CardID;
        if(count($cards)>1){
            DecisionQueueController::AddDecision($seat,'MZREARRANGE','Bottom='.implode(',',$cards),1,'Order_pitched_cards_on_the_bottom_of_your_deck');
            DecisionQueueController::AddDecision($seat,'CUSTOM','FAB_PITCH_ORDER',1);
        }elseif(count($cards)===1)FaBReturnPitchInOrder($seat,$cards);
    }
    if(FaBHasPendingDecision())return true;
    return FaBFinishEndTurn($player);
}

$customDQHandlers['FAB_PITCH_ORDER']=function($player,$parts,$lastDecision){
    $piles=[];foreach(explode(';',(string)$lastDecision)as$pile){$pair=explode('=',$pile,2);if(count($pair)===2)$piles[$pair[0]]=array_filter(explode(',',$pair[1]));}
    FaBReturnPitchInOrder(intval($player),array_merge($piles['Bottom']??[],$piles['Top']??[]));
};

function FaBReturnPitchInOrder(int $player,array $cards): void {
    $byID=[];foreach(GetPitch($player)as$obj)if(is_object($obj)&&empty($obj->removed))$byID[$obj->CardID][]=intval($obj->UniqueID);
    foreach($cards as$id)if(!empty($byID[$id]))FaBMoveUID(array_shift($byID[$id]),'Deck',$player);
    // Never lose cards if a stale or malformed ordering omits a card.
    foreach($byID as$uids)foreach($uids as$uid)FaBMoveUID($uid,'Deck',$player);
}

function FaBFinishEndTurn(int $player): bool {
    $state=FaBGetState();unset($state['endingTurn']);FaBSetState($state);
    $hero = GetHero($player); $intellect = !empty($hero) ? max(0, intval(CardIntelligence($hero[0]->CardID))) : 4;
    if (function_exists('FaBWTRIntellectModifier')) $intellect += FaBWTRIntellectModifier($player);
    $hand = GetHand($player); $count = 0; foreach ($hand as $obj) if (is_object($obj) && empty($obj->removed)) ++$count;
    if ($count < $intellect) DoDrawCard($player, $intellect - $count);
    if (intval(GetTurnNumber()) === 1) {
        foreach (FaBLiveSeats() as $seat) {
            if ($seat === $player) continue;
            $hero = GetHero($seat);
            $intellect = intval(CardIntelligence($hero[0]->CardID ?? ''));
            DoDrawCard($seat, max(0, $intellect - FaBHandCount($seat)));
        }
    }
    foreach (FaBSeatOrder() as $seat) {
        foreach (FaBIdentityZones() as $zone) foreach (FaBZoneGet($zone, $seat) as $obj) {
            if (is_object($obj) && property_exists($obj, 'TurnEffects')) $obj->TurnEffects = [];
        }
        // Aura effects persist while their source remains in the arena.
        FaBWTRSetEffects($seat, array_values(array_filter(FaBWTREffects($seat), fn($effect) => !empty($effect['persistentUID']) || (!empty($effect['expiresAfterTurnOf']) && intval($effect['expiresAfterTurnOf']) !== $player))));
        foreach(GetBanish($seat)as$obj)if(is_object($obj)){$obj->PlayableFromBanish=0;$obj->PlayableChainLink=0;}
        AddResources($seat, 0);
    }
    AddResources($player, 0);
    $next = FaBNextInteractiveSeat($player); SetTurnPlayer($next); SetTurnNumber(intval(GetTurnNumber()) + 1);
    StartOfTurnPhase(); SetCurrentPhase('MAIN'); SaveUndoVersion($next, 'Start of turn');
    return true;
}

function FaBPassTurn($player) { return FaBPassPriority(intval($player)); }

?>
