<?php
// Part the Mistveil: Chi is a separately tracked subset of available resources.
function FaBMSTCount(int $p,string $key): int {return FaBARCEffect($p,'MST_'.$key);}
function FaBMSTAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'MST_'.$key,$n,$data);}
function FaBMSTClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='MST_'.$key)));}
function FaBMSTChi(int $p): int {return min(intval(GetResources($p)),max(0,intval(GetChi($p))));}
function FaBMSTBeforeResources($p,$value,$source=null): bool {
 if(!FaBELEBeforeResources($p,$value,$source))return false;
 $spent=max(0,intval(GetResources($p))-intval($value));if($spent)AddChi($p,max(0,intval(GetChi($p))-$spent));return true;
}
function FaBMSTPitch(int $p,string $id,int $amount): void {
 if(FaBHasType($id,'Earth')){$rosUID=intval(FaBGetState()['pendingPayment']['uid']??0);if($rosUID)FaBARCSetCard($rosUID,'rosEarthPitch',true);}
 if(FaBHasType($id,'Chi')){AddChi($p,intval(GetChi($p))+$amount);$uid=intval(FaBGetState()['pendingPayment']['uid']??0);if($uid)FaBARCSetCard($uid,'mstChiPitched',intval(FaBARCCard($uid,'mstChiPitched'))+1);}
 if(FaBHasType($id,'Chi'))foreach(FaBCRUEquipment($p,'meridian_pathway') as $r)FaBRunSourceMacro('ResolveAbility',$p,'meridian_pathway',['mzID'=>$r,'mstEvent'=>'pitch']);
 if(FaBMSTColor($p,$id)===3)FaBMSTAdd($p,'BLUE_PITCHED');
}
function FaBMSTColor(int $p,string $id): int {
 foreach(FaBChoiceRefs($p,'Hero') as $r)if(!empty(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['MST_COLORLESS_UNTIL']))return 0;
 return intval(CardPitch($id));
}
function FaBMSTBlue(int $p): int {return FaBMSTCount($p,'BLUE_PITCHED');}
function FaBMSTOtherBlue(int $p,int $uid): bool {return FaBMSTCount($p,'BLUE_PLAYED')>((intval(FaBARCCard($uid,'mstBluePlayedTurn'))===intval(GetTurnNumber())&&intval(FaBARCCard($uid,'mstBluePlayedPlayer'))===$p)?1:0);}
function FaBMSTTranscend(int $p,int $uid): void {
 $f=FaBFindUID($uid);if(!$f)return;$owner=intval($f['object']->Owner??$f['player']);if(!$owner)$owner=$f['player'];
 $f['object']->CardID='inner_chi_blue';$o=FaBMoveUID($uid,'Hand',$owner);if(!$o)return;FaBMSTAdd($p,'TRANSCENDED');
 foreach(FaBCRUEquipment($p,'twelve_petal_kasaya') as $r)FaBRunSourceMacro('ResolveAbility',$p,'twelve_petal_kasaya',['mzID'=>$r,'mstEvent'=>'transcend']);
}
function FaBMSTChiCost(string $id): int {return in_array($id,['enigma','enigma_ledger_of_ancestry','enigma_new_moon','nuu','nuu_alluring_desire','zen','zen_tamer_of_purpose','mask_of_recurring_nightmares','meridian_pathway','twelve_petal_kasaya','rippling_wave','kimono_of_layered_lessons'],true)?3:0;}
function FaBMSTAvailableChi(int $p): int {$n=FaBMSTChi($p);foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasType($o,'Chi')&&!FaBARCNamedProhibited($o->CardID)&&FaBELECanPitch($p,$o)&&FaBOUTCanPitch($p,$o))$n+=intval(CardPitch($o->CardID));}return $n;}
function FaBMSTPendingChi(array $pending): int {return !empty($pending['isAbility'])?FaBMSTChiCost((string)(FaBFindUID(intval($pending['uid']))['object']->CardID??'')):0;}
function FaBMSTCanPitch(int $p,object $o,array $pending): bool {return FaBMSTChi($p)>=FaBMSTPendingChi($pending)||FaBHasType($o,'Chi');}
function FaBMSTHidden(object $o): bool {return intval($o->FaceDown??0)===1&&FaBHasType($o,'Equipment');}
function FaBMSTCloakAbility(object $o): bool {return FaBMSTHidden($o)&&!in_array('NO_ABILITIES',(array)($o->TurnEffects??[]),true)&&empty(FaBObjectCounters($o)['_overrides']['NO_ABILITIES'])&&in_array($o->CardID,['rippling_wave','kimono_of_layered_lessons','aqua_laps','aqua_seeing_shell','waves_of_aqua_marine','truths_retold','uphold_tradition','skybody_keikoi','skycrest_keikoi','skyhold_keikoi','skywalker_keikoi'],true);}
function FaBMSTSetup(int $p): void {foreach(FaBChoiceRefs($p,'Equipment') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasKeyword($o->CardID,'Cloaked')||FaBMONHero($p,'enigma_new_moon'))$o->FaceDown=1;}}
function FaBMSTFlip(int $uid): void {$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Equipment'||!FaBMSTHidden($f['object']))return;$f['object']->FaceDown=0;FaBARCSetCard($uid,'mstFlipped',true);if($f['object']->CardID==='koi_blessed_kimono')FaBRunSourceMacro('ResolveAbility',$f['player'],$f['object']->CardID,['mzID'=>$f['mzID']]);}
function FaBMSTWard(int $p,object $o): int {
 if (!HasNoAbilities($o) && in_array(FaBWTRBase($o->CardID), ['holo_shield','corrosive_space_dust'], true)) return FaBOMNHolo($o) ? 5-intval(CardPitch($o->CardID)) : 1;
 if(HasNoAbilities($o))return 0;$penWard=FaBARCCard(intval($o->UniqueID),'penWard',[]);if(intval($penWard['turn']??-1)===intval(GetTurnNumber()))return intval($penWard['amount']);$b=FaBWTRBase($o->CardID);$text=(string)CardFunctional_text_plain($o->CardID);
 if($b==='manifestation_of_miragai')return max(0,intval(FaBObjectCounters($o)['POWER']??0));
 if($b==='haze_shelter')return FaBMSTBlue($p)?4:1;
 if($b==='three_visits')return 3*FaBMSTBlue($p);
 if($b==='rage_specter')return intval(GetTurnPlayer())===$p?6:1;
 if($b==='meridian_pathway'&&in_array('MST_WARD3',(array)($o->TurnEffects??[]),true))return 3;
 if(preg_match('/\bWard (\d+)/i',$text,$m))return intval($m[1]);return 0;
}
function FaBMSTAuras(int $p,bool $ward=false): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Aura']),fn($r)=>!$ward||FaBMSTWardActive(FaBIdentityFromMZ($r)['object'])));}
function FaBMSTCounters(string $r,int $n): void {$f=FaBIdentityFromMZ($r);if($f)FaBEVOCounter($f['object'],'POWER',max(0,intval(FaBObjectCounters($f['object'])['POWER']??0)+$n));}
function FaBMSTShield(int $p,int $power=0,int $n=1,?bool $action=null): void {$o=FaBHVYToken($p,'spectral_shield',$n,$p,$action);if($o&&$power)FaBEVOCounter($o,'POWER',$power);}
function FaBMSTTiger(int $p,bool $hand=true,int $n=1): void {$n=FaBPENCreatedCount($p,$n);for($i=0;$i<$n;++$i){if($hand)AddHand($p,CardID:'crouching_tiger',Owner:$p,Controller:$p);else{$o=AddBanish($p,CardID:'crouching_tiger',Owner:$p,Controller:$p,PlayableFromBanish:1);}FaBMSTAdd($p,'TIGER_CREATED');}}
function FaBMSTEphemeral(int $p,string $id): void {$n=FaBPENCreatedCount($p,1);for($i=0;$i<$n;++$i)AddHand($p,CardID:$id,Owner:$p,Controller:$p);}
function FaBMSTAttackRefs(int $p,string $kind='',bool $own=true): string {
 $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f||($own&&intval($s['attacker'])!==$p))return '';$o=$f['object'];
 $ok=match($kind){'aa'=>FaBWTRIsAttackAction($o),'hybrid'=>FaBWTRIsAttackAction($o)&&(FaBHasType($o,'Assassin')||FaBHasType($o,'Mystic')),'small'=>FaBWTRIsAttackAction($o)&&FaBMONBasePower($p,$o)<=1,'stealth'=>FaBHasKeyword($o,'Stealth'),'tiger'=>$o->CardID==='crouching_tiger',default=>true};return $ok?$f['mzID']:'';
}
function FaBMSTIllusionAuras(int $p,int $exclude=0): array {return array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Aura']),fn($r)=>($f=FaBIdentityFromMZ($r))&&FaBHasType($f['object'],'Illusionist')&&intval($f['object']->UniqueID)!==$exclude);}
function FaBMSTEnter(int $p,object $o): void {
 $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$other=FaBMSTIllusionAuras($p,$uid);$power=0;
 if($b==='restless_coalescence'&&!FaBARCCard($uid,'mstRestlessMoved'))FaBRunSourceMacro('ResolveCard',$p,$o->CardID,['mzID'=>FaBDTDSource($uid)]);
 if($b==='manifestation_of_miragai')$power=FaBARCCard($uid,'mstChiPitched')?4:2;
 if($b==='waxing_specter'&&FaBMSTBlue($p))$power=1;
 if($b==='single_minded_determination'&&!$other)$power=3;
 if($power)FaBEVOCounter($o,'POWER',$power);
 if($b==='rage_specter'&&!$other)AddActionPoints($p,intval(GetActionPoints($p))+1);
 if($b==='solitary_companion'&&!$other)FaBMSTShield($p,0,1,true);
 if(FaBHasType($o,'Aura')&&FaBARCCard($uid,'mstVengeful')){FaBARCSetCard($uid,'mstVengeful',false);FaBMSTCounters(FaBDTDSource($uid),1);}
}
function FaBMSTAfterMove(int $p,object $o,string $from,string $to): void {
 if($from==='CombatChain'&&$to!=='CombatChain')FaBARCSetCard(intval($o->UniqueID),'mstZeroDefense',false);
 if($from==='Arena'&&$to!=='Arena'&&FaBWTRBase($o->CardID)==='restless_coalescence')FaBARCSetCard(intval($o->UniqueID),'mstRestlessMoved',false);
 if($to==='Arena'&&$from!=='Arena')FaBMSTEnter($p,$o);
 if($from!=='Arena'||$to==='Arena')return;$p=intval($o->Controller??$p)?:$p;$b=FaBWTRBase($o->CardID);$none=!FaBMSTIllusionAuras($p);
 if($b==='haunting_specter')FaBMSTShield($p,$none?1:0,1,true);
 if($b==='waning_vengeance'&&FaBMSTBlue($p))FaBMSTShield($p,0,1,false);
 if($b==='vengeful_apparition'&&$none)FaBMSTAdd($p,'VENGEFUL');
 if(str_starts_with($b,'essence_of_ancestry_')&&$none)FaBMSTAdd($p,'PREVENT_COLOR',intval(CardPitch($o->CardID)));
}
function FaBMSTAuraBase(int $p,object $o): ?int {if(empty(FaBObjectCounters($o)['MON_AURA']))return null;$src=FaBFindUID(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0));return $src&&FaBMONWeapon($p,'cosmo_scroll_of_ancestral_tapestry')?FaBMSTWard($p,$src['object']):null;}
function FaBMSTCounterRefs(int $p): string {return implode('&',array_filter(explode('&',FaBMSTAuras($p)),fn($r)=>($f=FaBIdentityFromMZ($r))&&intval(FaBObjectCounters($f['object'])['POWER']??0)>0));}
function FaBMSTRemoveCounters(int $p,int $source,string $r,int $n,bool $move=false): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Arena')return;$n=min(max(0,$n),intval(FaBObjectCounters($f['object'])['POWER']??0));FaBMSTCounters($r,-$n);if($move)FaBMSTCounters(FaBDTDSource($source),$n);}
function FaBMSTChiSearch(int $p): string {return FaBStageSearch($p,['type'=>'Chi']);}
function FaBMSTComboSearch(int $p): string {return FaBStageSearch($p,['keyword'=>'Combo']);}
function FaBMSTSearchBanish(int $p,string $r): void {$o=FaBMoveChoice($p,$r,'Temp','Banish');if($o)$o->PlayableFromBanish=1;FaBFinishSearch($p);}
function FaBMSTSearchHand(int $p,string $r): void {FaBRevealChoices($p,$r);FaBMoveChoice($p,$r,'Temp','Hand');FaBFinishSearch($p);}
function FaBMSTBanish(int $p,int $uid,string $r): int {
 $f=FaBDYNPrivateOriginal($r);if(!$f)return 0;$id=$f['object']->CardID;$owner=$f['player'];$color=FaBMSTColor($owner,$id);$b=FaBWTRBase(FaBFindUID($uid)['object']->CardID??'');
 $history=(array)FaBARCCard($uid,'mstBanished',[]);$name=(string)CardName($id);FaBDYNBanishChoice($f['mzID'],$p);
 $gain=match($b){'desires_of_flesh'=>FaBHasType($id,'Action')&&FaBHasType($id,'Attack'),'minds_desire'=>FaBHasType($id,'Action')&&!FaBHasType($id,'Attack'),'impulsive_desire'=>FaBHasType($id,'Instant')||FaBHasType($id,'Attack Reaction')||FaBHasType($id,'Defense Reaction'),'persuasive_prognosis'=>FaBHasType($id,'Action'),'bonds_of_attraction'=>$color>0&&in_array($color,array_column($history,'color'),true),'bonds_of_memory'=>in_array($name,array_column($history,'name'),true),default=>false};
 if(str_starts_with($b,'art_of_desire_')&&$color===intval(CardPitch(FaBFindUID($uid)['object']->CardID))){$gain=true;DoDrawCard($p,1);}if($gain)FaBCRUGainLife($p,1);
 $history[]=['color'=>$color,'name'=>$name];FaBARCSetCard($uid,'mstBanished',$history);return $color;
}
function FaBMSTBanishTop(int $p,int $uid,int $v,int $n=1): int {$color=0;for($i=0;$i<$n;++$i){$r=FaBChoiceRefs($v,'Deck')[0]??'';if($r==='')break;$color=FaBMSTBanish($p,$uid,$r);}return $color;}
function FaBMSTPrivateColor(int $p,int $v,int $color): array {$uids=FaBDYNPrivateHand($p,$v);$refs=[];foreach($uids as $uid){$f=FaBFindUID($uid);if($f&&FaBMSTColor($v,$f['object']->CardID)===$color)$refs[]=$f['mzID'];}return ['uids'=>$uids,'refs'=>implode('&',$refs)];}
function FaBMSTAddDefense(int $p,int $v,string $r,bool $zero=false): bool {$f=FaBDYNPrivateOriginal($r);if(!$f||!in_array($f['zone'],['Temp','Hand'],true))return false;$o=FaBMoveUID(intval($f['object']->UniqueID),'CombatChain',$v);if(!$o)return false;$o->Role='DEFENSE';$o->ChainLink=intval(FaBGetState()['chainLink']);$o->FromZone=$f['zone'];if($f['zone']==='Hand'){$s=FaBGetState();$s['handBlockUIDs'][]=intval($o->UniqueID);FaBSetState($s);}if($zero&&FaBMSTColor($v,$o->CardID)===3)FaBARCSetCard(intval($o->UniqueID),'mstZeroDefense',true);OnDefended($v,FaBDTDSource(intval($o->UniqueID)),$v);FaBMONPhantasm(FaBGetState(),false);return true;}
function FaBMSTNuuPermission(int $p,int $v): void {FaBMSTAdd($p,'NUU',1,['victim'=>$v]);}
function FaBMSTStolen(int $p,array $f): bool {if($f['zone']!=='Banish'||!empty($f['object']->FaceDown))return false;$uid=intval($f['object']->UniqueID);foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='MST_NUU'&&intval($e['victim'])===$f['player']&&FaBMSTColor($f['player'],$f['object']->CardID)===3)return true;if(($e['type']??'')==='MST_GORGON'&&in_array($uid,$e['uids']??[],true))return true;}return false;}
function FaBMSTGorgon(int $p,int $uid): void {$uids=[];foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBWTRIsAttackAction($o)){$uids[]=intval($o->UniqueID);FaBDYNBanishChoice($r,$p);}}if(FaBARCCard($uid,'mstChiPitched'))FaBMSTAdd($p,'GORGON',1,['uids'=>$uids,'chain'=>true]);}
function FaBMSTCost(int $p,object $o): int {$f=FaBFindUID(intval($o->UniqueID));return (FaBARCCard(intval($o->UniqueID),'mstFree')||($f&&FaBMSTStolen($p,$f)))?-intval(CardCost($o->CardID)):(FaBWTRIsAttackAction($o)?-FaBMSTCount($p,'NEXT_DISCOUNT'):0);}
function FaBMSTPrevent(int $p,int $n,string $source): int {$f=FaBIdentityFromMZ($source);if(!$f||$n<=0)return $n;$color=FaBMSTObjectColor($f['player'],$f['object']);$left=[];$done=false;foreach(FaBWTREffects($p) as $e){if(!$done&&($e['type']??'')==='MST_PREVENT_COLOR'&&intval($e['amount'])===$color){$done=true;$n=0;}else $left[]=$e;}FaBWTRSetEffects($p,$left);return $n;}
function FaBMSTCloakedRefs(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Equipment'),fn($r)=>FaBMSTHidden(FaBIdentityFromMZ($r)['object'])));}
function FaBMSTHasWard(int $uid): bool {$f=FaBFindUID($uid);return $f&&FaBMSTWardActive($f['object']);}
function FaBMSTPeekBlue(int $p,array $uids): bool {$f=FaBFindUID(intval($uids[0]??0));return $f&&FaBMSTColor($p,$f['object']->CardID)===3;}
function FaBMSTBanishPeek(int $p,int $v,array $uids): void {foreach($uids as $uid){$f=FaBFindUID($uid);if($f&&$f['zone']==='Temp')FaBMoveUID($uid,'Banish',$v);}}
function FaBMSTBottomMany(string $refs): void {foreach(explode('&',$refs) as $r)FaBHVYBottomChoice($r);}
function FaBMSTBanishMany(int $p,string $refs): void {foreach(explode('&',$refs) as $r)FaBDYNBanishChoice($r,$p);}
function FaBMSTCounterAmount(string $r): int {$f=FaBIdentityFromMZ($r);return $f?intval(FaBObjectCounters($f['object'])['POWER']??0):0;}
function FaBMSTCounterRefsExcept(int $p,int $uid): string {return implode('&',array_filter(explode('&',FaBMSTCounterRefs($p)),fn($r)=>($f=FaBIdentityFromMZ($r))&&intval($f['object']->UniqueID)!==$uid));}
function FaBMSTTotalCounters(int $p): int {return array_sum(array_map('FaBMSTCounterAmount',explode('&',FaBMSTCounterRefs($p))));}
function FaBMSTReplaceCost(int $uid): void {$s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)===$uid){$s['pendingPayment']['cost']=max(0,intval($s['pendingPayment']['cost'])-intval(CardCost(FaBFindUID($uid)['object']->CardID)));FaBSetState($s);}}
function FaBMSTPreviousTiger(int $uid): bool {return in_array('Crouching Tiger',(array)FaBARCCard($uid,'outPreviousNames',[]),true);}
function FaBMSTLevels(int $p,int $uid,string $modes): void {foreach(explode(',',$modes) as $mode){if($mode==='0')DoDrawCard($p,1);if($mode==='1')FaBTagUID($uid,'WTR_POWER:2');if($mode==='2')FaBTagUID($uid,'GO_AGAIN');}}
function FaBMSTRemainingTemp(array $uids): array {return array_values(array_filter($uids,fn($u)=>(FaBFindUID($u)['zone']??'')==='Temp'));}
function FaBMSTOpponentGraves(int $p): string {$refs=[];foreach(FaBOpponents($p) as $v)$refs=array_merge($refs,FaBChoiceRefs($v,'Graveyard'));return implode('&',$refs);}
function FaBMSTAllWardCounters(int $p): void {foreach(explode('&',FaBMSTAuras($p,true)) as $r)FaBMSTCounters($r,1);}
function FaBMSTBlanch(int $p,int $v): void {foreach(FaBChoiceRefs($v,'Hero') as $r)FaBEVOCounter(FaBIdentityFromMZ($r)['object'],'MST_COLORLESS_UNTIL',intval(GetTurnNumber())+(intval(GetTurnPlayer())===$v?0:1));}
function FaBMSTStart(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena',['base'=>'sigil_of_solitude']) as $r)if(!HasNoAbilities(FaBIdentityFromMZ($r)['object']))FaBRunSourceMacro('StartTurn',$p,FaBIdentityFromMZ($r)['object']->CardID,['mzID'=>$r]);
 foreach(FaBChoiceRefs($p,'Equipment') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array($o->CardID,['rippling_wave','kimono_of_layered_lessons','aqua_laps','aqua_seeing_shell','waves_of_aqua_marine','heirloom_of_rabbit_hide','koi_blessed_kimono'],true))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);}
}
function FaBMSTEnd(int $p): void {
 foreach(FaBLiveSeats() as $v){foreach(FaBChoiceRefs($v,'Arena',['base'=>'mistcloak_gully']) as $r)FaBRunSourceMacro('EndTurn',$v,FaBIdentityFromMZ($r)['object']->CardID,['mzID'=>$r]);}
 foreach(FaBChoiceRefs($p,'Hero') as $r)FaBEVOCounter(FaBIdentityFromMZ($r)['object'],'MST_COLORLESS_UNTIL',0);
}
function FaBMSTHit(int $p,object $o): void {$uid=intval($o->UniqueID);if(!FaBFaiHeroHit())return;foreach((array)$o->TurnEffects as $t){if($t==='MST_BANISH_HIT')FaBMSTBanishTop($p,$uid,intval(FaBGetState()['defender']));if($t==='MST_TIGERS_HIT')FaBMSTTiger($p,false,2);}}
function FaBMSTDeclared(int $p,object $o): void {
 $uid=intval($o->UniqueID);FaBARCSetCard($uid,'mstReactions',0);FaBARCSetCard($uid,'mstBanished',[]);foreach((array)$o->TurnEffects as $t)if($t==='MST_DRAW_ATTACK')DoDrawCard($p,1);
 foreach(FaBMSTAttackVictims() as $v){if(FaBMONArena($v,'mistcloak_gully')&&!FaBMSTCount($v,'GULLY_USED')){FaBMSTAdd($v,'GULLY_USED');FaBTagUID($uid,'WTR_POWER:-1');}}
}
function FaBMSTAttune(int $p,int $uid,int $v): void {$r=FaBChoiceRefs($v,'Deck')[0]??'';if($r==='')return;FaBRevealChoices($v,$r);if(FaBMSTColor($v,FaBIdentityFromMZ($r)['object']->CardID)===3){FaBTagUID($uid,'WTR_POWER:3');FaBTagUID($uid,'WTR_DEFENSE:3');}}
function FaBMSTHiddenTargets(int $v): string {return implode('&',array_merge(array_filter(FaBChoiceRefs($v,'Arsenal'),fn($r)=>intval(FaBIdentityFromMZ($r)['object']->FaceDown??1)===1),array_filter(FaBChoiceRefs($v,'Equipment'),fn($r)=>FaBMSTHidden(FaBIdentityFromMZ($r)['object']))));}
function FaBMSTPreview(int $p,string $r): array {$f=FaBIdentityFromMZ($r);if(!$f)return [];$o=AddTemp($p,CardID:$f['object']->CardID,Owner:$p,Controller:$p);return [intval($o->UniqueID)];}
function FaBMSTNoDefense(int $p,int $v): array {$uids=FaBDYNPrivateHand($p,$v);$refs=[];foreach($uids as $uid){$f=FaBFindUID($uid);if($f&&(string)CardDefense($f['object']->CardID)==='')$refs[]=$f['mzID'];}return ['uids'=>$uids,'refs'=>implode('&',$refs)];}
function FaBMSTDiscardPrivate(int $v,string $r): bool {$f=FaBDYNPrivateOriginal($r);if(!$f||$f['zone']!=='Hand')return false;FaBDiscardChoice($v,$f['mzID']);return true;}
function FaBMSTAgonySearch(int $p,int $v,string $r): string {$f=FaBDYNPrivateOriginal($r);if(!$f)return '';$name=CardName($f['object']->CardID);$refs=[];foreach(['Hand','Deck','Graveyard'] as $z)foreach(FaBChoiceRefs($v,$z) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(CardName($o->CardID)!==$name)continue;$copy=AddTemp($p,CardID:$o->CardID,Owner:$p,Controller:$p);FaBARCSetCard(intval($copy->UniqueID),'dynOriginal',intval($o->UniqueID));$refs[]='p'.$p.'Temp-'.$copy->mzIndex;}return implode('&',$refs);}
function FaBMSTFinishAgony(int $p,int $v,string $chosen,string $previews): void {foreach(explode('&',$chosen) as $r){$f=FaBDYNPrivateOriginal($r);if($f)FaBDYNBanishChoice($f['mzID'],$p);}FaBHVYClearPreviews($previews);FaBShuffleDeck($v);}
function FaBMSTShadowCost(int $p,int $uid): void {$refs=FaBChoiceRefs($p,'Graveyard');$six=0;$uids=[];for($i=0;$i<3&&$refs;++$i){$j=EngineRandomInt(0,count($refs)-1);$f=FaBIdentityFromMZ($refs[$j]);array_splice($refs,$j,1);if(FaBMONBasePower($p,$f['object'])>=6)++$six;$uids[]=intval($f['object']->UniqueID);FaBMoveUID(end($uids),'Banish',$p);}FaBARCSetCard($uid,'mstSix',$six);FaBARCSetCard($uid,'mstShadowBanished',$uids);}
function FaBMSTShadowRefs(int $uid): string {return FaBEVRUIDRefs((array)FaBARCCard($uid,'mstShadowBanished',[]));}
function FaBMSTPlayable(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Banish')$f['object']->PlayableFromBanish=1;}
function FaBMSTMurky(int $p,string $refs): void {$uids=FaBUPRUIDs($refs);if(count($uids)!==3)return;foreach($uids as $uid){$o=FaBMoveUID($uid,'Banish',$p);if($o)$o->FaceDown=1;}if(FaBELEArsenalSpace($p)){$o=FaBMoveUID($uids[EngineRandomInt(0,2)],'Arsenal',$p);if($o)$o->FaceDown=1;}}
function FaBMSTHyperDrivers(int $p): string {return implode('&',FaBChoiceRefs($p,'Arena',['base'=>'hyper_driver']));}
function FaBMSTSupercell(int $p,int $x,string $refs): void {foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Arena'&&FaBWTRBase($f['object']->CardID)==='hyper_driver')FaBARCSteam($f['object'],$x);}$o=FaBWTRCreateArena($p,'hyper_driver');if($o){FaBEVOCounter($o,'STEAM',$x);if($x===0)AddStack(CardID:'hyper_driver',Controller:$p,Kind:'ABILITY',SourceZone:'Arena',SourceUniqueID:intval($o->UniqueID),Params:['mstEmptyDriver'=>intval($o->UniqueID)]);}}
function FaBMSTCanEquip(int $p,object $o): bool {
 if(FaBHasType($o,'Weapon')||FaBHasType($o,'Off-Hand')){$hands=FaBHasType($o,'2H')?2:1;foreach(array_merge(FaBChoiceRefs($p,'Weapons'),FaBChoiceRefs($p,'Equipment',['type'=>'Off-Hand'])) as $r){$w=FaBIdentityFromMZ($r)['object'];$hands+=FaBHasType($w,'2H')?2:1;}return $hands<=(FaBMONHero($p,'kayo')?1:2);}
 if(!FaBHasType($o,'Equipment'))return false;foreach(FaBProfessorEquipped($p) as $eq)foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBHasType($o,$slot)&&FaBHasType($eq,$slot))return false;return true;
}
function FaBMSTInventory(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Inventory'),fn($r)=>FaBMSTCanEquip($p,FaBIdentityFromMZ($r)['object'])));}
function FaBMSTEquip(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Inventory'||!FaBMSTCanEquip($p,$f['object']))return;$new=FaBMoveUID(intval($f['object']->UniqueID),FaBHasType($f['object'],'Weapon')?'Weapons':'Equipment',$p);if($new){if(FaBHasKeyword($new->CardID,'Cloaked')||FaBMONHero($p,'enigma_new_moon'))$new->FaceDown=1;FaBEVOEquipped($p,$new);}}

function FaBMSTAnyLifeLost(): bool {foreach(FaBLiveSeats() as $p)if(FaBDTDCount($p,'LOST'))return true;return false;}
function FaBMSTMechActions(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Banish',['type'=>'Action']),fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Mechanologist')));}
function FaBMSTUnpreventable(object $o): bool {return FaBWTRBase($o->CardID)==='pick_to_pieces'&&intval(FaBARCCard(intval($o->UniqueID),'mstReactions'))>=1;}
function FaBMSTFinishBlocks(): void {$s=FaBGetState();$refs=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&($o->Role??'')==='DEFENSE')$refs[]=$r;}if(count($refs)===1){$f=FaBIdentityFromMZ($refs[0]);if(FaBWTRBase($f['object']->CardID)==='battlefront_bastion')FaBWTRAddEffect($f['player'],'PREVENT_DAMAGE',1);}}
function FaBMSTAttackCounters(object $o): int {$uid=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0);$f=$uid?FaBFindUID($uid):null;return intval(FaBObjectCounters($f?$f['object']:$o)['POWER']??0);}
function FaBMSTAmp(int $p,int $uid): int {$f=FaBFindUID($uid);if(!$f||(!FaBHasType($f['object'],'Action')&&!FaBHasType($f['object'],'Instant'))||FaBHasType($f['object'],'Token'))return 0;$n=FaBMSTCount($p,'AMP');if($n)FaBMSTClear($p,'AMP');return $n;}
function FaBMSTWardActive(object $o): bool {if($o->CardID==='touch_of_reality')return !HasNoAbilities($o)&&intval(FaBARCCard(intval($o->UniqueID),'penTouchTurn',-1))===intval(GetTurnNumber());return !HasNoAbilities($o)&&(preg_match('/\bWard\b/i',(string)CardFunctional_text_plain($o->CardID))||in_array('MST_WARD3',(array)($o->TurnEffects??[]),true));}
function FaBMSTWardRefs(int $p): array {return array_values(array_filter(array_merge(FaBChoiceRefs($p,'Arena'),FaBChoiceRefs($p,'Equipment')),fn($r)=>($f=FaBIdentityFromMZ($r))&&FaBMSTWardActive($f['object'])));}
function FaBMSTBeforeClose(): void {foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain',['base'=>'eloquent_eulogy']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(($o->Role??'')==='ATTACK'&&!HasNoAbilities($o))FaBRunSourceMacro('CombatChainClosed',$p,$o->CardID,[]);}}
function FaBMSTPrepareBoost(int $p,object $o): bool {
 if(!FaBWTRIsAttackAction($o))return false;$found=false;$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='MST_NEXT'&&($e['tag']??'')==='MST_BOOST')$found=true;else $left[]=$e;}if(!$found)return false;FaBWTRSetEffects($p,$left);
 $already=FaBHasKeyword($o,'Boost');FaBWTRTag($o,'MST_BOOST');if($already||!FaBChoiceRefs($p,'Deck'))return false;
 FaBRunSourceMacro('ResolveAbility',$p,'evo_speedslip_blue',['mzID'=>FaBDTDSource(intval($o->UniqueID)),'mstEvent'=>'boost']);return true;
}
function FaBMSTContinuePrepare(int $p,int $uid): void {$f=FaBFindUID($uid);if(!$f)return;if(!FaBRunSourceMacro('PrepareCard',$p,$f['object']->CardID,['mzID'=>$f['mzID']]))FaBFinishPreparedCard($uid);}
function FaBMSTCanPlay(int $p,object $o): bool {
 $b=FaBWTRBase($o->CardID);if(in_array($b,['a_drop_in_the_ocean','path_well_traveled','the_grain_that_tips_the_scale'],true)&&FaBMSTAttackRefs($p,'',false)==='')return false;if($b==='astral_etchings'&&FaBMSTAuras($p,true)==='')return false;
 $kind=match($b){'fang_strike','slither'=>'aa','hiss','venomous_bite','tide_chakra','intimate_inducement'=>'hybrid','wide_blue_yonder'=>'',default=>null};
 if($kind!==null&&FaBMSTAttackRefs($p,$kind)==='')return false;
 if($b==='just_a_nick'&&FaBMSTAttackRefs($p,'small')===''&&FaBMSTAttackRefs($p,'stealth')==='')return false;
 if($b==='maul'&&FaBMSTAttackRefs($p,'small')===''&&FaBMSTAttackRefs($p,'tiger')==='')return false;
 return true;
}
function FaBMSTAttackVictims(): array {$s=FaBGetState();$targets=$s['attackTargets']??[];if(!$targets)$targets=[$s['attackTarget']??['type'=>'HERO','player'=>intval($s['defender'])]];$out=[];foreach($targets as $t)if(($t['type']??'HERO')==='HERO'&&intval($t['player']??0)>0)$out[]=intval($t['player']);return array_values(array_unique($out));}
function FaBMSTObjectColor(int $p,object $o): int {return intval(FaBARCCard(intval($o->UniqueID??0),'penColor',FaBMSTColor(intval($o->Owner??0)?:$p,$o->CardID)));}
function FaBMSTEnigma(int $p): bool {foreach(FaBChoiceRefs($p,'Hero') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array($o->CardID,['enigma','enigma_ledger_of_ancestry'],true)&&!HasNoAbilities($o))return true;}return false;}
function FaBMSTShieldDiscount(int $p,object $o): int {return $o->CardID==='spectral_shield'&&FaBMSTEnigma($p)&&!FaBMSTCount($p,'SHIELD_ATTACK')?1:0;}
