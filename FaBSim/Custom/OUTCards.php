<?php
// Outsiders card-local helpers; continuations live in the CardEditor snapshot.
function FaBOUTAbilityRows(): array {
 $rows=[
 'uzuri'=>[['REACTION',0,false,false,true,0,'Replace stealth attack']],
 'uzuri_switchblade'=>[['REACTION',0,false,false,true,0,'Replace stealth attack']],
 'barbed_castaway'=>[['INSTANT',1,false,false,true,0,'Load arrow face up'],['INSTANT',1,false,false,true,0,'Turn arrow face up and aim']],
 'blade_cuff'=>[['ACTION',2,true,true,false,0,'Empower daggers']],
 'flick_knives'=>[['REACTION',0,false,false,true,0,'Throw a dagger']],
 'fisticuffs'=>[['REACTION',2,true,false,false,0,'Empower attack action']],
 'fleet_foot_sandals'=>[['REACTION',0,true,false,false,0,'Give small attack go again']],
 'mask_of_malicious_manifestations'=>[['ACTION',1,true,true,false,0,'Find an attack action']],
 'mask_of_many_faces'=>[['INSTANT',1,true,false,false,0,'Name next attack']],
 'mask_of_shifting_perspectives'=>[['REACTION',0,true,false,false,0,'Cycle cards on dagger hits']],
 'driftwood_quiver'=>[['INSTANT',0,true,false,false,0,'Bottom arsenal card']],
 'quiver_of_abyssal_depths'=>[['INSTANT',3,true,false,false,0,'Shuffle arrows into deck']],
 'quiver_of_rustling_leaves'=>[['INSTANT',3,false,false,false,0,'Reveal and load top arrow']],
 'redback_shroud'=>[['REACTION',0,true,false,false,0,'Reduce next reaction cost']],
 'silken_gi'=>[['INSTANT',0,true,false,false,0,'Discount and weaken next attack']],
 'threadbare_tunic'=>[['INSTANT',0,true,false,false,0,'Gain one resource']],
 'toxic_tips'=>[['ACTION',1,true,true,false,0,'Add disease on hit']],
 'trench_of_sunken_treasure'=>[['INSTANT',0,false,false,true,0,'Bottom face-down arsenal for resource']],
 'vambrace_of_determination'=>[['REACTION',1,false,false,true,0,'Weaken next prevention']],
 ];
 foreach(['seekers_hood','seekers_gilet','seekers_mitts','seekers_leggings'] as $id)$rows[$id]=[['INSTANT',1,true,false,false,0,'Prevent one and opt']];
 foreach(['red','yellow','blue'] as $color)$rows['silverwind_shuriken_'.$color]=[['REACTION',0,true,false,false,0,'Empower combo attack']];return $rows;
}
function FaBOUTArsenal(int $p,bool $arrow=false,bool $down=false): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal'),function($r)use($arrow,$down){$o=FaBIdentityFromMZ($r)['object'];return (!$arrow||FaBHasType($o,'Arrow'))&&(!$down||intval($o->FaceDown));}));}
function FaBOUTAbilityLegal(int $p,array $f,array $spec): bool {
 $id=$f['object']->CardID;$b=FaBWTRBase($id);
 if(in_array($id,['uzuri','uzuri_switchblade'],true))return FaBOUTTargets($p,'stealth')!==''&&FaBHandCount($p)>0;
 if($id==='flick_knives')return FaBArakniDaggers($p)!=='';
 if($id==='threadbare_tunic')return FaBHandCount($p)===0;
 if($id==='mask_of_malicious_manifestations')return FaBHandCount($p)>0||FaBOUTArsenal($p)!=='';
 if($id==='trench_of_sunken_treasure')return FaBOUTArsenal($p,false,true)!=='';
 if($id==='driftwood_quiver')return FaBOUTArsenal($p)!=='';
 if($id==='barbed_castaway'&&intval($spec['index'])===0)return FaBELEArsenalSpace($p)&&count(FaBChoiceRefs($p,'Hand',['type'=>'Arrow']))>0;
 if($id==='barbed_castaway'&&intval($spec['index'])===1)return FaBOUTArsenal($p,true,true)!=='';
 if($id==='fisticuffs')return FaBOUTTargets($p,'aa')!=='';
 if($id==='fleet_foot_sandals')return FaBOUTTargets($p,'tiny')!=='';
 if($b==='silverwind_shuriken')return FaBOUTTargets($p,'combo')!=='';
 return true;
}
function FaBOUTPrepareAbility(int $p,object $stack): bool {
 if(!in_array($stack->CardID,['uzuri','uzuri_switchblade','trench_of_sunken_treasure','mask_of_malicious_manifestations'],true))return false;
 return FaBRunSourceMacro('PrepareCard',$p,$stack->CardID,['mzID'=>FaBOUTSource(intval($stack->UniqueID))])>0;
}
function FaBOUTBanishCost(int $p,int $uid,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Hand')return;$cardUID=intval($f['object']->UniqueID);$o=FaBMoveUID($cardUID,'Banish',$p);$o->FaceDown=1;FaBARCSetCard($uid,'outSwapUID',$cardUID);FaBARCSetCard($uid,'outSwapTarget',intval(FaBGetState()['attackUID']));}
function FaBOUTSwap(int $p,int $uid): void {
 $newUID=intval(FaBARCCard($uid,'outSwapUID'));$f=FaBFindUID($newUID);if(!$f||$f['zone']!=='Banish')return;$f['object']->FaceDown=0;FaBRevealChoices($p,$f['mzID']);
 $s=FaBGetState();$old=FaBFindUID(intval(FaBARCCard($uid,'outSwapTarget')));
 if(!$old||intval($s['attackUID'])!==intval($old['object']->UniqueID)||$old['zone']!=='CombatChain'||!FaBHasKeyword($old['object'],'Stealth')||!FaBWTRIsAttackAction($f['object'])||intval(CardCost($f['object']->CardID))>2)return;
 FaBARCToDeck(intval($old['object']->Owner?:$p),intval($old['object']->UniqueID),false);
 $o=FaBMoveUID($newUID,'CombatChain',$p);$o->Controller=$p;$o->Role='ATTACK';$o->FromZone='Banish';$o->ChainLink=intval($s['chainLink']);$o->TurnEffects=[];
 $s=FaBGetState();$s['attackUID']=$newUID;$s['lastAttackCardID']=$o->CardID;$s['attackGoAgain']=false;FaBSetState($s);
}
function FaBOUTBottomCost(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&in_array($f['zone'],['Hand','Arsenal'],true))FaBARCToDeck($p,intval($f['object']->UniqueID),false);}
function FaBOUTRevealUntil(int $p,bool $hand,int $attackUID=0): void {
 $found=0;foreach(FaBChoiceRefs($p,'Deck') as $r){FaBRevealChoices($p,$r);$f=FaBIdentityFromMZ($r);if(FaBWTRIsAttackAction($f['object'])){$found=intval($f['object']->UniqueID);break;}}
 $power=$found?intval(CardPower(FaBFindUID($found)['object']->CardID)):7;if($found)FaBMoveUID($found,$hand?'Hand':'Banish',$p);if(!$hand)FaBTagUID($attackUID,'WTR_POWER:'.(-$power));FaBShuffleDeck($p);
}
function FaBOUTAim(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Arsenal'||!FaBHasType($f['object'],'Arrow')||!intval($f['object']->FaceDown))return;$f['object']->FaceDown=0;FaBSetObjectCounter($f['object'],'AIM',intval(FaBObjectCounters($f['object'])['AIM']??0)+1);FaBARCArsenalFaceUp($f['player'],$f['object']);}
function FaBOUTFaceUp(int $p,object $o,string $from): void {
 if(FaBWTRBase($o->CardID)==='spire_sniping')FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
 if(in_array($from,['Deck','Temp'],true)&&FaBHasType($o,'Arrow')&&FaBCRUEquipment($p,'crows_nest'))FaBRunSourceMacro('ResolveAbility',$p,'crows_nest',['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
}
function FaBOUTRustling(int $p,int $sourceUID): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';FaBRevealChoices($p,$r);$f=FaBIdentityFromMZ($r);if($f&&FaBHasType($f['object'],'Arrow')&&FaBARCLoadArsenal($p,$r,true))FaBMONDestroy($sourceUID);}
function FaBOUTThrow(int $p,string $dagger,string $target): void {
 $f=FaBIdentityFromMZ($dagger);$hero=FaBIdentityFromMZ($target);if(!$f||!$hero||$hero['zone']!=='Hero'||!FaBSeatIsLive($hero['player'])||!in_array($dagger,explode('&',FaBArakniDaggers($p)),true))return;
 $uid=intval($f['object']->UniqueID);$victim=$hero['player'];$n=DoDamage($p,$dagger,$victim,1,'PHYSICAL');
 if($n>0){FaBOUTDaggerHit($p,$f['object'],$victim,$n);if($f['object']->CardID==='spiders_bite')FaBDYNAdd($victim,'SPIDER');}
 FaBMONDestroy($uid);
}
function FaBOUTHandAndArsenal(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Hand'),FaBChoiceRefs($p,'Arsenal')));}
function FaBOUTInertiaStage(int $p): array {$uids=FaBUPRUIDs(FaBOUTHandAndArsenal($p));foreach($uids as $uid)FaBMoveUID($uid,'Temp',$p,false);return $uids;}
function FaBOUTCodexLoad(int $p,string $r,string $zone): bool {
 $f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!==$zone)return false;
 if($zone==='Graveyard'&&!FaBWTRIsAttackAction($f['object']))return false;
 // Effects can put cards into occupied arsenals; the one-card limit applies to normal loading.
 $o=FaBMoveUID(intval($f['object']->UniqueID),'Arsenal',$p);if(!$o)return false;$o->FaceDown=1;return true;
}
function FaBOUTQuiverTargets(int $p,array $names): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['type'=>'Arrow']),fn($r)=>!array_intersect(FaBOUTNames(FaBIdentityFromMZ($r)['object']),$names)));}
function FaBOUTQuiverShuffle(int $p,array $uids): void {foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['player']===$p&&$f['zone']==='Graveyard')FaBMoveUID(intval($uid),'Deck',$p);}FaBShuffleDeck($p);}
function FaBOUTSearchName(int $p,array $names): string {$refs=implode('&',array_filter(FaBChoiceRefs($p,'Deck'),fn($r)=>array_intersect(FaBOUTNames(FaBIdentityFromMZ($r)['object']),$names)));$uids=FaBARCStageRefs($p,$refs);return FaBEVRUIDRefs($uids);}
function FaBOUTBondsBanish(int $p,string $r): array {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Graveyard'||!FaBHasKeyword($f['object'],'Combo'))return [];$names=FaBOUTNames($f['object']);FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);return $names;}
function FaBOUTBondsPlay(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Temp')return;$uid=intval($f['object']->UniqueID);FaBGrantBanishPlay($p,$r);FaBARCSetCard($uid,'outChainPlay',true);}
function FaBOUTDishonor(int $p,int $victim): void {foreach(['Surging Strike','Descendent Gustwave','Bonds of Ancestry'] as $name){$ok=false;foreach(FaBChoiceRefs($p,'CombatChain') as $r)if(FaBOUTNamed(FaBIdentityFromMZ($r)['object'],$name))$ok=true;if(!$ok)return;}foreach(GetHero($victim) as $o)if(empty($o->removed)){$c=FaBObjectCounters($o);$c['_overrides']['NO_ABILITIES']=true;$o->Counters=$c;}}
function FaBOUTHumble(int $p,int $victim): void {FaBWTRAddEffect($victim,'NO_HERO_ABILITY',1,['expiresAfterTurnOf'=>$victim]);}
function FaBOUTGiveTargets(int $p,int $attackUID): string {$s=FaBGetState();if(intval($s['attackUID'])!==$attackUID)return '';$power=FaBAttackPower($s);return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['type'=>'Action']),fn($r)=>is_numeric(CardCost(FaBIdentityFromMZ($r)['object']->CardID))&&intval(CardCost(FaBIdentityFromMZ($r)['object']->CardID))<$power));}
function FaBOUTTopChoice(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p)FaBARCToDeck($p,intval($f['object']->UniqueID),true);}
function FaBOUTDefended(int $p,object $o): void {
 if(in_array(FaBWTRBase($o->CardID),['pitfall_trap','rockslide_trap','tripwire_trap'],true))FaBOUTTrapTriggered($p);
 if(($o->Role??'')==='DEFENSE_REACTION')FaBOUTDefendGroup($p,[intval($o->UniqueID)]);
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&FaBWTRBase($a['object']->CardID)==='give_and_take'&&FaBHasType($o,'Action'))FaBRunSourceMacro('ResolveAbility',intval($s['attacker']),$a['object']->CardID,['mzID'=>$a['mzID']]);
}
function FaBOUTCyclone(): void {$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||FaBWTRBase($a['object']->CardID)!=='cyclone_roundhouse'||!FaBOUTCombo(intval($s['attacker']),'cyclone_roundhouse',$a['object']))return;$groups=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$groups[intval($o->ChainLink)][]=intval($o->UniqueID);}foreach($groups as $uids)FaBMoveUID($uids[EngineRandomInt(0,count($uids)-1)],'Banish');}
function FaBOUTMeltingTargets(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Weapons',['type'=>'1H']),fn($r)=>intval(CardPower(FaBIdentityFromMZ($r)['object']->CardID))===1));}
function FaBOUTScrapTargets(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard'),fn($r)=>is_numeric(CardPower(FaBIdentityFromMZ($r)['object']->CardID))&&intval(CardPower(FaBIdentityFromMZ($r)['object']->CardID))===1));}
function FaBOUTScrap(int $p,int $uid,string $r): void {if(in_array($r,explode('&',FaBOUTScrapTargets($p)),true)&&FaBMoveChoice($p,$r,'Graveyard','Banish')){FaBTagUID($uid,'WTR_POWER:1');FaBTagUID($uid,'GO_AGAIN');}}
function FaBOUTCodexTokens(int $p,string $token): void {FaBOUTToken($p,'ponder');foreach(FaBOpponents($p) as $victim)FaBOUTToken($victim,$token);}
function FaBOUTInfectious(int $p,int $victim): void {foreach(['frailty','inertia','bloodrot_pox'] as $token)if(FaBMONArena($p,$token))FaBOUTToken($victim,$token);}
function FaBOUTFlip(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Arsenal'){$f['object']->FaceDown=0;FaBARCArsenalFaceUp($f['player'],$f['object']);}}
function FaBOUTPitchRefs(array $uids,int $pitch): string {return implode('&',array_filter(explode('&',FaBEVRUIDRefs($uids)),fn($r)=>intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===$pitch));}
function FaBOUTRevealUIDs(int $p,array $uids): void {FaBRevealChoices($p,FaBEVRUIDRefs($uids));}
function FaBOUTHeadName(int $p,string $r,string $name): void {$f=FaBIdentityFromMZ($r);$name=str_replace('_',' ',$name);if($f&&!FaBOUTNamed($f['object'],$name))FaBOUTAdd($p,'HEAD_NAME',1,['name'=>$name,'chain'=>true]);}
function FaBOUTWaterName(int $uid,string $choice): void {FaBOUTNameCard($uid,['Head Jab','Surging Strike','Twin Twisters'][intval($choice)]??'Head Jab');}
function FaBOUTDojoTargets(int $p,bool $surging): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard'),fn($r)=>$surging?FaBOUTNamed(FaBIdentityFromMZ($r)['object'],'Surging Strike'):FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Combo')));}
function FaBOUTStageDojo(int $p,string $r,array $uids): array {$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Graveyard'){$uids[]=intval($f['object']->UniqueID);FaBMoveUID(intval($f['object']->UniqueID),'Temp',$p);}return $uids;}
function FaBOUTInventoryDaggers(int $p): string {return FaBArakniWeaponSpace($p)?implode('&',FaBChoiceRefs($p,'Inventory',['type'=>'Dagger'])):'';}
function FaBOUTEquipDagger(int $p,string $r): void {if(in_array($r,explode('&',FaBOUTInventoryDaggers($p)),true))FaBMoveChoice($p,$r,'Inventory','Weapons');}
function FaBOUTInfiltrate(int $p,int $victim): void {
 $r=FaBChoiceRefs($victim,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$victim);$o->Owner=$victim;
 $s=FaBGetState();$s['outInfiltrate'][intval($o->UniqueID)]=['player'=>$p,'turn'=>intval(GetTurnNumber())];FaBSetState($s);
}
function FaBOUTCanPlayStolen(int $p,array $f): bool {return $f['zone']==='Banish'&&empty($f['object']->FaceDown)&&intval(FaBGetState()['outInfiltrate'][intval($f['object']->UniqueID)]['player']??0)===$p;}
function FaBOUTEndPermissions(int $p): void {$s=FaBGetState();$s['outInfiltrate']=array_filter($s['outInfiltrate']??[],fn($e)=>intval($e['player'])!==$p||intval($e['turn'])>=intval(GetTurnNumber()));FaBSetState($s);}
function FaBOUTEndTargets(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena'),fn($r)=>in_array(FaBIdentityFromMZ($r)['object']->CardID,['bloodrot_pox','frailty','inertia','ponder'],true)));}
function FaBOUTHasDiseases(int $p): bool {foreach(['bloodrot_pox','frailty','inertia'] as $id)if(FaBMONArena($p,$id))return true;return false;}
function FaBOUTPrevent(int $p,int $amount,string $type): int {
 $left=[];foreach(FaBWTREffects($p) as $e){$kind=$e['type']??'';if($amount>0&&(($kind==='OUT_BRUSH'&&$amount<=intval($e['amount']))||($kind==='OUT_PEACE'&&$type==='PHYSICAL'))){$amount=$kind==='OUT_BRUSH'?0:max(0,$amount-intval($e['amount']));}else $left[]=$e;}FaBWTRSetEffects($p,$left);return $amount;
}
function FaBOUTVambrace(int $before,int $after,string $type): int {if($type!=='PHYSICAL'||$after>=$before)return $after;foreach(FaBLiveSeats() as $p){$n=FaBOUTCount($p,'VAMBRACE');if($n){$after=min($before,$after+$n);FaBOUTClear($p,'VAMBRACE');}}return $after;}
function FaBOUTSuppressHit(object $o): void {if(!FaBWTRIsAttackAction($o))return;foreach(FaBLiveSeats() as $p)if(FaBOUTCount($p,'TARPIT')){FaBWTRTag($o,'CRU_NO_HIT');FaBOUTClear($p,'TARPIT');}}
