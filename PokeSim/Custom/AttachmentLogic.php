<?php
/** Shared attachment, Stadium and field-passive helpers called by card macros. */
function PokeStadium() { foreach (GetStadium() as $obj) if (!$obj->Removed()) return $obj; return null; }
function PokeDiscardStadium(): void {
    $obj=PokeStadium(); if (!$obj) return;
    PokeAdd($obj->Controller,'Discard',$obj->CardID); $obj->Remove();
}
function PokePlayStadium(int $player,string $ref): void {
    $obj=GetZoneObject($ref); $id=$obj->CardID; $obj->Remove(); PokeDiscardStadium();
    $zone=&GetStadium(); $zone=[];
    $stadium=new Stadium($id.' '.$player,'Stadium',0,0); $stadium->CardID=$id; $stadium->Controller=$player; $zone[]=$stadium;
    PokeSetVar('stadiumPlayed:'.$player,GetTurnNumber());
}
function PokeToolTargets(int $player): string {
    return implode('&',array_filter(PokeFieldRefs($player),fn($ref)=>GetZoneObject($ref)->Tool==='-'));
}
function PokeAttachTool(int $player,string $source,string $target): void {
    $obj=GetZoneObject($target); $tool=GetZoneObject($source);
    if (!$obj||$obj->Removed()||!$tool||$tool->Removed()||$obj->Tool!=='-') return;
    $obj->Tool=$tool->CardID; $tool->Remove();
}
function PokeMegaTargets(int $player): string {
    return implode('&',array_filter(PokeFieldRefs($player),fn($ref)=>str_starts_with(CardName(GetZoneObject($ref)->CardID)??'','Mega ') && PokeHasRuleBox(GetZoneObject($ref)->CardID)));
}
function PokeReturnAttachedEnergy(int $player,string $ref,?int $index=null): void {
    $obj=GetZoneObject($ref);
    foreach ($obj->Energy as $i=>$id) if ($index===null||$index===$i) { PokeAdd($player,'Hand',$id); unset($obj->Energy[$i]); }
    $obj->Energy=array_values($obj->Energy);
}
function PokeHealMega(int $player,string $ref): void {
    $obj=GetZoneObject($ref); if ($obj->Damage>0) { $obj->Damage=0; PokeReturnAttachedEnergy($player,$ref); }
}
function PokeReturnPokemonStack(int $player,string $ref,string $zone): void {
    $obj=GetZoneObject($ref); if (!$obj||$obj->Removed()) return;
    foreach (array_merge([$obj->CardID],$obj->Evolutions,$obj->Energy,$obj->Tool!=='-'?[$obj->Tool]:[]) as $id) PokeAdd($player,$zone,$id);
    $obj->Remove(); if ($zone==='Deck') PokeShuffle($player);
    PokeLog('return-pokemon',['player'=>$player,'card'=>$obj->CardID,'zone'=>$zone]);
    // Attack resolution handles its own promotion after damage; abilities need it now.
    if (PokeVar('resolvingAbility',false)) PokeResolveKnockouts();
}
function PokeAbilityUnused(string $ref): bool { return (GetZoneObject($ref)->Counters['abilityTurn']??-1)!==GetTurnNumber(); }
function PokeMetalTargets(int $player): string {
    return implode('&', array_filter(PokeFieldRefs($player), fn($ref)=>in_array('Metal', explode(',', EffectiveCardElement(GetZoneObject($ref))), true)));
}
function PokeAttachDiscardMetal(int $player, string $source, string $target): void {
    $energy=GetZoneObject($source); $pokemon=GetZoneObject($target);
    if (!$energy || $energy->Removed() || !$pokemon || $pokemon->Removed()) return;
    if (!str_starts_with($source, "p{$player}Discard-") || $pokemon->Controller !== $player
        || CardType($energy->CardID) !== 'Energy' || CardEnergyType($energy->CardID) !== 'Normal'
        || !in_array('Metal', PokeEnergyTypes($energy->CardID), true)
        || !in_array('Metal', explode(',', EffectiveCardElement($pokemon)), true)) return;
    $id=$energy->CardID; $pokemon->Energy[]=$id; $energy->Remove();
    EnergyAttached($player, $target, $id);
}
function PokeMarkAbilityUsed(string $ref): void { GetZoneObject($ref)->Counters['abilityTurn']=GetTurnNumber(); }
function PokeBasicEnergySources(int $player): string {
    return implode('&', array_filter(PokeFieldRefs($player), fn($ref)=>count(array_filter(GetZoneObject($ref)->Energy, fn($id)=>CardEnergyType($id) === 'Normal')) > 0));
}
/** Chooser-only copies map to attachment indexes and are removed after the move. */
function PokePreviewBasicEnergy(int $player, string $source): array {
    $preview=[];
    foreach (GetZoneObject($source)->Energy as $i=>$id) if (CardEnergyType($id) === 'Normal') {
        $obj=PokeAdd($player,'TempZone',$id); $preview[PokeRef($player,'TempZone',$obj->mzIndex)]=$i;
    }
    return $preview;
}
function PokeClearEnergyPreview(array $preview): void {
    foreach ($preview as $ref=>$index) { $obj=GetZoneObject($ref); if ($obj && !$obj->Removed()) $obj->Remove(); }
}
/** Moving an attached Energy does not trigger effects for newly attached Energy. */
function PokeMoveAttachedBasicEnergy(int $player, string $source, int $index, string $target): void {
    $from=GetZoneObject($source); $to=GetZoneObject($target);
    if ($source === $target || !$from || !$to || $from->Removed() || $to->Removed() || $from->Controller !== $player || $to->Controller !== $player) return;
    $id=$from->Energy[$index] ?? null;
    if ($id === null || CardEnergyType($id) !== 'Normal') return;
    unset($from->Energy[$index]); $from->Energy=array_values($from->Energy); $to->Energy[]=$id;
}
function PokeDarknessBenchTargets(int $player): string {
    return implode('&', array_filter(explode('&', PokeCandidates($player,'Bench','pokemon')), fn($ref)=>$ref !== '' && in_array('Darkness', explode(',', EffectiveCardElement(GetZoneObject($ref))), true)));
}
/** Effect attachments do not spend the turn's manual attachment. */
function PokeAttachEnergyFromZone(int $player, string $source, string $target): void {
    $energy=GetZoneObject($source); $pokemon=GetZoneObject($target);
    if (!$energy || $energy->Removed() || !$pokemon || $pokemon->Removed() || $pokemon->Controller !== $player || EffectiveCardType($energy) !== 'Energy') return;
    $id=$energy->CardID; $pokemon->Energy[]=$id; $energy->Remove();
    EnergyAttached($player, $target, $id);
}
function PokeAttachEnergySelection(int $player, string $selection, string $target): void {
    foreach (explode('&', $selection) as $ref) if ($ref !== '' && $ref !== '-' && $ref !== 'PASS') PokeAttachEnergyFromZone($player, $ref, $target);
}
function PokeDifferentBasicEnergyCandidates(int $player, array $excludedTypes): string {
    return implode('&', array_filter(explode('&', PokeCandidates($player, 'Deck', 'basicEnergy')), fn($ref)=>$ref !== '' && !array_intersect($excludedTypes, PokeEnergyTypes(GetZoneObject($ref)->CardID))));
}
function PokeStageTwoTargets(int $player): string {
    return implode('&', array_filter(PokeFieldRefs($player), fn($ref)=>CardStage(GetZoneObject($ref)->CardID) === 'Stage2'));
}
function PokeEnergyBearingTargets(int $player): string {
    return implode('&', array_filter(PokeFieldRefs($player), fn($ref)=>count(GetZoneObject($ref)->Energy) > 0));
}
/** Discard by an effect, without triggering attack-only Energy return rules. */
function PokeDiscardAttachedEnergy(string $target, int $index): void {
    $pokemon=GetZoneObject($target);
    if (!$pokemon || $pokemon->Removed() || !isset($pokemon->Energy[$index])) return;
    PokeAdd($pokemon->Controller, 'Discard', $pokemon->Energy[$index]);
    unset($pokemon->Energy[$index]); $pokemon->Energy=array_values($pokemon->Energy);
}
/** Only attack-effect discards qualify for Nitro; keep it in discard until all attack effects finish. */
function PokeDiscardAttackEnergy(int $player, string $ref): void {
    $obj = GetZoneObject($ref);
    if (!$obj || $obj->Removed()) return;
    $fire = in_array('Fire', explode(',', EffectiveCardElement($obj)), true);
    $returns = PokeVar('attackEnergyReturns:' . $player, []);
    foreach ($obj->Energy as $id) {
        $discarded = PokeAdd($player, 'Discard', $id);
        if ($id === 'me04-086' && $fire) $returns[] = PokeRef($player, 'Discard', $discarded->mzIndex);
    }
    $obj->Energy = [];
    PokeSetVar('attackEnergyReturns:' . $player, $returns);
}
function PokeReturnAttackEnergy(int $player): void {
    $returns = PokeVar('attackEnergyReturns:' . $player, []);
    PokeSetVar('attackEnergyReturns:' . $player, []);
    foreach ($returns as $ref) PokeMoveSimple($ref, $player, 'Hand');
}
function PokeMovedToActiveThisTurn(string $ref): bool { return (GetZoneObject($ref)->Counters['movedActiveTurn']??-1)===GetTurnNumber(); }
function PokeCountEx(int $player): int { return count(array_filter(PokeFieldRefs($player),fn($ref)=>preg_match('/ ex$/i',CardName(GetZoneObject($ref)->CardID)??'')===1)); }
/** Evaluate authored value modifiers on live field sources, with disabled abilities excluded. */
function PokeFieldModifier(string $macro,int $controller,$subject,$source): int {
    $value=0; $evaluate='Evaluate'.$macro;
    foreach (PokeFieldRefs($controller) as $ref) { $obj=GetZoneObject($ref); if (!HasNoAbilities($obj)) $value+=$evaluate($obj->CardID,$controller,$subject,$source); }
    return $value;
}
function PokeSelfKnockoutAbilityBlocked($obj,int $index): bool {
    $effect=CardAbilities($obj->CardID)[$index]['effect']??'';
    if (!preg_match('/this Pok.mon.*(?:Knocked Out|Knock Out this)/iu',$effect)) return false;
    return PokeFieldModifier('SelfKnockoutAbilityLock',1,$obj,$obj)+PokeFieldModifier('SelfKnockoutAbilityLock',2,$obj,$obj)>0;
}

/** Stadium effects apply to both players, including tools already attached. */
function PokeToolsEnabled(): bool { return (PokeStadium()->CardID ?? '') !== 'sv06-153'; }
function PokeCurrentHP($obj): int {
    return max(0, (int)CardHp($obj->CardID) - ((PokeStadium()->CardID ?? '') === 'sv08-177' && CardStage($obj->CardID) === 'Stage2' ? 30 : 0));
}
