<?php

// PHP 8.1 ZTS/Apache on Windows crashes while caching the large generated
// macro file. Keep this request uncached on that legacy development runtime;
// CLI already runs uncached, and production Linux retains opcode caching.
if (PHP_OS_FAMILY === 'Windows' && PHP_ZTS && PHP_VERSION_ID < 80200 && PHP_SAPI !== 'cli') {
    ini_set('opcache.enable', '0');
}

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
include_once __DIR__ . '/EVOCards.php';
include_once __DIR__ . '/EVORuntime.php';
include_once __DIR__ . '/MultiTargetCombat.php';
include_once __DIR__ . '/ProfessorBot.php';
include_once __DIR__ . '/IraCards.php';
include_once __DIR__ . '/MONCards.php';
include_once __DIR__ . '/MONAbilities.php';
include_once __DIR__ . '/IraBot.php';
include_once __DIR__ . '/BoltynCards.php';
include_once __DIR__ . '/BoltynBot.php';
include_once __DIR__ . '/ELECards.php';
include_once __DIR__ . '/ELEAbilities.php';
include_once __DIR__ . '/EVRCards.php';
include_once __DIR__ . '/EVRAbilities.php';
include_once __DIR__ . '/UPRCards.php';
include_once __DIR__ . '/UPRAbilities.php';
include_once __DIR__ . '/DYNCards.php';
include_once __DIR__ . '/DYNRuntime.php';
include_once __DIR__ . '/PrismCards.php';
include_once __DIR__ . '/PrismBot.php';
include_once __DIR__ . '/LexiBot.php';
include_once __DIR__ . '/DromaiBot.php';
include_once __DIR__ . '/ArakniCards.php';
include_once __DIR__ . '/ArakniBot.php';
include_once __DIR__ . '/UzuriBot.php';
include_once __DIR__ . '/AMXCards.php';
include_once __DIR__ . '/MaxxBot.php';
include_once __DIR__ . '/OUTCards.php';
include_once __DIR__ . '/OUTRuntime.php';
include_once __DIR__ . '/DTDCards.php';
include_once __DIR__ . '/DTDRuntime.php';
include_once __DIR__ . '/ROSCards.php';
include_once __DIR__ . '/ROSRuntime.php';
include_once __DIR__ . '/HNTCards.php';
include_once __DIR__ . '/HNTRuntime.php';
include_once __DIR__ . '/SEACards.php';
include_once __DIR__ . '/MPGCards.php';
include_once __DIR__ . '/SUPCards.php';
include_once __DIR__ . '/PENCards.php';
include_once __DIR__ . '/OMNCards.php';
include_once __DIR__ . '/IARCards.php';
include_once __DIR__ . '/IARRuntime.php';
include_once __DIR__ . '/SEARuntime.php';
include_once __DIR__ . '/MSTCards.php';
include_once __DIR__ . '/MSTRuntime.php';
include_once __DIR__ . '/HVYCards.php';
include_once __DIR__ . '/HVYRuntime.php';
include_once __DIR__ . '/LeviaCards.php';
include_once __DIR__ . '/LeviaBot.php';
include_once __DIR__ . '/Bot.php';

$customDQHandlers = [];
$customDQHandlers['FAB_ELE_EMERGING_DECLARE']=function($p,$parts,$lastDecision){
    $uid=intval($parts[0]??0);$epoch=intval($parts[1]??0);
    $entry=FaBGetState()['eleEmergingDeclarations'][$uid.':'.$epoch]??null;
    $controller=is_array($entry)?intval($entry['controller']??0):0;
    if(intval($p)!==$controller||($parts[2]??'')!=='answer')return;
    FaBELEEmergingDeclare($controller,$uid,$epoch,strval($lastDecision??''),true);
};
$customDQHandlers['FAB_EVR_HIGH_ROLLER_TARGET']=function($player,$parts,$lastDecision){
    if(count($parts)!==3||!ctype_digit((string)$parts[0])||!ctype_digit((string)$parts[1])||!ctype_digit((string)$parts[2]))return;
    FaBEVRAcceptHighRollerTarget(intval($player),intval($parts[0]),intval($parts[1]),intval($parts[2]),(string)$lastDecision);
};
$additionalActivationCosts = [];
$customDQHandlers['FAB_DISCARD_CHOICE'] = function($player, $parts, $lastDecision) {
    if (count($parts) === 1 && (string)$parts[0] === (string)intval($player)) {
        FaBApplyDiscardRequest(intval($player), (string)$lastDecision);
    }
};
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

/** Existing Core DQ hook: inspect every configured seat, even during cleanup. */
function GetSeatOrderArray(): array { return FaBSeatOrder(); }

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

function FaBDeathmatchHeroAttackPermission(): bool {
    foreach (FaBLiveSeats() as $holder) if (FaBMONArena($holder,'deathmatch_arena')) return true;
    return false;
}
function FaBAttackableSeats(int $seat, bool $heroTarget = false): array {
    $state = FaBGetState();
    if ($heroTarget && !FaBSeatIsLive($seat)) return [];
    $opponents = $state['gameMode'] === 'UPF'
        ? ($heroTarget && FaBDeathmatchHeroAttackPermission() ? FaBOpponents($seat) : FaBAdjacentOpponents($seat))
        : FaBOpponents($seat);
    if ($state['gameMode'] === 'UPF' && !empty($state['combatOpen']) && intval($state['defender']) > 0) {
        $opponents = array_values(array_intersect($opponents, [intval($state['defender'])]));
    }
    return $opponents;
}

function FaBDefaultDefender(int $attacker): int {
    $next = FaBNextSeat($attacker);
    return $next === $attacker ? 0 : $next;
}

function FaBRequestIntimidate(int $player, int $amount = 1, int $attackSourceUID = 0): void {
    $seats = FaBGetState()['gameMode'] === 'UPF' ? FaBAdjacentOpponents($player) : FaBOpponents($player);
    if (count($seats) === 1) { if($attackSourceUID>0)FaBWTRQueueAttackIntimidate($player,$seats[0],$amount,$attackSourceUID);else FaBIntimidate($player,$seats[0],$amount); return; }
    $targets = [];
    foreach ($seats as $seat) foreach (GetHero($seat) as $hero) {
        if (!is_object($hero) || !empty($hero->removed)) continue;
        $targets[] = FaBAttackTargetChoiceMZ(['uid'=>intval($hero->UniqueID)], $player);
    }
    if (!$targets) return;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_a_hero_to_intimidate');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_INTIMIDATE|' . max(1,$amount) . ($attackSourceUID>0 ? '|' . $attackSourceUID : ''), 1);
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
    foreach (['mpgDurations', 'botProfiles', 'turnEffects', 'nextTurnEffects', 'hitsThisTurn', 'cardsPlayedThisTurn', 'intimidated', 'weaponHits', 'attackActionHits', 'arcNames', 'arcaneDealt', 'arcCards', 'arcActions'] as $key) {
        $state[$key] = is_array($previous[$key] ?? null) ? $previous[$key] : [];
    }
    if(isset($previous['intimidateReturns'])&&is_array($previous['intimidateReturns']))$state['intimidateReturns']=$previous['intimidateReturns'];
    if(!empty($previous['dynEdicts'])&&is_array($previous['dynEdicts']))$state['dynEdicts']=$previous['dynEdicts'];
    if(!empty($previous['iarWind'])&&is_array($previous['iarWind']))$state['iarWind']=$previous['iarWind'];
    // The first Blood Debt play is per turn, not per combat chain. Keep
    // only this named repaired history; other IAR histories retain their policy.
    $iarTurn = intval(GetTurnNumber());
    foreach ((array)($previous['iar'][$iarTurn] ?? []) as $iarSeat => $history)
        if (is_array($history) && isset($history['DEBT_ATTACK']))
            $state['iar'][$iarTurn][$iarSeat]['DEBT_ATTACK'] = $history['DEBT_ATTACK'];
    if(!empty($previous['dtdPermissions'])&&is_array($previous['dtdPermissions']))$state['dtdPermissions']=$previous['dtdPermissions'];
    if (is_array($previous['monHatchetAttackHistory'] ?? null)
        && intval($previous['monHatchetAttackHistory']['turn'] ?? -1) === intval(GetTurnNumber()))
        $state['monHatchetAttackHistory'] = $previous['monHatchetAttackHistory'];
    return $state;
}

function FaBGetState(): array {
    // Card legality/stat hooks repeatedly read the same state within an action.
    // Key by the serialized value so direct setters, undo, and game loads also
    // invalidate the cache. PHP arrays return by value (copy on write).
    static $serialized = null, $state = [];
    $current = (string)GetGameState();
    if ($current !== $serialized) {
        $decoded = json_decode($current, true);
        $state = array_replace(FaBStateDefaults(), is_array($decoded) ? $decoded : []);
        $serialized = $current;
    }
    return $state;
}

/** Optional pure regression hook. Snapshot only; no setters, actions, or legality queries. */
function GameRegressionState(string $rootName): ?string {
    if ($rootName !== 'FaBSim') return null;
    $snapshot = FaBGetState();
    $effects = $snapshot['turnEffects'] ?? null;
    $turnEffectRows = [];
    foreach ([1, 2, 3, 4] as $seat) {
        $counts = [];
        $rows = is_array($effects) ? ($effects[(string)$seat] ?? null) : null;
        if (is_array($rows)) foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $type = $row['type'] ?? null;
            if (!is_string($type) || $type === '') continue;
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        $turnEffectRows[(string)$seat] = (object)$counts;
    }
    // Reserved snapshot-only view; never persist it or evaluate gameplay effects here.
    $snapshot['_regression'] = ['turnEffectRows' => $turnEffectRows];
    return json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
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
/** Universal is a while-static class effect in every zone (CR8.3.35). */
function FaBUniversalClasses(object $obj): array {
    $keywords = array_merge((array)CardCard_keywords($obj->CardID), (array)(FaBObjectCounters($obj)['_overrides']['granted_keywords'] ?? []));
    if (!in_array('Universal', $keywords, true)) return [];
    // Ability suppression itself queries types; guard only this object's recursive query.
    static $checking = [];
    $key = spl_object_id($obj);
    if (isset($checking[$key])) return [];
    $checking[$key] = true;
    try {
        if (!FaBHasKeyword($obj, 'Universal')) return [];
        $controlled = in_array($obj->Location ?? '', ['Arena','Equipment','Weapons','CombatChain','Stack','Hero'], true);
        $player = intval($controlled ? ($obj->Controller ?? 0) : ($obj->Owner ?? 0));
        if ($player < 1) $player = intval(FaBFindUID(intval($obj->UniqueID ?? 0))['player'] ?? 0);
        if ($player < 1 || !FaBSeatIsLive($player)) return [];
        $hero = GetHero($player)[0] ?? null;
        if (!is_object($hero) || $hero === $obj) return [];
        // CR2.11.6a classes only, never talents such as Shadow or Revered.
        return array_values(array_intersect(EffectiveCardType($hero), ['Adjudicator','Assassin','Bard','Brute','Guardian','Illusionist','Mechanologist','Merchant','Necromancer','Ninja','Pirate','Ranger','Runeblade','Shapeshifter','Thief','Warrior','Wizard']));
    } finally { unset($checking[$key]); }
}

function EffectiveCardType($obj): array {
    if (($obj->Location??'')==='Graveyard' && !empty($obj->FaceDown)) return [];
    $override=FaBObjectCounters($obj)['_overrides']['type']??null;
    if($override!==null)return is_array($override)?$override:array_values(array_filter(array_map('trim',explode(',',$override))));
    $types=FaBROSMeldTypes($obj,(array)CardTypes($obj->CardID));
    if($obj->CardID==='colors_of_aria_red' && empty($obj->FaceDown))$types=array_merge($types,['Earth','Ice','Lightning']);
    if(in_array('Demi-Hero',$types,true)&&((FaBFindUID(intval($obj->UniqueID??0))['zone']??'')==='Hero'))$types[]='Hero';
    if(in_array(CardName($obj->CardID), FaBGetState()['dynIllusionNames']??[], true))$types[]='Illusionist';
    if(in_array('UPR_GHOST',(array)($obj->TurnEffects??[]),true))$types=['Illusionist','Ally'];
    if(!empty(FaBObjectCounters($obj)['EVR_TOKEN']))$types[]='Token';
    if(in_array($obj->CardID,['adaptive_plating','adaptive_dissolver','adaptive_alpha_mold'],true)&&!empty(FaBObjectCounters($obj)['EVO_SLOT']))$types[]=FaBObjectCounters($obj)['EVO_SLOT'];
    if($obj->CardID==='teklovossen_the_mechropotent')$types[]='Hero';
    if(in_array('MON_ILLUSIONIST',(array)($obj->TurnEffects??[]),true))$types[]='Illusionist';
    if(!empty(FaBObjectCounters($obj)['MON_AURA']))$types[]='Weapon';
    if(in_array('FAI_DRACONIC',(array)($obj->TurnEffects??[]),true))$types[]='Draconic';
    $p=intval($obj->Controller??$obj->Owner??0);
    if(!$p&&intval($obj->UniqueID??0))$p=intval(FaBFindUID(intval($obj->UniqueID))['player']??0);
    if($p>0 && FaBARCEffect($p,'PEN_DRACONIC_CHAIN')>0 && in_array($obj->Location??'', ['Stack','CombatChain'],true) && (in_array('Attack',$types,true)||in_array('Weapon',$types,true)))$types[]='Draconic';
    if($p>0&&$p===intval(GetTurnPlayer())&&in_array('Illusionist',$types,true)&&in_array('Aura',$types,true)&&(FaBMONWeapon($p,'iris_of_reality')||FaBMONWeapon($p,'luminaris')||FaBMONWeapon($p,'reality_refractor')||(FaBMONWeapon($p,'cosmo_scroll_of_ancestral_tapestry')&&preg_match('/\bWard\b/i',(string)CardFunctional_text_plain($obj->CardID))&&!in_array('NO_ABILITIES',(array)($obj->TurnEffects??[]),true)&&empty(FaBObjectCounters($obj)['_overrides']['NO_ABILITIES'])))&&((FaBFindUID(intval($obj->UniqueID??0))['zone']??'')==='Arena'))$types[]='Weapon';
    if($p>0&&($obj->Location??'')!=='Hero'&&!in_array('Hero',$types,true)&&FaBWTRHeroActive($p))foreach(GetHero($p) as $hero)if(is_object($hero)&&empty($hero->removed)&&!empty(FaBObjectCounters($hero)['CRU_SHIYANA']))$types=array_merge($types,array_intersect((array)CardTypes($hero->CardID),['Brute','Guardian','Ninja','Warrior','Mechanologist','Ranger','Runeblade','Wizard','Merchant','Shapeshifter']));
    if($p>0&&FaBUPRCount($p,'ERASE'))$types=array_values(array_diff($types,['Brute','Guardian','Ninja','Warrior','Mechanologist','Ranger','Runeblade','Wizard','Illusionist','Merchant','Shapeshifter','Light','Shadow','Elemental','Earth','Ice','Lightning','Draconic']));
    $universal = FaBUniversalClasses($obj);
    if ($universal) $types = array_merge(array_values(array_diff($types, ['Generic'])), $universal);
    return array_values(array_unique($types));
}

function HasNoAbilities($obj): bool {
    if(in_array('IAR_WIND_LOCK',(array)($obj->TurnEffects??[]),true)||FaBMPGSuppressed($obj)||FaBSUPSuppressed($obj))return true;
    if(($obj->Location??'')==='Graveyard'&&!empty($obj->FaceDown))return true;
    if(FaBMSTHidden($obj))return true;
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
        $extra=[];if($cardID->CardID==='gloves_of_azure_waves' && FaBPENHighTide(intval($cardID->Controller??$cardID->Owner??0)))$extra[]='Blade Break';if($cardID->CardID==='runechant'){$f=FaBFindUID(intval($cardID->UniqueID));if($f&&FaBDYNCount($f['player'],'TIARA'))$extra[]='Spellvoid 1';}
        if($cardID->CardID==='lightning_flow'&&!empty(FaBGetState()['omnMacro']))$extra[]='Spellvoid 1';if($cardID->CardID==='plutonic_starplate')$extra[]='Arcane Barrier 1';
        return array_merge($extra,FaBPENKeywords($cardID),FaBPENPrintedKeywords($cardID),(array)(FaBObjectCounters($cardID)['_overrides']['granted_keywords']??[]));
    }
    $keywords = function_exists('CardCard_keywords') ? CardCard_keywords($cardID) : [];
    return is_array($keywords) ? $keywords : [];
}

function FaBHasKeyword($cardID, string $keyword): bool {
    if(is_object($cardID)&&strcasecmp($keyword,'Boost')===0&&in_array('MST_BOOST',(array)($cardID->TurnEffects??[]),true))return true;
    if(is_object($cardID)&&strcasecmp($keyword,'Blood Debt')===0){$p=intval($cardID->Owner??$cardID->Controller??0);if(!$p)$p=intval(FaBFindUID(intval($cardID->UniqueID??0))['player']??0);if($p&&FaBMONHero($p,'levia_redeemed'))return false;}
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
    if($name==='POWER'&&$value>intval($counters[$name]??0))FaBELEKorshemActivity();
    if ($value === 0) unset($counters[$name]); else $counters[$name] = $value;
    $obj->Counters = $counters;
    FaBMPGCounterUpdated($obj,$name,$value);
}

function FaBCurrentDefense(object $obj, int $player): int {
    if(FaBMSTHidden($obj))return 0;
    $omnDefense=in_array('OMN_RAZOR_DEFENSE',(array)($obj->TurnEffects??[]),true)?-1:0;
    if(FaBWTRBase($obj->CardID)==='numbskull')return max(0,intval(CardDefense($obj->CardID)));
    $base = $obj->CardID==='fractal_replication_red'?FaBEVRFractalValue($obj,'DEFENSE'):max(0, intval(CardDefense($obj->CardID)));
    $base=FaBSUPBase($player,$obj,$base,false);
    foreach((array)($obj->TurnEffects??[]) as $tag)if($tag==='DTD_HALF_BASE')$base=intval(ceil($base/2));
    if(in_array('MON_FOOT_DEFENSE',(array)($obj->TurnEffects??[]),true))$base=1;
    if(FaBPENShoesActive($player)&&FaBWTRIsAttackAction($obj))$base=intval(ceil($base/2));
    if (FaBHasType($obj, 'Equipment')) $base -= intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0);
    $delta = $omnDefense + (function_exists('EvaluateDefenseModifier') ? intval(EvaluateDefenseModifier($obj->CardID, $player, $obj, $base, $obj)) : 0);
    if (function_exists('FaBWTRDefenseModifier')) $delta += FaBWTRDefenseModifier($player, $obj);
    if ($obj->CardID==='arcanite_skullcap'&&FaBARCLowerLife($player)) ++$delta;
    $delta+=FaBMPGDefense($player,$obj);
    if($obj->CardID==='gloves_of_azure_waves' && !HasNoAbilities($obj) && FaBPENHighTide($player))$delta+=3;
    if(in_array($obj->Role??'', ['DEFENSE','DEFENSE_REACTION'],true)&&FaBSUPNoDefenseGain())$delta=min(0,$delta);
    return max(0, $base + $delta);
}

/** Locate the active CombatChain attack, including weapon, aura and ally proxies. */
function FaBFindCurrentAttack(array $state): ?array {
    if (empty($state['combatOpen']) || intval($state['attackUID'] ?? 0) < 1
        || intval($state['chainLink'] ?? 0) < 1) return null;
    $attack = FaBFindUID(intval($state['attackUID']));
    if ($attack === null || $attack['zone'] !== 'CombatChain'
        || ($attack['object']->Role ?? '') !== 'ATTACK'
        || intval($attack['object']->ChainLink ?? 0) !== intval($state['chainLink'])) return null;
    return $attack;
}

/** Live continuous defense association; departed attack types use the existing LKI. */
function FaBDefendingCardFacesWeapon(object $obj): bool {
    $uid = intval($obj->UniqueID ?? 0); $physical = $uid > 0 ? FaBFindUID($uid) : null;
    if ($physical === null || $physical['zone'] !== 'CombatChain'
        || $physical['object']->CardID !== $obj->CardID
        || !in_array((string)($physical['object']->Role ?? ''), ['DEFENSE','DEFENSE_REACTION'], true)
        || HasNoAbilities($physical['object'])) return false;
    $state = FaBGetState(); $link = intval($physical['object']->ChainLink ?? 0);
    if (empty($state['combatOpen']) || $link < 1) return false;
    $attack = FaBFindCurrentAttack($state);
    if ($attack !== null && intval($attack['object']->ChainLink ?? 0) === $link) return FaBWTRIsWeapon($attack['object']);
    $associated = null; $associatedCount = 0;
    foreach (FaBLiveSeats() as $seat) foreach (FaBZoneGet('CombatChain', $seat) as $candidate) {
        if (is_object($candidate) && empty($candidate->removed)
            && ($candidate->Role ?? '') === 'ATTACK' && intval($candidate->ChainLink ?? 0) === $link) {
            $associated = $candidate; ++$associatedCount;
        }
    }
    if ($associatedCount === 1) return FaBWTRIsWeapon($associated);
    if ($associatedCount !== 0) return false;
    foreach ((array)($state['departedChainTypes'] ?? []) as $links)
        if (in_array('Weapon', (array)($links[(string)$link] ?? []), true)) return true;
    return false;
}

function FaBCurrentAttackHasKeyword(array $state, string $keyword): bool {
    $attack = FaBFindCurrentAttack($state);
    if ($attack === null) return false;
    if(FaBMPGSuppressed($attack['object']) || in_array('PEN_BLANK_ATTACK',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&in_array('PEN_NO_DOMINATE',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&in_array('MPG_NO_DOMINATE',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&FaBDTDCount(intval($state['attacker']),'DOMINATE'))return true;
    if(strtolower($keyword)==='overpower'&&FaBPENConditionalOverpower(intval($state['attacker']),$attack['object']))return true;
    if(strtolower($keyword)==='overpower'&&FaBWTRBase($attack['object']->CardID)==='wall_breaker'&&FaBMONCount(intval($state['attacker']),'BANISHED_SIX'))return true;
    if(strtolower($keyword)==='dominate'&&FaBEVRCount(intval($state['attacker']),'TIMID'))return false;
    $effects = is_array($attack['object']->TurnEffects ?? null) ? $attack['object']->TurnEffects : [];
    if (in_array(strtoupper($keyword), array_map('strtoupper', $effects), true)) return true;
    $base = FaBPrintedKeywordIsActive($attack['object']->CardID, $keyword) ? 1 : 0;
    $evaluator = 'Evaluate' . str_replace(' ', '', ucwords(strtolower($keyword))) . 'Modifier';
    $delta = function_exists($evaluator) ? intval($evaluator($attack['object']->CardID, intval($state['attacker']), $attack['object'], $base, $attack['object'])) : 0;
    return max(0, min(1, $base + $delta)) === 1;
}

function FaBPrintedKeywordIsActive(string $cardID, string $keyword): bool {
    // An unambiguous own keyword in text does not require complete keyword metadata.
    $text = trim((string)(function_exists('CardFunctional_text_plain') ? CardFunctional_text_plain($cardID) : ''));
    $needle = strtolower($keyword);
    // A keyword granted inside a quoted ability is not this card's own keyword.
    // Preserve a quoted sentence's terminal punctuation so a following own
    // keyword sentence remains separate (for example, Abyssal Rush).
    $text = preg_replace_callback('/"[^"]*"|\x{201C}[^\x{201D}]*\x{201D}|(?<!\w)\'[^\']*\'(?!\w)|\x{2018}[^\x{2019}]*\x{2019}/u', static function(array $match): string {
        return '[quoted ability]' . (preg_match('/[.!?][\'"\x{201D}\x{2019}]$/u', $match[0]) === 1 ? '.' : '');
    }, $text);
    if (!FaBHasKeyword($cardID, $keyword)) {
        // Missing-metadata recovery is limited to own text before an unquoted
        // activated-ability section. Its post-colon Go again belongs to that layer.
        $text = preg_split('/(?<!\w)(?:Once\h+per\h+Turn\h+)?(?:Attack\h+Reaction|Defense\h+Reaction|Action|Instant)\h*(?:--?|\p{Pd})/iu', $text, 2)[0];
    }
    // A wrapped conditional/grant clause after a comma or colon stays a clause.
    $text = preg_replace('/([,:])\h*\R\h*/u', '$1 ', $text);
    foreach (preg_split('/\R+|(?<=[.!?])\h+/u', $text) as $sentence) {
        if (strcasecmp(rtrim(trim($sentence), '.!?'), $keyword) === 0) return true;
    }
    // Keyword-only cards sometimes have no text in older source records.
    return $text === '' && in_array($needle, ['go again', 'dominate'], true)
        && FaBHasKeyword($cardID, $keyword);
}

function FaBComboActive(array $state, string $requiredCardID): bool {
    if(array_key_exists(intval($state['attacker']),$state['outPreviousNames']??[]))return in_array((string)CardName($requiredCardID),$state['outPreviousNames'][intval($state['attacker'])],true);
    return strcasecmp((string)($state['previousAttackCardID'] ?? ''), $requiredCardID) === 0;
}

function FaBAttackHasGoAgain(array $state, object $attack): bool {
    if(FaBMPGSuppressed($attack) || in_array('PEN_BLANK_ATTACK',(array)($attack->TurnEffects??[]),true))return false;
    if(FaBMONArena(intval($state['attacker']),'hypothermia'))return FaBPrintedKeywordIsActive($attack->CardID,'Go again');
    foreach (FaBWTREffects(intval($state['attacker'])) as $effect) if (($effect['type'] ?? '') === 'NO_GO_AGAIN') return false;
    $effects = is_array($attack->TurnEffects ?? null) ? $attack->TurnEffects : [];
    if($attack->CardID==='teklo_blaster'&&FaBEvoActive(intval($state['attacker']),'evo_rapid_fire_blue'))return true;
    if(in_array('ELE_NO_GO',$effects,true))return false;
    if(!HasNoAbilities($attack) && $attack->CardID==='chain_of_brutality_red' && FaBAttackPower($state)>=6)return true;
    if(FaBArakniStringsApplies(intval($state['attacker']),$attack))return true;
    if(FaBWTRBase($attack->CardID)==='bonds_of_ancestry'&&FaBOUTCombo(intval($state['attacker']),'bonds_of_ancestry',$attack))return true;
    if(in_array($attack->CardID,['spiders_bite','nerve_scalpel','orbitoclast','scale_peeler'],true)||FaBDYNQuicksilver(intval($state['attacker']),$attack))return true;
    if(FaBHasType($attack,'Dragon')&&FaBMONHero(intval($state['attacker']),'dromai')&&FaBUPRRed(intval($state['attacker'])))return true;
    if(in_array(FaBWTRBase($attack->CardID),['cinderskin_devotion','searing_emberblade'],true)&&FaBFaiChainCount(intval($state['attacker']))>=2)return true;
    if (in_array('GO_AGAIN', $effects, true)) return true;
    if(FaBELEGoAgain(intval($state['attacker']),$attack))return true;
    if(FaBIARGoAgain(intval($state['attacker']),$attack)||FaBSUPGoAgain(intval($state['attacker']),$attack)||FaBSEAGoAgain(intval($state['attacker']),$attack)||FaBHNTGoAgain(intval($state['attacker']),$attack)||FaBROSGoAgain(intval($state['attacker']),$attack))return true;
    if(FaBMONGAgain(intval($state['attacker']),$attack,$state)||FaBDTDGoAgain(intval($state['attacker']),$attack,$state)||FaBEVOGoAgain(intval($state['attacker']),$attack)||FaBHVYGoAgain(intval($state['attacker']),$attack)||FaBMSTGoAgain(intval($state['attacker']),$attack))return true;
    $base = FaBPrintedKeywordIsActive($attack->CardID, 'Go again') ? 1 : 0;
    if(CardName($attack->CardID)==='Visit Anvilheim' && FaBChoiceRefs(intval($attack->Owner??$state['attacker']),'Weapons',['base'=>'shield_beater']))$base=1;
    $delta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($attack->CardID, intval($state['attacker']), $attack, $base, $attack)) : 0;
    if (function_exists('FaBWTRAttackHasGoAgain') && FaBWTRAttackHasGoAgain($state, $attack)) return true;
    return max(0, min(1, $base + $delta)) === 1;
}

function FaBIntimidate(int $sourcePlayer, int $targetPlayer, int $amount = 1, int $sourceUID = 0, string $sourceCardID = ''): array {
    $banished = [];
    for ($n = 0; $n < max(0, $amount); ++$n) {
        $choices = [];
        foreach (GetHand($targetPlayer) as $obj) if (is_object($obj) && empty($obj->removed)) $choices[] = intval($obj->UniqueID ?? 0);
        $choices = array_values(array_filter($choices)); if (empty($choices)) break;
        $uid = $choices[EngineRandomInt(0, count($choices) - 1)];
        $moved = FaBMoveUID($uid, 'Banish', $targetPlayer);
        if ($moved !== null) { $moved->FaceDown = 1; $moved->ReturnAtEndTurn = 1; $banished[] = $uid; }
    }
    // Moves can update shared counters; capture fresh state after their callbacks.
    $state = FaBGetState();
    $state['intimidated'] = array_values(array_unique(array_merge($state['intimidated'] ?? [], $banished)));
    FaBSetState($state);
    FaBRememberIntimidateReturn($sourcePlayer, $sourceUID, $sourceCardID, $banished);
    FaBHVYAdd($sourcePlayer,'INTIMIDATED'); return $banished;
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
        $discardSource = $found === null ? null : clone $found['object'];
        // Generated AddHand permits Owner0: its actual Hand container supplies ownership.
        // Normalize only the clone; keep explicit foreign owners and malformed LKI rejected.
        if ($discardSource !== null && $found['zone'] === 'Hand' && intval($found['player']) === $player
            && intval($discardSource->Owner ?? -1) === 0) $discardSource->Owner = $player;
        $reincarnateEligible = FaBDYNPrepareReincarnateRandom($player,$uid,(string)$cardID,$discardSource);
        if (FaBMoveUID($uid, 'Graveyard', $player) !== null) {
            $discarded[] = $uid;
            $discardGraveEntry = intval(FaBARCCard($uid, 'dynReincarnateGraveEntry', 0));
            if (function_exists('FaBWTRCardDiscarded')) FaBWTRCardDiscarded($player, (string)$cardID, $uid, $discardSource);
            FaBDYNRandomDiscard($player,$uid,(string)$cardID,$discardSource,$discardGraveEntry,$reincarnateEligible);
        }
    }
    return $discarded;
}

function FaBHandCount(int $player): int {
    $count = 0; foreach (GetHand($player) as $obj) if (is_object($obj) && empty($obj->removed)) ++$count; return $count;
}

/** A fresh Intimidate invocation owns its own delayed return (CR 8.5.10c). */
function FaBRememberIntimidateReturn(int $controller, int $sourceUID, string $sourceCardID, array $uids): void {
    $state=FaBGetState();
    $group=intval($state['intimidateReturns']['next']??0)+1;
    $state['intimidateReturns']['next']=$group;
    $cards=[];
    foreach(array_unique(array_map('intval',$uids)) as $uid){
        $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Banish')continue;
        $owner=intval($f['object']->Owner??0);if($owner<1)$owner=intval($f['player']);
        $cards[]=['uid'=>$uid,'owner'=>$owner,'holder'=>intval($f['player'])];
        $state['arcCards'][(string)$uid]['intimidateReturnGroup']=$group;
    }
    // Empty-source metadata is an honest legacy caller, never an invented caster/card.
    $state['intimidateReturns']['pending'][(string)$group]=[
        'controller'=>$controller,'sourceUID'=>$sourceUID,'cardID'=>$sourceCardID,
        'cards'=>$cards,'queued'=>false];
    FaBSetState($state);
}

/** Queue one independent layer per invocation at the beginning of the end phase. */
function FaBQueueIntimidateReturns(): void {
    $groups=(array)(FaBGetState()['intimidateReturns']['pending']??[]);
    foreach($groups as $group=>$record){
        if(!empty($record['queued'])||!FaBSeatIsLive(intval($record['controller']??0)))continue;
        $state=FaBGetState();
        if(!isset($state['intimidateReturns']['pending'][(string)$group]))continue;
        $state['intimidateReturns']['pending'][(string)$group]['queued']=true;
        FaBSetState($state);
        FaBROSQueue(intval($record['controller']),(string)$record['cardID'],intval($record['sourceUID']),
            ['rosEvent'=>'wtrIntimidateReturn','intimidateGroup'=>intval($group)]);
    }
}

function FaBResolveIntimidateReturn(int $controller, object $trigger): void {
    $group=intval($trigger->Params['intimidateGroup']??0);$state=FaBGetState();
    $record=$state['intimidateReturns']['pending'][(string)$group]??null;
    if(!is_array($record)||empty($record['queued'])||intval($record['controller'])!==$controller
        ||intval($trigger->SourceUniqueID)!==intval($record['sourceUID'])
        ||(string)$trigger->CardID!==(string)$record['cardID'])return;
    // Consume before callbacks; source departure and later ability loss do not cancel it.
    unset($state['intimidateReturns']['pending'][(string)$group]);FaBSetState($state);
    $finished=[];
    foreach((array)$record['cards'] as $card){
        $uid=intval($card['uid']);
        $entryGroup=intval(FaBARCCard($uid,'intimidateReturnGroup'));
        if($entryGroup!==$group){if($entryGroup===0)$finished[]=$uid;continue;}
        $f=FaBFindUID($uid);
        if($f&&$f['zone']==='Banish'&&intval($f['object']->ReturnAtEndTurn??0)===1
            &&intval($f['player'])===intval($card['holder'])
            &&(intval($f['object']->Owner??0)>0?intval($f['object']->Owner):intval($f['player']))===intval($card['owner']))
            FaBMoveUID($uid,'Hand',intval($card['owner']));
        $state=FaBGetState();$now=intval($state['arcCards'][(string)$uid]['intimidateReturnGroup']??0);
        if($now===$group)unset($state['arcCards'][(string)$uid]['intimidateReturnGroup']);
        if($now===0||$now===$group)$finished[]=$uid;
        FaBSetState($state);
    }
    $state=FaBGetState();
    $state['intimidated']=array_values(array_diff((array)$state['intimidated'],$finished));
    FaBSetState($state);
}

/** Direct helper compatibility; normal end cleanup touches only unattributed old saves. */
function FaBReturnIntimidatedCards(bool $legacyOnly = false): void {
    $state=FaBGetState();$protected=[];$finished=[];
    $initialGroups=array_map('intval',array_keys((array)($state['intimidateReturns']['pending']??[])));
    $initialTracked=[];foreach((array)($state['intimidateReturns']['pending']??[]) as $record)foreach((array)$record['cards'] as $card)$initialTracked[]=intval($card['uid']);
    if($legacyOnly)foreach((array)($state['intimidateReturns']['pending']??[]) as $record)
        foreach((array)$record['cards'] as $card)$protected[]=intval($card['uid']);
    foreach((array)$state['intimidated'] as $uid){
        $uid=intval($uid);$current=FaBGetState();$oldGroup=intval($current['arcCards'][(string)$uid]['intimidateReturnGroup']??0);
        if($legacyOnly&&(in_array($uid,$protected,true)||($oldGroup>0&&isset($current['intimidateReturns']['pending'][(string)$oldGroup]))))continue;
        if(!$legacyOnly&&$oldGroup>0&&!in_array($oldGroup,$initialGroups,true))continue;
        if(!$legacyOnly&&$oldGroup===0&&in_array($uid,$initialTracked,true)){$finished[]=$uid;continue;}
        $f=FaBFindUID($uid);
        if($f&&$f['zone']==='Banish'&&intval($f['object']->ReturnAtEndTurn??0)===1)
            FaBMoveUID($uid,'Hand',intval($f['object']->Owner??$f['player']));
        $current=FaBGetState();$now=intval($current['arcCards'][(string)$uid]['intimidateReturnGroup']??0);
        if($now===$oldGroup)unset($current['arcCards'][(string)$uid]['intimidateReturnGroup']);
        if($now===0||$now===$oldGroup)$finished[]=$uid;
        FaBSetState($current);
    }
    $state=FaBGetState();$state['intimidated']=array_values(array_diff((array)$state['intimidated'],$finished));
    if(!$legacyOnly)foreach($initialGroups as $group)unset($state['intimidateReturns']['pending'][(string)$group]);
    FaBSetState($state);
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
        'Soul' => GetSoul($player), 'Arena' => GetArena($player), 'CombatChain' => GetCombatChain($player), 'Deck' => GetDeck($player),
        'Hand' => GetHand($player), 'Arsenal' => GetArsenal($player), 'Graveyard' => GetGraveyard($player),
        'Banish' => GetBanish($player), 'Pitch' => GetPitch($player), 'Temp' => GetTemp($player), 'Inventory' => GetInventory($player), 'Stack' => GetStack(),
        default => [],
    };
}

function FaBIdentityZones(): array {
    return ['Hero', 'Weapons', 'Equipment', 'Arena', 'CombatChain', 'Deck', 'Hand', 'Arsenal', 'Graveyard', 'Banish', 'Soul', 'Pitch', 'Temp', 'Inventory'];
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
    if(str_starts_with($mzID,'@BurnDamage:')||str_starts_with($mzID,'@PoxDamage:'))return null;
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
    $heroSeats = FaBAttackableSeats($attacker,true);
    $permanentSeats = FaBAttackableSeats($attacker);
    foreach (array_values(array_unique(array_merge($heroSeats,$permanentSeats))) as $seat) {
        if (in_array($seat,$heroSeats,true)) foreach (GetHero($seat) as $index => $hero) {
            if (!is_object($hero) || !empty($hero->removed)) continue;
            $targets[] = FaBAttackTargetDescriptor(['player'=>$seat,'zone'=>'Hero','index'=>$index,'object'=>$hero]);
        }
        if (in_array($seat,$permanentSeats,true)) foreach (GetArena($seat) as $index => $permanent) {
            if (!is_object($permanent) || !empty($permanent->removed) || !FaBObjectCanBeAttacked($permanent)) continue;
            $targets[] = FaBAttackTargetDescriptor(['player'=>$seat,'zone'=>'Arena','index'=>$index,'object'=>$permanent]);
        }
    }
    $sentinels=array_values(array_filter($targets,fn($t)=>($f=FaBFindUID(intval($t['uid'])))&&$f['object']->CardID==='arc_light_sentinel_yellow'&&!HasNoAbilities($f['object'])));
    return $sentinels?:FaBSEAChumTargets($attacker,$targets);
}

function FaBResolveAttackTarget(array $descriptor, int $attacker): ?array {
    $found = FaBFindUID(intval($descriptor['uid'] ?? 0));
    if ($found === null || !in_array(intval($found['player']), !empty($descriptor['anyHero'])?FaBOpponents($attacker):FaBAttackableSeats($attacker,$found['zone']==='Hero'), true)) return null;
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
    // A registered Hatchet source may move within the arena without becoming
    // a new object. Leaving it invalidates only its already-armed epoch.
    $hatchetArena = ['Hero','Arena','Equipment','Weapons','CombatChain'];
    if (in_array((string)($source->Location ?? ''), $hatchetArena, true)
        && !in_array($zone, $hatchetArena, true))
        FaBAdvanceCapturedSourceEpochs($source, (string)$source->Location, $zone, ['monHatchetSourceEpoch']);

    // Zone changes create a new Mirage source instance despite physical UID reuse.
    if (($zone === 'CombatChain') !== (($source->Location ?? '') === 'CombatChain')) {
        $s = FaBGetState(); $key = (string)intval($source->UniqueID ?? 0);
        if (isset($s['arcCards'][$key]['dtdMirage'])) {
            unset($s['arcCards'][$key]['dtdMirage']); FaBSetState($s);
        }
    }
    FaBAdvanceCapturedSourceEpochs($source,(string)($source->Location??''),$zone,['eleEmergingEntryEpoch']);
    $added = match ($zone) {
        'Hero' => AddHero($player, CardID:$source->CardID, sourceObject:$source),
        'Temp' => AddTemp($player, CardID:$source->CardID, sourceObject:$source),
        'Inventory' => AddInventory($player, CardID:$source->CardID, sourceObject:$source),
        'Weapons' => AddWeapons($player, CardID:$source->CardID, sourceObject:$source),
        'Equipment' => AddEquipment($player, CardID:$source->CardID, sourceObject:$source),
        'Arena' => AddArena($player, CardID:$source->CardID, sourceObject:$source),
        'CombatChain' => AddCombatChain($player, CardID:$source->CardID, sourceObject:$source),
        'Deck' => AddDeck($player, CardID:$source->CardID, sourceObject:$source),
        'Hand' => AddHand($player, CardID:$source->CardID, sourceObject:$source),
        'Arsenal' => AddArsenal($player, CardID:$source->CardID, sourceObject:$source),
        'Graveyard' => AddGraveyard($player, CardID:$source->CardID, sourceObject:$source),
        'Soul' => AddSoul($player, CardID:$source->CardID, sourceObject:$source),
        'Banish' => AddBanish($player, CardID:$source->CardID, sourceObject:$source),
        'Pitch' => AddPitch($player, CardID:$source->CardID, sourceObject:$source),
        default => null,
    };
    if($added!==null){
        if(property_exists($added,'Owner')&&intval($added->Owner)<1)$added->Owner=$player;
        if(property_exists($added,'Controller')&&intval($added->Controller)<1)$added->Controller=$player;
        if($zone==='Arsenal')FaBARCHeadShotArsenalEntry($added);
        FaBELEEmergingAfterMove($player,$added,(string)($source->Location??''),$zone);
    }
    return $added;
}

function FaBMoveUID(int $uid, string $toZone, ?int $targetPlayer = null, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null) return null;
    if ($found['zone'] === 'Stack') {
        // Only the currently resolving physical source may move itself.
        // Plain spells share that permission; unrelated Stack layers remain rejected.
        $pending = FaBGetState()['resolvingReaction'] ?? null;
        if (!is_array($pending) || intval($pending['uid'] ?? 0) !== $uid) return null;
        $kind = (string)($found['object']->Kind ?? '');
        $plainSpell = ($pending['resolutionClass'] ?? '') === 'PLAIN_SPELL'
            && in_array($kind, ['ACTION','INSTANT'], true);
        if (!$plainSpell && !in_array($kind, ['ATTACK_REACTION','DEFENSE_REACTION'], true)) return null;
        return FaBMoveStackUID($uid, $toZone,
            $targetPlayer ?? intval($pending['owner']), $animate);
    }
    $source = $found['object'];
    $burnDamageGeneration = $found['zone']==='Arena' ? intval(FaBARCCard($uid,'burnDamageSourceEpoch',0)) : 0;
    $burnDamageOwner = $found['zone']==='Arena' ? FaBCapturedBurnLayerController($uid) : 0;
    $burnDamageBefore = $burnDamageOwner>0 ? FaBCaptureDamageSourceRecord($uid,'burn',$burnDamageOwner,false) : [];
    // CR 3.0.8: reveal a private card before ordinary graveyard movement/replacements.
    // Explicit destination effects may make it private again in their after-move hook.
    if ($toZone === 'Graveyard' && property_exists($source, 'FaceDown')) $source->FaceDown = 0;
    if ($toZone === 'Graveyard' && function_exists('FaBWTRBase') && FaBWTRBase((string)$source->CardID) === 'drone_of_brutality') $toZone = 'Deck';
    if ($toZone === 'Graveyard' && in_array($source->CardID, ['engulfing_shadows_yellow','embraforged_gauntlet'], true)) $toZone = 'Banish';
    if($toZone==='Graveyard'&&FaBARCCard($uid,'hntTrapReplaceTurn',-1)===intval(GetTurnNumber()))$toZone='Banish';
    if(!in_array($toZone,['Stack','CombatChain'],true)&&!empty(FaBObjectCounters($source)['HNT_ORIGINAL'])){$source->CardID=FaBObjectCounters($source)['HNT_ORIGINAL'];unset($source->Counters['HNT_ORIGINAL']);}
    if($found['zone']==='Banish'){$permissions=FaBGetState();unset($permissions['outInfiltrate'][$uid],$permissions['arcCards'][(string)$uid]['intimidateReturnGroup']);FaBSetState($permissions);}
    if($toZone==='Soul'&&$source->CardID==='spirit_of_eirina_yellow')$toZone='Arena';
    if((in_array($found['zone'],['Arena','Equipment'],true)||($found['zone']==='CombatChain'&&($source->Role??'')!=='ATTACK'))&&!in_array($toZone,['Arena','Equipment','CombatChain'],true))FaBDYNLeaving($found['player'],$source,$toZone);
    if($toZone==='Graveyard'&&(in_array('EVR_BOTTOM',(array)($source->TurnEffects??[]),true)||in_array('SEA_BOTTOM',(array)($source->TurnEffects??[]),true)))$toZone='Deck';
    if($toZone==='Graveyard'&&$source->CardID==='new_horizon')FaBELEDestroyHook($uid);
    if($toZone==='Graveyard'&&in_array('ELE_BANISH_REPLACE',(array)($source->TurnEffects??[]),true))$toZone='Banish';
    if($toZone==='Graveyard'&&$source->CardID==='mark_of_the_beast_yellow')$toZone='Banish';
    $targetPlayer ??= intval($source->Owner ?? $found['player']);
    if($found['zone']==='Arena'&&$toZone!=='Arena')FaBUPRLeaving($found['player'],$source,$toZone);
    if ($targetPlayer < 1) $targetPlayer = $found['player'];
    if($found['zone']==='CombatChain'&&($source->Role??'')==='ATTACK'){
        // Chain-link properties survive their active attack leaving (CR 7.0.3c).
        $s=FaBGetState();$s['departedChainTypes'][(string)$found['player']][(string)$source->ChainLink]=EffectiveCardType($source);FaBSetState($s);
    }
    if(!in_array($toZone,['Equipment','Weapons','CombatChain'],true)&&!empty(FaBObjectCounters($source)['SUBCARDS'])){foreach(FaBObjectCounters($source)['SUBCARDS'] as $under)if(!FaBHasType($under,'Token'))AddGraveyard($targetPlayer,CardID:$under);unset($source->Counters['SUBCARDS']);}
    if($found['zone']==='Weapons' && $source->CardID==='bank_breaker' && !in_array($toZone,['Weapons','CombatChain'],true))$source->CardID='construct_bank_breaker_yellow';
    // A declared permanent target cannot become the same target again after leaving the arena.
    if (in_array($found['zone'], ['Arena','Equipment','Weapons','CombatChain','Hero'], true)
        && !in_array($toZone, ['Arena','Equipment','Weapons','CombatChain','Hero'], true)) {
        $epoch=intval(FaBARCCard($uid,'declaredPermanentEpoch',0));
        if($epoch>0)FaBARCSetCard($uid,'declaredPermanentEpoch',$epoch+1);
    }
    // Capture the resolved Arena-to-graveyard event before any move callbacks.
    $maliceDeath = FaBIARCaptureMaliceDeath($source, intval($found['player']), (string)$found['zone'], $toZone);
    if($burnDamageGeneration>0)FaBFreezeCapturedBurnSource($uid,(string)$found['zone'],$toZone,$burnDamageBefore,$burnDamageGeneration,$source);
    $source->removed = true;
    // CR 8.1.8a / 3.11.5: tokens remain only in the arena or as subcards (Soul).
    if (FaBHasType($source, 'Token') && !in_array($toZone,
        ['Arena','Equipment','Weapons','Hero','CombatChain','Soul'], true)) {
        FaBIARQueueMaliceDeath($maliceDeath, null, true);
        FaBPENObserveArenaExit(intval($found['player']), $source, (string)$found['zone'], $toZone);
        return null;
    }
    if($toZone==='Graveyard' && $source->CardID==='goldfin_harpoon_yellow')return null;
    if(($source->CardID==='hyper_driver'&&!in_array($toZone,['Arena','CombatChain'],true))||($toZone==='Graveyard'&&FaBHasKeyword($source,'Ephemeral')))return null;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null) FaBIARQueueMaliceDeath($maliceDeath, $newObj);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        $newIndex = intval($newObj->mzIndex ?? 0);
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . $newIndex, 360, true, $uid, $uid);
    }
    if ($newObj !== null) FaBCRUAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBEVRAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBMONAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBSEAAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBMPGAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);if($newObj!==null)FaBSUPAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBPENAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBOMNAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBIARAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    return $newObj;
}

function FaBMoveStackUID(int $uid, string $toZone, int $targetPlayer, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] !== 'Stack') return null;
    $source = $found['object'];
    // Preserve last-known layer properties if this resolving defense reaction
    // moves itself before its remaining effects/go again have completed.
    $resolving = FaBGetState();
    if (($resolving['resolvingReaction']['resolutionClass'] ?? '') === 'DEFENSE_REACTION'
        && intval($resolving['resolvingReaction']['uid'] ?? 0) === $uid
        && ($source->Kind ?? '') === 'DEFENSE_REACTION') {
        $resolving['resolvingReaction']['completionSource'] = (array)$source;
        FaBSetState($resolving);
    }
    if ($toZone === 'Graveyard' && in_array($source->CardID, ['engulfing_shadows_yellow','embraforged_gauntlet'], true)) $toZone = 'Banish';
    if($toZone==='Graveyard'&&FaBARCCard($uid,'hntTrapReplaceTurn',-1)===intval(GetTurnNumber()))$toZone='Banish';
    if(!in_array($toZone,['Stack','CombatChain'],true)&&!empty(FaBObjectCounters($source)['HNT_ORIGINAL'])){$source->CardID=FaBObjectCounters($source)['HNT_ORIGINAL'];unset($source->Counters['HNT_ORIGINAL']);}
    if(in_array($toZone,['Graveyard','Deck','Banish','Hand'],true)&&intval($source->Owner??0)>0)$targetPlayer=intval($source->Owner);
    if($toZone==='Graveyard'&&(in_array('EVR_BOTTOM',(array)($source->TurnEffects??[]),true)||in_array('SEA_BOTTOM',(array)($source->TurnEffects??[]),true)))$toZone='Deck';
    if($toZone==='Graveyard'&&$source->CardID==='new_horizon')FaBELEDestroyHook($uid);
    if($toZone==='Graveyard'&&in_array('ELE_BANISH_REPLACE',(array)($source->TurnEffects??[]),true))$toZone='Banish';
    if($toZone==='Graveyard'&&$source->CardID==='mark_of_the_beast_yellow')$toZone='Banish';
    if($toZone==='Graveyard'&&FaBWTRBase($source->CardID)==='drone_of_brutality')$toZone='Deck';
    if(!in_array($toZone,['Equipment','Weapons','CombatChain'],true)&&!empty(FaBObjectCounters($source)['SUBCARDS'])){foreach(FaBObjectCounters($source)['SUBCARDS'] as $under)if(!FaBHasType($under,'Token'))AddGraveyard($targetPlayer,CardID:$under);unset($source->Counters['SUBCARDS']);}
    if($found['zone']==='Weapons' && $source->CardID==='bank_breaker' && !in_array($toZone,['Weapons','CombatChain'],true))$source->CardID='construct_bank_breaker_yellow';
    $source->removed = true;
    // CR 8.1.8a / 3.11.5: tokens remain only in the arena or as subcards (Soul).
    if (FaBHasType($source, 'Token') && !in_array($toZone,
        ['Arena','Equipment','Weapons','Hero','CombatChain','Soul'], true)) return null;
    if($toZone==='Graveyard'&&$source->CardID==='goldfin_harpoon_yellow')return null;
    if(($source->CardID==='hyper_driver'&&!in_array($toZone,['Arena','CombatChain'],true))||($toZone==='Graveyard'&&FaBHasKeyword($source,'Ephemeral')))return null;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
    if ($newObj !== null && $animate && function_exists('QueueZoneMoveAnimation')) {
        QueueZoneMoveAnimation($found['mzID'], 'p' . $targetPlayer . $toZone . '-' . intval($newObj->mzIndex ?? 0), 360, true, $uid, $uid);
    }
    if ($newObj !== null) FaBCRUAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBEVRAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBMONAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
    if($newObj!==null)FaBSEAAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBMPGAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);if($newObj!==null)FaBSUPAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBPENAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBOMNAfterMove($targetPlayer,$newObj,$found['zone'],$toZone,$source);if($newObj!==null)FaBIARAfterMove($targetPlayer,$newObj,$found['zone'],$toZone);
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

/** Imported X, XX and X1 costs encode variable symbols plus a fixed numeral. */
function FaBPrintedFixedResourceCost(string $cost): int {
    return max(0, intval(str_replace('X', '', $cost)));
}

function FaBCardCost(object $obj, int $player): int {
    $base = $obj->CardID==='imposing_visage_blue'?3:FaBPrintedFixedResourceCost((string)CardCost($obj->CardID));
    if(FaBWTRBase($obj->CardID)==='numbskull')return $base;
    $delta = function_exists('EvaluateCostModifier') ? intval(EvaluateCostModifier($obj->CardID, $player, $obj, $base, $obj)) : 0;
    if (function_exists('FaBWTRCostModifier')) $delta += FaBWTRCostModifier($player, $obj);
    $delta += FaBARCCostModifier($player, $obj) + FaBProfessorCost($player,$obj) + FaBCRUCost($player,$obj) + FaBELETax($player) + FaBUPRCost($player,$obj) + FaBDTDCost($player,$obj) + FaBEVOCost($player,$obj) + FaBHVYCost($player,$obj) + FaBMSTCost($player,$obj) + FaBROSCost($player,$obj) + FaBHNTCost($player,$obj) + FaBSEACost($player,$obj) + FaBSUPCost($player,$obj) - ($obj->CardID==='solid_ground_blue'?FaBMPGSurges($player):0);
    if(FaBWTRBase($obj->CardID)==='jump_start'&&FaBChoiceRefs($player,'Arena',['base'=>'hyper_driver']))--$delta;
    return max(0, $base + $delta + FaBIARCost($player,$obj) + FaBOMNCost($player,$obj) + FaBMPGDefense($player,$obj) + FaBPENInflation() + FaBPENBetaCost($player,$obj) + FaBPENHavocCost());
}

function FaBAvailablePitch(int $player, int $excludedUID = 0, array $additionalExcludedUIDs = [], bool $recompenseSpent = false): int {
    $excluded = array_fill_keys(array_map('intval', array_merge([$excludedUID], $additionalExcludedUIDs)), true);
    $total = max(0, intval(GetResources($player)));$recompense=false;
    foreach (GetHand($player) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || isset($excluded[intval($obj->UniqueID ?? 0)])) continue;
        if(!FaBARCNamedProhibited($obj->CardID)&&!FaBHNTNamed($obj->CardID)&&FaBOUTCanPitch($player,$obj)&&FaBELECanPitch($player,$obj)){$pitch=FaBMONPitchValue($player,$obj->CardID);$total+=$pitch;if($pitch===1)$recompense=true;}
    }
    return $total+(!$recompenseSpent&&$recompense&&FaBMONArena($player,'talisman_of_recompense')?2:0);
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
    if (FaBAutomaticBeginningEndActive()) return false;
    if (FaBReactionResolutionIsPending()) return false;
    if (function_exists('FaBGeneratedCanPlayCard') && !FaBGeneratedCanPlayCard($player, $mzID)) return false;
    $player = intval($player);
    if (!FaBSeatIsLive($player) || intval(GetWinner()) !== 0 || intval(GetPriorityPlayer()) !== $player) return false;
    if (FaBHasPendingDecision()) return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || ($found['player'] !== $player&&!FaBIARPermission($player,$found['object'],$found['zone'])&&!FaBOUTCanPlayStolen($player,$found)&&!FaBMPGCanStealPlay($player,$found)) || !in_array($found['zone'], ['Hand', 'Arsenal', 'Banish', 'Deck', 'Graveyard'], true)) return false;
    if(!FaBIARCanPlay($player,$found)||!FaBPENCanPlay($player,$found))return false;
    if(!FaBMPGCanPlay($player,$found)||FaBSUPBaitLocked($player,$found['object']))return false;
    if ($found['zone'] === 'Graveyard' && !FaBIARPermission($player,$found['object'],'Graveyard') && !FaBOMNPlayable($found) && !FaBSEAGravePlayable($player,$found)&&!FaBSUPEncorePlayable($player,$found['object'])) return false;
    if ($found['zone'] === 'Deck' && !FaBEVODashTop($player,$found['object'])) return false;
    if ($found['zone'] === 'Banish' && !FaBEVOBanishPlayable($player,$found['object']) && !FaBOUTCanPlayStolen($player,$found) && empty($found['object']->PlayableFromBanish) && !FaBMONBanishPlayable($player,$found['object']) && !FaBIARBanishPlayable($player,$found['object']) && !FaBDTDBanishPlayable($player,$found['object']) && !(FaBProfessorActive($player)&&FaBHasType($found['object'],'Evo')&&empty($found['object']->FaceDown))) return false;
    if ($found['zone'] === 'Banish' && intval($found['object']->PlayableChainLink ?? 0) > 0 && intval($found['object']->PlayableChainLink) !== intval(FaBGetState()['chainLink'])) return false;
    $obj = $found['object']; $state = FaBGetState();
    if ($state['pendingPayment'] !== null) return false;
    if (FaBHasType($obj,'Instant') && FaBOMNLocked($player)) return false;
    if (FaBHasType($obj,'Defense Reaction') && !FaBOMNBlockLegal($obj)) return false;
    $isAttackReaction = FaBHasType($obj, 'Attack Reaction');
    $isDefenseReaction = FaBHasType($obj, 'Defense Reaction');
    if($isDefenseReaction&&!FaBSUPBlockLegal($player,$obj))return false;
    $isInstant = FaBHasType($obj, 'Instant') || FaBARCAsInstant($player,$obj);
    $isAction = FaBHasType($obj, 'Action');
    $isAttack = FaBHasType($obj, 'Attack');
    $actionWindow = GetCurrentPhase() !== 'END' && ($state['window'] === 'ACTION'
        || ($state['window'] === 'RESOLUTION' && $isAttack));
    $timingLegal = ($isAction && $player === intval(GetTurnPlayer()) && $actionWindow && intval(GetActionPoints($player)) > 0)
        || ($isAttackReaction && FaBReactionPriorityForState($state) && $player === intval($state['attacker']))
        || ($isDefenseReaction && FaBReactionPriorityForState($state) && FaBIsDefendingHero($player,$state))
        || ($isInstant && !in_array($state['window'], ['PITCH', 'DEFEND_DECLARE'], true));
    if (function_exists('FaBWTRCanPlay') && !FaBWTRCanPlay($player, $found, $state)) return false;
    if (!FaBARCCanPlay($player, $found) || !FaBCRUCanPlay($player,$found) || !FaBMONCanPlay($player,$found) || !FaBELECanPlay($player,$found) || !FaBEVRCanPlay($player,$found) || !FaBUPRCanPlay($player,$found) || !FaBDYNCanPlay($player,$found) || !FaBOUTCanPlay($player,$found) || !FaBDTDCanPlay($player,$found) || !FaBEVOCanPlay($player,$found) || !FaBHVYCanPlay($player,$found)||!FaBMSTCanPlay($player,$obj)||!FaBROSCanPlay($player,$obj)||!FaBHNTCanPlay($player,$obj)) return false;
    if(FaBWTRBase($obj->CardID)==='duty_bound_blitz'&&!FaBMONCount($player,'YELLOW_SOUL'))return false;
    if($obj->CardID==='edict_of_steel_red'&&FaBBoltynSwordChoices($player)==='')return false;
    if($obj->CardID==='shadowrealm_horror_red'&&count(FaBChoiceRefs($player,'Graveyard'))<3)return false;
    if ($obj->CardID==='apocalypse_automaton_red'&&FaBEvoCount($player)<1)return false;
    return $timingLegal && (($obj->CardID==='10000_year_reunion_red'&&FaBMSTTotalCounters($player)>=3&&FaBAvailablePitch($player,intval($obj->UniqueID))>=max(0,FaBCardCost($obj,$player)-intval(CardCost($obj->CardID))))||($obj->CardID==='sonata_galaxia_red'&&FaBAvailablePitch($player,intval($obj->UniqueID))>=max(0,FaBCardCost($obj,$player)-FaBARCRunechants($player))) || ($obj->CardID==='double_down_red'&&FaBHVYGold($player)!==''&&FaBAvailablePitch($player,intval($obj->UniqueID))>=max(0,FaBCardCost($obj,$player)-intval(CardCost($obj->CardID)))) || (FaBWTRBase($obj->CardID)==='life_of_the_party'&&FaBEVRBrew($player)!=='') || (FaBWTRBase($obj->CardID)==='blinding_beam'&&FaBMONShadowCombatTarget()) || (($obj->CardID==='cash_in_yellow'&&FaBCRUCashOptions($player)!=='Pay_resources')) || (in_array(FaBWTRBase($obj->CardID),['moon_wish','rise_above','soul_reaping'],true) && FaBHandCount($player)>($found['zone']==='Hand'?1:0)) || FaBIARAlternativeAvailable($player,$found) || FaBAvailablePitch($player, intval($obj->UniqueID ?? 0)) >= FaBCardCost($obj, $player));
}

function DoPlayCard($player, $mzID) {
    $player = intval($player);
    if (!CanPlayCard($player, $mzID)) return false;
    $found = FaBIdentityFromMZ((string)$mzID); if ($found === null) return false;
    if(FaBIARChoosePermission($player,$found))return true;
    $lungingTargetUID = 0; $lungingTargetChainLink = -1;
    if ($found['object']->CardID === 'lunging_press_blue' && !HasNoAbilities($found['object'])) {
        $lungingTargetUID = FaBCRULungingClaimOrRequestTarget($player, $found);
        if ($lungingTargetUID === null) return true;
        if ($lungingTargetUID === false) return false;
        $lungingTargetChainLink = intval(FaBFindUID($lungingTargetUID)['object']->ChainLink ?? -1);
    }
    $isAttack = FaBHasType($found['object'], 'Attack');
    $attackTarget = null;
    if ($isAttack) {
        $attackTarget = FaBClaimOrRequestAttackTarget($player, intval($found['object']->UniqueID), 'PLAY');
        if ($attackTarget === null) return true;
        if ($attackTarget === false) return false;
    }
    SaveUndoVersion($player, 'Before playing ' . (CardName($found['object']->CardID) ?: $found['object']->CardID));
    $source = $found['object'];
    $uid = intval($source->UniqueID); $fromZone = $found['zone'];FaBIARSpendPermission($player,$found);
    // Direct Graveyard plays bypass ordinary after-move hooks; invalidate only armed Memorial targets.
    if($fromZone==='Graveyard')FaBAdvanceCapturedSourceEpochs($source,'Graveyard','Stack',['monMemorialGraveEpoch']);
    FaBARCSetCard($uid,'mstChiPitched',0); FaBARCSetCard($uid,'rosMeld',-1);FaBARCSetCard($uid,'rosSurgeGo',false);
    if($fromZone==='Arsenal')FaBARCSetCard($uid,'faceUp',intval($source->FaceDown??1)===0);
    $kind = FaBHasType($source, 'Attack') ? 'ATTACK'
        : (FaBHasType($source, 'Defense Reaction') ? 'DEFENSE_REACTION'
        : (FaBHasType($source, 'Attack Reaction') ? 'ATTACK_REACTION'
        : (FaBHasType($source, 'Instant') || FaBARCAsInstant($player,$source) ? 'INSTANT' : 'ACTION')));
    $dashPlay=$fromZone==='Deck'&&FaBEVODashTop($player,$source);FaBARCSetCard($uid,'evoDashPlay',$dashPlay);
    $source->removed = true;
    if(FaBMSTStolen($player,$found))FaBARCSetCard($uid,'mstFree',true);
    if(intval($source->Owner??0)<1)$source->Owner=$found['player'];
    $playParams = $attackTarget === null ? [] : ['attackTarget'=>$attackTarget];
    if ($lungingTargetUID > 0) {
        $playParams['cruLungingTargetUID'] = $lungingTargetUID;
        $playParams['cruLungingTargetChainLink'] = $lungingTargetChainLink;
    }
    $stackObj = AddStack(CardID:$source->CardID, Controller:$player, Kind:$kind, SourceZone:$fromZone,
        SourceUniqueID:$uid, Params:$playParams, sourceObject:$source);
    $stackObj->Controller = $player; $stackObj->Kind = $kind; $stackObj->SourceZone = $fromZone; $stackObj->SourceUniqueID = $uid;
    // This direct play path does not invoke the ordinary Banish-to-Stack move hook.
    if ($fromZone === 'Banish') {
        $departureState = FaBGetState();
        unset($departureState['dtdPermissions'][$uid]);
        FaBSetState($departureState);
    }
    $state['combatPlaySequence'] = intval($state['combatPlaySequence'] ?? 0) + 1;
    $stackObj->Counters = FaBObjectCounters($stackObj);
    $stackObj->Counters['FAB_PLAY_ORDER'] = $state['combatPlaySequence'];
    if (function_exists('QueueZoneMoveAnimation')) QueueZoneMoveAnimation($found['mzID'], 'Stack-' . intval($stackObj->mzIndex), 360, true, $uid, $uid);
    $state = FaBGetState();
    $state['pendingPayment'] = ['player' => $player, 'uid' => $uid, 'cost' => FaBCardCost($stackObj, $player), 'fromZone' => $fromZone,
        'kind' => $kind, 'returnWindow' => (string)$state['window'], 'returnCombatStep' => (string)$state['combatStep']];
    if (in_array((string)$stackObj->CardID, ['tome_of_harvests_blue','rouse_the_ancients_blue','nimble_strike_red','nimble_strike_yellow','nimble_strike_blue','celestial_cataclysm_yellow','dread_screamer_red','dread_screamer_yellow','dread_screamer_blue'], true) || FaBHVYUsesBeatChestCost((string)$stackObj->CardID) || FaBELEPreparedFusionElements((string)$stackObj->CardID) !== []) {
        $preparedVersions = GetVersions($player); $preparedUndo = $preparedVersions[0] ?? null;
        $state['pendingPayment']['preparedEffectUndoHash'] = is_object($preparedUndo) ? hash('sha256', (string)$preparedUndo->Version) : '';
        $state['pendingPayment']['preparedEffectCardID'] = (string)$stackObj->CardID;
    }
    $state['window'] = 'PITCH'; FaBSetState($state);
    FaBELEInitializeBrackenPlay($player,$stackObj);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
    if (FaBWTRBase((string)$stackObj->CardID) === 'flock_of_the_feather_walkers') {
        $versions = GetVersions($player); $undo = $versions[0] ?? null;
        $state = FaBGetState();
        $state['pendingPayment']['wtrFlockStage'] = 'ASSETS';
        $state['pendingPayment']['wtrFlockUndoHash'] = is_object($undo) ? hash('sha256', (string)$undo->Version) : '';
        FaBSetState($state);
        // Preserve the existing externally granted Boost preparation. Its known
        // effect-cost ordering issue is separate from Flock's reveal cost.
        if (FaBMSTPrepareBoost($player, $stackObj)) return true;
        return FaBTryCompletePayment();
    }
    if(FaBMSTPrepareBoost($player,$stackObj))return true;
    if(FaBARCPrepareMissingBoost($player,$stackObj))return true;
    // Palantir's Cog-tap preparation is an activated-ability cost, not its attack cost.
    if (($kind !== 'ATTACK' || (string)$stackObj->CardID !== 'palantir_aeronought_red')
        && FaBRunSourceMacro('PrepareCard', $player, $stackObj->CardID, ['mzID'=>'Stack-'.intval($stackObj->mzIndex)]) > 0) return true;
    return FaBTryCompletePayment();
}

function FaBEnergyCounters($obj): int { return intval(FaBObjectCounters($obj)['ENERGY']??0); }
function FaBSteamCounters($obj): int { return intval(FaBObjectCounters($obj)['STEAM']??0); }
function FaBDefenseCounters($obj): int { return -intval(FaBObjectCounters($obj)['DEFENSE']??0); }
function FaBPowerCounters($obj): int { return intval(FaBObjectCounters($obj)['POWER']??0); }
function FaBDisplayCombatPower($obj): int {
    $state=FaBGetState();
    return ($obj->Role??'')==='ATTACK' && intval($obj->UniqueID)===intval($state['attackUID']) ? FaBAttackPower($state) : -1;
}
function FaBDisplayGoAgain($obj): int {
    $state = FaBGetState();
    return !empty($state['combatOpen']) && ($obj->Role ?? '') === 'ATTACK'
        && intval($obj->UniqueID) === intval($state['attackUID'])
        && FaBAttackHasGoAgain($state, $obj) ? 1 : 0;
}
/** Regression assertion hook for the live active attack only. */
function ObjectCurrentPower($obj): ?int {
    if (!is_object($obj) || !empty($obj->removed)) return null;
    $state = FaBGetState();
    if (empty($state['combatOpen']) || ($obj->Role ?? '') !== 'ATTACK'
        || intval($obj->UniqueID ?? 0) !== intval($state['attackUID'] ?? 0)) return null;
    return FaBAttackPower($state);
}

/** Optional regression hook: only the current attack has a go-again value. */
function ObjectCurrentGoAgain($obj): ?int {
    $state = FaBGetState();
    if (!empty($obj->removed) || empty($state['combatOpen']) || ($obj->Role ?? '') !== 'ATTACK'
        || intval($obj->UniqueID ?? 0) !== intval($state['attackUID'])) return null;
    return FaBDisplayGoAgain($obj);
}

function FaBDisplayCombatDefense($obj): int {
    return in_array($obj->Role??'',['DEFENSE','DEFENSE_REACTION'],true) ? FaBCurrentDefense($obj,intval($obj->Controller??$obj->Owner??0)) : -1;
}
function FaBDisplayCombatTotals($obj): array {
    $state = FaBGetState();
    if (($obj->Role ?? '') !== 'ATTACK' || intval($obj->UniqueID) !== intval($state['attackUID'])) return [];
    $blocks = [];
    foreach ($state['attackTargets'] ?? [$state['attackTarget'] ?? ['type'=>'HERO', 'player'=>$state['defender']]] as $target) {
        $seat = intval($target['player'] ?? $state['defender']);
        $blocks[(string)$seat] = ($target['type'] ?? 'HERO') === 'HERO' ? FaBDefenseValue($state, $seat) : 0;
    }
    return ['attack'=>FaBAttackPower($state), 'blocks'=>$blocks];
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
    if(!empty($pending['arcBoostError'])||!empty($pending['arcBoostAssetsPaid']))return false;
    $source=FaBFindUID(intval($pending['uid']));
    if ($source !== null && ((string)$source['object']->CardID === 'aethersling_red'
        || FaBUPRUsesDeclaredSandTarget((string)$source['object']->CardID))
        && !empty($pending['preparedEffectError'])) return false;
    if($source!==null&&FaBWTRNeedsDiscard($source['object']->CardID)&&FaBHandCount(intval($player))<=1)return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    return $found !== null && $found['player'] === intval($player) && $found['zone'] === 'Hand'
        && !FaBARCNamedProhibited($found['object']->CardID) && !FaBHNTNamed($found['object']->CardID) && FaBOUTCanPitch(intval($player),$found['object']) && FaBELECanPitch(intval($player),$found['object'])
        && FaBMSTCanPitch(intval($player),$found['object'],$pending) && max(0, intval(CardPitch($found['object']->CardID))) > 0
        && FaBWTRFlockPitchPreservesCost(intval($player), $pending, $found['object'])
        && FaBPreparedEffectCostCanPitch($pending, $found['object']);
}

function DoPitchCard($player, $mzID) {
    if (!CanPitchCard($player, $mzID)) return false;
    $found = FaBIdentityFromMZ((string)$mzID); if ($found === null) return false;
    FaBPENRecordPitch(intval($player),$found['object']);
    FaBELERecordPitch(intval($player),$found['object']->CardID);
    FaBDYNRecordPitch(intval($player),$found['object']->CardID);
    $pitch = FaBEVRPitch(intval($player),FaBMONPitchValue(intval($player),$found['object']->CardID),true);
    $pitchedCardID = (string)$found['object']->CardID;
    $uid = intval($found['object']->UniqueID); FaBMoveUID($uid, 'Pitch', intval($player));
    AddResources(intval($player), intval(GetResources(intval($player))) + $pitch, 'PITCH');
    FaBMSTPitch(intval($player),$pitchedCardID,$pitch);
    if (function_exists('FaBWTRCardPitched')) FaBWTRCardPitched(intval($player), $pitchedCardID);
    FaBRunSourceMacro('CardPitched',intval($player),$pitchedCardID,['mzID'=>FaBFindUID($uid)['mzID']]);
    FaBIARPitched(intval($player),$uid);
    if(FaBHasPendingDecision())return true;
    return FaBTryCompletePayment();
}

function FaBTryCompletePayment(): bool {
    if(intval($GLOBALS['fabBoostCostDepth']??0)>0)return true;
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending)) return false;
    if (!FaBPreparedEffectCostMayComplete($pending)) return true;
    if (!empty($pending['preparedEffectError']) || in_array((string)($pending['preparedEffectCost']['stage'] ?? ''), ['PAYING_ASSETS','PAYING_EFFECTS'], true)) return true;
    if(!empty($pending['arcBoostError'])){FaBARCRollbackBoostPayment($pending,(string)$pending['arcBoostError']);return true;}
    $player = intval($pending['player']); $cost = max(0, intval($pending['cost']));
    if (isset($pending['wtrFlockStage'])) {
        if ($pending['wtrFlockStage'] !== 'COMPLETE') return FaBWTRAdvanceFlockPayment();
        if (!FaBWTRFlockPaymentIsComplete($pending)) return FaBWTRRollbackFlockPayment($pending, 'cost completion is invalid');
    } else {
        if (!empty($pending['preparedEffectCost']['assetsPaid'])) {
            if (!FaBPreparedEffectCostAssetsArePaid($pending)) { FaBPreparedEffectCostRollback($pending, 'asset cost changed after payment'); return true; }
        } elseif(!empty($pending['arcBoostAssetsPaid'])){
            if(intval($pending['arcBoostChargedCost']??-1)!==$cost){FaBARCRollbackBoostPayment($pending,'asset cost changed after payment');return true;}
        }else{
            if (FaBMSTChi($player)<FaBMSTPendingChi($pending)||intval(GetResources($player)) < $cost) return true;
            $paysAction=in_array((string)$pending['kind'], ['ACTION', 'ATTACK'], true)||!empty($pending['abilityAction']);
            if(intval($pending['arcBoostDeclaredCount']??0)>0&&$paysAction&&intval(GetActionPoints($player))<1){FaBARCRollbackBoostPayment($pending,'action cost cannot be paid');return true;}
            if (!FaBPreparedEffectCostBeginAssets($pending)) return true;
            AddResources($player, intval(GetResources($player)) - $cost);
            if ($paysAction) AddActionPoints($player, max(0, intval(GetActionPoints($player)) - 1));
            if(intval($pending['arcBoostDeclaredCount']??0)>0){
                $state=FaBGetState();$state['pendingPayment']['arcBoostAssetsPaid']=true;$state['pendingPayment']['arcBoostChargedCost']=$cost;FaBSetState($state);
            }
        }
    }
    if (!FaBPreparedEffectCostMarkAssetsPaid($cost)) return true;
    if(intval($pending['arcBoostDeclaredCount']??0)>0){
        if(!FaBARCPayDeferredBoostCosts())return true;
        // Boost callbacks update turnEffects and may grant resources/AP. Never
        // overwrite them with the state captured before cost payment.
        $state=FaBGetState();$pending=$state['pendingPayment'];
        if(!is_array($pending))return true;
    }
    if (isset($pending['preparedEffectCost'])) {
        if (!FaBPayDeclaredPreparedEffectCost()) return true;
        $state = FaBGetState(); $pending = $state['pendingPayment'] ?? null;
        if (!is_array($pending)) return true;
    }
    $weaponUID = intval($pending['weaponUID'] ?? 0);
    if ($weaponUID > 0) {
        $weapon = FaBFindUID($weaponUID);
        if ($weapon !== null && in_array($weapon['zone'],['Weapons','Arena','Equipment','Hero'],true)) FaBCRUUseWeapon($weapon['object']);
        if($weapon!==null&&$weapon['object']->CardID==='spectral_shield')FaBMSTAdd($player,'SHIELD_ATTACK');
        if ($weapon !== null && in_array($weapon['object']->CardID,['teklo_plasma_pistol','plasma_barrel_shot','symbiosis_shot'],true)) FaBARCSteam($weapon['object'],-1);
        if($weapon!==null&&$weapon['object']->CardID==='talishar_the_lost_prince')FaBSetObjectCounter($weapon['object'],'RUST',intval(FaBObjectCounters($weapon['object'])['RUST']??0)+1);
    }
    if($weaponUID>0){$w=FaBFindUID($weaponUID);if($w&&$w['object']->CardID==='hanabi_blaster')FaBARCSteam($w['object'],-2);}
    // Payment and weapon-use hooks may write turn history (e.g. the first
    // Spectral Shield attack). Complete payment from their current state.
    $state = FaBGetState();
    $livePending = $state['pendingPayment'] ?? null;
    if (!is_array($livePending) || intval($livePending['uid'] ?? 0) !== intval($pending['uid'])) return true;
    $pending = $livePending;
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
            FaBELEActivated($player,$found['object']);
            FaBSetObjectCounter($found['object'],'WEAPON_UID',$weaponUID);
            FaBIARApplyNext($player,$found['object']);FaBOMNApplyNext($player,$found['object']);FaBWTRCardPlayed($player,$found['mzID'],$found['object']->CardID,'Weapons');
        } elseif(!empty($pending['isArenaAttack'])){
            FaBSetObjectCounter($found['object'],'WEAPON_UID',$weaponUID);
            FaBIARAllyAttacks($player,$found['object']);FaBIARApplyNext($player,$found['object']);FaBMONAdd($player,'ALLY_ATTACKS');if($found['object']->CardID==='spectral_shield')FaBMSTAdd($player,'SHIELD_ATTACK');FaBDTDPlayed($player,$found['object'],'Arena');FaBEVOPlayed($player,$found['object'],'Arena');FaBHNTApplyNextAttack($player,$found['object']);FaBSEAPlayed($player,$found['object'],'Arena');
            FaBELEActivated($player,$found['object']);
        } elseif (!empty($pending['isAbility'])) {
            FaBWTRPayAbilityCosts($player,$found['object']);
        } else {
            FaBWTRPayAdditionalCosts($player, $found['object']);
            OnCardPlayed($player, $found['mzID'], $found['object']->CardID, (string)$pending['fromZone']);
        }
    }
    $priorityPreferences = GetShortcutPreferencesState($player);
    SetPriorityPlayer(!empty($priorityPreferences['holdPriority']) ? $player : FaBNextSeat($player));
    SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
    return true;
}

function FaBReactionWindowForState(array $state): bool { return !empty($state['combatOpen']) && $state['combatStep'] === 'REACTION'; }

/** A reaction may be announced after a higher layer resolves (CR 7.4.2-3).
 * PRIORITY is the stack-window label; combatStep retains the Reaction Step.
 * Require the live active attack and completed continuations before exposing it.
 */
function FaBReactionPriorityForState(array $state): bool {
    if (!FaBReactionWindowForState($state) || GetCurrentPhase() !== 'MAIN'
        || !in_array($state['window'] ?? '', ['REACTION', 'PRIORITY'], true)
        || ($state['pendingPayment'] ?? null) !== null
        || ($state['pendingAttackTarget'] ?? null) !== null
        || FaBReactionResolutionIsPending() || FaBReactionResolutionHasWork()) return false;
    $attacker = intval($state['attacker'] ?? 0);
    $uid = intval($state['attackUID'] ?? 0); $link = intval($state['chainLink'] ?? 0);
    if (!FaBSeatIsLive($attacker) || $uid <= 0 || $link <= 0) return false;
    $attack = FaBFindUID($uid);
    if ($attack === null || $attack['zone'] !== 'CombatChain'
        || !empty($attack['object']->removed) || ($attack['object']->Role ?? '') !== 'ATTACK'
        || intval($attack['object']->Controller ?? 0) !== $attacker
        || intval($attack['object']->ChainLink ?? 0) !== $link) return false;
    // Empty-stack reaction priority uses the existing REACTION window.
    return $state['window'] === 'REACTION' || FaBStackTop() !== null;
}

function OnCardPlayed($player, $mzID, $cardID, $fromZone) {
    $omnPlayed = FaBIdentityFromMZ((string)$mzID);
    if ($omnPlayed) { FaBOMNPlayed(intval($player), $omnPlayed['object']); FaBIARPlayed(intval($player), $omnPlayed['object'], (string)$fromZone); }
    $params = ['mzID' => $mzID, 'cardID' => $cardID, 'fromZone' => $fromZone];
    if (function_exists('FaBWTRCardPlayed')) FaBWTRCardPlayed(intval($player), (string)$mzID, (string)$cardID, (string)$fromZone);
    $count = FaBRunSourceMacro('CardPlayed', intval($player), (string)$cardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('CardPlayed', intval($player), $params) : 0);
}

function OnAttackDeclared($player, $mzID, $attacker, $defender) {
    $params = compact('mzID', 'attacker', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if($found){FaBOMNDeclared(intval($player),$found['object']);FaBPENDeclared(intval($player),$found['object']);FaBHVYAttackDeclared(intval($player),$found['object']);FaBMSTDeclared(intval($player),$found['object']);FaBROSDeclared(intval($player),$found['object']);FaBHNTDeclared(intval($player),$found['object']);FaBSEADeclared(intval($player),$found['object']);FaBSUPDeclared(intval($player),$found['object']);}
    if ($found !== null && function_exists('FaBWTRAttackDeclared')) FaBWTRAttackDeclared(intval($player), $found['object'], intval($defender));
    if($found!==null)FaBCRUAttack(intval($player),$found['object']);
    if($found!==null)FaBMONAttack(intval($player),$found['object']);
    if($found!==null)FaBEVRAttack(intval($player),$found['object']);
    if($found!==null)FaBELEAttack(intval($player),$found['object']);
    if($found!==null)FaBUPRAttack(intval($player),$found['object']);
    if($found!==null)FaBDYNAttack(intval($player),$found['object']);
    if($found!==null)FaBEVRFractalDispatch(intval($player),$found['object'],'AttackDeclared',['mzID'=>$mzID,'attacker'=>$attacker,'defender'=>$defender]);
    if ($found === null) $count = 0;
    elseif ((string)$found['object']->CardID === 'flood_of_force_yellow')
        $count = FaBCRUQueueFloodAttack(intval($player), $found['object'], $params);
    elseif (in_array((string)$found['object']->CardID, ['lay_to_rest_red','lay_to_rest_yellow','lay_to_rest_blue'], true)) {
        $count = FaBDTDQueueLayAttack(intval($player), $found['object'], $params);
        if ($count === 0) $count = FaBRunSourceMacro('AttackDeclared', intval($player), $found['object']->CardID, $params);
    }
    else $count = in_array((string)$found['object']->CardID, ['suraya_archangel_of_erudition','head_leads_the_tail_red','deathly_duet_red','deathly_duet_yellow','deathly_duet_blue'], true)
        ? FaBQueueOwnedEventMacro(intval($player), $found['object'], 'AttackDeclared', $params)
        : FaBRunSourceMacro('AttackDeclared', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('AttackDeclared', intval($player), $params) : 0);
}

function OnDefended($player, $mzID, $defender) {
    $unforgetAttacker = intval(FaBGetState()['attacker']);
    $params = compact('mzID', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found) FaBOMNDefended($found['object']);
    if ($found !== null && function_exists('FaBWTRDefended')) FaBWTRDefended(intval($player), $found['object']);if($found)FaBSEADefended(intval($player),$found['object']);if($found)FaBMPGDefended(intval($player),$found['object']);if($found)FaBSUPDefended(intval($player),$found['object']);
    if($found!==null){FaBDTDDefended(intval($player),$found['object']);FaBHVYDefended($found['object']);FaBMSTDefended(intval($player),$found['object']);FaBOUTDefended(intval($player),$found['object']);FaBCRUDefended(intval($player),$found['object']);FaBDYNDefended(intval($player),$found['object']);FaBMONDefended($found['object']);FaBELEDefended(intval($player),$found['object']);if($found['object']->CardID==='stalagmite_bastion_of_isenloft')FaBELEFrost(intval(FaBGetState()['attacker']));if($found['object']->CardID==='fractal_replication_red')FaBEVRFractalCopy(intval($player),$found['object']);}
    if($found)FaBPENLunar($found['object']);
    $count = $found === null ? 0 : ((string)$found['object']->CardID === 'unforgetting_unforgiving_red'
        ? FaBQueueOwnedEventMacro(intval($player), $found['object'], 'Defended', $params + ['supUnforgetAttacker'=>$unforgetAttacker])
        : FaBRunSourceMacro('Defended', intval($player), $found['object']->CardID, $params));
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Defended', intval($player), $params) : 0);
}

function OnHit($player, $mzID, $amount) {
    if(FaBSUPGallowStops(intval($player)))return 0;
    $params = compact('mzID', 'amount'); $found = FaBIdentityFromMZ((string)$mzID);
    if($found!==null&&FaBHasType($found['object'],'Dagger')){$s=FaBGetState();$s['daggerHits']=intval($s['daggerHits']??0)+1;FaBSetState($s);}
    if($found!==null&&FaBFaiHeroHit())FaBHNTBeforeHit(intval($player),$found['object'],intval(FaBGetState()['defender']));
    if($found!==null)FaBHNTAnyHit(intval($player),$found['object']);
    if($found!==null)FaBOUTSuppressHit($found['object']);
    $queuedPureTokenHit = $found !== null
        && FaBWTRPureTokenHitSpec((string)$found['object']->CardID) !== null;
    if ($found !== null && function_exists('FaBWTRHit')) FaBWTRHit(intval($player), $found['object'], intval($amount));
    if($found!==null&&FaBCRUHitSuppressed($found['object'],true))return 0;
    if($found!==null){FaBIARHit(intval($player),$found['object']);FaBOMNHit(intval($player),$found['object']);FaBSUPHit(intval($player),$found['object']);FaBPENHit(intval($player));FaBMPGHit(intval($player),$found['object'],intval($amount));FaBSEAHit(intval($player),$found['object'],intval($amount));FaBCRUHit(intval($player),$found['object'],intval($amount));FaBMONHit(intval($player),$found['object']);FaBELEHit(intval($player),$found['object'],intval($amount));FaBEVRHit(intval($player),$found['object'],intval($amount));FaBDYNHit(intval($player),$found['object'],intval($amount));FaBOUTHit(intval($player),$found['object'],intval($amount));FaBDTDHit(intval($player),$found['object'],intval($amount));FaBEVOHit(intval($player),$found['object'],intval($amount));FaBHVYHit(intval($player),$found['object'],intval($amount));FaBMSTHit(intval($player),$found['object']);FaBROSHit(intval($player),$found['object']);if(FaBFaiHeroHit())FaBHNTHit(intval($player),$found['object'],intval(FaBGetState()['defender']));FaBEVRFractalDispatch(intval($player),$found['object'],'Hit',['mzID'=>$mzID,'amount'=>$amount]);if(intval($amount)>0)FaBBoltynHit(intval($player));}
    // Effects such as Seek Enlightenment can move the attack before its own hit ability.
    if($found!==null){$live=FaBFindUID(intval($found['object']->UniqueID));if($live!==null)$params['mzID']=$live['mzID'];}
    // These printed hit abilities are queued by FaBWTRHit; keep generic listeners below.
    $count = $found === null || FaBCRUHitSuppressed($found['object'])
        || $found['object']->CardID === 'command_and_conquer_red' || $queuedPureTokenHit ? 0
        : ($found['object']->CardID === 'drowning_dire_red'
            ? FaBQueueOwnedEventMacro(intval($player), $found['object'], 'Hit', $params)
            : FaBRunSourceMacro('Hit', intval($player), $found['object']->CardID, $params));
    $count += FaBDispatchOwnedHitListeners(intval($player), $params);
    if ($found !== null) FaBARCAfterHit(intval($player), $found['object'], intval($amount));
    if($found!==null&&in_array('WTR_RETURN_HAND',(array)$found['object']->TurnEffects,true)){
        $state=FaBGetState();$state['attackGoAgain']=FaBAttackHasGoAgain($state,$found['object']);FaBSetState($state);
        FaBMoveUID(intval($found['object']->UniqueID),'Hand',intval($player));
    }
    return $count;
}

function OnChainLinkResolved($player, $mzID) {
    $params = compact('mzID'); $found = FaBIdentityFromMZ((string)$mzID);
    if($found){FaBOMNRepeatAttack(intval($player),intval($found['object']->UniqueID));FaBHVYResolveWagers(intval($player),intval($found['object']->UniqueID));FaBMSTChainResolved(intval($player),$found['object']);FaBROSChainEnd();}
    $count = $found === null ? 0 : FaBRunSourceMacro('ChainLinkResolved', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('ChainLinkResolved', intval($player), $params) : 0);
}

function OnCombatChainClosed($player) {
    FaBOMNClose();FaBPENClose();
    return function_exists('DispatchMacroListeners') ? DispatchMacroListeners('CombatChainClosed', intval($player), []) : 0;
}

function FaBRunSourceMacro(string $macroName, int $player, string $cardID, array $params): int {
    $sourceRef=FaBIdentityFromMZ((string)($params['mzID']??''));
    $grantedBoost=$sourceRef&&FaBARCGrantedBoostMacro($sourceRef['object'],$macroName,$player,$cardID,$params);
    if($sourceRef&&!$grantedBoost&&(FaBMPGSuppressed($sourceRef['object'])||FaBSUPSuppressed($sourceRef['object']))){if($macroName==='PrepareCard'){FaBFinishPreparedCard(intval($sourceRef['object']->UniqueID));return 1;}if(!str_ends_with($macroName,'Modifier'))return 1;}
    // These Cog costs belong to the activated abilities, not ordinary attack plays.
    if ($macroName === 'PrepareCard'
        && in_array($cardID, ['backspin_thrust_red','cloud_skiff_blue','sky_skimmer_blue','tip_off_blue','cloud_city_steamboat_red','cogwerx_zeppelin_red'], true)
        && $sourceRef !== null && $sourceRef['zone'] === 'Stack'
        && empty($sourceRef['object']->removed)
        && (string)$sourceRef['object']->CardID === $cardID
        && (string)($sourceRef['object']->Kind ?? '') === 'ATTACK'
        && intval($sourceRef['object']->Controller ?? 0) === $player) {
        $pending = FaBGetState()['pendingPayment'] ?? null;
        if (is_array($pending) && empty($pending['isAbility'])
            && empty($pending['isWeaponAttack']) && empty($pending['isArenaAttack'])
            && (string)($pending['kind'] ?? '') === 'ATTACK'
            && intval($pending['player'] ?? 0) === $player
            && intval($pending['uid'] ?? 0) > 0
            && intval($pending['uid']) === intval($sourceRef['object']->UniqueID ?? 0)) return 0;
    }
    // Local missing-Prepare compatibility; canonical High Roller calls the same helper.
    if ($macroName === 'PrepareCard' && FaBEVRHighRollerCard($cardID) && $sourceRef !== null
        && (string)$sourceRef['object']->CardID === $cardID && CardPrepareCardCount($cardID) === 0) {
        FaBEVRPrepareHighRoller($player,intval($sourceRef['object']->UniqueID));
        return 1;
    }

    // Current generated card code predates these PrepareCard declarations; canonical bodies call the same helper.
    if ($macroName === 'PrepareCard' && $sourceRef !== null && FaBUsesDeclaredReactionTarget($cardID)) {
        FaBPrepareDeclaredReaction($player, $sourceRef['object']);
        return 1;
    }
    // Native/canonical profiles bind declarations even without a generated body.
    if ($macroName === 'ResolveCard' && $sourceRef !== null
        && (string)$sourceRef['object']->CardID === $cardID && FaBUsesDeclaredReactionTarget($cardID)) {
        foreach ($params as $name => $value) DecisionQueueController::StoreVariable($name, $value);
        $previousEffect = $GLOBALS['fabEffectController'] ?? 0; $GLOBALS['fabEffectController'] = $player;
        try { FaBResolveDeclaredReaction($player, $sourceRef['object']); }
        finally { $GLOBALS['fabEffectController'] = $previousEffect; }
        return 1;
    }
    // Tip Off's target is declared before its physical Hand source is discarded as cost.
    if ($macroName === 'PrepareCard' && $cardID === 'tip_off_blue' && $sourceRef !== null
        && CardPrepareCardCount($cardID) === 0) {
        FaBHNTPrepareTipBridge($player, intval($sourceRef['object']->UniqueID));
        return 1;
    }
    // Captain's announcement mode, before assets; canonical Prepare takes over after publication.
    if ($macroName === 'PrepareCard' && $cardID === 'captains_call_red' && $sourceRef !== null
        && CardPrepareCardCount($cardID) === 0) {
        FaBMONPrepareCaptainBridge($player, intval($sourceRef['object']->UniqueID));
        return 1;
    }
    // Memorial's formal Graveyard target is declared before assets/response priority.
    if ($macroName === 'PrepareCard' && $cardID === 'memorial_ground_blue' && $sourceRef !== null
        && CardPrepareCardCount($cardID) === 0) {
        FaBMONPrepareMemorialBridge($player, intval($sourceRef['object']->UniqueID));
        return 1;
    }
    // Local Prepare compatibility for the one native-confirmed new Aethersling declaration.
    if ($macroName === 'PrepareCard' && $cardID === 'aethersling_red' && $sourceRef !== null
        && CardPrepareCardCount($cardID) === 0) {
        FaBOMNAetherslingPrepareBridge($player, intval($sourceRef['object']->UniqueID));
        return 1;
    }
    // Exact Sand Cover cohort: declare the Ash before normal payment/priority.
    if ($macroName === 'PrepareCard' && FaBUPRUsesDeclaredSandTarget($cardID)
        && $sourceRef !== null && $sourceRef['object']->CardID === $cardID) {
        FaBUPRPrepareSandTarget($player, $sourceRef['object']);
        return 1;
    }
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
        $dreadCall = $macroName === 'PrepareCard' && $i === 0 && FaBDreadPreparedCostCard($cardID);
        $dreadContext = FaBDreadPrepareCostContext($macroName, $player, $cardID, $i, $sourceRef);
        if ($dreadCall && $dreadContext === null) {
            $pending = FaBGetState()['pendingPayment'] ?? null;
            if (is_array($pending) && ($pending['preparedEffectCardID'] ?? '') === $cardID
                && intval($pending['player'] ?? 0) === $player) {
                FaBPreparedEffectCostRollback($pending, 'Dread Prepare source context changed');
            }
            ++$ran; continue;
        }
        $hadDreadContext = array_key_exists('fabDreadPrepareCostContext', $GLOBALS);
        $previousDreadContext = $GLOBALS['fabDreadPrepareCostContext'] ?? null;
        // Nested, unrelated callbacks never inherit the outer Prepare admission.
        $GLOBALS['fabDreadPrepareCostContext'] = $dreadContext;
        $previousEffect=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$player;
        try {
            if ($macroName === 'ResolveCard' && $cardID === 'lunging_press_blue' && $i === 0) {
                if ($sourceRef !== null) FaBCRUResolveLunging($player, $sourceRef['object']);
            } elseif ($macroName === 'ResolveCard' && in_array($cardID, ['embolden_red','embolden_yellow','embolden_blue'], true) && $i === 0) {
                // Its actual Arena-entry observer owns the earned draw; never run the old inline body.
            } elseif ($macroName === 'ResolveCard' && $cardID === 'gorganian_tome' && $i === 0) {
                if ($sourceRef !== null) FaBCRUResolveGorganian($player, $sourceRef['object']);
            } elseif ($macroName === 'ResolveCard' && FaBUPRUsesDeclaredSandTarget($cardID) && $i === 0
                && $sourceRef !== null && $sourceRef['object']->CardID === $cardID) {
                FaBUPRResolveSandTarget($player, $sourceRef['object']);
            } elseif ($macroName === 'ResolveCard' && FaBMONArcanicCard($cardID) && $i === 0
                && $sourceRef !== null && $sourceRef['zone'] === 'CombatChain'
                && $sourceRef['object']->CardID === $cardID) {
                FaBMONQueueArcanicAttack($player, $sourceRef['object']);
            } elseif ($macroName === 'AttackDeclared' && $i === 0
                && in_array($cardID, ['assault_and_battery_red','assault_and_battery_yellow','assault_and_battery_blue'], true)) {
                if ($sourceRef !== null && $sourceRef['zone'] === 'CombatChain'
                    && (string)$sourceRef['object']->CardID === $cardID) FaBHVYQueueAssaultAttack($player, $sourceRef['object']);
            } elseif ($macroName === 'AttackDeclared' && $i === 0
                && in_array($cardID, ['pulping_red','pulping_yellow','pulping_blue'], true)) {
                if ($sourceRef !== null && $sourceRef['zone'] === 'CombatChain'
                    && (string)$sourceRef['object']->CardID === $cardID) FaBMONQueuePulpingAttack($player, $sourceRef['object']);
            } elseif ($macroName === 'Defended' && $cardID === 'smoke_out_red' && $i === 0
                && $sourceRef !== null && $sourceRef['object']->CardID === $cardID) {
                FaBHNTQueueSmokeDefended($player, $sourceRef['object']);
            } elseif ($macroName === 'Hit' && $cardID === 'kiss_of_death_red' && $i === 0
                && $sourceRef !== null && $sourceRef['object']->CardID === $cardID) {
                FaBHNTQueueKissHeroHit($player, $sourceRef['object'], intval($params['amount'] ?? 0));
            } elseif ($macroName === 'Hit' && FaBIARHellboundCard($cardID) && $i === 0
                && $sourceRef !== null && $sourceRef['object']->CardID === $cardID) {
                FaBIARQueueHellboundHit($player, $sourceRef['object'], intval($params['amount'] ?? 0));
            } else $abilities[$key]($player);
            ++$ran;
        } finally {
            $finishedDreadContext = $GLOBALS['fabDreadPrepareCostContext'] ?? null;
            $GLOBALS['fabEffectController']=$previousEffect;
            if ($hadDreadContext) $GLOBALS['fabDreadPrepareCostContext'] = $previousDreadContext;
            else unset($GLOBALS['fabDreadPrepareCostContext']);
            if ($dreadCall && !empty($finishedDreadContext['error'])) {
                FaBPreparedEffectCostRollback($dreadContext['pending'], (string)$finishedDreadContext['error']);
            }
        }
    }
    return $ran;
}

/** Generated await continuations must receive PASS as a return value. */
function GameCustomHandlerRunsOnPass(string $handlerName): bool {
    // Native CUSTOM handlers retain the normal skip-on-PASS behavior. Only the
    // generator's cardID:index:MacroName-await contract opts into continuation.
    return preg_match('/^[a-z0-9_]+:[0-9]+:(?:Listener:)?[A-Za-z_][A-Za-z0-9_]*-await$/D', $handlerName) === 1
        && is_callable($GLOBALS['customDQHandlers'][$handlerName] ?? null);
}

/**
 * An attack reaction remains on the stack until all its resolution effects finish.
 * The marker is serialized; the depth guard prevents re-entry during the initial
 * callback and the final zone-move hooks in this request.
 */
function FaBReactionResolutionIsPending(): bool {
    return intval($GLOBALS['fabReactionResolutionDepth'] ?? 0) > 0
        || is_array(FaBGetState()['resolvingReaction'] ?? null);
}

function FaBReactionResolutionHasWork(): bool {
    if (intval($GLOBALS['fabReactionResolutionDepth'] ?? 0) > 0) return true;
    // FaBHasPendingDecision deliberately ignores static rows. Clearing a source
    // must wait for those rows as well, and for every seat rather than just live seats.
    foreach (FaBSeatOrder() as $seat) {
        if (count(GetDecisionQueue($seat)) > 0) return true;
    }
    $variables = json_decode((string)GetDecisionQueueVariables(), true);
    return is_array($variables) && !empty($variables['__awaitFrames']);
}

/** Read frame ownership without claiming unrelated or legacy await work. */
function FaBTrackReactionAwaitFrames(): void {
    $state = FaBGetState();
    $pending = $state['resolvingReaction'] ?? null;
    if (!is_array($pending) || !isset($pending['baselineFrameKeys'], $pending['cardID'], $pending['sourceMZ'])) return;
    $variables = json_decode((string)GetDecisionQueueVariables(), true);
    $owned = (array)($pending['ownedFrameKeys'] ?? []);
    $abilityFrame = in_array((string)($pending['resolutionClass'] ?? ''), ['ARC_ABILITY','ROS_ABILITY','ROS_EVENT'], true)
        && ($pending['kind'] ?? '') === 'ABILITY';
    $frameMacro = $abilityFrame ? (string)($pending['frameMacro'] ?? 'ResolveAbility') : 'ResolveCard';
    $pattern = '/^' . preg_quote((string)$pending['cardID'], '/') . ':[0-9]+:'.preg_quote($frameMacro, '/').'#[0-9]+$/D';
    foreach ((array)($variables['__awaitFrames'] ?? []) as $key => $frame) {
        if (in_array($key, (array)$pending['baselineFrameKeys'], true)) continue;
        if (preg_match($pattern, (string)$key) !== 1) continue;
        $sourceLocal = $frameMacro === 'Listener:Hit' ? 'listenerMZ' : 'mzID';
        if (($frame['locals'][$sourceLocal] ?? null) !== $pending['sourceMZ']) continue;
        if (!in_array($key, $owned, true)) $owned[] = $key;
    }
    $state['resolvingReaction']['ownedFrameKeys'] = $owned;
    FaBSetState($state);
}

/** Exact canonical CUSTOM continuation -> existing frame reference. */
function FaBReactionQueuedAwaitFrame(object $row): ?string {
    if (($row->Type ?? '') !== 'CUSTOM') return null;
    $parts = explode('|', (string)($row->Param ?? ''));
    $handler = $parts[0] ?? '';
    if (preg_match('/^[a-z0-9_]+:[0-9]+:(?:Listener:)?[A-Za-z_][A-Za-z0-9_]*-await$/D', $handler) !== 1
        || !is_callable($GLOBALS['customDQHandlers'][$handler] ?? null)) return null;
    $frame = (string)($parts[1] ?? '');
    $stem = substr($handler, 0, -strlen('-await'));
    return preg_match('/^' . preg_quote($stem, '/') . '#[0-9]+$/D', $frame) === 1 ? $frame : null;
}

function FaBReactionEliminationDiagnostic(string $reason, string $frame, int $seat): void {
    $state = FaBGetState();
    if (!is_array($state['resolvingReaction'] ?? null)) return;
    // Stable keys make repeated cleanup checks bounded and visible to reproducers.
    $key = $reason . '|' . $seat . '|' . $frame;
    $state['resolvingReaction']['eliminationDiagnostics'][$key] = [
        'reason'=>$reason, 'frame'=>$frame, 'seat'=>$seat];
    FaBSetState($state);
}

/**
 * Cancel only a removed controller's precisely owned resolution. A surviving
 * controller's eliminated chooser is explicitly held for a separate rules fix;
 * no generic PASS resumption or frame purge can safely implement that case.
 */
function FaBPrepareReactionEliminationCleanup(): array {
    FaBTrackReactionAwaitFrames();
    $pending = FaBGetState()['resolvingReaction'] ?? null;
    if (!is_array($pending) || intval($GLOBALS['fabReactionResolutionDepth'] ?? 0) > 0) return [];
    $owned = (array)($pending['ownedFrameKeys'] ?? []);
    $controllerGone = !FaBSeatIsLive(intval($pending['controller'] ?? 0));
    foreach (FaBSeatOrder() as $seat) {
        $queue = &GetDecisionQueue($seat);
        $remove = [];
        foreach ($queue as $index => $row) {
            if (!is_object($row)) continue;
            $frame = FaBReactionQueuedAwaitFrame($row);
            if ($frame === null) continue;
            if (!in_array($frame, $owned, true)) {
                if (!FaBSeatIsLive($seat)) FaBReactionEliminationDiagnostic('unowned_dead_queue_frame_preserved', $frame, $seat);
                continue;
            }
            if (!$controllerGone) {
                if (!FaBSeatIsLive($seat)) FaBReactionEliminationDiagnostic('unsupported_live_controller_dead_chooser', $frame, $seat);
                continue;
            }
            $remove[$index] = true;
            // Current generated awaits enqueue one interactive decision followed
            // immediately by its CUSTOM continuation at the same block. Remove
            // that exact canonical pair, never an arbitrary neighboring row.
            $choice = $queue[$index - 1] ?? null;
            if (is_object($choice) && intval($row->Block ?? -1) === 1
                && intval($choice->Block ?? -2) === 1
                && in_array($choice->Type ?? '', ['MZCHOOSE','MZMAYCHOOSE','MZMULTICHOOSE',
                    'MZMODAL','MZREARRANGE','NUMBERCHOOSE','MZNUMBERCHOOSE','NAMECARD',
                    'MZNAMECARD','YESNO','MZSPLITASSIGN'], true)) $remove[$index - 1] = true;
        }
        if ($remove) $queue = array_values(array_filter($queue,
            fn($row, $index) => !isset($remove[$index]), ARRAY_FILTER_USE_BOTH));
        unset($queue);
    }
    return $controllerGone ? $owned : [];
}

/** Called after the existing eliminated-seat queues/objects have been removed. */
function FaBFinishReactionEliminationCleanup(array $candidates): void {
    if (intval($GLOBALS['fabReactionResolutionDepth'] ?? 0) > 0) return;
    foreach (array_values(array_unique($candidates)) as $frame) {
        $referenced = false;
        foreach (FaBLiveSeats() as $seat) {
            foreach (GetDecisionQueue($seat) as $row) {
                if (!is_object($row) || ($row->Type ?? '') !== 'CUSTOM') continue;
                $parts = explode('|', (string)($row->Param ?? ''));
                // Preserve even an unknown live handler's reference to this key.
                if (($parts[1] ?? null) === $frame) { $referenced = true; break 2; }
            }
        }
        if ($referenced) {
            FaBReactionEliminationDiagnostic('live_reference_preserved', $frame, $seat);
            continue;
        }
        if (DecisionQueueController::GetAwaitFrame($frame) !== null) DecisionQueueController::FinishAwaitFrame($frame);
    }
}

/** Complete a defense reaction only after its body and owned awaits have finished. */
function FaBFinishDefenseReactionLayer(array $pending, ?array $found): void {
    $controller = intval($pending['controller']); $uid = intval($pending['uid']);
    $source = $found !== null && $found['zone'] === 'Stack'
        ? $found['object'] : (object)($pending['completionSource'] ?? []);
    // CR 5.3.5 precedes leaving Stack, including go again granted to this layer.
    if (FaBSeatIsLive($controller) && !FaBSUPSuppressed($source)) {
        // Ordinary ability loss removes printed abilities, not a later grant.
        // SUP's explicit cannot-gain restriction still gates both branches.
        $base = (!HasNoAbilities($source)
            && FaBPrintedKeywordIsActive((string)$source->CardID, 'Go again'))
            || in_array('GO_AGAIN', (array)($source->TurnEffects ?? []), true) ? 1 : 0;
        $delta = function_exists('EvaluateGoAgainModifier')
            ? intval(EvaluateGoAgainModifier($source->CardID, $controller, $source, $base, $source)) : 0;
        // Fog Down's non-attack Action restriction does not apply to a DR layer.
        if (FaBWTRMayGoAgain($controller) && max(0, min(1, $base + $delta)) === 1) {
            AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);
            FaBROSGo($controller, $uid);
        }
    }
    // An effect may already have moved its own source: never resurrect/move it twice.
    if ($found === null || $found['zone'] !== 'Stack') return;
    $state = FaBGetState();
    if (!FaBSeatIsLive($controller) || empty($state['combatOpen'])
        || intval($state['chainLink']) !== intval($pending['chainLink'])
        || intval($state['attackUID']) !== intval($pending['attackUID'])) {
        FaBMoveStackUID($uid, 'Graveyard', intval($pending['owner']));
        return;
    }
    $chain = FaBMoveStackUID($uid, 'CombatChain', $controller);
    if ($chain === null) return;
    $chain->Role = 'DEFENSE_REACTION';
    $chain->ChainLink = intval($pending['chainLink']);
    $chain->FromZone = (string)$pending['fromZone'];
    FaBWTRApplyNextDefense($controller, $chain);
    $state = FaBGetState();
    if (($pending['fromZone'] ?? '') === 'Hand') {
        $state['handBlockUIDs'][] = $uid;
        $state['handBlockUIDs'] = array_values(array_unique(array_map('intval', $state['handBlockUIDs'])));
        FaBSetState($state);
    }
    $current = FaBFindUID($uid);
    if ($current !== null && $current['zone'] === 'CombatChain') OnDefended($controller, $current['mzID'], $controller);
}

function FaBFinishReactionResolution(): bool {
    $state = FaBGetState();
    $pending = $state['resolvingReaction'] ?? null;
    if (!is_array($pending) || FaBReactionResolutionHasWork()) return false;

    $depth = intval($GLOBALS['fabReactionResolutionDepth'] ?? 0);
    $GLOBALS['fabReactionResolutionDepth'] = $depth + 1;
    try {
        // Remove the marker before move hooks; the local guard still prevents
        // actions or automatic passes while those hooks are executing.
        unset($state['resolvingReaction']);
        FaBSetState($state);
        $uid = intval($pending['uid'] ?? 0);
        $found = FaBFindUID($uid);
        $virtualClass = in_array((string)($pending['resolutionClass'] ?? ''), ['ARC_ABILITY','ROS_ABILITY','RUNECHANT_TRIGGER','MON_ARCANIC_TRIGGER','ROS_EVENT'], true);
        if ($virtualClass) FaBFinishManagedVirtualGoAgain($pending);
        if (($pending['resolutionClass'] ?? '') === 'PLAIN_SPELL') {
            // Preserve initial layer LKI if an effect moved its own source.
            // The final Stack check below prevents a second move after completion.
            $completionSource = $found !== null && $found['zone'] === 'Stack'
                ? $found['object'] : (object)($pending['completionSource'] ?? []);
            FaBFinishPlainSpellGoAgain($pending, $completionSource);
        }
        if (($pending['resolutionClass'] ?? '') === 'DEFENSE_REACTION') {
            FaBFinishDefenseReactionLayer($pending, $found);
        } elseif ($found !== null && $found['zone'] === 'Stack') {
            // A resolution effect may already have returned/banished the source.
            // FaBMoveStackUID applies owner routing and destination replacements,
            // including Ephemeral, after the effect has generated its results.
            if ($virtualClass) $found['object']->removed = true;
            else FaBMoveStackUID($uid, 'Graveyard', intval($pending['owner']));
        }

        // Read current state again: effects and move hooks can update combat.
        $state = FaBGetState();
        if (($pending['resolutionClass'] ?? '') === 'ARC_ABILITY') {
            $state['combatStep'] = (string)($pending['returnCombatStep'] ?? $state['combatStep']);
        }
        if (FaBStackTop() !== null) {
            $state['window'] = 'PRIORITY';
        } elseif (($pending['resolutionClass'] ?? '') === 'ARC_ABILITY') {
            $state['window'] = (string)($pending['returnWindow'] ?? 'ACTION');
        } elseif (!empty($state['combatOpen'])) {
            $state['window'] = $state['combatStep'] === 'DEFEND'
                ? 'DEFEND_PRIORITY' : $state['combatStep'];
        } else {
            $state['window'] = 'ACTION';
        }
        FaBSetState($state);
        // CR 5.3.7: the turn-player gains priority after resolution.
        SetPriorityPlayer(intval(GetTurnPlayer()));
        SetConsecutivePasses(0);
    } finally {
        $GLOBALS['fabReactionResolutionDepth'] = $depth;
    }
    return true;
}


/** Only previously identified ordinary-spell lifecycle concerns, not all NAA. */
function FaBKnownPlainSpellLifecycleCard(string $cardID): bool {
    return in_array($cardID, ['chorus_of_rotwood_red','this_rounds_on_me_blue','rousing_aether_red','rousing_aether_yellow','rousing_aether_blue','aether_quickening_red','aether_quickening_yellow','aether_quickening_blue','battle_prep_red','battle_prep_yellow','battle_prep_blue','laden_with_lightning_red','tome_of_harvests_blue','flying_high_red','flying_high_yellow','flying_high_blue','regain_composure_blue','blood_tribute_red','blood_tribute_yellow','blood_tribute_blue','whisper_of_the_oracle_red','whisper_of_the_oracle_yellow','whisper_of_the_oracle_blue','sift_red','sift_yellow','sift_blue','prognosticate_red','prognosticate_yellow','prognosticate_blue','cull_red'], true);
}

/** Earn the existing generated callback; only the two reproduced event profiles. */
/** Let the real listener dispatcher evaluate eligibility now; defer only Bolters' body. */
function FaBDispatchOwnedHitListeners(int $eventPlayer, array $params): int {
    if (!function_exists('DispatchMacroListeners')) return 0;
    $original = $GLOBALS['macroListenerAbilities']['Hit']['refraction_bolters:0'] ?? null;
    if (!is_callable($original)) return DispatchMacroListeners('Hit', $eventPlayer, $params);
    $GLOBALS['macroListenerAbilities']['Hit']['refraction_bolters:0'] = static function($controller) {
        $source = FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('listenerMZ'));
        $event = (array)DecisionQueueController::GetVariable('eventParams');
        $attack = FaBIdentityFromMZ((string)($event['mzID'] ?? ''));
        if ($source === null || $attack === null) return;
        $event['attackUID'] = intval($attack['object']->UniqueID);
        $event['eventPlayer'] = intval(DecisionQueueController::GetVariable('eventPlayer'));
        FaBQueueOwnedEventMacro(intval($controller), $source['object'], 'Listener:Hit', $event);
    };
    try { return DispatchMacroListeners('Hit', $eventPlayer, $params); }
    finally { $GLOBALS['macroListenerAbilities']['Hit']['refraction_bolters:0'] = $original; }
}

function FaBResolveOwnedHitListener(int $controller, object $layer, ?array $source): void {
    $params = (array)$layer->Params['eventParams'];
    $attack = FaBFindUID(intval($params['attackUID'] ?? 0));
    // The optional destruction cannot be paid after the real Equipment has left.
    if ($source === null || $source['zone'] !== 'Equipment' || $attack === null
        || (string)$source['object']->CardID !== 'refraction_bolters') return;
    $callback = $GLOBALS['macroListenerAbilities']['Hit']['refraction_bolters:0'] ?? null;
    if (!is_callable($callback)) return;
    $params['mzID'] = (string)$attack['mzID']; $eventPlayer = intval($params['eventPlayer'] ?? $controller);
    unset($params['attackUID'], $params['eventPlayer']);
    $savedContext = CaptureMacroListenerContext('Hit'); $savedPlayer = $GLOBALS['playerID'] ?? null;
    $savedEffect = $GLOBALS['fabEffectController'] ?? 0;
    $GLOBALS['playerID'] = $controller; $GLOBALS['fabEffectController'] = $controller;
    StoreMacroListenerContext((string)$source['mzID'], 'refraction_bolters', 'Hit', $eventPlayer, $params);
    try { $callback($controller); }
    finally { RestoreMacroListenerContext($savedContext); $GLOBALS['playerID'] = $savedPlayer; $GLOBALS['fabEffectController'] = $savedEffect; }
}

function FaBOwnedEventMacro(object $layer): string {
    if(FaBIsCapturedBurnLayer($layer))return 'ResolveAbility';
    // This already-earned bound Mark uses the existing real ResolveAbility await.
    if (($layer->Kind ?? '') === 'ABILITY' && !empty($layer->Params['rosTrigger'])
        && ($layer->Params['rosEvent'] ?? '') === 'iarBound'
        && (string)$layer->CardID === 'mark_of_neverest_blue'
        && intval($layer->SourceUniqueID ?? 0) > 0) return 'ResolveAbility';
    if (($layer->Kind ?? '') !== 'ABILITY' || empty($layer->Params['rosTrigger'])
        || ($layer->Params['rosEvent'] ?? '') !== 'ownedCardMacro'
        || intval($layer->SourceUniqueID ?? 0) <= 0) return '';
    $macro = (string)($layer->Params['eventMacro'] ?? '');
    $expected = match ((string)$layer->CardID) {
        'drowning_dire_red' => 'Hit',
        'show_time_blue' => 'ResolveCard',
        'unforgetting_unforgiving_red' => 'Defended',
        'lay_to_rest_red','lay_to_rest_yellow','lay_to_rest_blue','suraya_archangel_of_erudition','flood_of_force_yellow','head_leads_the_tail_red','deathly_duet_red','deathly_duet_yellow','deathly_duet_blue' => 'AttackDeclared',
        'refraction_bolters' => 'Listener:Hit',
        'tear_asunder_blue' => 'ResolveAbility',
        'read_the_ripples_red','read_the_ripples_yellow','read_the_ripples_blue' => 'StartTurn', default => ''
    };
    return $expected !== '' && $macro === $expected && is_array($layer->Params['eventParams'] ?? null) ? $macro : '';
}

function FaBQueueOwnedEventMacro(int $controller, object $source, string $macro, array $params, string $abilityCardID = ''): int {
    if (HasNoAbilities($source) || FaBMPGSuppressed($source) || FaBSUPSuppressed($source)) return 0;
    $abilityCardID = $abilityCardID !== '' ? $abilityCardID : (string)$source->CardID;
    $probe = (object)['Kind'=>'ABILITY', 'CardID'=>$abilityCardID,
        'SourceUniqueID'=>intval($source->UniqueID), 'Params'=>['rosTrigger'=>true,
        'rosEvent'=>'ownedCardMacro', 'eventMacro'=>$macro, 'eventParams'=>$params]];
    if (FaBOwnedEventMacro($probe) === '') return 0;
    FaBROSQueue($controller, $abilityCardID, intval($source->UniqueID), $probe->Params);
    return 1;
}

function FaBResolveOwnedEventMacro(int $controller, object $layer): void {
    $macro = FaBOwnedEventMacro($layer); $pending = FaBGetState()['resolvingReaction'] ?? null;
    if ($macro === '' || !is_array($pending) || ($pending['resolutionClass'] ?? '') !== 'ROS_EVENT'
        || intval($pending['uid'] ?? 0) !== intval($layer->UniqueID)
        || intval($layer->Controller ?? 0) !== $controller || !FaBSeatIsLive($controller)) return;
    $params = (array)($layer->Params['eventParams'] ?? []);
    $physical = FaBFindUID(intval($layer->SourceUniqueID));
    if ($macro === 'Listener:Hit') { FaBResolveOwnedHitListener($controller, $layer, $physical); return; }
    $params['mzID'] = FaBIsCapturedBurnLayer($layer) ? FaBCapturedBurnSourceRef($layer) : (string)($physical['mzID'] ?? '');
    // Eligibility was checked when earned. Later source ability loss does not erase it.
    foreach ($params as $name => $value) DecisionQueueController::StoreVariable($name, $value);
    $callbacks = $GLOBALS[lcfirst($macro).'Abilities'] ?? [];
    $callback = $callbacks[(string)$layer->CardID.':0'] ?? null;
    $nativeLay = $macro === 'AttackDeclared' && in_array((string)$layer->CardID,
        ['lay_to_rest_red','lay_to_rest_yellow','lay_to_rest_blue'], true);
    if (!is_callable($callback) && !$nativeLay) return;
    $previous = $GLOBALS['fabEffectController'] ?? 0; $GLOBALS['fabEffectController'] = $controller;
    try {
        if ($macro === 'AttackDeclared' && (string)$layer->CardID === 'flood_of_force_yellow')
            FaBCRUResolveFloodAttack($controller, $layer);
        elseif ($nativeLay) FaBDTDResolveLayAttack($controller, $layer);
        else $callback($controller);
    } finally { $GLOBALS['fabEffectController'] = $previous; }
}

function FaBManagedVirtualResolutionClass(object $stack): string {
    if (FaBMONArcanicTrigger($stack)) return 'MON_ARCANIC_TRIGGER';
    if (FaBELEEmergingManagedLayer($stack)) return 'ROS_ABILITY';
    if (FaBOwnedEventMacro($stack) !== '') return 'ROS_EVENT';
    if (($stack->Kind ?? '') === 'ABILITY' && isset($stack->Params['arcSpec'])
        && empty($stack->Params['rosTrigger'])) return 'ARC_ABILITY';
    // Marlynn's named draw layer owns its generated arrow-loading await.
    if (($stack->Kind ?? '') === 'ABILITY' && ($stack->CardID ?? '') === 'marlynn'
        && ($stack->Params['rosTrigger'] ?? false) === true
        && ($stack->Params['rosEvent'] ?? '') === 'seaMarlynn'
        && intval($stack->SourceUniqueID ?? 0) > 0) return 'ROS_ABILITY';
    // Only the generic ResolveAbility fallback; named ROS events retain their route.
    if (($stack->Kind ?? '') === 'ABILITY' && !empty($stack->Params['rosTrigger'])
        && in_array((string)($stack->Params['rosEvent'] ?? ''), ['', 'ability'], true)) return 'ROS_ABILITY';
    if (($stack->Kind ?? '') === 'TRIGGER' && (string)$stack->CardID === 'runechant'
        && ($stack->Params['arcRunechant'] ?? false) === true
        && ($stack->SourceZone ?? '') === 'Arena' && intval($stack->SourceUniqueID ?? 0) > 0) return 'RUNECHANT_TRIGGER';
    return '';
}

function FaBManagedAbilityResolutionOwns(object $stack): bool {
    $pending = FaBGetState()['resolvingReaction'] ?? null;
    return is_array($pending) && ($pending['resolutionClass'] ?? '') === 'ARC_ABILITY'
        && intval($pending['uid'] ?? 0) === intval($stack->UniqueID)
        && (string)($pending['cardID'] ?? '') === (string)$stack->CardID
        && FaBManagedVirtualResolutionClass($stack) === 'ARC_ABILITY';
}

/** The resolver itself is not serialized: only the established marker is saved. */
function FaBResolveManagedVirtualLayer(int $controller, object $stack, array $found): bool {
    $class = FaBManagedVirtualResolutionClass($stack);
    if ($class === '' || ($class === 'MON_ARCANIC_TRIGGER' && !empty($stack->Params['monArcanicPending']))
        || ($found['zone'] ?? '') !== 'Stack'
        || intval($found['object']->UniqueID ?? 0) !== intval($stack->UniqueID)) return false;
    $state = FaBGetState();
    $variables = json_decode((string)GetDecisionQueueVariables(), true);
    $physical = FaBFindUID($class === 'ROS_ABILITY' ? FaBROSAbilityMacroSourceUID($stack) : intval($stack->SourceUniqueID ?? 0));
    $state['resolvingReaction'] = [
        'uid'=>intval($stack->UniqueID), 'owner'=>$controller, 'controller'=>$controller,
        'kind'=>(string)$stack->Kind, 'resolutionClass'=>$class,
        'cardID'=>(string)$stack->CardID,
        'sourceMZ'=>($class==='ROS_EVENT'&&FaBIsCapturedBurnLayer($stack)) ? FaBCapturedBurnSourceRef($stack) : (in_array($class, ['ARC_ABILITY','ROS_ABILITY','ROS_EVENT'], true) ? (string)($physical['mzID'] ?? '') : (string)$found['mzID']),
        'frameMacro'=>$class === 'ROS_EVENT' ? FaBOwnedEventMacro($stack) : (in_array($class, ['ARC_ABILITY','ROS_ABILITY'], true) ? 'ResolveAbility' : 'ResolveCard'),
        'abilityGoAgain'=>$class === 'ARC_ABILITY' && !empty($stack->Params['arcSpec']['goAgain']),
        'sourceUniqueID'=>intval($stack->SourceUniqueID ?? 0),
        'returnWindow'=>(string)($stack->Params['returnWindow'] ?? $state['window']),
        'returnCombatStep'=>(string)($stack->Params['returnCombatStep'] ?? $state['combatStep']),
        'baselineFrameKeys'=>array_keys((array)($variables['__awaitFrames'] ?? [])), 'ownedFrameKeys'=>[]];
    FaBSetState($state);
    $depth = intval($GLOBALS['fabReactionResolutionDepth'] ?? 0);
    $GLOBALS['fabReactionResolutionDepth'] = $depth + 1;
    try {
        if ($class === 'ARC_ABILITY') FaBWTRResolveAbility($controller, $stack);
        elseif ($class === 'ROS_ABILITY') FaBROSResolveTrigger($controller, $stack);
        elseif ($class === 'ROS_EVENT') FaBResolveOwnedEventMacro($controller, $stack);
        elseif ($class === 'MON_ARCANIC_TRIGGER') FaBMONResolveArcanicTrigger($controller, $stack);
        // Producer and exact target/destroy rules are owned by the Runechant repair.
        else FaBARCResolveRunechantTrigger($controller, $stack);
    } finally { $GLOBALS['fabReactionResolutionDepth'] = $depth; }
    FaBTrackReactionAwaitFrames();
    FaBFinishReactionResolution();
    // Generic ROS triggers previously returned directly; retain that synchronous policy.
    if (!in_array($class, ['ROS_ABILITY','ROS_EVENT'], true) && !FaBReactionResolutionIsPending()) FaBAutoPassShortcuts();
    return true;
}

function FaBFinishManagedVirtualGoAgain(array $pending): void {
    if (($pending['resolutionClass'] ?? '') !== 'ARC_ABILITY' || empty($pending['abilityGoAgain'])) return;
    $controller = intval($pending['controller'] ?? 0);
    if (!FaBSeatIsLive($controller) || !FaBWTRMayGoAgain($controller)) return;
    AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);
    FaBROSGo($controller, intval($pending['sourceUniqueID'] ?? 0));
}

/** Structural admission for owned nonpersistent ACTION/INSTANT sources.
 * DoResolveCard keeps persistent cards on their existing Arena-entry path.
 * Preserve the existing foreign-owner boundary and the Tome exception.
 */
function FaBPlainSpellResolutionIsManaged(object $o): bool {
    if (!in_array((string)($o->Kind ?? ''), ['ACTION','INSTANT'], true)) return false;
    if ((string)$o->CardID === 'gorganian_tome') return true;
    $owner = intval($o->Owner ?? 0); $controller = intval($o->Controller ?? 0);
    if ($owner > 0 && $owner !== $controller) return false;
    return true;
}

/** Resolution effects finish before go again and source clearing (CR 5.3.4-7). */
function FaBFinishPlainSpellGoAgain(array $pending, object $source): void {
    $controller = intval($pending['controller']);
    if (!FaBSeatIsLive($controller)) return;
    if (FaBKnownPlainSpellLifecycleCard((string)$source->CardID) && HasNoAbilities($source)) return;
    // Keep this repair bounded to Tome; do not certify other cohorts' suppression contracts.
    if ((string)$source->CardID === 'gorganian_tome' && HasNoAbilities($source)) return;
    $kind = (string)$pending['kind']; $uid = intval($pending['uid']);
    $baseGoAgain = !FaBMPGSuppressed($source) && (!FaBROSMeld($source->CardID)||FaBARCCard($uid,'rosMeld')!==1) && FaBPrintedKeywordIsActive($source->CardID, 'Go again') || in_array('GO_AGAIN',(array)($source->TurnEffects??[]),true) ? 1 : 0;
    $goAgainDelta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($source->CardID, $controller, $source, $baseGoAgain, $source)) : 0;
    if (($kind === 'ACTION' || ($kind === 'INSTANT' && (FaBHasType($source,'Action')||in_array('GO_AGAIN',(array)($source->TurnEffects??[]),true)))) && !FaBUPRFog() && FaBWTRMayGoAgain($controller) && $source->CardID!=='construct_nitro_mechanoid_yellow' && max(0, min(1, $baseGoAgain + $goAgainDelta)) === 1) { AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);FaBROSGo($controller,$uid); }
}

function DoResolveCard($player, $mzID) {
    if (FaBReactionResolutionIsPending()) return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || $found['zone'] !== 'Stack') return false;
    $obj = $found['object']; $controller = intval($obj->Controller ?? $player); $uid = intval($obj->UniqueID);
    $kind = (string)($obj->Kind ?? 'ACTION'); $state = FaBGetState();
    if($kind==='ABILITY'&&!empty($obj->Params['mstEmptyDriver'])){$obj->removed=true;$driver=FaBFindUID(intval($obj->Params['mstEmptyDriver']));if($driver&&$driver['zone']==='Arena'&&FaBSteamCounters($driver['object'])===0)FaBMONDestroy(intval($driver['object']->UniqueID));SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);return true;}
    if($kind==='ABILITY'&&!empty($obj->Params['uprPhantasm'])){
        $obj->removed=true;FaBMONPhantasm($state);
        // Chain closure may have produced a destruction trigger requiring responses.
        if(FaBStackTop()!==null){$s=FaBGetState();$s['window']='PRIORITY';FaBSetState($s);}
        SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);return true;
    }
    if (FaBROSMeldFirst($controller,$obj)) return true;
    if (FaBManagedVirtualResolutionClass($obj) !== '') return FaBResolveManagedVirtualLayer($controller, $obj, $found);
    if ($kind === 'ABILITY') {
        $obj->removed=true;
        if(!empty($obj->Params['rosTrigger'])) { FaBROSResolveTrigger($controller,$obj);$s=FaBGetState();$s['window']=FaBStackTop()!==null?'PRIORITY':(($obj->Params['rosEvent']??'')==='wtrIntimidateReturn'?'END_PHASE':(!empty($s['combatOpen'])?($s['combatStep']==='DEFEND'?'DEFEND_PRIORITY':$s['combatStep']):'ACTION'));FaBSetState($s); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0); return true; }
        FaBWTRResolveAbility($controller,$obj);
        $state=FaBGetState();
        $state['window']=(string)($obj->Params['returnWindow']??'ACTION');
        $state['combatStep']=(string)($obj->Params['returnCombatStep']??'NONE');
        FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    } elseif ($kind === 'ATTACK') {
        if (!FaBMONAttackSourceExists($obj)) {
            $obj->removed = true;
            FaBCloseCombatChain();
            SetPriorityPlayer($controller); SetConsecutivePasses(0);
            return true;
        }
        $attackTargets=[];
        foreach(($obj->Params['attackTargets']??[$obj->Params['attackTarget']??[]]) as $descriptor){$t=FaBResolveAttackTarget((array)$descriptor,$controller);if($t!==null)$attackTargets[]=$t;}
        $attackTarget=$attackTargets[0]??null;
        if ($attackTarget === null) {
            // A target can leave the game while players respond. Resolve the failed
            // attack off the stack instead of leaving everyone passing forever.
            if (!empty(FaBObjectCounters($obj)['MON_ARENA_ATTACK'])) $obj->removed = true;
            else FaBMoveStackUID($uid, 'Graveyard', $controller);
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
        $state['outPreviousNames'][$controller]=$prior!==null?FaBOUTNames($prior['object']):($state['outPreviousNames'][$controller]??[]);
        $state['previousAttackCardID'] = $prior !== null && FaBOUTNames($prior['object']) ? (string)$prior['object']->CardID : '';
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
        FaBMONSpectra($attackTarget);
    } elseif ($kind === 'ATTACK_REACTION') {
        $owner = intval($obj->Owner ?? 0);
        if ($owner < 1) $owner = $controller;
        $variables = json_decode((string)GetDecisionQueueVariables(), true);
        $state['resolvingReaction'] = ['uid'=>$uid, 'owner'=>$owner, 'controller'=>$controller,
            'cardID'=>(string)$obj->CardID, 'sourceMZ'=>(string)$found['mzID'],
            'baselineFrameKeys'=>array_keys((array)($variables['__awaitFrames'] ?? [])), 'ownedFrameKeys'=>[]];
        FaBSetState($state);
        $depth = intval($GLOBALS['fabReactionResolutionDepth'] ?? 0);
        $GLOBALS['fabReactionResolutionDepth'] = $depth + 1;
        try {
            // CR 5.3.4 precedes clearing (5.3.7). Awaiting macros keep a live source.
            FaBResolveRules($controller, $obj, $obj);
        } finally {
            $GLOBALS['fabReactionResolutionDepth'] = $depth;
        }
        FaBTrackReactionAwaitFrames();
        FaBFinishReactionResolution();
        if (FaBReactionResolutionIsPending()) return true;
    } elseif ($kind === 'DEFENSE_REACTION') {
        $owner = intval($obj->Owner ?? 0); if ($owner < 1) $owner = $controller;
        if (empty($state['combatOpen']) || intval($state['chainLink']) < 1
            || ($obj->SourceZone === 'Hand' && FaBCurrentAttackHasKeyword($state, 'Dominate')
                && FaBHandDefendingCount($state, $controller) >= 1)) {
            FaBMoveStackUID($uid, 'Graveyard', $owner);
            SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0); return true;
        }
        $variables = json_decode((string)GetDecisionQueueVariables(), true);
        $state['resolvingReaction'] = ['uid'=>$uid, 'owner'=>$owner, 'controller'=>$controller,
            'kind'=>$kind, 'resolutionClass'=>'DEFENSE_REACTION', 'completionSource'=>(array)$obj,
            'cardID'=>(string)$obj->CardID, 'sourceMZ'=>(string)$found['mzID'],
            'chainLink'=>intval($state['chainLink']), 'attackUID'=>intval($state['attackUID']),
            'fromZone'=>(string)$obj->SourceZone,
            'baselineFrameKeys'=>array_keys((array)($variables['__awaitFrames'] ?? [])), 'ownedFrameKeys'=>[]];
        FaBSetState($state);
        $depth = intval($GLOBALS['fabReactionResolutionDepth'] ?? 0);
        $GLOBALS['fabReactionResolutionDepth'] = $depth + 1;
        try { FaBResolveRules($controller, $obj, $obj); }
        finally { $GLOBALS['fabReactionResolutionDepth'] = $depth; }
        FaBTrackReactionAwaitFrames();
        FaBFinishReactionResolution();
        if (FaBReactionResolutionIsPending()) return true;
    } else {
        $persistent = FaBHasType($obj, 'Figment') || FaBHasType($obj, 'Ash') || FaBHasType($obj,'Construct') || FaBHasType($obj, 'Aura') || FaBHasType($obj, 'Item') || FaBHasType($obj, 'Ally') || FaBHasType($obj,'Landmark') || FaBHasType($obj,'Invocation') || FaBHasType($obj,'Affliction');
        if (!$persistent && FaBPlainSpellResolutionIsManaged($obj)) {
            $owner = intval($obj->Owner ?? 0); if ($owner < 1) $owner = $controller;
            $variables = json_decode((string)GetDecisionQueueVariables(), true);
            // Reuse the existing serialized lifecycle, guards, queue wait and ownership cleanup.
            $state['resolvingReaction'] = ['uid'=>$uid, 'owner'=>$owner, 'controller'=>$controller,
                'kind'=>$kind, 'resolutionClass'=>'PLAIN_SPELL', 'completionSource'=>(array)$obj,
                'cardID'=>(string)$obj->CardID, 'sourceMZ'=>(string)$found['mzID'],
                'baselineFrameKeys'=>array_keys((array)($variables['__awaitFrames'] ?? [])), 'ownedFrameKeys'=>[]];
            FaBSetState($state);
            $depth = intval($GLOBALS['fabReactionResolutionDepth'] ?? 0);
            $GLOBALS['fabReactionResolutionDepth'] = $depth + 1;
            try { FaBResolveRules($controller, $obj, $obj); }
            finally { $GLOBALS['fabReactionResolutionDepth'] = $depth; }
            FaBTrackReactionAwaitFrames();
            FaBFinishReactionResolution();
            if (FaBReactionResolutionIsPending()) return true;
        } else {
            $resolved = FaBMoveStackUID($uid, $persistent ? 'Arena' : 'Graveyard', $persistent ? $controller : intval($obj->Owner ?? $controller));
            FaBResolveRules($controller, $obj, $resolved);
            $baseGoAgain = !FaBMPGSuppressed($obj) && (!FaBROSMeld($obj->CardID)||FaBARCCard($uid,'rosMeld')!==1) && FaBPrintedKeywordIsActive($obj->CardID, 'Go again') || in_array('GO_AGAIN',(array)($obj->TurnEffects??[]),true) ? 1 : 0;
            $goAgainDelta = function_exists('EvaluateGoAgainModifier') ? intval(EvaluateGoAgainModifier($obj->CardID, $controller, $obj, $baseGoAgain, $obj)) : 0;
            if (($kind === 'ACTION' || ($kind === 'INSTANT' && (FaBHasType($obj,'Action')||in_array('GO_AGAIN',(array)($obj->TurnEffects??[]),true)))) && !FaBUPRFog() && FaBWTRMayGoAgain($controller) && $obj->CardID!=='construct_nitro_mechanoid_yellow' && max(0, min(1, $baseGoAgain + $goAgainDelta)) === 1) { AddActionPoints($controller, intval(GetActionPoints($controller)) + 1);FaBROSGo($controller,$uid); }
            $state = FaBGetState();
            if (!empty($state['combatOpen']) && in_array($state['combatStep'], ['ATTACK', 'DEFEND', 'REACTION', 'DAMAGE', 'RESOLUTION'], true)) {
                $state['window'] = $state['combatStep'] === 'DEFEND' ? 'DEFEND_PRIORITY' : $state['combatStep'];
            } else {
                $state['window'] = 'ACTION';
            }
            FaBSetState($state); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
        }
    }
    if(FaBStackTop()!==null){$s=FaBGetState();$s['window']='PRIORITY';FaBSetState($s);}
    else {$s=FaBGetState();if($s['window']==='PRIORITY'&&!empty($s['combatOpen'])){$s['window']=$s['combatStep']==='DEFEND'?'DEFEND_PRIORITY':$s['combatStep'];FaBSetState($s);}}
    FaBAutoPassShortcuts();
    return true;
}

function FaBResolveRules(int $player, object $source, ?object $resolved): void {
    if ($resolved === null) return;
    $found = FaBFindUID(intval($resolved->UniqueID));
    if ($found === null) return;
    DecisionQueueController::StoreVariable('fabSourceZone', (string)($source->SourceZone ?? ''));
    // This normal Aura play earns its own entry layer after the spell resolves.
    if ($source->CardID === 'show_time_blue' && ($source->Kind ?? '') === 'ACTION'
        && $resolved->CardID === 'show_time_blue' && $found['zone'] === 'Arena'
        && intval($resolved->Controller ?? 0) === $player) {
        FaBQueueOwnedEventMacro($player, $resolved, 'ResolveCard', ['showTimeEntry'=>true]);
        return;
    }
    if (FaBRunSourceMacro('ResolveCard', $player, $source->CardID, ['mzID'=>$found['mzID'], 'evoEvent'=>'']) === 0) {
        FaBWTRResolveCard($player, $source, $resolved);
    }
}

function FaBCanBlock(int $player, string $mzID): bool {
    $state = FaBGetState(); if ($state['window'] !== 'DEFEND_DECLARE' || intval($state['defender']) !== $player) return false;
    if (($state['attackTarget']['type'] ?? 'HERO') !== 'HERO') return false;
    $found = FaBIdentityFromMZ($mzID);
    if ($found === null || $found['player'] !== $player) return false;
    if(!FaBOMNBlockLegal($found['object'])||FaBMSTHidden($found['object'])||!FaBMPGBlockLegal($found['object'])||!FaBSUPBlockLegal($player,$found['object']))return false;
    $rosAttack=FaBFindUID(intval(FaBGetState()['attackUID']));if($rosAttack&&FaBWTRBase($rosAttack['object']->CardID)==='cut_through_the_facade'&&FaBHasType($found['object'],'Aura'))return false;
    if ($found['zone']==='Hero'&&!FaBEVOHeroCanDefend($player,$found['object']))return false;
    if (!($found['zone']==='Weapons'&&($found['object']->CardID==='parry_blade'||FaBHasType($found['object'],'Equipment'))) && !in_array($found['zone'], ['Hand', 'Equipment','Hero'], true) && !($found['zone']==='Arsenal'&&(FaBWTRBase($found['object']->CardID)==='down_and_dirty'||(FaBHasKeyword($found['object'],'Ambush')||($found['object']->CardID==='no_hero_stands_alone_yellow'&&FaBHVYCount($player,'CONTROLLED_toughness')))||(FaBFaiEffect($player,'AOW_ARSENAL')>0&&FaBWTRIsAttackAction($found['object']))))) return false;
    if ((!is_numeric(CardDefense($found['object']->CardID))&&!in_array($found['object']->CardID,['base_of_the_mountain','arcanite_fortress','mutated_mass_blue','headliner_helm','bloodied_oval','grandstand_legplates','ticket_puncher','stadium_centerpiece'],true))) return false;
    if (FaBARCNamedProhibited($found['object']->CardID) || !FaBCRUBlockLegal($player,$found) || !FaBDYNBlockLegal($player,$found) || !FaBEVOBlockLegal($player,$found) || !FaBHVYBlockLegal($player,$found)||!FaBHNTBlockLegal($player,$found)) return false;
    if ($found['zone']==='Hand' && FaBHasType($found['object'],'Defense Reaction')) return false;
    if ($found['zone'] === 'Hand' && FaBCurrentAttackHasKeyword($state, 'Dominate') && FaBHandDefendingCount($state,$player) >= 1) return false;
    return true;
}

function FaBDeclareBlock(int $player, string $mzID): bool {
    if (!FaBCanBlock($player, $mzID)) return false;
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return false;
    SaveUndoVersion($player, 'Before blocking with ' . (CardName($found['object']->CardID) ?: $found['object']->CardID));
    $uid = intval($found['object']->UniqueID); $from = $found['zone'];
    $chain = $from==='Hero'?FaBEVOHeroDefend($player,$found['object']):FaBMoveUID($uid, 'CombatChain', $player);
    if($chain)$uid=intval($chain->UniqueID);
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
    if(FaBCRUMustEquip(intval($state['defender']))||FaBEVOMustEquip(intval($state['defender'])))return;
    FaBDYNDefendGroup(intval($state['defender']));
    FaBOUTDefendGroup(intval($state['defender']),(array)$state['declaredBlockUIDs']);
    FaBDTDDefendGroup(intval($state['defender']),(array)$state['declaredBlockUIDs']);FaBHVYDefendGroup(intval($state['defender']),(array)$state['declaredBlockUIDs']);
    foreach ((array)($state['declaredBlockUIDs'] ?? []) as $uid) {
        $found = FaBFindUID(intval($uid));
        if ($found !== null && $found['zone'] === 'CombatChain' && $found['player']===intval($state['defender'])) OnDefended(intval($state['defender']), $found['mzID'], intval($state['defender']));
    }
    $state = FaBGetState();
    FaBMSTFinishBlocks();FaBROSFinishBlocks();
    if(FaBMONPhantasm($state,false))return;
    if(FaBNextDefendTarget($state))return;
    $state['combatStep'] = 'DEFEND'; $state['window'] = 'DEFEND_PRIORITY';
    $state['defenseValue'] = FaBDefenseValue($state);
    FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
}

/** Continuous Commander bonus follows control and live, unsuppressed Arena sources. */
function FaBControlledZombiePower(int $player, object $attack): int {
    if (($attack->Role ?? '') !== 'ATTACK' || intval($attack->Controller ?? 0) !== $player
        || !FaBHasType($attack, 'Zombie')
        || (FaBWTRIsAttackAction($attack) && FaBCRUCount($player, 'SNAG'))) return 0;
    $amount = 0;
    foreach (GetArena($player) as $source)
        if (is_object($source) && empty($source->removed) && ($source->CardID ?? '') === 'restless_commander_red'
            && intval($source->Controller ?? 0) === $player && !HasNoAbilities($source)) ++$amount;
    return FaBPENPowerGain($attack, $amount);
}

function FaBAttackPower(array $state): int {
    $attack = FaBFindCurrentAttack($state);
    if ($attack === null) return 0;
    $base = max(0, intval(CardPower($attack['object']->CardID)));
    // Its property-static ability defines '*' before subsequent power modifiers.
    if($attack['object']->CardID==='blasmophets_boon_blue')$base=FaBMONBasePower(intval($state['attacker']),$attack['object']);
    if(!empty(FaBObjectCounters($attack['object'])['MON_ARENA_ATTACK']))$base=FaBMSTAuraBase(intval($state['attacker']),$attack['object'])??intval(FaBObjectCounters($attack['object'])['MON_ARENA_POWER']??0);
    if($attack['object']->CardID==='fractal_replication_red')$base=FaBEVRFractalValue($attack['object'],'POWER');
    if(in_array('EVR_HALF_BASE',(array)$attack['object']->TurnEffects,true))$base=intval(ceil($base/2));
    if(FaBWTRBase($attack['object']->CardID)==='numbskull')return intval(CardPower($attack['object']->CardID));
    if($attack['object']->CardID==='mutated_mass_blue')$base=FaBMONMass(intval($state['attacker']));
    $delta = function_exists('EvaluateAttackPowerModifier') ? intval(EvaluateAttackPowerModifier($attack['object']->CardID, intval($state['attacker']), $attack['object'], $base, $attack['object'])) : 0;
    if (function_exists('FaBWTRAttackPowerModifier')) $delta += FaBWTRAttackPowerModifier(intval($state['attacker']), $attack['object'], $state);
    $delta += FaBControlledZombiePower(intval($state['attacker']), $attack['object']);
    if(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'SNAG'))$delta-=max(0,intval(EvaluateAttackPowerModifier($attack['object']->CardID,intval($state['attacker']),$attack['object'],$base,$attack['object'])));
    $base=FaBSUPBase(intval($state['attacker']),$attack['object'],FaBPENBase(intval($state['attacker']),$attack['object'],FaBCRUBasePower($attack['object'],$base)));
    if(FaBCRUCount(intval($state['attacker']),'CHOKESLAM')&&FaBWTRIsAttackAction($attack['object']))$delta=min(0,$delta);
    $delta+=FaBEVRPower(intval($state['attacker']),$attack['object'])+FaBUPRPower(intval($state['attacker']),$attack['object'])+FaBDYNPower(intval($state['attacker']),$attack['object'])+FaBOUTPower(intval($state['attacker']),$attack['object'])+FaBDTDPower(intval($state['attacker']),$attack['object'])+FaBEVOPower(intval($state['attacker']),$attack['object'])+FaBAMXPower(intval($state['attacker']),$attack['object'])+FaBHVYPower(intval($state['attacker']),$attack['object'])+FaBMSTPower(intval($state['attacker']),$attack['object'])+FaBROSPower(intval($state['attacker']),$attack['object'])+FaBHNTPower(intval($state['attacker']),$attack['object'])+FaBSEAPower(intval($state['attacker']),$attack['object'])+FaBMPGPower(intval($state['attacker']),$attack['object']);
    foreach((array)$attack['object']->TurnEffects as $tag)if(str_starts_with($tag,'UPR_BASE:'))$base=intval(substr($tag,9));
    $delta+=FaBCRUPower(intval($state['attacker']),$attack['object'])+FaBMONPower(intval($state['attacker']),$attack['object'])+FaBELEPower(intval($state['attacker']),$attack['object']);
    $result=max(0,$base+$delta+(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'SNAG')?0:FaBProfessorPower(intval($state['attacker']),$attack['object'])));
    if(FaBWTRIsAttackAction($attack['object'])&&FaBCRUCount(intval($state['attacker']),'CHOKESLAM'))$result=min($base,$result);
    $result+=(in_array('OMN_QUICK_POWER',(array)($attack['object']->TurnEffects??[]),true)&&FaBOMNQuick($attack['object'])?1:0);
    $result+=FaBIARPower(intval($state['attacker']),$attack['object']);
    $result+=FaBSUPPower(intval($state['attacker']),$attack['object'],$result)+FaBPENPower(intval($state['attacker']),$attack['object']);
    return max(0,FaBOMNFinalPower($attack['object'],FaBPENFinalPower(intval($state['attacker']),$attack['object'],$base,$result)));
}

function FaBDefenseValue(array $state, ?int $defender=null): int {
    $defender ??= intval($state['defender']);
    $total = 0;
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (!in_array((string)($obj->Role ?? ''), ['DEFENSE', 'DEFENSE_REACTION'], true)) continue;
        if($seat===$defender||intval(FaBObjectCounters($obj)['DEFENDING_HERO']??0)===$defender)$total += FaBCurrentDefense($obj, $defender);
    }
    return $total;
}

/** Captured packet-source characteristics remain separate from live card identities. */
function FaBCaptureDamageSourceRecord(int $uid, string $purpose, int $controller,bool $atEarning=true): array {
    $f=FaBFindUID($uid);
    if(!$f||!in_array($purpose,['pox','burn'],true)||!in_array($controller,FaBSeatOrder(),true)
        ||($atEarning&&!FaBSeatIsLive($controller)))return [];
    $o=$f['object'];$types=EffectiveCardType($o);
    if($purpose==='pox'&&((string)$o->CardID!=='bloodrot_pox'||$f['zone']!=='Arena'||!FaBHasType($o,'Token')))return [];
    if($purpose==='burn'&&($f['zone']!=='Arena'||($atEarning&&!FaBHasType($o,'Dragon'))))return [];
    $owner=intval($o->Owner??0);$sourceController=intval($o->Controller??$f['player']);
    if($uid<1||!in_array($owner,FaBSeatOrder(),true)||!in_array($sourceController,FaBSeatOrder(),true))return [];
    if($purpose==='burn'&&$atEarning&&$sourceController!==$controller)return [];
    $generation=$purpose==='burn'?($atEarning?FaBArmCapturedSourceEpoch($uid,'burnDamageSourceEpoch'):intval(FaBARCCard($uid,'burnDamageSourceEpoch',0))):0;
    if($purpose==='burn'&&$generation<1)return [];
    // JSON copy eliminates all object references, including nested material/counter data.
    $fields=json_decode(json_encode(get_object_vars($o),JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
    $metadata=json_decode(json_encode((array)(FaBGetState()['arcCards'][(string)$uid]??[]),JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
    $linkedUID=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??$uid);
    $linkedMetadata=$linkedUID===$uid?$metadata:json_decode(json_encode((array)(FaBGetState()['arcCards'][(string)$linkedUID]??[]),JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
    return ['version'=>1,'purpose'=>$purpose,'uid'=>$uid,'controller'=>$controller,
        'player'=>intval($f['player']),'zone'=>(string)$f['zone'],'object'=>$fields,
        'types'=>array_values($types),'color'=>FaBMSTObjectColor(intval($f['player']),$o),
        'metadata'=>$metadata,'linkedMetadataUID'=>$linkedUID,'linkedMetadata'=>$linkedMetadata,
        'generation'=>$generation,'departed'=>false,'earnedDragon'=>$purpose==='burn'&&$atEarning];
}
function FaBValidDamageSourceRecord(array $r,string $purpose): bool {
    return ($r['version']??0)===1&&($r['purpose']??'')===$purpose
        &&intval($r['uid']??0)>0&&is_array($r['object']??null)&&is_array($r['types']??null)
        &&is_array($r['metadata']??null)&&intval($r['object']['UniqueID']??0)===intval($r['uid'])
        &&in_array(intval($r['object']['Owner']??0),FaBSeatOrder(),true)
        &&in_array(intval($r['object']['Controller']??0),FaBSeatOrder(),true)
        &&in_array(intval($r['controller']??0),FaBSeatOrder(),true)
        &&($purpose!=='pox'||((string)($r['object']['CardID']??'')==='bloodrot_pox'&&FaBRecordedDamageType($r['types'],'Token')))
        &&($purpose!=='burn'||(!empty($r['earnedDragon'])&&intval($r['generation']??0)>0));
}
function FaBIsCapturedBurnLayer(object $o): bool {
    $r=(array)($o->Params['burnDamageSource']??[]);
    return ($o->Kind??'')==='ABILITY'&&(string)$o->CardID==='burn_them_all_red'
        &&!empty($o->Params['rosTrigger'])&&($o->Params['rosEvent']??'')==='ownedCardMacro'
        &&($o->Params['eventMacro']??'')==='ResolveAbility'&&is_array($o->Params['eventParams']??null)
        &&intval($o->SourceUniqueID??0)>0&&FaBValidDamageSourceRecord($r,'burn')
        &&intval($r['auraUID']??0)===intval($o->SourceUniqueID)
        &&intval($r['controller'])===intval($o->Controller??0);
}
function FaBCapturedBurnSourceRef(object $o): string {
    return FaBIsCapturedBurnLayer($o)&&intval($o->UniqueID??0)>0
        ?'@BurnDamage:'.intval($o->UniqueID).':'.intval($o->Params['burnDamageSource']['uid']):'';
}
function FaBCapturedBurnSourceLayer(string $ref): ?object {
    if(preg_match('/\A@BurnDamage:([1-9][0-9]*):([1-9][0-9]*)\z/D',$ref,$m)!==1)return null;
    $pending=FaBGetState()['resolvingReaction']??null;$f=FaBFindUID(intval($m[1]));
    if(!is_array($pending)||($pending['resolutionClass']??'')!=='ROS_EVENT'
        ||($pending['cardID']??'')!=='burn_them_all_red'||($pending['frameMacro']??'')!=='ResolveAbility'
        ||($pending['sourceMZ']??'')!==$ref||intval($pending['uid']??0)!==intval($m[1])
        ||!$f||$f['zone']!=='Stack'||!FaBIsCapturedBurnLayer($f['object']))return null;
    $o=$f['object'];
    if(FaBCapturedBurnSourceRef($o)!==$ref||intval($pending['controller']??0)!==intval($o->Controller)
        ||intval($pending['owner']??0)!==intval($o->Controller)
        ||intval($pending['sourceUniqueID']??0)!==intval($o->SourceUniqueID)
        ||!FaBSeatIsLive(intval($o->Controller)))return null;
    return $o;
}
function FaBFreezeCapturedBurnSource(int $uid,string $from,string $to,array $before,int $generation,object $source): void {
    if($from!=='Arena'||$to==='Arena'||$generation<1||intval($source->UniqueID??0)!==$uid
        ||intval(FaBARCCard($uid,'burnDamageSourceEpoch',0))!==$generation)return;
    // No bank: only earned layers retaining this source generation own the record.
    if(intval($before['uid']??0)===$uid&&($before['purpose']??'')==='burn'
        &&intval($before['generation']??0)===$generation){
        foreach(GetStack() as $layer){
            if(!is_object($layer)||!empty($layer->removed)||!FaBIsCapturedBurnLayer($layer))continue;
            $r=(array)$layer->Params['burnDamageSource'];
            if(intval($r['uid'])!==$uid||intval($r['generation'])!==$generation||!empty($r['departed']))continue;
            $before['controller']=intval($r['controller']);$before['departed']=true;
            $before['earnedDragon']=true;$before['auraUID']=intval($r['auraUID']);
            if(!FaBValidDamageSourceRecord($before,'burn'))continue;
            $layer->Params['burnDamageSource']=$before;
        }
    }
    // Even without a pending layer, an already armed source must get a new generation on reentry.
    FaBAdvanceCapturedSourceEpochs($source,$from,$to,['burnDamageSourceEpoch']);
}
function FaBCapturedBurnLayerController(int $uid): int {
    if($uid<1)return 0;
    foreach(GetStack() as $layer){
        if(!is_object($layer)||!empty($layer->removed)||!FaBIsCapturedBurnLayer($layer))continue;
        $r=(array)$layer->Params['burnDamageSource'];
        if(intval($r['uid'])===$uid&&empty($r['departed'])
            &&intval($r['generation'])===intval(FaBARCCard($uid,'burnDamageSourceEpoch',0))
            &&FaBSeatIsLive(intval($layer->Controller)))return intval($layer->Controller);
    }
    return 0;
}
function FaBFreezeEliminatedBurnSources(int $leavingSeat=0): void {
    $seen=[];
    foreach(GetStack() as $layer){
        if(!is_object($layer)||!empty($layer->removed)||!FaBIsCapturedBurnLayer($layer))continue;
        $r=(array)$layer->Params['burnDamageSource'];$uid=intval($r['uid']);
        if(!empty($r['departed'])||isset($seen[$uid]))continue;
        $seen[$uid]=true;$f=FaBFindUID($uid);
        if(!$f||$f['zone']!=='Arena'||($leavingSeat>0?intval($f['player'])!==$leavingSeat:FaBSeatIsLive(intval($f['player']))))continue;
        $owner=FaBCapturedBurnLayerController($uid);if($owner<1)continue;
        $before=FaBCaptureDamageSourceRecord($uid,'burn',$owner,false);
        if($before)FaBFreezeCapturedBurnSource($uid,'Arena','Eliminated',$before,intval($before['generation']),$f['object']);
    }
}
function FaBCapturedDamageRecordFromRef(string $ref): ?array {
    if(str_starts_with($ref,'@BurnDamage:')){
        $layer=FaBCapturedBurnSourceLayer($ref);return $layer?(array)$layer->Params['burnDamageSource']:null;
    }
    if(!str_starts_with($ref,'@PoxDamage:'))return null;
    $raw=base64_decode(substr($ref,11),true);if($raw===false)return null;
    $r=json_decode($raw,true);return is_array($r)&&FaBValidDamageSourceRecord($r,'pox')?$r:null;
}
function FaBDamageSourceView(string $ref,int $uid=0): ?array {
    if(!str_starts_with($ref,'@BurnDamage:')&&!str_starts_with($ref,'@PoxDamage:')){
        $f=$ref!==''?FaBIdentityFromMZ($ref):FaBFindUID($uid);
        return $f&&($uid<1||intval($f['object']->UniqueID??0)===$uid)?$f:null;
    }
    $r=FaBCapturedDamageRecordFromRef($ref);if(!$r||($uid>0&&intval($r['uid'])!==$uid))return null;
    if(empty($r['departed'])){
        if($r['purpose']==='burn'&&intval($r['generation'])!==intval(FaBARCCard(intval($r['uid']),'burnDamageSourceEpoch',0)))return null;
        $live=FaBFindUID(intval($r['uid']));
        if($live&&$live['zone']==='Arena')return $live;
        // A Burn source cannot disappear through an unobserved path and silently use stale earning data.
        if($r['purpose']==='burn')return null;
    }
    $o=(object)$r['object'];$o->Location='LKI';$o->removed=true;
    $o->__fabDamageTypes=$r['types'];$o->__fabDamageColor=intval($r['color']);$o->__fabDamageMeta=$r['metadata'];$o->__fabDamageRef=$ref;
    return ['object'=>$o,'player'=>intval($r['player']),'zone'=>'LKI','lastKnownZone'=>(string)$r['zone'],'mzID'=>$ref,'captured'=>true];
}
function FaBDamageSourceLiveRef(string $ref): string {
    $f=FaBDamageSourceView($ref);return $f&&empty($f['captured'])?(string)$f['mzID']:'';
}
function FaBDamageSourceActivityRef(string $ref): string {
    $f=FaBDamageSourceView($ref);if(!$f||!empty($f['captured']))return '';
    if((str_starts_with($ref,'@BurnDamage:')||str_starts_with($ref,'@PoxDamage:'))
        &&(!FaBSeatIsLive(intval($f['object']->Controller??$f['player']))
        ||HasNoAbilities($f['object'])||FaBMPGSuppressed($f['object'])||FaBSUPSuppressed($f['object'])))return '';
    return (string)$f['mzID'];
}
function FaBDamageSourceHasType(object $o,string $type): bool {
    if(!isset($o->__fabDamageTypes))return FaBHasType($o,$type);
    return FaBRecordedDamageType((array)$o->__fabDamageTypes,$type);
}
function FaBRecordedDamageType(array $types,string $type): bool {
    foreach($types as $candidate)if(strcasecmp((string)$candidate,$type)===0)return true;
    return false;
}
function FaBDamageSourceObjectCard(object $o,string $key,$default=0) {
    return isset($o->__fabDamageMeta)?($o->__fabDamageMeta[$key]??$default):FaBARCCard(intval($o->UniqueID),$key,$default);
}
function FaBDamageSourceCard(string $ref,int $uid,string $key,$default=0) {
    $f=FaBDamageSourceView($ref,$uid);return $f?FaBDamageSourceObjectCard($f['object'],$key,$default):$default;
}
function FaBDamageSourceMetadata(string $ref,int $uid,string $key,$default=0) {
    $f=FaBDamageSourceView($ref);if(!$f)return $default;
    if(empty($f['captured']))return FaBARCCard($uid,$key,$default);
    $r=FaBCapturedDamageRecordFromRef($ref);if(!$r)return $default;
    if($uid===intval($r['uid']))return $r['metadata'][$key]??$default;
    return $uid===intval($r['linkedMetadataUID']??0)?($r['linkedMetadata'][$key]??$default):$default;
}
function FaBDamageSourceActor(string $ref,int $uid,int $fallback): int {
    if(!str_starts_with($ref,'@BurnDamage:')&&!str_starts_with($ref,'@PoxDamage:'))return $fallback;
    $f=FaBDamageSourceView($ref,$uid);$p=$f?intval($f['object']->Controller??0):0;
    return in_array($p,FaBSeatOrder(),true)?$p:0;
}
function FaBCapturedDamageBonus(object $o,int $n): int {
    if(!isset($o->__fabDamageRef))return FaBOMNDamageBonus($o,$n);
    $bonus=intval(FaBDamageSourceObjectCard($o,'omnChromatic'));
    if($bonus<=0||$n<=0)return $n;
    $ref=(string)$o->__fabDamageRef;
    if(str_starts_with($ref,'@BurnDamage:')){
        $layer=FaBCapturedBurnSourceLayer($ref);if(!$layer||!empty($layer->Params['burnDamageSpent']['omnChromatic']))return $n;
        $record=(array)$layer->Params['burnDamageSource'];
        foreach(GetStack() as $peer){
            if(!is_object($peer)||!empty($peer->removed)||!FaBIsCapturedBurnLayer($peer))continue;
            $r=(array)$peer->Params['burnDamageSource'];
            if(intval($r['uid'])===intval($record['uid'])&&intval($r['generation'])===intval($record['generation']))
                $peer->Params['burnDamageSpent']['omnChromatic']=true;
        }
    }
    // A Pox record has one terminal packet; no live per-UID bank is mutated after departure.
    return $n+$bonus;
}
function FaBCapturedBurnSourceUID(int $player,string $ref): int {
    $layer=FaBCapturedBurnSourceLayer($ref);return $layer&&intval($layer->Controller)===$player?intval($layer->Params['burnDamageSource']['uid']):0;
}
function FaBCapturedBurnDamageBonus(int $p,string $ref,int $uid,int $n,string $type): int {
    return $type==='ARCANE'&&$uid===FaBCapturedBurnSourceUID($p,$ref)&&FaBDamageSourceView($ref,$uid)
        ?FaBUPRDamageBonus(FaBDamageSourceActor($ref,$uid,$p),$uid,$n,$type,$ref):0;
}
function FaBCapturedBurnDeal(int $p,string $ref,int $uid,int $targetUID,int $n,string $type='ARCANE',int $payment=0): int {
    $target=FaBFindUID($targetUID);
    if($type!=='ARCANE'||$uid<1||$uid!==FaBCapturedBurnSourceUID($p,$ref)||!FaBDamageSourceView($ref,$uid)
        ||!$target||$target['zone']!=='Hero'||!in_array(intval($target['player']),FaBOpponents($p),true))return 0;
    return FaBUPRDeal(FaBDamageSourceActor($ref,$uid,$p),$uid,$targetUID,$n,$type,$payment,$ref);
}
function FaBCapturedPoxDamage(int $p,array $r,int $victim,int $n,string $type='GENERIC'): int {
    if(!FaBValidDamageSourceRecord($r,'pox')||intval($r['controller'])!==$p||$victim!==$p||$n!==2||$type!=='GENERIC')return 0;
    $ref=FaBCapturedPoxSourceRef($p,$r);
    if(!FaBDamageSourceView($ref,intval($r['uid'])))return 0;
    return DoDamage(FaBDamageSourceActor($ref,intval($r['uid']),$p),$ref,$victim,$n,$type);
}
function FaBCapturedPoxSourceRef(int $p,array $r): string {
    return FaBValidDamageSourceRecord($r,'pox')&&intval($r['controller'])===$p
        ?'@PoxDamage:'.base64_encode(json_encode($r,JSON_THROW_ON_ERROR)):'';
}

function DoDamage($player, $sourceMZ, $targetPlayer, $amount, $damageType = 'PHYSICAL') {
    $iarSource=FaBDamageSourceView((string)$sourceMZ);if($iarSource&&!FaBDTDUnpreventable(intval($player),intval($iarSource['object']->UniqueID),(string)$damageType,(string)$sourceMZ))$amount=FaBIARConsecrateDamage(intval($iarSource['object']->UniqueID),intval($amount),(string)$sourceMZ);
    $targetPlayer = intval($targetPlayer); $amount = max(0, intval($amount));
    if ($amount <= 0 || !FaBSeatIsLive($targetPlayer)) return 0;
    $redirect=FaBDYNYoji(intval($player),$targetPlayer,$amount,(string)$damageType,(string)$sourceMZ);if($redirect!==null)return $redirect;
    if($damageType==='PHYSICAL')$amount=FaBELEDamageBonus(intval($player),(string)$sourceMZ,$amount,(string)$damageType);
    $source=FaBDamageSourceView((string)$sourceMZ);
    $amount=FaBPENArcDamage((string)$sourceMZ,$amount);
    if($source)$amount=FaBCapturedDamageBonus($source['object'],$amount);
    $unpreventable=($damageType==='PHYSICAL' && FaBARCEffect(intval($player),'OMN_UNPREVENTABLE'))||!empty($GLOBALS['hntUnpreventableReflection'])||($damageType==='ARCANE'&&FaBEVRUnpreventable(intval($player),$targetPlayer))||($source&&(FaBMSTUnpreventable($source['object'])||in_array('UPR_UNPREVENTABLE',(array)($source['object']->TurnEffects??[]),true)||in_array(FaBWTRBase($source['object']->CardID),['malign','murkmire_grapnel'],true)));
    if($damageType==='ARCANE'&&$unpreventable)FaBROSShelterFallback($targetPlayer,$amount,true);
    if($unpreventable)FaBDYNWard($targetPlayer,$amount,true);
    if(!$unpreventable){
    $amount = FaBOMNArcanePrevent($targetPlayer, $amount, (string)$damageType);
    $amount = FaBOMNPrevent($targetPlayer, $amount, (string)$damageType);
    if($damageType==='ARCANE')$amount=FaBSUPArcanePrevent($targetPlayer,$amount,intval($source['object']->UniqueID??0));
    $amount=FaBROSPrevent($targetPlayer,$amount,(string)$damageType,intval($source['object']->UniqueID??0));
    $amount=FaBPENPrevent($targetPlayer,$amount);$amount=FaBSUPPrevent($targetPlayer,$amount);$amount=FaBHNTPrevent($targetPlayer,$amount);$amount=FaBSEAPrevent($targetPlayer,$amount);
    $amount=FaBMSTPrevent($targetPlayer,$amount,(string)$sourceMZ);
    $amount=FaBDTDConsumePrevention($targetPlayer,intval($source['object']->UniqueID??0),$amount);
    $amount=FaBDTDPrevent($targetPlayer,$amount,(string)$sourceMZ);$amount=FaBHVYPrevent($targetPlayer,$amount);
    $beforePrevention=$amount;$amount=FaBOUTPrevent($targetPlayer,$amount,(string)$damageType);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBWTRPreventDamage($targetPlayer, $amount, (string)$damageType);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBARCPreventDamage($targetPlayer, $amount, (string)$damageType);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBCRUPrevent($targetPlayer,$amount,(string)$damageType);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBEVRPrevent($targetPlayer,$amount,(string)$damageType,(string)$sourceMZ);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBUPRPrevent($targetPlayer,$amount,(string)$sourceMZ);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    if($damageType==='ARCANE')$amount=FaBROSShelterFallback($targetPlayer,$amount,false);
    $beforePrevention=$amount;$amount=FaBDYNWard($targetPlayer,$amount);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBMONPrevent($targetPlayer,$amount);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    $beforePrevention=$amount;$amount=FaBBoltynPrevent($targetPlayer,$amount);$amount=FaBOUTVambrace($beforePrevention,$amount,(string)$damageType);
    }
    if($amount>0)FaBCRUAdd($targetPlayer,'DAMAGED',$amount);
    if($amount>0 && $damageType==='ARCANE')FaBOMNAdd($targetPlayer,'ARCANE_TAKEN');
    if($amount>0)FaBIARAdd(intval($player),'DAMAGE:'.$targetPlayer);
    if($amount>0)FaBOMNDamaged(intval($player),$targetPlayer,FaBIdentityFromMZ(FaBDamageSourceActivityRef((string)$sourceMZ))['object']??null);
    if ($amount <= 0) return 0;
    AddHealth($targetPlayer, max(0, intval(GetHealth($targetPlayer)) - $amount));
    $hero = null; foreach (GetHero($targetPlayer) as $candidate) if (is_object($candidate) && empty($candidate->removed)) { $hero = $candidate; break; }
    if (function_exists('QueueDamageAnimation')) QueueDamageAnimation('p' . $targetPlayer . 'Hero-0', $amount, 500, true, intval($hero->UniqueID ?? 0));
    FaBMPGDamaged(intval($player),$amount);FaBSEADamaged(intval($player),$targetPlayer,$amount,FaBDamageSourceActivityRef((string)$sourceMZ));FaBHVYDamage(intval($player),$targetPlayer,$amount,(string)$damageType);FaBROSDamaged(intval($player),$targetPlayer,$amount,(string)$damageType);FaBHNTDamageRecord(intval($player),$targetPlayer,$amount,intval($source['object']->UniqueID??0),(string)$damageType,(string)$sourceMZ);FaBHNTBellona($targetPlayer,intval($source['object']->UniqueID??0),$amount,(string)$sourceMZ);
    if (intval(GetHealth($targetPlayer)) <= 0) FaBEliminateSeat($targetPlayer, intval($player));
    FaBMONDamage(intval($player),$targetPlayer,$amount,(string)$damageType,FaBDamageSourceActivityRef((string)$sourceMZ));
    FaBMONLifeLost($targetPlayer,$amount);
    FaBEVRDamaged(intval($player),$targetPlayer,$amount,(string)$damageType);
    FaBUPRDamaged(intval($player),$targetPlayer,$amount,(string)$damageType,FaBDamageSourceActivityRef((string)$sourceMZ));
    FaBELEDamaged(intval($player),$targetPlayer,$amount,FaBDamageSourceActivityRef((string)$sourceMZ));
    FaBDYNDamaged(intval($player),$amount,FaBDamageSourceActivityRef((string)$sourceMZ));
    return $amount;
}

function FaBEliminateSeat(int $seat, int $sourcePlayer = 0): void {
    FaBFreezeEliminatedBurnSources($seat);
    $live = array_values(array_filter(FaBLiveSeats(), fn($candidate) => $candidate !== $seat));
    SetLiveSeats(implode('', $live));
    if (count($live) <= 1) SetWinner(intval($live[0] ?? $sourcePlayer));
}

/** Finish elimination after the current effect has finished using its source. */
/** An attack proxy cannot outlive its physical source before damage (CR 7.2.2d). */
function FaBRetireDepartedAttackSource(): bool {
    if(FaBReactionResolutionIsPending()||FaBHasPendingDecision())return false;
    $s=FaBGetState();
    if(empty($s['combatOpen'])||(string)($s['combatStep']??'')!=='ATTACK')return false;
    $attack=FaBFindCurrentAttack($s);
    if(!$attack||intval($attack['player'])!==intval($s['attacker']??0)||intval(FaBObjectCounters($attack['object'])['MON_SOURCE_UID']??0)<1
        ||FaBMONAttackSourceExists($attack['object']))return false;
    // Ceasing is not chain-link resolution: do not award go again or fire resolved-link hooks.
    $attack['object']->removed=true;
    FaBCloseCombatChain();
    // Earned unrelated layers survive the attack's close step and retain real responses.
    if(FaBStackTop()!==null){$s=FaBGetState();$s['window']='PRIORITY';FaBSetState($s);}
    return true;
}

function GameAfterEngineAction($action, $result): void {
    $GLOBALS['fabEffectController']=0;
    FaBFreezeEliminatedBurnSources();
    $reactionFramesToCancel = FaBPrepareReactionEliminationCleanup();
    foreach (FaBSeatOrder() as $seat) {
        if (FaBSeatIsLive($seat)) continue;
        $queue = &GetDecisionQueue($seat); $queue = [];
        foreach (FaBIdentityZones() as $zone) {
            if ($zone === 'Hero') continue;
            foreach (FaBZoneGet($zone, $seat) as $obj) if (is_object($obj)) $obj->removed = true;
        }
        foreach (GetStack() as $obj) if (is_object($obj) && intval($obj->Controller ?? 0) === $seat) $obj->removed = true;
    }
    FaBFinishReactionEliminationCleanup($reactionFramesToCancel);
    FaBFinishReactionResolution();
    FaBELEEmergingAdvanceDeclarations();
    FaBRetireDepartedAttackSource();
    if(!FaBReactionResolutionIsPending()&&!FaBHasPendingDecision())FaBDTDMirage();
    if (intval(GetWinner()) !== 0) return;
    $state = FaBGetState();
    if(!empty($state['endingTurn'])&&!FaBHasPendingDecision()&&FaBStackTop()===null){
        if(!empty($state['rosEndPitchWaiting']))FaBQueueEndPitch(intval($state['endingTurn']));else FaBFinishEndTurn(intval($state['endingTurn']));
        return;
    }
    FaBRepairMultiTargetDefender();$state=FaBGetState();
    // A defending hero can leave after damage without erasing the attack's earned
    // hit choices or go again. Keep the chain focused until its normal close.
    if (!empty($state['combatOpen']) && (!FaBSeatIsLive(intval($state['attacker']))
        || (!FaBSeatIsLive(intval($state['defender'])) && !in_array($state['combatStep'], ['DAMAGE', 'RESOLUTION'], true)))) {
        FaBCloseCombatChain();
    }
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
    if(FaBDTDCombatPrevention())return;
    $state = FaBGetState();
    $attack=FaBFindUID(intval($state['attackUID']));
    if($attack&&!FaBARCCard(intval($state['attackUID']),'evrShatterChecked')&&in_array('EVR_SHATTER',(array)$attack['object']->TurnEffects,true)&&FaBEVRShatterTargets()!==''){FaBARCSetCard(intval($state['attackUID']),'evrShatterChecked',true);FaBRunSourceMacro('ResolveAbility',intval($state['attacker']),'shatter_yellow',['mzID'=>$attack['mzID']]);return;}
    if($attack&&!FaBUPRUnpreventable(intval($state['attackUID']))&&($state['attackTarget']['type']??'HERO')==='HERO'&&!FaBARCCard(intval($state['attackUID']),'uprQuellChecked')){FaBARCSetCard(intval($state['attackUID']),'uprQuellChecked',true);if(FaBUPRQuellChoices(intval($state['defender']))!==''&&FaBAttackPower($state)>FaBDefenseValue($state)){FaBRunSourceMacro('ResolveAbility',intval($state['defender']),'quelling_robe',['mzID'=>$attack['mzID'],'uprResumeDamage'=>true]);return;}}
    if(count($state['attackTargets']??[])>1){FaBMultiTargetDamage($state);return;}
    $power = FaBAttackPower($state); $defense = FaBDefenseValue($state);
    $amount = FaBEVRCount(intval($state['attacker']),'SHATTER_REPLACE')?0:max(0, $power - $defense);FaBEVRClear(intval($state['attacker']),'SHATTER_REPLACE'); $attack = FaBFindUID(intval($state['attackUID']));
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
                $amount=FaBUPRDeal(intval($state['attacker']),intval($state['attackUID']),intval($target['uid']),$amount,'PHYSICAL');
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
    if (($attack !== null && $attack['zone']==='CombatChain' && FaBAttackHasGoAgain($state, $attack['object'])) || !empty($state['attackGoAgain'])) { AddActionPoints(intval($state['attacker']), intval(GetActionPoints(intval($state['attacker']))) + 1);FaBROSGo(intval($state['attacker']),intval($state['attackUID'])); }
    FaBCleanupResolvedLink($state);
    if($attack!==null)FaBHNTChainResolved($attack['object']);
    $state = FaBGetState(); $state['combatStep'] = 'RESOLUTION'; $state['window'] = 'RESOLUTION'; $state['handBlockUIDs'] = []; FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
    FaBAutoPassShortcuts();
}

/**
 * Timing-only compatibility correction: defer the existing per-link decision.
 * Dynamic Nerves/ability eligibility at the actual close event is separate work.
 */
function FaBDeferBattlewornCounter(object $source): void {
    $uid=intval($source->UniqueID??0);$link=intval($source->ChainLink??0);
    if($uid<1||$link<1)return;
    $state=FaBGetState();
    $state['battlewornClosePending'][(string)$link][(string)$uid]=true;
    FaBSetState($state);
}

/** Preserve existing relative close-hook order and never recreate a departed card. */
function FaBApplyDeferredBattlewornCounters(): void {
    $state=FaBGetState();$pending=$state['battlewornClosePending']??[];
    unset($state['battlewornClosePending']);FaBSetState($state);
    if(empty($state['combatOpen'])||!is_array($pending))return;
    foreach($pending as$link=>$uids){
        if(!is_array($uids))continue;
        foreach($uids as$uid=>$eligible){
            if($eligible!==true)continue;
            $found=FaBFindUID(intval($uid));
            if($found===null||$found['zone']!=='CombatChain')continue;
            $source=$found['object'];
            if(($source->Role??'')!=='DEFENSE'||intval($source->ChainLink??0)!==intval($link)
                ||!in_array($source->FromZone??'',['Equipment','Hero','Weapons'],true))continue;
            FaBSetObjectCounter($source,'DEFENSE',intval(FaBObjectCounters($source)['DEFENSE']??0)+1);
        }
    }
}

function FaBCleanupResolvedLink(array $state): void {
    FaBPENLinkResolved($state);
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        FaBSUPRememberMiss($obj,$state);FaBPENResolvedDefender($seat,$obj);if(!empty($obj->removed))continue;
        if(($obj->Role??'')==='ATTACK'&&$obj->CardID==='swing_big_red'&&empty($state['attackHit']))FaBWTRTag($obj,'EVR_SWING_MISS:'.intval($state['defender']));
        if (($obj->Role ?? '') !== 'DEFENSE' || !in_array(($obj->FromZone ?? ''),['Equipment','Hero','Weapons'],true)) continue;
        if(FaBMONArena($seat,'nerves_of_steel')&&FaBAttackPower($state)<=2)FaBWTRTag($obj,'EVR_SKIP_TEMPER');
        if (FaBHasKeyword($obj, 'Blade Break')) {
            $obj->TurnEffects = array_values(array_unique(array_merge(is_array($obj->TurnEffects) ? $obj->TurnEffects : [], ['DESTROY_ON_CHAIN_CLOSE'])));
        } elseif (FaBHasKeyword($obj, 'Battleworn')&&!(FaBMONArena($seat,'nerves_of_steel')&&FaBAttackPower($state)<=2)) {
            FaBDeferBattlewornCounter($obj);
        }
    }
}

function FaBCloseCombatChain(): void {
    FaBApplyDeferredBattlewornCounters();
    FaBIARClose();FaBMSTBeforeClose();FaBDTDClose();FaBHNTClose();
    FaBCRUClose();
    FaBELEClose();
    foreach(FaBLiveSeats() as $p)FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='MON_VANGUARD')));
    $state = FaBGetState(); if (empty($state['combatOpen'])) return;
    foreach (FaBSeatOrder() as $seat) {
        FaBWTRSetEffects($seat,array_values(array_filter(FaBWTREffects($seat),fn($e)=>!in_array($e['type']??'',['FAI_BRAND','MON_VANGUARD'],true))));
        foreach(GetBanish($seat) as $card)if(is_object($card)&&!empty(FaBObjectCounters($card)['FAI_CHAIN_PLAY']))$card->PlayableFromBanish=0;
        foreach(GetBanish($seat)as$card)if(is_object($card)&&intval($card->PlayableChainLink??0)>0){$card->PlayableFromBanish=0;$card->PlayableChainLink=0;}
        $chain = GetCombatChain($seat);
        foreach ($chain as $obj) {
            if (!is_object($obj) || !empty($obj->removed)) continue;
            $uid = intval($obj->UniqueID ?? 0); $role = (string)($obj->Role ?? ''); $from = (string)($obj->FromZone ?? '');
            if(FaBPENCloseCard($seat,$obj))continue;FaBHVYClose($seat,$obj);FaBSUPClose($obj);
            if(!in_array('EVR_SKIP_TEMPER',(array)$obj->TurnEffects,true))FaBBoltynTemper($seat,$obj);
            foreach((array)$obj->TurnEffects as $tag)if(str_starts_with($tag,'EVR_SWING_MISS:'))FaBEVRCreate(intval(substr($tag,15)),'quicken',1);
            $effects = is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [];
            if(in_array('UPR_CLOSE_DRAW',$effects,true))DoDrawCard($seat,1);
            if(in_array('ELE_BOTTOM_CLOSE',(array)$obj->TurnEffects,true)){FaBARCToDeck($seat,intval($obj->UniqueID),false);continue;}
            if(FaBEVOHeroClose($seat,$obj)||FaBMONCloseMove($obj,$seat))continue;
            if ($role === 'DEFENSE' && in_array($from,['Equipment','Weapons'],true) && !in_array('DESTROY_ON_CHAIN_CLOSE', $effects, true)) FaBMoveUID($uid, $from, intval($obj->Owner ?? $seat));
            elseif ($role === 'ATTACK' && $from === 'Weapons') {
                // Preserve the same attack-type LKI as FaBMoveUID before retiring this proxy.
                $s=FaBGetState();$s['departedChainTypes'][(string)$seat][(string)$obj->ChainLink]=EffectiveCardType($obj);FaBSetState($s);
                $obj->removed = true;
            }
            elseif (function_exists('FaBWTRMoveReplacement') && FaBWTRMoveReplacement($obj, 'Graveyard', intval($obj->Owner ?? $seat))) continue;
            else FaBMoveUID($uid, 'Graveyard', intval($obj->Owner ?? $seat));
        }
    }
    foreach(FaBLiveSeats() as $p)FaBHVYClear($p,'ASSASSIN_CHAIN');
    $s=FaBGetState();$s['supSoulBanished']=[];FaBSetState($s);
    FaBMSTClose();FaBDYNClose();
    FaBOUTClose();
    OnCombatChainClosed(intval(GetTurnPlayer()));
    $state = FaBResetWindowState(); FaBSetState($state);
    SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0);
}

function FaBPassPriority(int $player, bool $automatic = false): bool {
    if (FaBReactionResolutionIsPending()) return false;
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
        $state['combatStep'] = 'REACTION'; $state['window'] = 'REACTION'; FaBSetState($state); FaBOUTCyclone();
        SetPriorityPlayer(intval(GetTurnPlayer())); FaBAutoPassShortcuts(); return true;
    }
    if ($state['window'] === 'REACTION') { FaBBeginDamageStep(); return true; }
    if ($state['window'] === 'DAMAGE') { FaBBeginResolutionStep(); return true; }
    if ($state['window'] === 'RESOLUTION') { FaBCloseCombatChain(); return true; }
    if ($state['window'] === 'ACTION') {
        $turnPlayer = intval(GetTurnPlayer());
        $state['window'] = 'END_PHASE'; FaBSetState($state); SetPriorityPlayer($turnPlayer);
        FaBDTDEndActionPermissions($turnPlayer);
        if (FaBUPRBeginReadEnd($turnPlayer)) return true;
        FaBQueueIntimidateReturns(); return true;
    }
    if ($state['window'] === 'END_PHASE') { FaBEndTurn(intval(GetTurnPlayer())); return true; }
    SetPriorityPlayer(intval(GetTurnPlayer())); return true;
}

function FaBPlayerHasPriorityAction(int $player): bool {
    foreach (['Hand','Arsenal','Banish','Hero','Weapons','Equipment','Arena','CombatChain','Deck','Graveyard'] as $zone) foreach (FaBZoneGet($zone,$player) as $index => $obj) {
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
    $actor=$obj->CardID==='great_library_of_solana'?intval(GetPriorityPlayer()):$owner;
    if($location==='Arsenal'&&FaBMPGCanStealPlay(intval(GetPriorityPlayer()),['object'=>$obj,'zone'=>$location,'player'=>$owner]))$actor=intval(GetPriorityPlayer());
    if ($owner < 1 || $index < 0 || $location === '' || intval(GetPriorityPlayer()) !== $actor) {
        return json_encode(['highlight' => false]);
    }

    $mzID = 'p' . $owner . $location . '-' . $index;
    // Prefer the window-specific action color when a card also has a legal
    // play/activation (for example, an instant that can be put in arsenal).
    if (CanPitchCard($actor, $mzID)) return json_encode(['color' => 'rgba(80, 165, 255, 0.92)']);
    if (FaBCanBlock($actor, $mzID)) return json_encode(['color' => 'rgba(180, 185, 195, 0.92)']);
    if (FaBCanArsenal($actor, $mzID)) return json_encode(['color' => 'rgba(255, 155, 55, 0.92)']);

    $legal = CanPlayCard($actor, $mzID)
        || (function_exists('FaBWTRCanActivate') && FaBWTRCanActivate($actor, $mzID));

    return $legal
        ? json_encode(['color' => 'rgba(86, 255, 126, 0.92)'])
        : json_encode(['highlight' => false]);
}

/** Map each priority opportunity to its independently configurable shortcut. */
function FaBShortcutWindow(int $player, array $state): string {
    return match ($state['window']) {
        'DEFEND_DECLARE' => 'BLOCK',
        'REACTION' => $player === intval($state['attacker']) ? 'ATTACK_REACTION'
            : (FaBIsDefendingHero($player, $state) ? 'DEFENSE_REACTION' : 'OTHER_REACTION'),
        'ACTION' => 'ACTION_PRIORITY',
        'PRIORITY' => 'INSTANT_PRIORITY',
        'ATTACK' => 'ATTACK_PRIORITY',
        'DEFEND_PRIORITY' => 'DEFEND_PRIORITY',
        'DAMAGE' => 'DAMAGE_PRIORITY',
        'RESOLUTION' => 'RESOLUTION_PRIORITY',
        'END_PHASE' => 'END_PHASE',
        default => '',
    };
}

/** No card/ability priority while a Read beginning-End cohort is being processed. */
function FaBAutomaticBeginningEndActive(): bool {
 $m=FaBGetState()['automaticBeginningEnd']??null;
 return is_array($m)&&intval($m['turn']??0)===intval(GetTurnNumber())
  &&intval($m['controller']??0)===intval(GetTurnPlayer());
}

/** Reuse native layer resolution and its owned awaits; stop only for real player choices. */
function FaBDrainAutomaticBeginningEnd(): bool {
 static $running=false;
 if(!FaBAutomaticBeginningEndActive())return false;
 $m=FaBGetState()['automaticBeginningEnd'];
 if(($m['stage']??'')!=='RESOLVING')return false;
 if($running)return true;
 $running=true;
 try{
  while(FaBAutomaticBeginningEndActive()){
   if(FaBHasPendingDecision()||FaBReactionResolutionIsPending())return true;
   $top=FaBStackTop();
   if(!$top){
    $s=FaBGetState();$s['automaticBeginningEnd']['stage']='ARSENAL_READY';$s['window']='END_PHASE';FaBSetState($s);
    SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);return true;
   }
   $uid=intval($top->UniqueID);$p=intval($top->Controller);
   $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Stack')return true;
   if(!DoResolveCard($p,$f['mzID']))return true;
   // Same existing elimination/await completion/game-state hooks as a native action.
   GameAfterEngineAction('AUTOMATIC_BEGINNING_END',true);
   if(FaBHasPendingDecision()||FaBReactionResolutionIsPending())return true;
   $next=FaBStackTop();if($next&&intval($next->UniqueID)===$uid)return true;
  }
 }finally{$running=false;}
 return true;
}

function FaBAutoPassShortcuts(): void {
    if (!empty(FaBGetState()['eleEmergingDeclarations'])) return;
    if (FaBDrainAutomaticBeginningEnd()) return;
    if (FaBReactionResolutionIsPending()) return;
    static $running = false;
    if ($running) return;
    $running = true;
    try {
        // Four-seat combat can require more than sixteen consecutive passes.
        for ($guard = 0; $guard < 256 && intval(GetWinner()) === 0; ++$guard) {
            if (!empty(FaBGetState()['eleEmergingDeclarations'])) return;
            if (FaBReactionResolutionIsPending()) return;
            foreach (FaBLiveSeats() as $seat) if (count(GetDecisionQueue($seat)) > 0) return;
            $player = intval(GetPriorityPlayer()); $state = FaBGetState();
            if (!FaBIsPassiveSeat($player)) {
                $window = FaBShortcutWindow($player, $state);
                if ($window === '' || !ShouldAutoPassShortcutWindow($player, $window)) break;
                // Preserve action/chain-continuation decisions on the active player's turn.
                if ($player === intval(GetTurnPlayer()) && FaBStackTop() === null) {
                    if (in_array($state['window'], ['ACTION', 'RESOLUTION'], true)
                        && FaBPlayerHasPriorityAction($player)) break;
                    if ($state['window'] === 'END_PHASE') {
                        foreach (FaBChoiceRefs($player, 'Hand') as $ref) if (FaBCanArsenal($player, $ref)) return;
                    }
                }
            }
            $turn = intval(GetTurnNumber());
            $wasInCombat = !empty($state['combatOpen']);
            if (!FaBPassPriority($player, true)) break;
            if ($turn !== intval(GetTurnNumber()) || ($wasInCombat && empty(FaBGetState()['combatOpen']))) break;
        }
    } finally {
        $running = false;
    }
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
    if($f&&(FaBSEAWeaponCanAttack($player,$f)||FaBMONArenaCanAttack($player,$f)))$actions['ACTIVATE']='Attack';
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
    if (FaBAutomaticBeginningEndActive() && $action !== 'ARSENAL') return false;
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

$customDQHandlers['FAB_DECLARE_REACTION_MODE'] = function($player, $parts, $lastDecision) {
    FaBContinueDeclaredReactionMode(intval($player), (array)$parts, (string)$lastDecision);
};

$customDQHandlers['FAB_DECLARE_REACTION_TARGET'] = function($player, $parts, $lastDecision) {
    FaBContinueDeclaredReaction(intval($player), (array)$parts, (string)$lastDecision);
};

$customDQHandlers['FAB_MON_MEMORIAL_TARGET'] = function($player, $parts, $lastDecision) {
    if (count($parts) !== 2) return;
    $uid = intval($parts[0]);
    if (FaBMONBindMemorialTarget(intval($player), $uid, intval($parts[1]), (string)$lastDecision)) FaBFinishPreparedCard($uid);
};

$customDQHandlers['FAB_HNT_TIP_TARGET'] = function($player, $parts, $lastDecision) {
    if (count($parts) !== 2) return;
    $uid = intval($parts[0]);
    if (FaBHNTBindTipTarget(intval($player), $uid, intval($parts[1]), (string)$lastDecision)) FaBFinishPreparedCard($uid);
};

$customDQHandlers['FAB_MON_CAPTAIN_MODE'] = function($player, $parts, $lastDecision) {
    if (count($parts) !== 2) return;
    $uid = intval($parts[0]);
    if (FaBMONBindCaptainMode(intval($player), $uid, intval($parts[1]), (string)$lastDecision)) FaBFinishPreparedCard($uid);
};

$customDQHandlers['FAB_OMN_AETHERSLING_TARGET'] = function($player, $parts, $lastDecision) {
    if (count($parts) !== 1) return;
    $uid = intval($parts[0]);
    if (FaBOMNAetherslingBind(intval($player), $uid, (string)$lastDecision)) FaBFinishPreparedCard($uid);
};

$customDQHandlers['FAB_UPR_SAND_TARGET'] = function($player, $parts, $lastDecision) {
    FaBUPRContinueSandTarget(intval($player), (array)$parts, (string)$lastDecision);
};

$customDQHandlers['FAB_MON_ARCANIC_TARGET'] = function($player, $parts, $lastDecision) {
    if (count($parts) === 1) FaBMONArcanicBind(intval($player), intval($parts[0]), (string)$lastDecision);
};

$customDQHandlers['FAB_LUNGING_TARGET'] = function($player, $parts, $lastDecision) {
    FaBCRULungingContinue(intval($player), (array)$parts, (string)$lastDecision);
};

$customDQHandlers['FAB_LUNGING_PLAY'] = function($player, $parts, $lastDecision) {
    FaBCRULungingPlaySelected(intval($player), (array)$parts);
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
    $sourceUID=max(0,intval($parts[1]??0));
    if($sourceUID>0)FaBWTRQueueAttackIntimidate(intval($player),intval($target['player']),max(1,intval($parts[0]??1)),$sourceUID);
    else FaBIntimidate(intval($player), $target['player'], max(1, intval($parts[0] ?? 1)));
};

function FaBCanArsenal(int $player, string $mzID): bool {
    if (FaBAutomaticBeginningEndActive() && (FaBGetState()['automaticBeginningEnd']['stage'] ?? '') !== 'ARSENAL_READY') return false;
    if ($player !== intval(GetTurnPlayer()) || intval(GetPriorityPlayer()) !== $player) return false;
    $state = FaBGetState(); if ($state['window'] !== 'END_PHASE' || $state['pendingPayment'] !== null) return false;
    $found = FaBIdentityFromMZ($mzID);
    if ($found === null || $found['player'] !== $player || $found['zone'] !== 'Hand') return false;
    if(!FaBELEArsenalSpace($player))return false;
    return true;
}

function FaBArsenalCard(int $player, string $mzID): bool {
    if (!FaBCanArsenal($player, $mzID)) return false;
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return false;
    SaveUndoVersion($player, 'Before putting a card in arsenal');
    return FaBMoveUID(intval($found['object']->UniqueID), 'Arsenal', $player) !== null;
}

function DoActivatedAbility($player, $mzID, $abilityIndex = 0) {
    if (FaBAutomaticBeginningEndActive()) return false;
    return function_exists('FaBWTRActivate') ? FaBWTRActivate(intval($player), (string)$mzID, intval($abilityIndex)) : false;
}

function DoDrawCard($player, $amount) {
    if (!FaBPENCanDraw()) return false;
    if (function_exists('FaBWTRCanDraw') && !FaBWTRCanDraw(intval($player))) return false;
    if(FaBUPRBleak()&&empty(FaBGetState()['uprRefill']))return;
    $amount=FaBPENGoldDraw(intval($player),intval($amount));$amount=FaBEVRDrawAmount(intval($player),intval($amount));$drawn=0;
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < intval($amount); ++$i) {
        $top = null;
        foreach ($deck as $obj) if (is_object($obj) && empty($obj->removed)) { $top = $obj; break; }
        if ($top === null) break;
        if(FaBIARDrawReplacement(intval($player))||FaBSEADrawReplacement(intval($player))||FaBDTDDrawReplacement(intval($player)))continue;
        FaBMoveUID(intval($top->UniqueID), 'Hand', intval($player));++$drawn;
    }
    FaBEVRDrew(intval($player),$drawn);
    FaBSEADrew(intval($player),$drawn);
    FaBDYNDrawn(intval($player),$drawn);FaBDTDAdd(intval($player),'DRAWN',$drawn);
    return true;
}

/** AddActionPoints assigns an absolute balance; veto only prohibited increases. */
function FaBBeforeActionPoints($player, $value, $source = null): bool {
    if ($value <= GetActionPoints(intval($player))) return true;
    return intval($player) === intval(GetTurnPlayer()) && GetCurrentPhase() === 'MAIN';
}

function StartOfTurnPhase() {
    $player = intval(GetTurnPlayer()); AddResources($player, 0);
    SetCurrentPhase('MAIN'); AddActionPoints($player, 1);
    foreach (['Hero', 'Weapons', 'Equipment', 'Arena'] as $zoneName) foreach (FaBZoneGet($zoneName, $player) as $obj) if (is_object($obj) && empty($obj->removed) && FaBSEACanUntap($obj) && FaBSUPReadyAtStart($obj)) $obj->Status = 2;
    $state = FaBResetWindowState();
    unset($state['automaticBeginningEnd'], $state['uprReadBeginningTurn']);
    $state['dtdStarting']=true;
    $state['turnEffects'][(string)$player] = array_merge($state['turnEffects'][(string)$player]??[], $state['nextTurnEffects'][(string)$player] ?? []);
    unset($state['nextTurnEffects'][(string)$player]);
    $state['hitsThisTurn'][(string)$player] = [];
    $state['cardsPlayedThisTurn'] = [];
    $state['weaponHits'] = []; $state['attackActionHits'] = [];
    $state['arcaneDealt'] = []; $state['arcActions'] = [];
    FaBSetState($state);
    FaBEnsureGoldfishOpponents($state);
    FaBMPGStart($player);FaBSUPStart($player);FaBEVRStart($player);
    if (function_exists('FaBWTRStartTurn')) FaBWTRStartTurn($player);
    FaBPENStart($player);FaBOMNStart($player);FaBIARStart();
    FaBARCStartTurn($player);
    FaBCRUStart($player);
    FaBIraStartTurn($player);
    FaBMONStart($player);
    $s=FaBGetState();$s['uprRefill']=false;FaBSetState($s);
    FaBELEStart($player);
    FaBUPRStart($player);
    FaBDYNStart($player);
    FaBOUTStart($player);FaBDTDStart($player);FaBEVOStart($player);FaBHVYStart($player);FaBMSTStart($player);FaBROSStart($player);FaBHNTStart($player);FaBSEAStart($player);
    FaBLeviaStart($player);
    FaBPrismStart($player);
    if(FaBHasPendingDecision())DecisionQueueController::AddDecision($player,'CUSTOM','FAB_DTD_ACTION_PHASE',1);else FaBDTDBeginActionPhase();
    SetPriorityPlayer($player); SetConsecutivePasses(0);
}

function MainPhase() {}

$customDQHandlers['FAB_DTD_ACTION_PHASE']=function($player,$parts,$lastDecision){FaBDTDBeginActionPhase();};
$customDQHandlers['FAB_ARC_SETUP']=function($player,$parts,$lastDecision){
    foreach(FaBChoiceRefs(intval($player),'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];FaBRunSourceMacro('StartTurn',intval($player),$o->CardID,['mzID'=>$ref]);}
};

function EndOfTurnPhase() { FaBEndTurn(intval(GetTurnPlayer())); }

function FaBEndTurn(int $player): bool {
    if (FaBAutomaticBeginningEndActive() && (FaBGetState()['automaticBeginningEnd']['stage'] ?? '') === 'RESOLVING') return true;
    if ($player !== intval(GetTurnPlayer())) return false;
    if(!empty(FaBGetState()['endingTurn']))return true;
    SetCurrentPhase('END');
    FaBCloseCombatChain();
    // Only flat-only pre-repair saves use the historical compatibility cleanup.
    FaBReturnIntimidatedCards(true);
    if (function_exists('FaBWTREndTurn')) FaBWTREndTurn($player);
    FaBARCEndTurn($player);
    FaBCRUEnd($player);
    $bloodDebtAtEnd=FaBMONBloodDebt($player);
    FaBIARWindEnd($player);FaBOMNEnd();FaBIAREnd($player);FaBMONEnd($player,$bloodDebtAtEnd);FaBDTDEnd($player);FaBHVYEnd();FaBMSTEnd($player);FaBROSEnd($player);FaBHNTEnd($player);FaBSEAEnd($player);FaBMPGEnd($player);FaBSUPEnd($player);FaBPENEnd();
    $state=FaBGetState();$state['endingTurn']=$player;FaBSetState($state);
    FaBEVREnd($player);
    FaBUPREnd($player);
    FaBDYNEnd($player);
    if(FaBOUTHasDiseases($player))FaBRunSourceMacro('EndTurn',$player,'bloodrot_pox',[]);
    FaBArakniEnd($player);
    FaBELEEnd($player);
    if(FaBHasPendingDecision()){DecisionQueueController::AddDecision($player,'CUSTOM','FAB_ELE_END_PITCH',1);return true;}
    return FaBQueueEndPitch($player);
}

$customDQHandlers['FAB_ELE_END_PITCH']=function($player,$parts,$lastDecision){FaBQueueEndPitch(intval($player));};
function FaBQueueEndPitch(int $player): bool {
    $s=FaBGetState();
    // A resumed End macro can append its next choice after this queued continuation.
    // Return pitch only after every actual End choice has completed.
    if(FaBHasPendingDecision()){$s['rosEndPitchWaiting']=true;FaBSetState($s);return true;}
    if(FaBStackTop()!==null){$s['rosEndPitchWaiting']=true;$s['window']='PRIORITY';FaBSetState($s);return true;}unset($s['rosEndPitchWaiting']);FaBSetState($s);
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
    FaBSUPReadyAtEnd($player);
    FaBEVOExpire($player);
    FaBOUTEndPermissions($player);
    $state=FaBGetState();unset($state['endingTurn']);FaBSetState($state);
    $hero = GetHero($player); $intellect = !empty($hero) ? max(0, intval(CardIntelligence($hero[0]->CardID))) : 4;
    if (function_exists('FaBWTRIntellectModifier')) $intellect += FaBWTRIntellectModifier($player);
    $hand = GetHand($player); $count = 0; foreach ($hand as $obj) if (is_object($obj) && empty($obj->removed)) ++$count;
    // CR 4.4.3e-f: clear every player's action points before end-turn refill.
    foreach (FaBSeatOrder() as $seat) AddActionPoints($seat, 0);
    $s=FaBGetState();$s['uprRefill']=true;FaBSetState($s);
    if ($count < $intellect) DoDrawCard($player, $intellect - $count);
    if (intval(GetTurnNumber()) === 1) {
        foreach (FaBLiveSeats() as $seat) {
            if ($seat === $player) continue;
            $hero = GetHero($seat);
            $intellect = intval(CardIntelligence($hero[0]->CardID ?? '')) + FaBWTRIntellectModifier($seat);
            DoDrawCard($seat, max(0, $intellect - FaBHandCount($seat)));
        }
    }
    foreach (FaBSeatOrder() as $seat) {
        foreach (FaBIdentityZones() as $zone) foreach (FaBZoneGet($zone, $seat) as $obj) {
            if (is_object($obj) && property_exists($obj, 'TurnEffects')) $obj->TurnEffects = [];
        }
        // Aura effects persist while their source remains in the arena.
        FaBWTRSetEffects($seat, array_values(array_filter(FaBWTREffects($seat), fn($effect) => !empty($effect['persistentUntilUsed']) || !empty($effect['persistentUID']) || !empty($effect['expiresAtStartOf']) || (!empty($effect['expiresAfterTurnOf']) && intval($effect['expiresAfterTurnOf']) !== $player))));
        foreach(GetBanish($seat)as$obj)if(is_object($obj)){if(!FaBARCCard(intval($obj->UniqueID),'hntTrapUntilStart')&&!(isset(FaBObjectCounters($obj)['EVR_PLAY_UNTIL'])&&($seat!==$player||intval(GetTurnNumber())<=intval(FaBObjectCounters($obj)['EVR_PLAY_UNTIL']))))$obj->PlayableFromBanish=0;$obj->PlayableChainLink=0;}
        AddResources($seat, 0);
    }
    AddResources($player, 0);
    FaBROSFinishTurn($player);FaBMPGExpire($player);
    $next = FaBNextInteractiveSeat($player); SetTurnPlayer($next); SetTurnNumber(intval(GetTurnNumber()) + 1);
    StartOfTurnPhase(); SetCurrentPhase('MAIN'); SaveUndoVersion($next, 'Start of turn');
    return true;
}

function FaBPassTurn($player) { return FaBPassPriority(intval($player)); }

?>
