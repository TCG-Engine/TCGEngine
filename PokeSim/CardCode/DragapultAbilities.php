<?php
/** Dragapult and Dusknoir package; saved through CardAbilityRepository by setup. */
$cards = [];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro, 'abilityName'=>$name, 'abilityCode'=>$code, 'prereqCode'=>$prereq, 'isImplemented'=>true];
$cards['me02.5-159'][] = $ability('ActivateAbility', 'Recon Directive', <<<'CODE'
PokeMarkAbilityUsed($mzID);
PokeRevealTop($player, 2);
$targets = PokeCandidates($player, 'TempZone');
$chosen = await $player.MZChoose($targets, 'Recon_Directive:_put_one_card_into_your_hand');
PokeMoveSimple($chosen, $player, 'Hand');
foreach (PokeObjects($player, 'TempZone') as $i => $obj) PokeMoveSimple(PokeRef($player, 'TempZone', $i), $player, 'Deck');
CODE, 'return PokeAbilityUnused($mzID) && PokeCount($player, "Deck") > 0;');
$cards['me02.5-160'][] = $ability('Attack', 'Jet Headbutt', 'PokeDealAttackDamage($player, $mzID, 70);');
$cards['me02.5-160'][] = $ability('Attack', 'Phantom Dive', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 200);
$targets = PokeCandidates(3 - $player, 'Bench');
if ($targets !== '') {
    for ($i = 0; $i < 6; ++$i) {
        $chosen = await $player.MZChoose($targets, 'Phantom_Dive:_place_the_next_damage_counter');
        PokePlaceDamageCounters($mzID, $chosen, 1);
    }
}
CODE);
$cards['sv06.5-018'][] = $ability('Attack', 'Come and Get You', <<<'CODE'
$targets = implode('&', array_filter(explode('&', PokeCandidates($player, 'Discard', 'pokemon')), fn($ref) => $ref !== '' && CardName(GetZoneObject($ref)->CardID) === 'Duskull'));
$maximum = min(3, 5 - PokeCount($player, 'Bench'));
if ($targets !== '' && $maximum > 0) {
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Come_and_Get_You:_Bench_up_to_three_Duskull');
    PokeResolveSearch($player, $chosen, 'Bench', false, false);
}
CODE);
foreach (['sv06.5-019'=>5, 'sv06.5-020'=>13] as $id => $counters) {
    $cards[$id][] = $ability('ActivateAbility', 'Cursed Blast', str_replace('COUNTERS', (string)$counters, <<<'CODE'
$targets = implode('&', PokeFieldRefs(3 - $player));
$chosen = await $player.MZChoose($targets, 'Cursed_Blast:_choose_an_opposing_Pokémon');
PokePlaceDamageCounters($mzID, $chosen, COUNTERS, 'Ability');
GetZoneObject($mzID)->Damage = max(GetZoneObject($mzID)->Damage, PokeCurrentHP(GetZoneObject($mzID)));
PokeResolveKnockouts();
CODE));
}
$cards['sv06.5-020'][] = $ability('Attack', 'Shadow Bind', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 150);
$target = PokeFirstRef(3 - $player, 'Active');
if ($target !== '' && !PokeEffectProtected(GetZoneObject($target), GetZoneObject($mzID))) {
    GetZoneObject($target)->TurnEffects['noRetreatThrough'] = GetTurnNumber() + 1;
}
CODE);
$cards['sv02-185'][] = $ability('TrainerPlayed', 'Iono', <<<'CODE'
$returned = PokeCount(1, 'Hand') + PokeCount(2, 'Hand');
PokeShuffleHandDraw($player, 0, true);
PokeShuffleHandDraw(3 - $player, 0, true);
if ($returned > 0) {
    PokeDraw($player, PokeCount($player, 'Prizes'), false);
    PokeDraw(3 - $player, PokeCount(3 - $player, 'Prizes'), false);
}
CODE, 'return PokeCount($player, "Hand") > (GetZoneObject($mzID)->Location === "Hand" ? 1 : 0) || PokeCount(3 - $player, "Hand") > 0;');
$cards['sv01-189'][] = $ability('TrainerPlayed', "Professor's Research", 'PokeDiscardSelection($player, PokeCandidates($player, "Hand")); PokeDraw($player, 7, false);', 'return PokeCount($player, "Deck") > 0;');
$cards['sv05-157'][] = $ability('TrainerPlayed', 'Prime Catcher', <<<'CODE'
$targets = PokeCandidates(3 - $player, 'Bench');
$chosen = await $player.MZChoose($targets, 'Prime_Catcher:_choose_an_opposing_Benched_Pokémon');
PokeSwitch(3 - $player, $chosen);
$targets = PokeCandidates($player, 'Bench');
if ($targets !== '') {
    $chosen = await $player.MZChoose($targets, 'Prime_Catcher:_choose_your_new_Active_Pokémon');
    PokeSwitch($player, $chosen);
}
CODE, 'return PokeCount(3 - $player, "Bench") > 0;');
return $cards;
