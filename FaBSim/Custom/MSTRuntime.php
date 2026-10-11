<?php
function FaBMSTAbilityRows(): array {
 $r=[];foreach(['enigma','enigma_ledger_of_ancestry','zen','zen_tamer_of_purpose'] as $id)$r[$id]=[['INSTANT',3,false,false,true,0,'Use Chi ability']];
 foreach(['nuu','nuu_alluring_desire','enigma_new_moon','meridian_pathway'] as $id)$r[$id]=[['INSTANT',3,false,false,false,0,'Use Chi ability']];
 $r['mask_of_recurring_nightmares']=[['REACTION',3,false,false,true,0,'Defending hero banishes from hand']];
 $r['twelve_petal_kasaya']=[['INSTANT',3,true,false,false,0,'Create Zen State']];
 foreach(['aqua_laps','waves_of_aqua_marine'] as $id)$r[$id]=[['REACTION',1,false,false,false,0,'Turn face up']];
 foreach(['aqua_seeing_shell'=>3,'truths_retold'=>1,'uphold_tradition'=>1] as $id=>$cost)$r[$id]=[['INSTANT',$cost,false,false,false,0,'Turn face up']];
 foreach(['skybody_keikoi','skycrest_keikoi','skyhold_keikoi','skywalker_keikoi'] as $id)$r[$id]=[['INSTANT',0,true,false,false,0,'Destroy face-down equipment to prevent damage']];
 foreach(['arousing_wave','undertow_stilettos'] as $id)$r[$id]=[['REACTION',1,true,false,false,0,'Create attack reaction in hand']];
 $r['longdraw_half_glove']=[['INSTANT',0,true,false,false,0,'Bottom two cards to empower arrow']];
 $r['restless_coalescence_yellow']=[['INSTANT',0,false,false,true,0,'Remove power counter to create shield']];return $r;
}
function FaBMSTAbilityLegal(int $p,array $f): bool {
 $id=$f['object']->CardID;
 if(in_array($id,['aqua_laps','aqua_seeing_shell','waves_of_aqua_marine','truths_retold','uphold_tradition','skybody_keikoi','skycrest_keikoi','skyhold_keikoi','skywalker_keikoi'],true)&&!FaBMSTHidden($f['object']))return false;
 if($id==='longdraw_half_glove'&&count(array_filter(explode('&',FaBHVYHandArsenal($p))))<2)return false;
 if($id==='restless_coalescence_yellow'&&intval(FaBObjectCounters($f['object'])['POWER']??0)<1)return false;
 return true;
}
function FaBMSTPaid(int $p,object $o): void {
 if(in_array($o->CardID,['aqua_laps','aqua_seeing_shell','waves_of_aqua_marine','truths_retold','uphold_tradition'],true))FaBMSTFlip(intval($o->UniqueID));
 if($o->CardID==='restless_coalescence_yellow')FaBMSTCounters(FaBDTDSource(intval($o->UniqueID)),-1);
}
function FaBMSTPlayed(int $p,object $o,string $from): void {
 if(FaBHasType($o,'Aura')&&intval(CardCost($o->CardID))<=2&&FaBMSTCount($p,'VENGEFUL')){FaBMSTClear($p,'VENGEFUL');FaBARCSetCard(intval($o->UniqueID),'mstVengeful',true);}
 if(FaBWTRIsAttackAction($o))FaBMSTClear($p,'NEXT_DISCOUNT');
 $uid=intval($o->UniqueID);FaBARCSetCard($uid,'mstFree',false);$spell=$from!=='Weapons'&&empty(FaBObjectCounters($o)['MON_ARENA_ATTACK']);if($spell&&(FaBMSTObjectColor($p,$o)===3&&FaBHasType($o,'Action')||FaBMSTObjectColor($p,$o)===3&&FaBHasType($o,'Instant')||FaBMSTObjectColor($p,$o)===3&&FaBHasType($o,'Attack Reaction')||FaBMSTObjectColor($p,$o)===3&&FaBHasType($o,'Defense Reaction'))){FaBMSTAdd($p,'BLUE_PLAYED');FaBARCSetCard($uid,'mstBluePlayedTurn',intval(GetTurnNumber()));FaBARCSetCard($uid,'mstBluePlayedPlayer',$p);}
 if(FaBHasType($o,'Attack Reaction'))FaBMSTReaction($p);
 $s=FaBGetState();if(FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)){
  FaBARCSetCard($uid,'mstPreviousPitch',intval($s['mstPreviousPitch']??0));FaBARCSetCard($uid,'mstPreviousAA',!empty($s['mstPreviousAA']));
  $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')!=='MST_NEXT'){$left[]=$e;continue;}$kind=$e['kind']??'';$match=match($kind){'blue'=>FaBMSTObjectColor($p,$o)===3,'tiger'=>$o->CardID==='crouching_tiger','small'=>FaBWTRIsAttackAction($o)&&FaBMONBasePower($p,$o)<=1,'arrow'=>FaBHasType($o,'Arrow'),'aa'=>FaBWTRIsAttackAction($o),'red'=>FaBMSTObjectColor($p,$o)===1,'yellow'=>FaBMSTObjectColor($p,$o)===2,default=>true};if(!$match){$left[]=$e;continue;}if($e['amount'])FaBWTRTag($o,'WTR_POWER:'.$e['amount']);if(!empty($e['tag']))FaBWTRTag($o,$e['tag']);}FaBWTRSetEffects($p,$left);
  if($o->CardID==='crouching_tiger'&&FaBMSTCount($p,'NAME_TIGERS'))FaBRunSourceMacro('ResolveAbility',$p,'shifting_winds_of_the_mystic_beast_blue',['mzID'=>FaBDTDSource($uid)]);
 }
 if(FaBHasType($o,'Action')&&FaBMSTObjectColor($p,$o)===3&&FaBMSTCount($p,'BLUE_GO')){FaBMSTClear($p,'BLUE_GO');FaBWTRTag($o,'GO_AGAIN');}
}
function FaBMSTReaction(int $p): void {$uid=intval(FaBGetState()['attackUID']);FaBARCSetCard($uid,'mstReactions',intval(FaBARCCard($uid,'mstReactions'))+1);}
function FaBMSTNext(int $p,int $n,string $kind,string $tag='',bool $chain=false): void {FaBMSTAdd($p,'NEXT',$n,['kind'=>$kind,'tag'=>$tag,'chain'=>$chain]);}
function FaBMSTPower(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$n=0;
 if($b==='deep_blue_sea')$n+=FaBMSTBlue($p);
 if(in_array($b,['droplet','rising_tide','spillover','tidal_surge'],true)&&FaBMSTOtherBlue($p,$uid))$n+=2;
 if($b==='second_tenet_of_chi_tide'&&FaBMSTCount($p,'TRANSCENDED'))$n+=2;
 if($b==='rowdy_locals'&&FaBMSTActionDefends())$n+=2;
 $reactions=intval(FaBARCCard($uid,'mstReactions'));if($b==='bonds_of_agony'&&$reactions>=3)$n+=3;if($b==='double_trouble'&&$reactions>=2)$n+=2;if($b==='pick_to_pieces'&&$reactions>=1)++$n;
 if($o->CardID==='crouching_tiger')$n+=FaBMSTCount($p,'TIGER_POWER');
 foreach(FaBOpponents($p) as $v)$n-=FaBMSTCount($v,'STONEWALL');
 foreach(FaBMSTAttackVictims() as $v)$n-=FaBMSTCount($v,'DENSE_MIST');
 if($b==='cosmic_awakening'){$count=intval(FaBARCCard($uid,'mstChiPitched'));if($count)$n+=(min(3,$count)*5+5)-intval(CardPower($o->CardID));}
 return $n;
}
function FaBMSTActionDefends(): bool {$s=FaBGetState();foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBHasType($o,'Action'))return true;}return false;}
function FaBMSTDefense(int $p,object $o): int {$b=FaBWTRBase($o->CardID);$n=0;if($b==='big_blue_sky')$n+=FaBMSTBlue($p);if($b==='territorial_domain'&&FaBMSTCount($p,'TIGER_CREATED'))$n+=3;if($b==='wash_away'&&FaBMSTOtherBlue($p,intval($o->UniqueID)))$n+=2;if(FaBARCCard(intval($o->UniqueID),'mstZeroDefense'))$n-=intval(CardDefense($o->CardID));return $n;}
function FaBMSTGoAgain(int $p,object $o): bool {if(in_array($o->CardID,['beckoning_mistblade','tiger_taming_khakkara'],true))return true;if(FaBWTRBase($o->CardID)==='second_tenet_of_chi_wind'&&FaBMSTCount($p,'TRANSCENDED'))return true;return FaBHasType($o,'Aura')&&FaBMONWeapon($p,'cosmo_scroll_of_ancestral_tapestry')&&FaBMSTAttackCounters($o)>0;}
function FaBMSTAsInstant(int $p,object $o): bool {return (FaBHasType($o,'Aura')&&FaBMSTCount($p,'AURA_INSTANT'))||(FaBWTRBase($o->CardID)==='astral_etchings'&&count(FaBMONArena($p,'spectral_shield'))>0)||(FaBHasType($o,'Aura')&&intval(CardCost($o->CardID))<=2&&FaBMSTCount($p,'VENGEFUL'));}
function FaBMSTCosmo(int $p,object $o): ?array {if(intval(GetTurnPlayer())!==$p||!FaBHasType($o,'Aura')||!FaBMONWeapon($p,'cosmo_scroll_of_ancestral_tapestry'))return null;$ward=FaBMSTWard($p,$o);if(!FaBMSTWardActive($o))return null;return ['cost'=>($o->CardID==='spectral_shield'&&FaBMSTEnigma($p)&&!FaBMSTCount($p,'SHIELD_ATTACK'))?0:1,'power'=>$ward,'iris'=>false,'aura'=>true];}
function FaBMSTChainResolved(int $p,object $o): void {
 $s=FaBGetState();$s['mstPreviousPitch']=FaBMSTObjectColor($p,$o);$s['mstPreviousAA']=FaBWTRIsAttackAction($o);FaBSetState($s);
 if(FaBMONHero($p,'nuu')&&FaBHasKeyword($o,'Stealth'))foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'CombatChain') as $r){$d=FaBIdentityFromMZ($r)['object'];if(intval($d->ChainLink)===intval($s['chainLink'])&&in_array($d->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBHasType($d,'Action'))FaBDYNBanishChoice($r,$p);}
 if(FaBWTRBase($o->CardID)==='second_tenet_of_chi_moon'&&FaBMSTCount($p,'TRANSCENDED'))DoDrawCard($p,1);
}
function FaBMSTDefended(int $p,object $o): void {if(FaBMSTObjectColor($p,$o)===3)FaBMSTAdd($p,'BLUE_DEFENDED');}
function FaBMSTClose(): void {$s=FaBGetState();unset($s['mstPreviousAA'],$s['mstPreviousPitch']);FaBSetState($s);foreach(FaBLiveSeats() as $p)FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!(str_starts_with($e['type']??'','MST_')&&(!empty($e['chain'])||($e['type']??'')==='MST_STONEWALL')))));}
