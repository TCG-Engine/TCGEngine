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
    foreach (['mpgDurations', 'botProfiles', 'turnEffects', 'nextTurnEffects', 'hitsThisTurn', 'cardsPlayedThisTurn', 'weaponHits', 'attackActionHits', 'arcNames', 'arcaneDealt', 'arcCards', 'arcActions'] as $key) {
        $state[$key] = is_array($previous[$key] ?? null) ? $previous[$key] : [];
    }
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
    if (($obj->Location??'')==='Graveyard' && !empty($obj->FaceDown)) return [];
    $override=FaBObjectCounters($obj)['_overrides']['type']??null;
    if($override!==null)return is_array($override)?$override:array_values(array_filter(array_map('trim',explode(',',$override))));
    $types=FaBROSMeldTypes($obj,(array)CardTypes($obj->CardID));
    if($obj->CardID==='colors_of_aria_red' && empty($obj->FaceDown))$types=array_merge($types,['Earth','Ice','Lightning']);
    if(in_array('Demi-Hero',$types,true)&&((FaBFindUID(intval($obj->UniqueID??0))['zone']??'')==='Hero'))$types[]='Hero';
    if(in_array(CardName($obj->CardID), FaBGetState()['dynIllusionNames']??[], true))$types[]='Illusionist';
    if(in_array('UPR_GHOST',(array)($obj->TurnEffects??[]),true))$types=['Illusionist','Ally'];
    if(!empty(FaBObjectCounters($obj)['EVR_TOKEN']))$types[]='Token';
    if(in_array($obj->CardID,['adaptive_plating','adaptive_dissolver'],true)&&!empty(FaBObjectCounters($obj)['EVO_SLOT']))$types[]=FaBObjectCounters($obj)['EVO_SLOT'];
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

function FaBCurrentAttackHasKeyword(array $state, string $keyword): bool {
    $attack = FaBFindUID(intval($state['attackUID'] ?? 0));
    if ($attack === null) return false;
    if(FaBMPGSuppressed($attack['object']) || in_array('PEN_BLANK_ATTACK',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&in_array('PEN_NO_DOMINATE',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&in_array('MPG_NO_DOMINATE',(array)$attack['object']->TurnEffects,true))return false;
    if(strtolower($keyword)==='dominate'&&FaBDTDCount(intval($state['attacker']),'DOMINATE'))return true;
    if(strtolower($keyword)==='overpower'&&FaBWTRBase($attack['object']->CardID)==='vantage_point'&&FaBROSCount(intval($state['attacker']),'AURA'))return true;
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
    FaBSetState($state); FaBHVYAdd($sourcePlayer,'INTIMIDATED'); return $banished;
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
            FaBDYNRandomDiscard($player,$uid,(string)$cardID);
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
    $sentinels=array_values(array_filter($targets,fn($t)=>($f=FaBFindUID(intval($t['uid'])))&&$f['object']->CardID==='arc_light_sentinel_yellow'&&!HasNoAbilities($f['object'])));
    return $sentinels?:FaBSEAChumTargets($attacker,$targets);
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
    }
    return $added;
}

function FaBMoveUID(int $uid, string $toZone, ?int $targetPlayer = null, bool $animate = true): ?object {
    $found = FaBFindUID($uid);
    if ($found === null || $found['zone'] === 'Stack') return null;
    if ($toZone === 'Graveyard' && function_exists('FaBWTRBase') && FaBWTRBase((string)$found['object']->CardID) === 'drone_of_brutality') $toZone = 'Deck';
    $source = $found['object'];
    if ($toZone === 'Graveyard' && in_array($source->CardID, ['engulfing_shadows_yellow','embraforged_gauntlet'], true)) $toZone = 'Banish';
    if($toZone==='Graveyard'&&FaBARCCard($uid,'hntTrapReplaceTurn',-1)===intval(GetTurnNumber()))$toZone='Banish';
    if(!in_array($toZone,['Stack','CombatChain'],true)&&!empty(FaBObjectCounters($source)['HNT_ORIGINAL'])){$source->CardID=FaBObjectCounters($source)['HNT_ORIGINAL'];unset($source->Counters['HNT_ORIGINAL']);}
    if($found['zone']==='Banish'){$permissions=FaBGetState();unset($permissions['outInfiltrate'][$uid]);FaBSetState($permissions);}
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
    $source->removed = true;
    if($toZone==='Graveyard' && $source->CardID==='goldfin_harpoon_yellow')return null;
    if(($source->CardID==='hyper_driver'&&!in_array($toZone,['Arena','CombatChain'],true))||($toZone==='Graveyard'&&FaBHasKeyword($source,'Ephemeral')))return null;
    $newObj = FaBAddToZone($toZone, $targetPlayer, $source);
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

function FaBCardCost(object $obj, int $player): int {
    $base = $obj->CardID==='imposing_visage_blue'?3:max(0, intval(CardCost($obj->CardID)));
    if(FaBWTRBase($obj->CardID)==='numbskull')return $base;
    $delta = function_exists('EvaluateCostModifier') ? intval(EvaluateCostModifier($obj->CardID, $player, $obj, $base, $obj)) : 0;
    if (function_exists('FaBWTRCostModifier')) $delta += FaBWTRCostModifier($player, $obj);
    $delta += FaBARCCostModifier($player, $obj) + FaBProfessorCost($player,$obj) + FaBCRUCost($player,$obj) + FaBELETax($player) + FaBUPRCost($player,$obj) + FaBDTDCost($player,$obj) + FaBEVOCost($player,$obj) + FaBHVYCost($player,$obj) + FaBMSTCost($player,$obj) + FaBROSCost($player,$obj) + FaBHNTCost($player,$obj) + FaBSEACost($player,$obj) + FaBSUPCost($player,$obj) - ($obj->CardID==='solid_ground_blue'?FaBMPGSurges($player):0);
    if(FaBWTRBase($obj->CardID)==='jump_start'&&FaBChoiceRefs($player,'Arena',['base'=>'hyper_driver']))--$delta;
    return max(0, $base + $delta + FaBIARCost($player,$obj) + FaBOMNCost($player,$obj) + FaBMPGDefense($player,$obj) + FaBPENInflation() + FaBPENBetaCost($player,$obj) + FaBPENHavocCost());
}

function FaBAvailablePitch(int $player, int $excludedUID = 0): int {
    $total = max(0, intval(GetResources($player)));$recompense=false;
    foreach (GetHand($player) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->UniqueID ?? 0) === $excludedUID) continue;
        if(!FaBARCNamedProhibited($obj->CardID)&&!FaBHNTNamed($obj->CardID)&&FaBOUTCanPitch($player,$obj)&&FaBELECanPitch($player,$obj)){$pitch=FaBMONPitchValue($player,$obj->CardID);$total+=$pitch;if($pitch===1)$recompense=true;}
    }
    return $total+($recompense&&FaBMONArena($player,'talisman_of_recompense')?2:0);
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
        || ($isAttackReaction && $state['window'] === 'REACTION' && $player === intval($state['attacker']))
        || ($isDefenseReaction && $state['window'] === 'REACTION' && FaBIsDefendingHero($player,$state))
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
    $stackObj = AddStack(CardID:$source->CardID, Controller:$player, Kind:$kind, SourceZone:$fromZone,
        SourceUniqueID:$uid, Params:$attackTarget === null ? [] : ['attackTarget'=>$attackTarget], sourceObject:$source);
    $stackObj->Controller = $player; $stackObj->Kind = $kind; $stackObj->SourceZone = $fromZone; $stackObj->SourceUniqueID = $uid;
    $state['combatPlaySequence'] = intval($state['combatPlaySequence'] ?? 0) + 1;
    $stackObj->Counters = FaBObjectCounters($stackObj);
    $stackObj->Counters['FAB_PLAY_ORDER'] = $state['combatPlaySequence'];
    if (function_exists('QueueZoneMoveAnimation')) QueueZoneMoveAnimation($found['mzID'], 'Stack-' . intval($stackObj->mzIndex), 360, true, $uid, $uid);
    $state = FaBGetState();
    $state['pendingPayment'] = ['player' => $player, 'uid' => $uid, 'cost' => FaBCardCost($stackObj, $player), 'fromZone' => $fromZone,
        'kind' => $kind, 'returnWindow' => (string)$state['window'], 'returnCombatStep' => (string)$state['combatStep']];
    $state['window'] = 'PITCH'; FaBSetState($state);
    SetPriorityPlayer($player); SetConsecutivePasses(0);
    if(FaBMSTPrepareBoost($player,$stackObj))return true;
    if (FaBRunSourceMacro('PrepareCard', $player, $stackObj->CardID, ['mzID'=>'Stack-'.intval($stackObj->mzIndex)]) > 0) return true;
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
    $source=FaBFindUID(intval($pending['uid']));
    if($source!==null&&FaBWTRNeedsDiscard($source['object']->CardID)&&FaBHandCount(intval($player))<=1)return false;
    $found = FaBIdentityFromMZ((string)$mzID);
    return $found !== null && $found['player'] === intval($player) && $found['zone'] === 'Hand'
        && !FaBARCNamedProhibited($found['object']->CardID) && !FaBHNTNamed($found['object']->CardID) && FaBOUTCanPitch(intval($player),$found['object']) && FaBELECanPitch(intval($player),$found['object'])
        && FaBMSTCanPitch(intval($player),$found['object'],$pending) && max(0, intval(CardPitch($found['object']->CardID))) > 0;
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
    $state = FaBGetState(); $pending = $state['pendingPayment'];
    if (!is_array($pending)) return false;
    $player = intval($pending['player']); $cost = max(0, intval($pending['cost']));
    if (FaBMSTChi($player)<FaBMSTPendingChi($pending)||intval(GetResources($player)) < $cost) return true;
    AddResources($player, intval(GetResources($player)) - $cost);
    if (in_array((string)$pending['kind'], ['ACTION', 'ATTACK'], true) || !empty($pending['abilityAction'])) AddActionPoints($player, max(0, intval(GetActionPoints($player)) - 1));
    $weaponUID = intval($pending['weaponUID'] ?? 0);
    if ($weaponUID > 0) {
        $weapon = FaBFindUID($weaponUID);
        if ($weapon !== null && in_array($weapon['zone'],['Weapons','Arena','Equipment','Hero'],true)) FaBCRUUseWeapon($weapon['object']);
        if($weapon!==null&&$weapon['object']->CardID==='spectral_shield')FaBMSTAdd($player,'SHIELD_ATTACK');
        if ($weapon !== null && in_array($weapon['object']->CardID,['teklo_plasma_pistol','plasma_barrel_shot','symbiosis_shot'],true)) FaBARCSteam($weapon['object'],-1);
        if($weapon!==null&&$weapon['object']->CardID==='talishar_the_lost_prince')FaBSetObjectCounter($weapon['object'],'RUST',intval(FaBObjectCounters($weapon['object'])['RUST']??0)+1);
    }
    if($weaponUID>0){$w=FaBFindUID($weaponUID);if($w&&$w['object']->CardID==='hanabi_blaster')FaBARCSteam($w['object'],-2);}
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
            FaBIARAllyAttacks($player,$found['object']);FaBIARApplyNext($player,$found['object']);FaBMONAdd($player,'ALLY_ATTACKS');if($found['object']->CardID==='spectral_shield')FaBMSTAdd($player,'SHIELD_ATTACK');FaBDTDPlayed($player,$found['object'],'Arena');FaBEVOPlayed($player,$found['object'],'Arena');FaBSEAPlayed($player,$found['object'],'Arena');
            FaBELEActivated($player,$found['object']);
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
    $count = $found === null ? 0 : FaBRunSourceMacro('AttackDeclared', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('AttackDeclared', intval($player), $params) : 0);
}

function OnDefended($player, $mzID, $defender) {
    $params = compact('mzID', 'defender'); $found = FaBIdentityFromMZ((string)$mzID);
    if ($found) FaBOMNDefended($found['object']);
    if ($found !== null && function_exists('FaBWTRDefended')) FaBWTRDefended(intval($player), $found['object']);if($found)FaBSEADefended(intval($player),$found['object']);if($found)FaBMPGDefended(intval($player),$found['object']);if($found)FaBSUPDefended(intval($player),$found['object']);
    if($found!==null){FaBDTDDefended(intval($player),$found['object']);FaBHVYDefended($found['object']);FaBMSTDefended(intval($player),$found['object']);FaBOUTDefended(intval($player),$found['object']);FaBCRUDefended(intval($player),$found['object']);FaBDYNDefended(intval($player),$found['object']);FaBMONDefended($found['object']);FaBELEDefended(intval($player),$found['object']);if($found['object']->CardID==='stalagmite_bastion_of_isenloft')FaBELEFrost(intval(FaBGetState()['attacker']));if($found['object']->CardID==='fractal_replication_red')FaBEVRFractalCopy(intval($player),$found['object']);}
    if($found)FaBPENLunar($found['object']);
    $count = $found === null ? 0 : FaBRunSourceMacro('Defended', intval($player), $found['object']->CardID, $params);
    return $count + (function_exists('DispatchMacroListeners') ? DispatchMacroListeners('Defended', intval($player), $params) : 0);
}

function OnHit($player, $mzID, $amount) {
    if(FaBSUPGallowStops(intval($player)))return 0;
    $params = compact('mzID', 'amount'); $found = FaBIdentityFromMZ((string)$mzID);
    if($found!==null&&FaBHasType($found['object'],'Dagger')){$s=FaBGetState();$s['daggerHits']=intval($s['daggerHits']??0)+1;FaBSetState($s);}
    if($found!==null&&FaBFaiHeroHit())FaBHNTBeforeHit(intval($player),$found['object'],intval(FaBGetState()['defender']));
    if($found!==null)FaBHNTAnyHit(intval($player),$found['object']);
    if($found!==null)FaBOUTSuppressHit($found['object']);
    if ($found !== null && function_exists('FaBWTRHit')) FaBWTRHit(intval($player), $found['object'], intval($amount));
    if($found!==null&&FaBCRUHitSuppressed($found['object'],true))return 0;
    if($found!==null){FaBIARHit(intval($player),$found['object']);FaBOMNHit(intval($player),$found['object']);FaBSUPHit(intval($player),$found['object']);FaBPENHit(intval($player));FaBMPGHit(intval($player),$found['object'],intval($amount));FaBSEAHit(intval($player),$found['object'],intval($amount));FaBCRUHit(intval($player),$found['object'],intval($amount));FaBMONHit(intval($player),$found['object']);FaBELEHit(intval($player),$found['object'],intval($amount));FaBEVRHit(intval($player),$found['object'],intval($amount));FaBDYNHit(intval($player),$found['object'],intval($amount));FaBOUTHit(intval($player),$found['object'],intval($amount));FaBDTDHit(intval($player),$found['object'],intval($amount));FaBEVOHit(intval($player),$found['object'],intval($amount));FaBHVYHit(intval($player),$found['object'],intval($amount));FaBMSTHit(intval($player),$found['object']);FaBROSHit(intval($player),$found['object']);if(FaBFaiHeroHit())FaBHNTHit(intval($player),$found['object'],intval(FaBGetState()['defender']));FaBEVRFractalDispatch(intval($player),$found['object'],'Hit',['mzID'=>$mzID,'amount'=>$amount]);if(intval($amount)>0)FaBBoltynHit(intval($player));}
    // Effects such as Seek Enlightenment can move the attack before its own hit ability.
    if($found!==null){$live=FaBFindUID(intval($found['object']->UniqueID));if($live!==null)$params['mzID']=$live['mzID'];}
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
    if($sourceRef&&(FaBMPGSuppressed($sourceRef['object'])||FaBSUPSuppressed($sourceRef['object']))){if($macroName==='PrepareCard'){FaBFinishPreparedCard(intval($sourceRef['object']->UniqueID));return 1;}if(!str_ends_with($macroName,'Modifier'))return 1;}
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
        $previousEffect=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$player;
        try {$abilities[$key]($player); ++$ran;} finally {$GLOBALS['fabEffectController']=$previousEffect;}
    }
    return $ran;
}

function DoResolveCard($player, $mzID) {
    $found = FaBIdentityFromMZ((string)$mzID);
    if ($found === null || $found['zone'] !== 'Stack') return false;
    $obj = $found['object']; $controller = intval($obj->Controller ?? $player); $uid = intval($obj->UniqueID);
    $kind = (string)($obj->Kind ?? 'ACTION'); $state = FaBGetState();
    if($kind==='ABILITY'&&!empty($obj->Params['mstEmptyDriver'])){$obj->removed=true;$driver=FaBFindUID(intval($obj->Params['mstEmptyDriver']));if($driver&&$driver['zone']==='Arena'&&FaBSteamCounters($driver['object'])===0)FaBMONDestroy(intval($driver['object']->UniqueID));SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);return true;}
    if($kind==='ABILITY'&&!empty($obj->Params['uprPhantasm'])){$obj->removed=true;FaBMONPhantasm($state);SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);return true;}
    if (FaBROSMeldFirst($controller,$obj)) return true;
    if ($kind === 'ABILITY') {
        $obj->removed=true;
        if(!empty($obj->Params['rosTrigger'])) { FaBROSResolveTrigger($controller,$obj);$s=FaBGetState();$s['window']=FaBStackTop()!==null?'PRIORITY':(!empty($s['combatOpen'])?($s['combatStep']==='DEFEND'?'DEFEND_PRIORITY':$s['combatStep']):'ACTION');FaBSetState($s); SetPriorityPlayer(intval(GetTurnPlayer())); SetConsecutivePasses(0); return true; }
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
        $persistent = FaBHasType($obj, 'Figment') || FaBHasType($obj, 'Ash') || FaBHasType($obj,'Construct') || FaBHasType($obj, 'Aura') || FaBHasType($obj, 'Item') || FaBHasType($obj, 'Ally') || FaBHasType($obj,'Landmark') || FaBHasType($obj,'Invocation') || FaBHasType($obj,'Affliction');
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
    if (!($found['zone']==='Weapons'&&$found['object']->CardID==='parry_blade') && !in_array($found['zone'], ['Hand', 'Equipment','Hero'], true) && !($found['zone']==='Arsenal'&&(FaBWTRBase($found['object']->CardID)==='down_and_dirty'||(FaBHasKeyword($found['object'],'Ambush')||($found['object']->CardID==='no_hero_stands_alone_yellow'&&FaBHVYCount($player,'CONTROLLED_toughness')))||(FaBFaiEffect($player,'AOW_ARSENAL')>0&&FaBWTRIsAttackAction($found['object']))))) return false;
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

function FaBAttackPower(array $state): int {
    $attack = FaBFindUID(intval($state['attackUID']));
    if ($attack === null) return 0;
    $base = max(0, intval(CardPower($attack['object']->CardID)));
    if(!empty(FaBObjectCounters($attack['object'])['MON_ARENA_ATTACK']))$base=FaBMSTAuraBase(intval($state['attacker']),$attack['object'])??intval(FaBObjectCounters($attack['object'])['MON_ARENA_POWER']??0);
    if($attack['object']->CardID==='fractal_replication_red')$base=FaBEVRFractalValue($attack['object'],'POWER');
    if(in_array('EVR_HALF_BASE',(array)$attack['object']->TurnEffects,true))$base=intval(ceil($base/2));
    if(FaBWTRBase($attack['object']->CardID)==='numbskull')return intval(CardPower($attack['object']->CardID));
    if($attack['object']->CardID==='mutated_mass_blue')$base=FaBMONMass(intval($state['attacker']));
    $delta = function_exists('EvaluateAttackPowerModifier') ? intval(EvaluateAttackPowerModifier($attack['object']->CardID, intval($state['attacker']), $attack['object'], $base, $attack['object'])) : 0;
    if (function_exists('FaBWTRAttackPowerModifier')) $delta += FaBWTRAttackPowerModifier(intval($state['attacker']), $attack['object'], $state);
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

function DoDamage($player, $sourceMZ, $targetPlayer, $amount, $damageType = 'PHYSICAL') {
    $iarSource=FaBIdentityFromMZ((string)$sourceMZ);if($iarSource&&!FaBDTDUnpreventable(intval($player),intval($iarSource['object']->UniqueID),(string)$damageType))$amount=FaBIARConsecrateDamage(intval($iarSource['object']->UniqueID),intval($amount));
    $targetPlayer = intval($targetPlayer); $amount = max(0, intval($amount));
    if ($amount <= 0 || !FaBSeatIsLive($targetPlayer)) return 0;
    $redirect=FaBDYNYoji(intval($player),$targetPlayer,$amount,(string)$damageType,(string)$sourceMZ);if($redirect!==null)return $redirect;
    if($damageType==='PHYSICAL')$amount=FaBELEDamageBonus(intval($player),(string)$sourceMZ,$amount,(string)$damageType);
    $source=FaBIdentityFromMZ((string)$sourceMZ);
    $amount=FaBPENArcDamage((string)$sourceMZ,$amount);
    if($source)$amount=FaBOMNDamageBonus($source['object'],$amount);
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
    if($amount>0)FaBOMNDamaged(intval($player),$targetPlayer,$source['object']??null);
    if ($amount <= 0) return 0;
    AddHealth($targetPlayer, max(0, intval(GetHealth($targetPlayer)) - $amount));
    $hero = null; foreach (GetHero($targetPlayer) as $candidate) if (is_object($candidate) && empty($candidate->removed)) { $hero = $candidate; break; }
    if (function_exists('QueueDamageAnimation')) QueueDamageAnimation('p' . $targetPlayer . 'Hero-0', $amount, 500, true, intval($hero->UniqueID ?? 0));
    FaBMPGDamaged(intval($player),$amount);FaBSEADamaged(intval($player),$targetPlayer,$amount,(string)$sourceMZ);FaBHVYDamage(intval($player),$targetPlayer,$amount,(string)$damageType);FaBROSDamaged(intval($player),$targetPlayer,$amount,(string)$damageType);FaBHNTDamageRecord(intval($player),$targetPlayer,$amount,intval($source['object']->UniqueID??0),(string)$damageType);FaBHNTBellona($targetPlayer,intval($source['object']->UniqueID??0),$amount);
    if (intval(GetHealth($targetPlayer)) <= 0) FaBEliminateSeat($targetPlayer, intval($player));
    FaBMONDamage(intval($player),$targetPlayer,$amount,(string)$damageType,(string)$sourceMZ);
    FaBMONLifeLost($targetPlayer,$amount);
    FaBEVRDamaged(intval($player),$targetPlayer,$amount,(string)$damageType);
    FaBUPRDamaged(intval($player),$targetPlayer,$amount,(string)$damageType,(string)$sourceMZ);
    FaBELEDamaged(intval($player),$targetPlayer,$amount,(string)$sourceMZ);
    FaBDYNDamaged(intval($player),$amount,(string)$sourceMZ);
    return $amount;
}

function FaBEliminateSeat(int $seat, int $sourcePlayer = 0): void {
    $live = array_values(array_filter(FaBLiveSeats(), fn($candidate) => $candidate !== $seat));
    SetLiveSeats(implode('', $live));
    if (count($live) <= 1) SetWinner(intval($live[0] ?? $sourcePlayer));
}

/** Finish elimination after the current effect has finished using its source. */
function GameAfterEngineAction($action, $result): void {
    $GLOBALS['fabEffectController']=0;
    if(!FaBHasPendingDecision())FaBDTDMirage();
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
    if(!empty($state['endingTurn'])&&!FaBHasPendingDecision()&&FaBStackTop()===null){
        if(!empty($state['rosEndPitchWaiting']))FaBQueueEndPitch(intval($state['endingTurn']));else FaBFinishEndTurn(intval($state['endingTurn']));
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
            FaBSetObjectCounter($obj, 'DEFENSE', intval(FaBObjectCounters($obj)['DEFENSE'] ?? 0) + 1);
        }
    }
}

function FaBCloseCombatChain(): void {
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
            elseif ($role === 'ATTACK' && $from === 'Weapons') $obj->removed = true;
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
        $state['window'] = 'END_PHASE'; FaBSetState($state); SetPriorityPlayer($turnPlayer); return true;
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

function FaBAutoPassShortcuts(): void {
    static $running = false;
    if ($running) return;
    $running = true;
    try {
        // Four-seat combat can require more than sixteen consecutive passes.
        for ($guard = 0; $guard < 256 && intval(GetWinner()) === 0; ++$guard) {
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

function StartOfTurnPhase() {
    $player = intval(GetTurnPlayer()); AddResources($player, 0); AddActionPoints($player, 1);
    SetCurrentPhase('MAIN');
    foreach (['Hero', 'Weapons', 'Equipment', 'Arena'] as $zoneName) foreach (FaBZoneGet($zoneName, $player) as $obj) if (is_object($obj) && empty($obj->removed) && FaBSEACanUntap($obj) && FaBSUPReadyAtStart($obj)) $obj->Status = 2;
    $state = FaBResetWindowState();
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
    if ($player !== intval(GetTurnPlayer())) return false;
    if(!empty(FaBGetState()['endingTurn']))return true;
    SetCurrentPhase('END');
    FaBCloseCombatChain();
    FaBReturnIntimidatedCards();
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
    $s=FaBGetState();if(FaBStackTop()!==null){$s['rosEndPitchWaiting']=true;$s['window']='PRIORITY';FaBSetState($s);return true;}unset($s['rosEndPitchWaiting']);FaBSetState($s);
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
