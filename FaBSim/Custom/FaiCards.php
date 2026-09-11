<?php
// Shared mechanics for the Fai practice deck. Interactive card choices live in CardEditor.
function FaBFaiChainCount(int $player, string $type = 'Draconic'): int {
    $links=$type==='Phoenix Flame'?[]:(FaBGetState()['departedChainTypes'][(string)$player]??[]);
    $count=0;
    foreach(GetCombatChain($player) as $obj) if(is_object($obj)&&empty($obj->removed)&&($obj->Role??'')==='ATTACK') {
        if($type==='Phoenix Flame'){if($obj->CardID==='phoenix_flame_red')++$count;}
        else $links[(string)$obj->ChainLink]=EffectiveCardType($obj);
    }
    return $type==='Phoenix Flame'?$count:count(array_filter($links,fn($types)=>in_array($type,$types,true)));
}
function FaBFaiEffect(int $player,string $type): int {
    $n=0; foreach(FaBWTREffects($player) as $e) if(($e['type']??'')===$type) $n+=intval($e['amount']??1); return $n;
}
function FaBFaiFlames(int $player): string {
    return implode('&',FaBChoiceRefs($player,'Graveyard',['base'=>'phoenix_flame']));
}
function FaBFaiReturnFlame(int $player,string $chosen): void {
    $f=FaBIdentityFromMZ($chosen);
    if($f!==null&&$f['player']===$player&&$f['zone']==='Graveyard'&&$f['object']->CardID==='phoenix_flame_red') FaBMoveUID(intval($f['object']->UniqueID),'Hand',$player);
}
function FaBFaiSetup(int $player,bool $useFlame): void {
    if($useFlame)foreach(GetDeck($player) as $obj)if(empty($obj->removed)&&$obj->CardID==='phoenix_flame_red'){FaBMoveUID(intval($obj->UniqueID),'Graveyard',$player);break;}
    DoDrawCard($player,max(1,intval(CardIntelligence(GetHero($player)[0]->CardID))));
}
function FaBFaiCardPlayed(int $player,object $obj): void {
    $effects=[];
    foreach(FaBWTREffects($player) as $e) {
        $type=$e['type']??'';
        if($type==='FAI_BRAND'&&(FaBWTRIsAttackAction($obj)||FaBWTRIsWeapon($obj))) {FaBWTRTag($obj,'FAI_DRACONIC');continue;}
        if($type==='FAI_RISE'&&FaBWTRIsAttackAction($obj)&&(FaBHasType($obj,'Draconic')||FaBHasType($obj,'Ninja'))) {FaBWTRTag($obj,'WTR_POWER:'.intval($e['amount']));continue;}
        if($type==='AOW_NEXT'&&FaBWTRIsAttackAction($obj)) {FaBWTRTag($obj,'GO_AGAIN');continue;}
        $effects[]=$e;
    }
    FaBWTRSetEffects($player,$effects);
    if($obj->CardID==='blaze_headlong_red') {
        $reds=array_filter(FaBGetState()['cardsPlayedThisTurn'][(string)$player]??[],fn($id)=>intval(CardPitch($id))===1);
        if(count($reds)>1)FaBWTRTag($obj,'GO_AGAIN');
    }
    if($obj->CardID==='promise_of_plenty_red'&&($obj->SourceZone??'')==='Arsenal')FaBWTRTag($obj,'GO_AGAIN');
}
function FaBFaiPower(int $player,object $obj): int {
    if(!FaBWTRIsAttackAction($obj))return 0;
    $n=FaBFaiEffect($player,'AOW_STATS');
    if(intval(CardPower($obj->CardID))<=3)$n+=FaBFaiEffect($player,'STUBBY');
    return $n;
}
function FaBFaiDoubleStrike(int $player,string $mzID): void {
    $f=FaBIdentityFromMZ($mzID);if($f===null||in_array('DOUBLE_REPLAY',(array)$f['object']->TurnEffects,true))return;
    $state=FaBGetState();$state['attackGoAgain']=FaBAttackHasGoAgain($state,$f['object']);FaBSetState($state);
    $obj=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);
    if($obj){$obj->PlayableFromBanish=1;FaBSetObjectCounter($obj,'FAI_CHAIN_PLAY',1);FaBWTRTag($obj,'DOUBLE_REPLAY');}
}
function FaBFaiBanishAttack(int $player,string $choice,bool $playable=false): bool {
    $f=FaBIdentityFromMZ($choice);
    if($f===null||$f['player']!==$player||$f['zone']!=='Hand'||!FaBWTRIsAttackAction($f['object']))return false;
    if($playable&&intval(CardCost($f['object']->CardID))>=FaBFaiChainCount($player))return false;
    $obj=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);
    if($obj&&$playable){$obj->PlayableFromBanish=1;FaBSetObjectCounter($obj,'FAI_RESENTMENT',1);}
    return $obj!==null;
}
function FaBFaiHandAttacks(int $player,bool $resentment=false): string {
    $refs=[];foreach(FaBChoiceRefs($player,'Hand') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(FaBWTRIsAttackAction($o)&&(!$resentment||intval(CardCost($o->CardID))<FaBFaiChainCount($player)))$refs[]=$ref;}return implode('&',$refs);
}
function FaBFaiPromise(): void {
    foreach(FaBLiveSeats() as $seat)if(!FaBChoiceRefs($seat,'Arsenal'))foreach(GetDeck($seat) as $o)if(empty($o->removed)){FaBMoveUID(intval($o->UniqueID),'Arsenal',$seat);break;}
}
function FaBFaiHeroHit(): bool {return (FaBGetState()['attackTarget']['type']??'HERO')==='HERO';}
function FaBFaiBreakArsenal(): void {
    if(!FaBFaiHeroHit()||intval(FaBGetState()['chainLink'])<4)return;
    $seat=intval(FaBGetState()['defender']);foreach(FaBChoiceRefs($seat,'Arsenal') as $ref)FaBMoveUID(intval(FaBIdentityFromMZ($ref)['object']->UniqueID),'Graveyard',$seat);
}
function FaBFaiRedHot(int $player): int {
    if(intval(FaBGetState()['chainLink'])<4)return 0;
    $refs=array_slice(FaBChoiceRefs($player,'Deck'),0,FaBFaiChainCount($player));FaBRevealChoices($player,implode('&',$refs));
    $reds=0;foreach($refs as $ref)if(intval(CardPitch(FaBIdentityFromMZ($ref)['object']->CardID))===1)++$reds;
    return $reds;
}
function FaBFaiDamageTargets(): string {
    $refs=[];foreach(FaBLiveSeats() as $p)foreach(['Hero','Arena'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if($zone==='Hero'||FaBObjectCanBeAttacked($o))$refs[]=$ref;}return implode('&',$refs);
}
function FaBFaiRedHotDamage(int $player,string $target,int $amount): void {
    $f=FaBIdentityFromMZ($target);if(!$f)return;
    if($f['zone']==='Hero')DoDamage($player,'',intval($f['player']),$amount,'PHYSICAL');
    elseif($f['zone']==='Arena'&&FaBObjectCanBeAttacked($f['object'])){$f['object']->Damage+=$amount;if($f['object']->Damage>=intval(CardHealth($f['object']->CardID)))FaBMoveUID(intval($f['object']->UniqueID),'Graveyard',intval($f['player']));}
}
function FaBFaiDefendingCount(): int {
    $n=0;foreach(FaBLiveSeats() as $p)foreach(GetCombatChain($p) as $o)if(empty($o->removed)&&in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true))++$n;return $n;
}
function FaBFaiLoseLife(int $seat,int $amount): void {
    AddHealth($seat,max(0,intval(GetHealth($seat))-$amount));if(intval(GetHealth($seat))===0)FaBEliminateSeat($seat);
}
