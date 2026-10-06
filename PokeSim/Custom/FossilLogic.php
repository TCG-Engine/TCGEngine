<?php
function PokeIsFossil(string $id): bool {
    return in_array($id,['me05-072','me05-073','me03-068','sv10.5w-079','sv07-130','sv07-129'],true);
}
/** Rare Candy follows the printed evolution chain, including Item fossils in play. */
function PokeRareCandyEligible(int $player, string $evolutionID, $basic): bool {
    if (!$basic || $basic->Removed() || $basic->Controller !== $player
        || EffectiveCardType($basic) !== 'Pokemon' || !in_array('Basic', EffectiveCardSubtypes($basic), true)
        || GetPlayerTurns($player) <= 1 || $basic->EnteredTurn >= GetTurnNumber()
        || $basic->EvolvedTurn >= GetTurnNumber() || CardStage($evolutionID) !== 'Stage2') return false;
    static $ancestors = [];
    if (!isset($ancestors[$evolutionID])) {
        $ancestors[$evolutionID] = [];
        foreach ($GLOBALS['evolveFromData'] as $id => $from)
            if (CardStage($id) === 'Stage1' && CardName($id) === CardEvolveFrom($evolutionID)) $ancestors[$evolutionID][] = $from;
    }
    return in_array(CardName($basic->CardID), $ancestors[$evolutionID], true);
}
function PokeRareCandyEvolutions(int $player, string $target): string {
    return implode('&', array_map(fn($c)=>PokeRef($player,'Hand',$c->mzIndex), array_filter(PokeObjects($player,'Hand'),
        fn($c)=>PokeRareCandyEligible($player,$c->CardID,GetZoneObject($target)))));
}
function PokeRareCandyTargets(int $player): string {
    return implode('&',array_filter(PokeFieldRefs($player),fn($ref)=>PokeRareCandyEvolutions($player,$ref)!==''));
}
function PokeAncientBulwark(int $player, $attacker): int {
    if (!$attacker || $attacker->Controller === $player || count($attacker->Energy) > 2) return 0;
    foreach (PokeObjects($player,'Bench') as $obj)
        if ($obj->CardID === 'me05-062' && !HasNoAbilities($obj)) return 1;
    return 0;
}
function PokeFossilBenchCount(int $player): int {
    return count(array_filter(PokeObjects($player,'Bench'),fn($obj)=>str_contains(CardName($obj->CardID)??'','Antique')));
}
function PokeAttackCost($pokemon,array $cost): array {
    $enemy=PokeFirstRef(3-$pokemon->Controller,'Active');
    if($enemy!==''&&(EffectiveCardSubtypes($pokemon)[0]??'')==='Basic'&&GetZoneObject($enemy)->CardID==='sv07-130'&&!HasNoAbilities(GetZoneObject($enemy)))$cost[]='Colorless';
    return $cost;
}
function PokeDiscardFossil(int $player,string $ref): void {
    PokeReturnPokemonStack($player,$ref,'Discard');
    // Ability resolution already queues promotion in PokeReturnPokemonStack.
    // Resolve here only when called outside that flow, to avoid a second chooser.
    if (!PokeVar('resolvingAbility',false)) PokeResolveKnockouts();
}
