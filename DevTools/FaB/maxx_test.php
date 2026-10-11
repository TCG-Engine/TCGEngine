<?php
require_once __DIR__.'/evo_rules_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures = [];
$maxxDeck = FaBBotDeck('maxx');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/maxx_source.json'), true)) === $maxxDeck, 'Maxx differs from linked deck.');
$check($maxxDeck['hero'] === 'maxx_the_hype_nitro' && count($maxxDeck['mainDeck']) === 60 && !$maxxDeck['unresolved'], 'Maxx deck identity/count incorrect.');
$coverage = [];
foreach (glob(__DIR__.'/*_abilities.json') as $file) foreach (json_decode(file_get_contents($file), true) as $card) $coverage[$card['cardId']] = true;
foreach (array_merge([$maxxDeck['hero'], 'bank_breaker'], $maxxDeck['equipment'], $maxxDeck['weapons'], $maxxDeck['mainDeck']) as $id) $check(isset($coverage[$id]), 'Missing Maxx card '.$id);
foreach ([2,4] as $p) {
    $evoReset($p); GetHero($p)[0]->CardID = 'maxx_the_hype_nitro';
    $jacket = AddEquipment($p, CardID:'puffer_jacket', Owner:$p, Controller:$p);
    $driver = AddArena($p, CardID:'hyper_driver_blue', Owner:$p, Controller:$p);
    FaBARCEnterItem($p, $driver); $answer($p, '1');
    $check(intval(FaBObjectCounters($driver)['STEAM']) === 2, 'Puffer Jacket missed non-token entry.');
    FaBEVOCreateDriver($p); $answer($p, '1');
    $token = FaBIdentityFromMZ(FaBChoiceRefs($p, 'Arena', ['base'=>'hyper_driver'])[1])['object'];
    $check(intval(FaBObjectCounters($token)['STEAM']) === 2, 'Puffer Jacket modified token steam.');
    $clampUID = $macro($p, 'clamp_press_blue', 'ResolveCard', 'Arena'); $answer($p, '0');
    $clamp = FaBFindUID($clampUID)['object'];
    $check(intval(FaBObjectCounters($clamp)['STEAM']) === 1, 'Clamp Press entry/crank counters wrong.');
    $a = $attack($p, 'banksy'); $check(FaBAMXPower($p, $a) === 2 && FaBAMXPower(1, $a) === 0, 'Clamp Press borrowed opposing items.');
    FaBRunSourceMacro('StartTurn', $p, 'clamp_press_blue', ['mzID'=>$ref($clamp)]); $answer($p, '0');
    $check(intval(FaBObjectCounters($clamp)['STEAM'] ?? 0) === 0 && FaBFindUID($clampUID), 'Clamp upkeep destroys too early.');
    FaBRunSourceMacro('StartTurn', $p, 'clamp_press_blue', ['mzID'=>$ref($clamp)]);
    $check(FaBFindUID($clampUID)['zone'] === 'Graveyard', 'Clamp survived unpaid upkeep.');

    $evoReset($p); $weapon = AddWeapons($p, CardID:'banksy', Owner:$p, Controller:$p);
    $brake = AddEquipment($p, CardID:'drive_brake', Owner:$p, Controller:$p); FaBSetObjectCounter($brake, 'DEFENSE', 1);
    AddEquipment($p, CardID:'fist_pump', Owner:$p, Controller:$p);
    $boosted = AddBanish($p, CardID:'hyper_driver_red', Owner:$p, Controller:$p);
    FaBAMXBoost($p, intval($boosted->UniqueID)); $answer($p, $ref($weapon));
    $check(intval(FaBObjectCounters($brake)['DEFENSE'] ?? 0) === 0 && in_array('WTR_POWER:1', (array)$weapon->TurnEffects, true), 'Boost gear did not repair/pump.');
    $a = $attack($p, 'banksy'); FaBSetObjectCounter($a, 'WEAPON_UID', intval($weapon->UniqueID));
    $check(FaBAttackPower(FaBGetState()) === intval(CardPower('banksy')) + 1, 'Fist Pump missed weapon attack.');
    $helm = AddCombatChain($p, CardID:'breaker_helm_protos', Owner:$p, Controller:$p, FromZone:'Equipment', Role:'DEFENSE', ChainLink:1);
    $discard = AddHand($p, CardID:'hyper_driver_blue', Owner:$p); AddDeck($p, CardID:'zero_to_sixty_red', Owner:$p);
    $before = FaBHandCount($p); FaBRunSourceMacro('Defended', $p, $helm->CardID, ['mzID'=>$ref($helm)]); $answer($p, $ref($discard));
    $check(FaBHandCount($p) === $before && FaBFindUID(intval($discard->UniqueID))['zone'] === 'Graveyard' && FaBCurrentDefense($helm, $p) === intval(CardDefense($helm->CardID)) + 1, 'Breaker Helm discard/draw/defense failed.');

    $evoReset($p); $weapon = AddWeapons($p, CardID:'banksy', Owner:$p, Controller:$p);
    $materials = [];
    foreach (['hyper_driver_red','hyper_driver_yellow','hyper_driver'] as $id) { $o = AddArena($p, CardID:$id, Owner:$p, Controller:$p); $materials[] = $ref($o); }
    $bankUID = $macro($p, 'construct_bank_breaker_yellow', 'ResolveCard', 'Arena');
    $answer($p, $ref($weapon)); $answer($p, implode('&', $materials));
    $bank = FaBFindUID($bankUID)['object'];
    $check($bank->CardID === 'bank_breaker' && FaBFindUID($bankUID)['zone'] === 'Weapons' && count(FaBEVOUnder($bank)) === 4 && !empty($weapon->removed), 'Bank Breaker transformation failed.');
    $check(count(FaBChoiceRefs($p, 'Arena', ['base'=>'hyper_driver'])) === 0, 'Transformed drivers remained in arena.');
    $check(!FaBDYNWeaponCanAttack($p, FaBFindUID($bankUID)), 'Bank attacked without cranking.'); FaBEVOAdd($p, 'CRANKED');
    for ($attackNumber = 0; $attackNumber < 2; ++$attackNumber) {
        $s = FaBGetState(); $s['window'] = 'ACTION'; FaBSetState($s); AddActionPoints($p, 1);
        $check(FaBDYNWeaponAttack($p, FaBFindUID($bankUID)), 'Bank attack activation failed.');
        if (GetDecisionQueue($p)) $answer($p, $ref(GetHero(1)[0]));
        $top = FaBStackTop(); $check($top !== null, 'Bank failed to create attack.');
        if ($top) DoResolveCard($p, $ref($top));
        if (GetDecisionQueue($p)) $answer($p, explode('&', explode('|', GetDecisionQueue($p)[0]->Param, 3)[2])[0]);
        $a = FaBFindUID(intval(FaBGetState()['attackUID']))['object'];
        $check(FaBAttackHasGoAgain(FaBGetState(), $a) && FaBDYNOverpower($a, FaBGetState()), 'Bank material did not grant go again/overpower.');
        FaBCloseCombatChain();
    }
    $check(count(FaBEVOUnder($bank)) === 2 && !FaBAMXReady($bank), 'Bank attack limit/material consumption incorrect.');
    FaBMONDestroy($bankUID);
    $check(FaBFindUID($bankUID)['object']->CardID === 'construct_bank_breaker_yellow' && count(FaBChoiceRefs($p, 'Graveyard', ['base'=>'hyper_driver'])) === 1, 'Bank leaving did not restore front face.');
    $check(!FaBChoiceRefs($p, 'Graveyard', ['type'=>'Token']), 'Transformed token did not cease to exist.');

    // Invalid construction never consumes another controller's items or our wrench.
    $evoReset($p); $weapon = AddWeapons($p, CardID:'banksy', Owner:$p, Controller:$p);
    $construct = AddArena($p, CardID:'construct_bank_breaker_yellow', Owner:$p, Controller:$p); $choices = [];
    foreach ([$p,$p,1] as $owner) { $o = AddArena($owner, CardID:'hyper_driver_red', Owner:$owner, Controller:$owner); $choices[] = $ref($o); }
    $check(!FaBAMXConstruct($p, intval($construct->UniqueID), $ref($weapon), implode('&', $choices)) && count(FaBChoiceRefs($p, 'Weapons')) === 1 && count(FaBChoiceRefs(1, 'Arena')) === 1, 'Invalid construction consumed components.');
    $bank = AddWeapons($p, CardID:'bank_breaker', Owner:$p, Controller:$p); FaBEVOCounter($bank, 'SUBCARDS', ['banksy']);
    $a = $attack($p, 'bank_breaker'); FaBSetObjectCounter($a, 'WEAPON_UID', intval($bank->UniqueID));
    FaBRunSourceMacro('AttackDeclared', $p, 'bank_breaker', ['mzID'=>$ref($a)]); $answer($p, '-');
    $check(count(FaBEVOUnder($bank)) === 1 && !FaBAttackHasGoAgain(FaBGetState(), $a) && !FaBChoiceRefs($p, 'Temp'), 'Declining material consumed it, granted go again, or leaked previews.');

    $evoReset($p); $before = GetResources($p); $driver = AddGraveyard($p, CardID:'hyper_driver_red', Owner:$p);
    $macro($p, 'twintek_charging_station_red'); $answer($p, $ref($driver));
    $check(FaBFindUID(intval($driver->UniqueID))['zone'] === 'Deck' && GetResources($p) === $before + 1, 'Twintek failed recycle/resource.');
    $attackCard = AddStack(CardID:'zero_to_sixty_red', Controller:$p, Kind:'ATTACK');
    FaBARCBoost($p, intval($attackCard->UniqueID));
    $check(in_array('WTR_POWER:3', (array)$attackCard->TurnEffects, true) && FaBEVOEffect($p, 'NEXT_BOOST') === 0, 'Twintek did not buff exactly next boosted attack.');
}
// An extra-attack effect is spent after both printed activations, not on the second.
$evoReset(2); $bank = AddWeapons(2, CardID:'bank_breaker', Owner:2, Controller:2);
FaBWTRTag($bank, 'CRU_EXTRA_ATTACK');
FaBCRUUseWeapon($bank); FaBCRUUseWeapon($bank);
$check(FaBCRUWeaponReady($bank), 'Bank spent its extra attack on a printed activation.');
FaBCRUUseWeapon($bank);
$check(!FaBCRUWeaponReady($bank), 'Bank repeated an already consumed extra attack.');
// Bot-only checks use duels; the card rules above also exercise seat four.
$evoReset(2); $s = FaBGetState(); $s['botProfiles'] = [2=>'maxx']; FaBSetState($s);
GetHero(2)[0]->CardID = 'maxx_the_hype_nitro'; AddDeck(2, CardID:'hyper_driver_blue'); AddDeck(2, CardID:'zero_to_sixty_red');
$d = (object)['Type'=>'MZMODAL', 'Tooltip'=>'Banish_top_card_to_boost', 'Param'=>'1|1|Boost&Do_not_boost'];
$answerBefore = FaBBotChoice(2, $d); GetDeck(2)[0]->CardID = 'heist_red'; AddHand(1, CardID:'command_and_conquer_red');
$check(FaBBotChoice(2, $d) === $answerBefore, 'Maxx inspected hidden cards.');
$bank = AddWeapons(2, CardID:'bank_breaker', Owner:2, Controller:2); FaBEVOCounter($bank, 'SUBCARDS', ['hyper_driver']);
$check(FaBMaxxAbilityScore(2, $bank) > 0, 'Bot will not use constructed weapon.');
// Give the bot a legal construction opportunity and drive the normal action loop.
// A shuffled game can finish without drawing the construction package together.
$evoReset(2); $s = FaBGetState(); $s['botProfiles'] = [2=>'maxx']; FaBSetState($s);
GetHero(2)[0]->CardID = 'maxx_the_hype_nitro';
AddWeapons(2, CardID:'banksy', Owner:2, Controller:2);
foreach (['hyper_driver_red','hyper_driver_yellow','hyper_driver_blue'] as $id) {
    $o = AddArena(2, CardID:$id, Owner:2, Controller:2); FaBSetObjectCounter($o, 'STEAM', 2);
}
AddHand(2, CardID:'construct_bank_breaker_yellow', Owner:2);
FaBEVOAdd(2, 'CRANKED');
$built = null;
for ($step = 0; $step < 100; ++$step) {
    $actor = intval(GetPriorityPlayer());
    foreach (FaBLiveSeats() as $seat) if (GetDecisionQueue($seat)) { $actor = $seat; break; }
    $check(FaBBotAct($actor), 'Bot stalled during construction scenario.');
    GameAfterEngineAction([], []);
    $refs = FaBChoiceRefs(2, 'Weapons', ['base'=>'bank_breaker']);
    if ($refs) {
        $built = FaBIdentityFromMZ($refs[0])['object'];
        if (intval(FaBObjectCounters($built)['WEAPON_ATTACKS'] ?? 0) === 2 && count(FaBEVOUnder($built)) === 2) break;
    }
}
$check($built && count(FaBEVOUnder($built)) === 2 && intval(FaBObjectCounters($built)['WEAPON_ATTACKS'] ?? 0) === 2, 'Bot missed construction or its two paid Bank Breaker attacks.');
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures).PHP_EOL); exit(1); }
echo "Maxx source, AMX rule outcomes, and bot choices passed.\n";
