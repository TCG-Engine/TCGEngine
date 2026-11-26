<?php
/** Delta Reign source; setup saves it through CardAbilityRepository. */
$cards = ['me06-090'=>[[
    'macroName'=>'TrainerPlayed',
    'abilityName'=>'Adventure Lantern',
    'isImplemented'=>true,
    'prereqCode'=>'return PokeCount($player, "Deck") > 0;',
    'abilityCode'=><<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicFire');
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, 'Adventure_Lantern:_search_for_a_Basic_Fire_Energy');
    PokeResolveSearch($player, $chosen, 'Hand', true, false);
}
$targets = PokeCandidates($player, 'Deck', 'basicLightning');
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, 'Adventure_Lantern:_search_for_a_Basic_Lightning_Energy');
    PokeResolveSearch($player, $chosen, 'Hand', true, false);
}
PokeShuffle($player);
CODE
]]];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me06-080'][] = $ability('Attack', 'Bouncy-Bounce Charge', <<<'CODE'
$bench = PokeCandidates($player, 'Bench', 'pokemon');
$energy = PokeCandidates($player, 'Deck', 'energy');
if ($bench !== '' && $energy !== '') {
    $chosen = await $player.MZMayChoose($energy, 'Bouncy-Bounce_Charge:_search_for_an_Energy');
    if ($chosen !== '' && $chosen !== '-' && $chosen !== 'PASS') {
        $target = await $player.MZChoose($bench, 'Bouncy-Bounce_Charge:_choose_a_Benched_Pokémon');
        PokeAttachEnergyFromZone($player, $chosen, $target);
    }
}
if ($bench !== '') PokeShuffle($player);
CODE);
$cards['me06-089'][] = $ability('TrainerPlayed', 'Aarune', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'supporterOrStadium');
if ($targets !== '') {
    $maximum = min(3, count(explode('&', $targets)));
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Aarune:_search_for_up_to_three_Supporters_or_Stadiums');
    PokeResolveSearch($player, $chosen);
} else {
    PokeShuffle($player);
}
CODE, 'return PokeCount($player, "Deck") > 0;');
foreach (['me06-096', 'me06-097'] as $id) {
    $cards[$id][] = $ability('TrainerPlayed', 'Legendary Summit', <<<'CODE'
$other = PokeSummitPartner($player, GetZoneObject($mzID)->CardID);
if ($other !== '') {
    PokePlayStadium($player, $mzID, $other);
    PokeResolveKnockouts();
}
CODE, 'return PokeSummitPartner($player, GetZoneObject($mzID)->CardID) !== "";');
    $cards[$id][] = $ability('KnockoutPrizeModifier', 'Legendary Summit', <<<'CODE'
return !empty($subjectObj->Counters['opposingAttackKO'])
    && in_array('Colorless', explode(',', EffectiveCardElement($subjectObj)), true) ? -1 : 0;
CODE);
}
return $cards;
