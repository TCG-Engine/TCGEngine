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
function PokeMarkAbilityUsed(string $ref): void { GetZoneObject($ref)->Counters['abilityTurn']=GetTurnNumber(); }
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
