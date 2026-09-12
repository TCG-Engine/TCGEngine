<?php
// Crouching Tigers are created cards, not tokens. Unplayed Tigers stay banished.
function FaBCreateTigers(int $player,int $count=1,int $power=0,bool $nextTurn=false): void {
    for($i=0;$i<$count;++$i){
        $o=AddBanish($player,CardID:'crouching_tiger',PlayableFromBanish:$nextTurn?0:1);
        if($power)FaBWTRTag($o,'WTR_POWER:'.$power);
        if($nextTurn)FaBARCSetCard(intval($o->UniqueID),'tigerNextTurn',true);
    }
}
function FaBIraStartTurn(int $player): void {
    foreach(FaBChoiceRefs($player,'Banish') as $ref){
        $o=FaBIdentityFromMZ($ref)['object'];$uid=intval($o->UniqueID);
        if(FaBARCCard($uid,'tigerNextTurn')){
            $o->PlayableFromBanish=1;
            FaBARCSetCard($uid,'tigerNextTurn',false);
        }
    }
    foreach(FaBChoiceRefs($player,'Arena',['base'=>'blessing_of_qi']) as $ref){
        $o=FaBIdentityFromMZ($ref)['object'];
        if(!HasNoAbilities($o))FaBRunSourceMacro('StartTurn',$player,$o->CardID,['mzID'=>$ref]);
    }
}
function FaBIraBlessing(int $player,string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f===null||$f['zone']!=='Arena')return;
    $power=FaBWTRPitchValue($f['object']->CardID,[3,2,1]);
    FaBMoveUID(intval($f['object']->UniqueID),'Graveyard',$player);
    FaBCreateTigers($player,1,$power);
}
function FaBIraTigerPlayed(int $player,string $ref): void {
    $left=[];$o=FaBIdentityFromMZ($ref)['object']??null;
    if($o===null)return;
    foreach(FaBWTREffects($player) as $e){
        if(in_array($e['type']??'',['IRA_NEXT_TIGER','IRA_CHAIN_TIGER'],true))FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));
        else $left[]=$e;
    }
    FaBWTRSetEffects($player,$left);
}
function FaBIraCombo(string $ref,int $power,bool $goAgain=false): void {
    $o=FaBIdentityFromMZ($ref)['object']??null;if($o===null)return;
    if(FaBWTRBase(FaBGetState()['previousAttackCardID']??'')!=='crouching_tiger')return;
    if($power)FaBCRUSelfTag($o,'WTR_POWER:'.$power);
    if($goAgain)FaBWTRTag($o,'GO_AGAIN');
    FaBWTRTag($o,'IRA_TIGER_COMBO');
}
function FaBIraMauling(int $player,string $ref): void {
    $o=FaBIdentityFromMZ($ref)['object']??null;
    if($o===null||!in_array('IRA_TIGER_COMBO',(array)$o->TurnEffects,true))return;
    $targets=FaBOpponents($player);
    foreach($targets as $target)DoDamage($player,$ref,$target,1,'PHYSICAL');
}
