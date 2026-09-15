<?php
// Rules shared by the Boltyn deck's cards. Interactive choices stay in saved await macros.
function FaBBoltynAbilityRows(): array {return [
    'flat_trackers'=>[['ACTION',0,true,true,false,0,'Create Agility']],
    'garland_of_spring'=>[['ACTION',0,true,true,false,0,'Gain one resource']],
    'radiant_touch'=>[['INSTANT',0,false,false,false,0,'Banish with soul to prevent damage']],
];}
function FaBBoltynCharged(int $p,string $id): void {
    if($id==='banneret_of_salvation_yellow')FaBWTRAddEffect($p,'BOLTYN_SALVATION',1);
}
function FaBBoltynSoulAdded(int $p,object $o): void {
    if(intval(CardPitch($o->CardID))===2)FaBMONAdd($p,'YELLOW_SOUL');
}
function FaBBoltynYellowChoice(string $ref): bool {
    $f=FaBIdentityFromMZ($ref);return $f!==null&&intval(CardPitch($f['object']->CardID))===2;
}
function FaBBoltynSwordChoices(int $p): string {return implode('&',FaBChoiceRefs($p,'Weapons',['type'=>'Sword']));}
function FaBBoltynSharpen(int $p,string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||!FaBHasType($f['object'],'Sword'))return;
    $o=$f['object'];FaBSetObjectCounter($o,'POWER',intval(FaBObjectCounters($o)['POWER']??0)+1);
    FaBSetObjectCounter($o,'BOLTYN_SHARPEN',1);
    if(intval(FaBObjectCounters($o)['POWER']??0)>0)FaBWTRCreateArena($p,'flurry');
}
function FaBBoltynEnd(): void {
    foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'Weapons') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(!empty(FaBObjectCounters($o)['BOLTYN_SHARPEN'])){FaBSetObjectCounter($o,'POWER',0);FaBSetObjectCounter($o,'BOLTYN_SHARPEN',0);}
    }
}
function FaBBoltynStart(int $p): void {
    foreach(FaBMONArena($p,'agility') as $ref){FaBMONDestroy(intval(FaBIdentityFromMZ($ref)['object']->UniqueID));FaBWTRAddEffect($p,'NEXT_ATTACK',0,['goAgain'=>true]);}
}
function FaBBoltynCardPlayed(int $p,object $o): void {
    if(!FaBWTRIsAttackAction($o)&&!FaBWTRIsWeapon($o))return;
    foreach(FaBMONArena($p,'courage') as $ref){FaBMONDestroy(intval(FaBIdentityFromMZ($ref)['object']->UniqueID));FaBWTRTag($o,'WTR_POWER:1');}
    if(FaBWTRIsWeapon($o))foreach(FaBMONArena($p,'flurry') as $ref){
        FaBMONDestroy(intval(FaBIdentityFromMZ($ref)['object']->UniqueID));
        $w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));
        if($w&&intval(FaBObjectCounters($w['object'])['WEAPON_ATTACKS']??0)<2&&!in_array('CRU_EXTRA_ATTACK',(array)$w['object']->TurnEffects,true))FaBWTRTag($w['object'],'CRU_EXTRA_ATTACK');
    }
}
function FaBBoltynHit(int $p): void {
    $n=FaBARCEffect($p,'BOLTYN_SALVATION');if(!$n)return;
    FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='BOLTYN_SALVATION')));FaBCRUGainLife($p,$n);
}
function FaBBoltynUnity(int $p,int $uid): void {
    $f=FaBFindUID($uid);if(!$f||in_array('BOLTYN_UNITY',(array)$f['object']->TurnEffects,true))return;
    foreach(FaBChoiceRefs($p,'CombatChain') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(intval($o->ChainLink)===intval($f['object']->ChainLink)&&($o->FromZone??'')==='Hand'&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)){
            FaBWTRTag($f['object'],'BOLTYN_UNITY');FaBWTRTag($f['object'],'WTR_DEFENSE:1');break;
        }
    }
}
function FaBBoltynTemper(int $p,object $o): void {
    if(($o->Role??'')!=='DEFENSE'||($o->FromZone??'')!=='Equipment'||!FaBHasKeyword($o,'Temper'))return;
    FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+1);
    if(FaBCurrentDefense($o,$p)<=0)FaBWTRTag($o,'DESTROY_ON_CHAIN_CLOSE');
    // Allow a new Unity trigger if this equipment defends on another chain.
    $o->TurnEffects=array_values(array_filter((array)$o->TurnEffects,fn($tag)=>$tag!=='BOLTYN_UNITY'));
}
function FaBBoltynPrevent(int $p,int $amount): int {
    $left=[];$tokens=0;
    foreach(FaBWTREffects($p) as $e){
        if(($e['type']??'')!=='BOLTYN_TOE'||$amount<=0){$left[]=$e;continue;}
        $amount-=min($amount,2);++$tokens;
    }
    FaBWTRSetEffects($p,$left);for($i=0;$i<$tokens;++$i)FaBWTRCreateArena($p,'flurry');return $amount;
}
