<?php
// Heavy Hitters: stable identities and explicit effect controllers across choices.
function FaBHVYCount(int $p,string $key): int {return FaBARCEffect($p,'HVY_'.$key);}
function FaBHVYAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'HVY_'.$key,$n,$data);}
function FaBHVYClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='HVY_'.$key)));}
function FaBHVYHero(int $p,string $hero): bool {return FaBMONHero($p,$hero);}
function FaBHVYGold(int $p): string {
    $refs=array_merge(FaBChoiceRefs($p,'Arena',['base'=>'gold']),FaBChoiceRefs($p,'Arena',['base'=>'golden_skull']),FaBSUPGoldEquipment($p));
    foreach(FaBCRUEquipment($p,'aurum_aegis') as $r)$refs[]=$r;
    return implode('&',$refs);
}
function FaBHVYDestroyGold(int $p,string $r): bool {
    if(!in_array($r,explode('&',FaBHVYGold($p)),true))return false;
    $f=FaBIdentityFromMZ($r);if(!$f)return false;FaBMONDestroy(intval($f['object']->UniqueID));return true;
}
function FaBHVYActionSource(): bool {
    $f=FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'));
    return $f&&FaBHasType($f['object'],'Action');
}
function FaBHVYToken(int $p,string $id,int $n=1,?int $controller=null,?bool $action=null,bool $wager=false): ?object {
    if(!FaBSeatIsLive($p)||$n<1)return null;$controller??=intval($GLOBALS['fabEffectController']??0)?:$p;$action??=FaBHVYActionSource();
    if(FaBPENReplaceFrost($p,$id,$n,$controller,$action,$wager))return null;
    $n=FaBMPGTokenCount($p,$id,$n);if(FaBHasType($id,'Aura') && (FaBHasType($id,'Elemental')||FaBHasType($id,'Runeblade')))$n+=FaBARCEffect($p,'PEN_VERDANT');if($n<1)return null;
    if(FaBHasType($id,'Aura')&&FaBROSActive($p,'florian'))++$n;
    if($wager)foreach(FaBLiveSeats() as $s)$n+=FaBHVYCount($s,'DOUBLE_DOWN');
    if($action)foreach(FaBLiveSeats() as $s)$n-=FaBHVYCount($s,'RIPPLE');
    $first=null;for($i=0;$i<max(0,$n);++$i){$o=FaBWTRCreateArena($p,$id,true);$first??=$o;}
    if($n>0&&$id==='runechant')FaBIARCreatedRunes($p,$n);
    if($n>0&&in_array($id,['agility','confidence','toughness','might','vigor'],true))FaBHVYAdd($p,'CONTROLLED_'.$id);
    if($n>0&&$id==='gold'&&$controller===$p&&FaBHVYHero($p,'victor_goldmane')&&!FaBHVYCount($p,'VICTOR_DRAW')&&in_array(GetCurrentPhase(),['MAIN','END'],true)) {
        FaBHVYAdd($p,'VICTOR_DRAW');DoDrawCard($p,1);
    }
    return $first;
}
function FaBHVYOwnedPower(int $p,object $o): int {return FaBMONBasePower($p,$o)+FaBMPGOutsidePower($p,$o);}
function FaBHVYPowerOutsideChain(int $p,object $o): int {
    $owner=intval($o->Owner??$p);if(!$owner)$owner=$p;
    if(!FaBWTRIsAttackAction($o)||!FaBHVYHero($owner,'kayo')||(FaBHVYHero($owner,'kayo_berserker_runt')||in_array(GetHero($owner)[0]->CardID,['kayo_strong_arm','kayo_underhanded_cheat'],true)))return 0;
    $f=isset($o->UniqueID)?FaBFindUID(intval($o->UniqueID)):null;
    return ($f&&$f['zone']==='CombatChain')||(!$f&&in_array($o->Role??'',['ATTACK','DEFENSE','DEFENSE_REACTION'],true))?0:FaBPENPowerGain($o,1);
}
function FaBHVYSix(int $p,string $zone='Hand'): string {
    return implode('&',array_filter(FaBChoiceRefs($p,$zone),fn($r)=>FaBHVYOwnedPower($p,FaBIdentityFromMZ($r)['object'])>=6));
}
function FaBHVYBeatChoices(int $p): string { $cost=intval(FaBGetState()['pendingPayment']['cost']??0);return implode('&',array_filter(explode('&',FaBHVYSix($p)),function($r)use($p,$cost){$f=FaBIdentityFromMZ($r);return $f&&FaBAvailablePitch($p,intval($f['object']->UniqueID))>=$cost;}));}
function FaBHVYBeat(int $p,string $r): void {if(in_array($r,explode('&',FaBHVYSix($p)),true)&&FaBDiscardChoice($p,$r))FaBHVYAdd($p,'BEAT');}
function FaBHVYBottom(int $uid): void {if(FaBFindUID($uid))FaBARCToDeck(FaBFindUID($uid)['player'],$uid,false);}
function FaBHVYClash(int $a,int $b): array {
    $out=['winner'=>0,'cards'=>[],'seats'=>[$a,$b]];
    if($a===$b||!FaBSeatIsLive($a)||!FaBSeatIsLive($b))return $out;
    $switch=FaBSUPSwitch($a,$b);$out['switch']=$switch;$power=[];
    foreach([$a,$b] as $p){$owner=$switch?($p===$a?$b:$a):$p;$r=FaBChoiceRefs($owner,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);$power[$p]=-1;
        if($f){$o=$f['object'];FaBRevealChoices($p,$r);$power[$p]=is_numeric(CardPower($o->CardID))?FaBHVYOwnedPower($owner,$o):-1;$out['cards'][]=['owner'=>$owner,'player'=>$p,'uid'=>intval($o->UniqueID),'card'=>$o->CardID];}}
    if($power[$a]!==$power[$b])$out['winner']=$power[$a]>$power[$b]?$a:$b;
    return FaBSUPClashResult($out);
}
function FaBHVYClashRetry(array $clash): int {
    foreach($clash['seats'] as $p)if($p!==intval($clash['winner'])&&FaBSeatIsLive($p)&&FaBHVYHero($p,'victor_goldmane')&&!FaBHVYCount($p,'VICTOR_RETRY')){FaBHVYAdd($p,'VICTOR_RETRY');return $p;}
    return 0;
}
function FaBHVYClashPreviews(int $p,array $clash): string {
    $refs=[];foreach($clash['cards'] as $entry){$o=AddTemp($p,CardID:$entry['card']);FaBARCSetCard(intval($o->UniqueID),'hvyOriginal',intval($entry['uid']));$refs[]='p'.$p.'Temp-'.$o->mzIndex;}return implode('&',$refs);
}
function FaBHVYClearPreviews(string $refs): void {foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Temp')$f['object']->removed=true;}}
function FaBHVYBottomPreview(string $r,string $refs): void {
    $f=FaBIdentityFromMZ($r);if($f&&in_array($r,explode('&',$refs),true))FaBHVYBottom(intval(FaBARCCard(intval($f['object']->UniqueID),'hvyOriginal')));
    FaBHVYClearPreviews($refs);
}
function FaBHVYClashWon(array $clash,int $controller,string $prize=''): int {
    $p=intval($clash['winner']);if(!$p||!FaBSeatIsLive($p))return 0;FaBHVYAdd($p,'CLASH_WINS');FaBSUPClashWon($clash);
    if($prize!=='')FaBHVYToken($p,$prize,1,$controller);

    foreach($clash['cards'] as $entry)if($entry['player']===$p&&intval($entry['owner']??$p)===$p){$token=['the_golden_son'=>'gold','thunk'=>'might','wallop'=>'vigor'][FaBWTRBase($entry['card'])]??'';if($token!=='')FaBHVYToken($p,$token,1,$p,true);}
    return $p;
}
function FaBHVYWager(int $p,int $uid,int $victim,array $tokens): bool {
    $f=FaBFindUID($uid);if(!$f||$f['player']!==$p||!FaBSeatIsLive($p)||!FaBSeatIsLive($victim)||$p===$victim)return false;
    $wagers=(array)FaBARCCard($uid,'hvyWagers',[]);$wagers[]=['victim'=>$victim,'tokens'=>$tokens,'action'=>FaBHasType($f['object'],'Action')];FaBARCSetCard($uid,'hvyWagers',$wagers);FaBHVYAdd($p,'WAGERED');
    foreach(FaBLiveSeats() as $s)if(FaBHVYCount($s,'NEXT_WAGER')){FaBTagUID($uid,'WTR_POWER:'.FaBHVYCount($s,'NEXT_WAGER'));FaBTagUID($uid,'OVERPOWER');FaBHVYClear($s,'NEXT_WAGER');}
    if(FaBHVYHero($p,'betsy'))FaBRunSourceMacro('ResolveAbility',$p,'betsy',['mzID'=>$f['mzID'],'hvyAttackUID'=>$uid]);
    return true;
}
function FaBHVYResolveWagers(int $p,int $uid): void {
    if(!FaBSeatIsLive($p))return;$s=FaBGetState();$wagers=(array)FaBARCCard($uid,'hvyWagers',[]);FaBARCSetCard($uid,'hvyWagers',[]);if(FaBPENWagerResolution($p,$uid,$wagers))return;$olympia=false;
    foreach($wagers as $w){$victim=intval($w['victim']);$hit=isset($s['targetDamage'][(string)$victim])?intval($s['targetDamage'][(string)$victim]['damage'])>0:(!empty($s['attackHit'])&&$victim===intval($s['defender']));$winner=$hit?$p:$victim;
        if(!FaBSeatIsLive($winner))continue;foreach($w['tokens'] as $token){if($token==='ROS_DRINK'){DoDrawCard($winner,1);FaBROSQueue($winner,'drink_em_under_the_table_red',$uid,['rosTarget'=>$hit?$victim:$p]);}else FaBHVYToken($winner,$token,1,$p,$w['action'],true);}
        if($hit&&!$olympia&&FaBHVYHero($p,'olympia')){$olympia=true;FaBHVYToken($p,'gold',1,$p,false);}}
}
function FaBHVYAttackTargets(int $p,string $kind=''): string {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f||$f['player']!==$p||($f['object']->Role??'')!=='ATTACK')return '';
    $o=$f['object'];$ok=match($kind){'warrior'=>FaBHasType($o,'Warrior'),'weapon'=>FaBWTRIsWeapon($o),'sword'=>FaBHasType($o,'Sword'),'warriorAbove'=>FaBHasType($o,'Warrior')&&FaBAttackPower($s)>intval(CardPower($o->CardID)),default=>true};return $ok?$f['mzID']:'';
}
function FaBHVYActionBlock(array $s): bool {foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBWTRIsAttackAction($o))return true;}return false;}
function FaBHVYNext(int $p,int $power,string $classes='',string $tag=''): void {FaBHVYAdd($p,'NEXT_ATTACK',$power,['classes'=>$classes,'tag'=>$tag]);}
function FaBHVYAbove(int $uid): bool {$f=FaBFindUID($uid);return $f&&FaBAttackPower(FaBGetState())>intval(CardPower($f['object']->CardID));}
function FaBHVYControlCount(int $p,string $kind): int {if($kind!=='Equipment')return count(FaBChoiceRefs($p,'Arena',['type'=>$kind]));$n=0;foreach(['Equipment','Weapons','CombatChain'] as $z)foreach(FaBChoiceRefs($p,$z,['type'=>'Equipment']) as $r){$o=FaBIdentityFromMZ($r)['object'];if($z!=='CombatChain'||($o->Role??'')==='DEFENSE')++$n;}return $n;}
function FaBHVYLowerLife(int $p): int {return count(array_filter(FaBOpponents($p),fn($v)=>FaBPENLifeMore($v,$p)));}
function FaBHVYNoFear(int $p,int $uid,string $refs): void {
    $n=0;foreach(explode('&',$refs) as $r)if(in_array($r,explode('&',FaBHVYSix($p)),true)){$o=FaBMoveChoice($p,$r,'Hand','Banish');if($o){$o->ReturnAtEndTurn=1;FaBARCSetCard(intval($o->UniqueID),'hvyFearReturn',true);++$n;}}FaBARCSetCard($uid,'hvyFear',$n);
}
function FaBHVYCostGold(int $p,int $uid,string $refs,bool $alternative=false): int {
    $n=0;foreach(explode('&',$refs) as $r)if(FaBHVYDestroyGold($p,$r))++$n;
    FaBARCSetCard($uid,'hvyGold',$n);if($n&&$alternative){$s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)===$uid){$f=FaBFindUID($uid);$s['pendingPayment']['cost']=max(0,intval($s['pendingPayment']['cost'])-intval(CardCost($f['object']->CardID)));FaBSetState($s);}}return $n;
}
function FaBHVYBanishColors(int $p,string $refs,int $each): bool {
    $uids=FaBUPRUIDs($refs);$counts=[1=>0,2=>0];foreach($uids as $uid){$f=FaBFindUID($uid);if(!$f||$f['player']!==$p||$f['zone']!=='Graveyard')return false;$pitch=intval(CardPitch($f['object']->CardID));if(!isset($counts[$pitch]))return false;++$counts[$pitch];}
    if($counts!==[1=>$each,2=>$each])return false;foreach($uids as $uid)FaBMoveUID($uid,'Banish',$p);return true;
}
function FaBHVYCastBones(int $p): void {
    $refs=array_slice(FaBChoiceRefs($p,'Deck'),0,6);$n=0;foreach($refs as $r)if(FaBHVYOwnedPower($p,FaBIdentityFromMZ($r)['object'])>=6)++$n;
    FaBRevealChoices($p,implode('&',$refs));$deck=&GetDeck($p);$deck=array_values(array_filter($deck,fn($o)=>is_object($o)&&empty($o->removed)));$top=array_splice($deck,0,6);EngineShuffle($top,true);$deck=array_merge($top,$deck);
    FaBHVYToken($p,'might',$n,$p,true);if(count(FaBMONArena($p,'might'))>=6)FaBHVYToken($p,'agility',1,$p,true);
}
function FaBHVYDestroyArsenal(int $p): void {foreach(FaBChoiceRefs($p,'Arsenal') as $r)FaBDYNDestroyChoice($r);}
function FaBHVYHandArsenal(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Hand'),FaBChoiceRefs($p,'Arsenal')));}
function FaBHVYBottomChoice(string $r): void {$f=FaBIdentityFromMZ($r);if($f)FaBHVYBottom(intval($f['object']->UniqueID));}
function FaBHVYBottomClash(array $c): void {foreach($c['cards'] as $e)FaBHVYBottom(intval($e['uid']));}
function FaBHVYDestroyRevealed(array $c,int $p): void {foreach($c['cards'] as $e)if($e['player']===$p)FaBMONDestroy(intval($e['uid']));}
function FaBHVYGrindstone(int $uid): void {$f=FaBFindUID($uid);$w=$f?FaBFindUID(intval(FaBObjectCounters($f['object'])['WEAPON_UID']??0)):null;if($w)FaBEVOCounter($w['object'],'POWER',intval(FaBObjectCounters($w['object'])['POWER']??0)-1);}
function FaBHVYIntellect(int $p,int $roll): void {$h=GetHero($p)[0]??null;if($h){FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='HVY_BASE_INTELLECT')));FaBHVYAdd($p,'BASE_INTELLECT',$roll-intval(CardIntelligence($h->CardID)));}}
function FaBHVYPackCall(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return;FaBRevealChoices($p,$r);if(FaBHVYOwnedPower($p,$f['object'])<6)FaBHVYBottom(intval($f['object']->UniqueID));}
function FaBHVYSmallEquipment(int $p): string {return implode('&',array_filter(array_merge(FaBChoiceRefs($p,'Equipment'),FaBChoiceRefs($p,'CombatChain')),function($r)use($p){$f=FaBIdentityFromMZ($r);return FaBHasType($f['object'],'Equipment')&&FaBCurrentDefense($f['object'],$p)<=1;}));}
function FaBHVYDestroyAuraTokens(int $p): void {foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasType($o,'Token')&&FaBHasType($o,'Aura'))FaBMONDestroy(intval($o->UniqueID));}}
function FaBHVYDissolve(int $p): void {foreach(FaBLiveSeats() as $s){foreach(FaBChoiceRefs($s,'Arsenal') as $r)FaBHVYBottomChoice($r);FaBHVYToken($s,'ponder',1,$p,true);}}
function FaBHVYPrevent(int $p,int $amount): int {
 $left=[];$tokens=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')!=='HVY_PREVENT_TOKEN'||$amount<=0){$left[]=$e;continue;}$n=min($amount,intval($e['amount']));$amount-=$n;$e['amount']-=$n;if($n>0&&empty($e['triggered'])){$tokens[]=$e['token'];$e['triggered']=true;}if($e['amount']>0)$left[]=$e;}FaBWTRSetEffects($p,$left);foreach($tokens as $t)FaBHVYToken($p,$t,1,$p,false);return $amount;
}
function FaBHVYPeek(int $p,int $owner,int $n): array {$uids=[];foreach(array_slice(FaBChoiceRefs($owner,'Deck'),0,$n) as $r){$uid=intval(FaBIdentityFromMZ($r)['object']->UniqueID);FaBMoveUID($uid,'Temp',$p);$uids[]=$uid;}return $uids;}
function FaBHVYFinishPeek(int $p,int $owner,array $uids,string $order): void {
 // Order in the viewer's private zone, then return the original cards to their owner.
 $by=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['zone']==='Temp')$by[$f['object']->CardID][]=$uid;}
 $top=[];$bottom=[];foreach(explode(';',$order) as $pile){$pair=explode('=',$pile,2);if(count($pair)!==2)continue;foreach(explode(',',$pair[1]) as $id)if(!empty($by[$id])){if($pair[0]==='Bottom')$bottom[]=array_shift($by[$id]);else $top[]=array_shift($by[$id]);}}
 foreach($by as $u)$top=array_merge($top,$u);foreach($bottom as $uid)FaBARCToDeck($owner,intval($uid),false);foreach(array_reverse($top) as $uid)FaBARCToDeck($owner,intval($uid),true);
}
function FaBHVYTraps(array $uids): string {return implode('&',array_filter(explode('&',FaBEVRUIDRefs($uids)),fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Trap')));}
function FaBHVYReel(int $p,array $uids,string $refs): void {foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&in_array(intval($f['object']->UniqueID),$uids,true)&&FaBHasType($f['object'],'Trap')){FaBRevealChoices($p,$r);FaBMoveUID(intval($f['object']->UniqueID),'Hand',$p);}}foreach($uids as $uid){$f=FaBFindUID($uid);if($f&&$f['zone']==='Temp')FaBMoveUID($uid,'Deck',$p);}FaBShuffleDeck($p);}
function FaBHVYSendPacking(int $p,int $uid): void {$v=intval(FaBGetState()['defender']);$uids=FaBUPRUIDs(implode('&',FaBChoiceRefs($v,'Arsenal')));foreach($uids as $u)FaBMoveUID($u,'Banish',$v);FaBARCSetCard($uid,'hvyPacking',$uids);}
function FaBHVYReturnPacking(int $uid): void {if(!empty(FaBGetState()['attackHit']))return;foreach((array)FaBARCCard($uid,'hvyPacking',[]) as $u){$f=FaBFindUID(intval($u));if($f&&$f['zone']==='Banish')FaBMoveUID(intval($u),'Hand',$f['player']);}}
function FaBHVYSonataRefs(int $p,int $x): string {return implode('&',array_filter(explode('&',FaBStageSearch($p,['type'=>'Aura'])),function($r)use($x){$f=FaBIdentityFromMZ($r);if(!$f)return false;$o=$f['object'];return FaBHasType($o,'Runeblade')&&FaBHasType($o,'Aura')&&intval(CardCost($o->CardID))<=$x;}));}
function FaBHVYGraven(int $p,int $uid): void {$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Graveyard')return;$o=FaBMoveUID($uid,'Weapons',$p);if($o)FaBEVOCounter($o,'POWER',1);}
function FaBHVYDamage(int $p,int $victim,int $n,string $type): void {
 if($n<=0||$p===$victim)return;
 if(FaBHVYCount($p,'SHIFT')){FaBHVYClear($p,'SHIFT');FaBHVYToken($p,'agility',1,$p,false);}
 if($type==='PHYSICAL'){$left=[];$tokens=0;foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='HVY_TALK'&&$n>=intval($e['amount']))$tokens+=intval($e['amount']);else $left[]=$e;}FaBWTRSetEffects($p,$left);FaBHVYToken($p,'might',$tokens,$p,true);}
 if(intval(GetHealth($victim))<=0)foreach(FaBLiveSeats() as $s)foreach(FaBMONArena($s,'deathmatch_arena') as $r)FaBHVYToken($p,'gold',FaBSeatCount(),$s,false);
}
function FaBHVYAttackDeclared(int $p,object $o): void {
 if(FaBWTRIsWeapon($o))FaBHVYAdd($p,'WEAPON_ATTACKED');
 if(FaBWTRBase($o->CardID)==='down_but_not_out'&&FaBFaiHeroHit()&&FaBHVYDown($p)){FaBARCSetCard(intval($o->UniqueID),'hvyDown',true);FaBWTRTag($o,'WTR_POWER:3');FaBWTRTag($o,'OVERPOWER');}
 if(FaBHasType($o,'Angel')){if(!FaBHVYCount($p,'ANGEL_ATTACKED'))FaBWTRTag($o,'HVY_FIRST_ANGEL');FaBHVYAdd($p,'ANGEL_ATTACKED');}
 if(FaBFaiHeroHit())foreach((array)($o->TurnEffects??[]) as $t){if(str_starts_with($t,'HVY_WAGER:'))FaBRunSourceMacro('ResolveAbility',$p,'money_where_ya_mouth_is_red',['mzID'=>FaBDTDSource(intval($o->UniqueID)),'hvyTokens'=>[substr($t,10)],'hvyMandatory'=>false]);if(str_starts_with($t,'HVY_WAGER_ALWAYS:'))FaBRunSourceMacro('ResolveAbility',$p,'money_where_ya_mouth_is_red',['mzID'=>FaBDTDSource(intval($o->UniqueID)),'hvyTokens'=>explode(',',substr($t,17)),'hvyMandatory'=>true]);}
}
function FaBHVYCheapItems(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Item']),fn($r)=>intval(CardCost(FaBIdentityFromMZ($r)['object']->CardID))<=1));}
function FaBHVYDestroyChoices(string $refs): void {foreach(explode('&',$refs) as $r)FaBDYNDestroyChoice($r);}
function FaBHVYWeaponReplacements(int $p): string {$refs=FaBChoiceRefs($p,'Weapons');$hands=0;foreach($refs as $r)$hands+=FaBHasType(FaBIdentityFromMZ($r)['object'],'2H')?2:1;return $hands>=2?implode('&',$refs):'';}
function FaBHVYDiscardExcept(int $p,string $r): void {$f=FaBIdentityFromMZ($r);$uid=intval($f['object']->UniqueID??0);foreach(FaBChoiceRefs($p,'Hand') as $ref)if(intval(FaBIdentityFromMZ($ref)['object']->UniqueID)!==$uid)FaBDiscardChoice($p,$ref);}
function FaBHVYPutAura(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Temp')FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);}
function FaBHVYAnte(int $p,int $uid,int $victim,string $modes): void {foreach(array_unique(explode(',',$modes)) as $m){if(in_array($m,['0','1','2'],true))FaBHVYWager($p,$uid,$victim,[['agility','gold','vigor'][intval($m)]]);elseif($m==='3')FaBTagUID($uid,'WTR_POWER:'.count((array)FaBARCCard($uid,'hvyWagers',[])));}}
function FaBHVYHarmony(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);if($o&&FaBHasKeyword($o,'Combo')){$o->PlayableFromBanish=1;FaBWTRTag($o,'HVY_HARMONY');}}
function FaBHVYAfterMove(int $p,object $o,string $from,string $to): void {
 if($to==='Arena'&&in_array($o->CardID,['might','vigor','agility'],true))FaBHVYAdd($p,'CONTROLLED_'.$o->CardID);
 if($o->CardID==='nasty_surprise_blue'&&$to==='Graveyard'&&$from!=='Graveyard'){$controller=intval($GLOBALS['fabEffectController']??0);if($controller&&$controller!==$p&&!FaBGetState()['pendingPayment'])foreach(['agility','confidence','toughness','might','vigor'] as $t)FaBHVYToken($p,$t,1,$p,true);}
}
function FaBHVYPowerTag(object $o,string $tag): string {
 if(!preg_match('/^WTR_POWER:([1-9][0-9]*)$/',$tag,$m)||($o->Role??'')!=='ATTACK')return $tag;
 $uid=intval($o->UniqueID);$count=intval(FaBARCCard($uid,'hvyIronWill'));if(!$count)return $tag;FaBARCSetCard($uid,'hvyIronWill',0);return 'WTR_POWER:'.max(0,intval($m[1])-$count);
}
function FaBHVYDefended(object $o): void {if($o->CardID==='gauntlets_of_iron_will'){$uid=intval(FaBGetState()['attackUID']);FaBARCSetCard($uid,'hvyIronWill',intval(FaBARCCard($uid,'hvyIronWill'))+1);}}
function FaBHVYClose(int $p,object $o): void {if(($o->Role??'')==='DEFENSE'&&FaBHasKeyword($o,'Guardwell'))FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+FaBCurrentDefense($o,$p));}
function FaBHVYMaxX(int $p,int $uid): int {if((FaBFindUID($uid)['object']->CardID??'')!=='sonata_galaxia_red')return FaBEVOMaxVariableCost($p,$uid);$cost=intval(FaBGetState()['pendingPayment']['cost']??0);return intdiv(max(0,FaBAvailablePitch($p)-$cost+FaBARCRunechants($p)),2);}
function FaBHVYSetX(int $uid,int $x): void {
 $f=FaBFindUID($uid);if(!$f)return;if($f['object']->CardID!=='sonata_galaxia_red'){FaBEVOSetX($uid,$x);return;}
 $s=FaBGetState();$p=intval($s['pendingPayment']['player']??0);if(!$p||intval($s['pendingPayment']['uid'])!==$uid)return;$x=max(0,min($x,FaBHVYMaxX($p,$uid)));FaBARCSetCard($uid,'evoX',$x);$s=FaBGetState();$s['pendingPayment']['cost']=max(0,intval($s['pendingPayment']['cost'])+2*$x-FaBARCRunechants($p));FaBSetState($s);
}
function GameBeforeCustomHandler(int $p,string $name,string $param): void {
 $parts=explode('|',$param);$frame=DecisionQueueController::GetAwaitFrame($parts[1]??'');$locals=$frame['locals']??[];
 $f=FaBIdentityFromMZ((string)($locals['mzID']??''));
 $GLOBALS['fabEffectController']=$f?intval($f['object']->Controller??$f['player']):0;
}
function FaBHVYEnd(): void {foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];$uid=intval($o->UniqueID);if(FaBARCCard($uid,'hvyFearReturn')){FaBARCSetCard($uid,'hvyFearReturn',false);FaBMoveUID($uid,'Hand',$p);}}}
