<?php
// Everfest's shared rules. Interactive decisions are authored as await macros.
function FaBEVRCount(int $p,string $key): int {return FaBARCEffect($p,'EVR_'.$key);}
function FaBEVRAdd(int $p,string $key,int $n=1): void {FaBWTRAddEffect($p,'EVR_'.$key,$n);}
function FaBEVRClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='EVR_'.$key)));}
function FaBEVRNext(int $p,string $kind,int $n): void {FaBEVRAdd($p,'NEXT_'.$kind,$n);}
function FaBEVRCreate(int $p,string $id,int $n): void {if(FaBSeatIsLive($p))for($i=0;$i<$n;++$i)FaBWTRCreateArena($p,$id);}
function FaBEVRRoll(int $p): int {
    $roll=EngineRandomInt(1,6);if(FaBEVRCount($p,'READY'))$roll=max($roll,EngineRandomInt(1,6));
    if($roll===6)FaBDTDAdd($p,'ROLL_SIX');
    if($roll>=4)FaBEVRAdd($p,'HIGH_ROLL');
    foreach(FaBCRUEquipment($p,'skull_crushers') as $r){if($roll===1)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));elseif($roll>=5)FaBEVRAdd($p,'CRUSHERS');}
    return $roll;
}
function FaBEVRTargets(int $p,string $kind,int $target=0): string {
    $out=[];
    foreach(FaBLiveSeats() as $seat){
        if($kind==='Echoes'){$names=[];foreach(FaBGetState()['cardsPlayedThisTurn'][(string)$seat]??[] as $id)$names[CardName($id)]=($names[CardName($id)]??0)+1;if($names&&max($names)>=2)$out=array_merge($out,FaBChoiceRefs($seat,'Hero'));continue;}
        if($target&&$target!==$seat)continue;
        $zones=in_array($kind,['WeaponAttack','1HAttack'],true)?['CombatChain','Stack']:($kind==='Bow'?['Weapons']:['Arena']);
        foreach($zones as $zone){if($zone==='Stack'&&$seat!==FaBLiveSeats()[0])continue;foreach(FaBChoiceRefs($seat,$zone) as $r){$f=FaBIdentityFromMZ($r);if(!$f)continue;$o=$f['object'];
            if($kind==='Bow'&&$seat===$p&&FaBHasType($o,'Bow'))$out[]=$r;
            if($kind==='Aura'&&FaBHasType($o,'Aura'))$out[]=$r;
            if($kind==='Item'&&FaBHasType($o,'Item')&&intval(CardCost($o->CardID))<=2)$out[]=$r;
            if(in_array($kind,['WeaponAttack','1HAttack'],true)&&FaBWTRIsWeapon($o)&&(($o->Role??'')==='ATTACK'||($o->Kind??'')==='ATTACK')&&($kind==='WeaponAttack'||FaBHasType($o,'1H')))$out[]=$r;
        }}
    }return implode('&',array_unique($out));
}
function FaBEVRBattering(int $p): void {$n=0;foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(!FaBHasType($o,'Action')){FaBDiscardChoice($p,$r);++$n;}}FaBARCLoseLife($p,$n,$p);}
function FaBEVRSmallChain(int $p,int $exclude): int {$n=0;foreach(FaBChoiceRefs($p,'CombatChain',['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->UniqueID)!==$exclude&&intval(CardPower($o->CardID))<=2)++$n;}return $n;}
function FaBEVRReturnWinds(int $p): void {foreach(FaBChoiceRefs($p,'CombatChain',['base'=>'hundred_winds']) as $r)FaBARCToDeck($p,intval(FaBIdentityFromMZ($r)['object']->UniqueID),false);FaBShuffleDeck($p);}
function FaBEVRBanishTop(int $p,string $duration): void {
    $r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);if(!$o)return;
    $o->PlayableFromBanish=1;if($duration==='CHAIN')FaBARCSetCard(intval($o->UniqueID),'untilChainCloses',true);
    if($duration==='NEXT_TURN')FaBSetObjectCounter($o,'EVR_PLAY_UNTIL',intval(GetTurnNumber()));
    if($duration==='AA'&&!FaBWTRIsAttackAction($o))$o->PlayableFromBanish=0;
}
function FaBEVRShuffleHandArsenal(int $p): int {$n=0;foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){FaBARCToDeck($p,intval(FaBIdentityFromMZ($r)['object']->UniqueID),false);++$n;}FaBShuffleDeck($p);return $n;}
function FaBEVRExtraBow(string $r,int $n): void {$f=FaBIdentityFromMZ($r);if($f){FaBSetObjectCounter($f['object'],'EVR_BOW_TURN',intval(GetTurnNumber()));FaBSetObjectCounter($f['object'],'EVR_BOW_USES',intval(FaBObjectCounters($f['object'])['EVR_BOW_USES']??0)+$n);}}
function FaBEVRRound(int $p): void {foreach(FaBLiveSeats() as $seat)FaBWTRAddEffect($seat,'EVR_ROUND',1,['target'=>$p,'expiresAtStartOf'=>$p]);}
function FaBEVRWildfire(int $n): void {foreach(FaBLiveSeats() as $p)FaBEVRAdd($p,'WILDFIRE',$n);}
function FaBEVRArcaneBonus(int $p,string $r,int $n,string $type): int {if($type==='ARCANE'&&FaBSEAArcaneCapped(intval(FaBIdentityFromMZ($r)['object']->UniqueID??0)))return $n;return FaBELEDamageBonus($p,$r,$n+intval(FaBARCCard(intval(FaBIdentityFromMZ($r)['object']->UniqueID??0),'arcaneBonus')),$type);}
function FaBEVRCanPlay(int $p,array $f): bool {
    $o=$f['object'];$b=FaBWTRBase($o->CardID);
    if(FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o)&&count(FaBARCPlayed($p,true)))foreach(FaBLiveSeats() as $seat)if(FaBMONArena($seat,'signal_jammer'))return false;
    if($b==='even_bigger_than_that'&&!FaBEVRCount($p,'PHYSICAL'))return false;
    if($b==='in_the_swing'&&FaBCRUCount($p,'WEAPONS')<2)return false;
    if(in_array($b,['blade_runner','in_the_swing'],true)&&FaBEVRTargets($p,$b==='blade_runner'?'1HAttack':'WeaponAttack')==='')return false;
    return true;
}
function FaBEVRAsInstant(int $p,object $o): bool {return $p!==intval(GetTurnPlayer())&&FaBMONHero($p,'iyslander')&&(FaBFindUID(intval($o->UniqueID))['zone']??'')==='Arsenal'&&intval(CardPitch($o->CardID))===3&&FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o);}
function FaBEVRPlayed(int $p,object $o,string $from): void {
    $aa=FaBWTRIsAttackAction($o);$uid=intval($o->UniqueID);$b=FaBWTRBase($o->CardID);$s=FaBGetState();
    FaBEVRAdd($p,'PLAYED');if(FaBHasType($o,'Aura'))FaBEVRAdd($p,'AURAS');
    if($b==='fractal_replication')FaBEVRFractalCopy($p,$o);
    if($from==='Banish')foreach(FaBMONArena($p,'talisman_of_cremation') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));FaBRunSourceMacro('ResolveAbility',$p,'talisman_of_cremation_blue',['mzID'=>'']);}
    if($aa&&intval(CardCost($o->CardID))>=3&&FaBEVRCount($p,'STAR')){FaBWTRTag($o,'WTR_POWER:2');FaBWTRTag($o,'DOMINATE');FaBWTRTag($o,'GO_AGAIN');FaBEVRClear($p,'STAR');}
    if(FaBHasType($o,'Ice')&&$p!==intval(GetTurnPlayer())&&FaBMONHero($p,'iyslander'))FaBELEFrost(intval(GetTurnPlayer()));
    foreach(FaBWTREffects($p) as $e){$k=$e['type']??'';$n=intval($e['amount']??0);$consume=false;
        if($k==='EVR_NEXT_MECH'&&$aa&&FaBHasType($o,'Mechanologist'))$consume=true;
        if($k==='EVR_NEXT_BRUTE_ATTACK'&&($aa||FaBWTRIsWeapon($o))&&FaBHasType($o,'Brute'))$consume=true;
        if($k==='EVR_NEXT_WEAPON'&&FaBWTRIsWeapon($o))$consume=true;
        if($k==='EVR_NEXT_ONE_HAND'&&FaBWTRIsWeapon($o)&&FaBHasType($o,'1H'))$consume=true;
        if($k==='EVR_NEXT_VEILED'&&$aa){$consume=true;FaBWTRTag($o,'MON_PHANTASM');FaBWTRTag($o,'MON_ILLUSIONIST');FaBWTRTag($o,'EVR_VEILED');}
        if($k==='EVR_TAILWIND'&&$aa&&intval(CardPower($o->CardID))<=2){FaBWTRTag($o,'GO_AGAIN');FaBEVRClear($p,'TAILWIND');}
        if($k==='EVR_PULVERIZE'&&($aa||FaBWTRIsWeapon($o))){FaBWTRTag($o,'WTR_POWER:-4');FaBEVRClear($p,'PULVERIZE');}
        if($k==='EVR_FATIGUE'&&$aa){FaBWTRTag($o,'EVR_HALF_BASE');FaBEVRClear($p,'FATIGUE');}
        if($consume){FaBWTRTag($o,'WTR_POWER:'.$n);FaBEVRClear($p,substr($k,4));}
    }
    if($aa&&FaBHasType($o,'Illusionist')){
        if(!FaBEVRCount($p,'ILLUSION_AA'))foreach(FaBMONArena($p,'pierce_reality') as $r)FaBWTRTag($o,'WTR_POWER:2');
        FaBEVRAdd($p,'ILLUSION_AA');
    }
    if(FaBHasType($o,'Wizard'))foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'CombatChain',['base'=>'sigil_of_parapets']) as $r){$a=FaBIdentityFromMZ($r)['object'];if($seat===$p&&in_array($a->Role,['DEFENSE','DEFENSE_REACTION'],true)&&intval($a->ChainLink)===intval($s['chainLink']))FaBWTRTag($a,'WTR_DEFENSE:2');}
}
function FaBEVRAttack(int $p,object $o): void {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$auras=FaBEVRCount($p,'AURAS');
    if(FaBHasKeyword($o,'Crush')&&FaBEVRCount($p,'VALDA'))FaBWTRTag($o,'DOMINATE');
    if(FaBHasType($o,'Arrow')&&FaBMONWeapon($p,'dreadbore'))FaBWTRTag($o,'CRU_NO_HAND_DR');
    if($b==='hundred_winds'&&FaBWTRBase($s['previousAttackCardID'])==='hundred_winds')FaBWTRTag($o,'WTR_POWER:'.max(0,count(FaBChoiceRefs($p,'CombatChain',['base'=>'hundred_winds']))-1));
    if(($b==='payload'&&FaBCRUCount($p,'CHAIN_BOOST'))||($b==='drowning_dire'&&$auras))FaBWTRTag($o,'DOMINATE');
    if($b==='shrill_of_skullform'&&$auras)FaBWTRTag($o,'WTR_POWER:3');
    if($b==='reek_of_corruption'&&$auras)FaBWTRTag($o,'CRU_DISCARD_HIT');
    if($b==='swarming_gloomveil'){if($auras>=1)FaBWTRTag($o,'GO_AGAIN');if($auras>=2)FaBWTRTag($o,'WTR_POWER:1');if($auras>=3)FaBWTRTag($o,'EVR_NO_PREVENT');}
    if(FaBHasType($o,'Illusionist')){if(!FaBEVRCount($p,'ILLUSION_ATTACK')&&FaBMONArena($p,'passing_mirage'))FaBWTRTag($o,'MON_NO_PHANTASM');FaBEVRAdd($p,'ILLUSION_ATTACK');}
    if(FaBWTRIsWeapon($o)){
        $uid=intval(FaBObjectCounters($o)['WEAPON_UID']??FaBObjectCounters($o)['MON_SOURCE_UID']??0);$f=FaBFindUID($uid);
        if($f&&FaBEVRCount($p,'OATH'))FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+FaBEVRCount($p,'OATH'));
        if(FaBHasType($o,'Sword')||FaBHasType($o,'Dagger')){FaBEVRAdd($p,'SWORD_DAGGER');$n=FaBEVRCount($p,'SWORD_DAGGER');if($n<=2&&FaBEVRCount($p,'SLICE'))FaBWTRTag($o,'WTR_POWER:'.($n===1?1:FaBEVRCount($p,'SLICE')));}
        if($f&&FaBHasType($o,'Illusionist')&&$f['zone']==='Arena')foreach(FaBMONArena($p,'shimmers_of_silver') as $r){$a=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($a)['EVR_TURN']??-1)!==intval(GetTurnNumber())){FaBSetObjectCounter($a,'EVR_TURN',intval(GetTurnNumber()));FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);}}
    }
}
function FaBEVRPower(int $p,object $o): int {
    $n=FaBHasType($o,'Brute')?FaBEVRCount($p,'CRUSHERS'):0;
    if(FaBHasType($o,'Arrow'))foreach(FaBLiveSeats() as $seat)$n+=FaBEVRCount($seat,'RAIN');
    foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='EVR_ROUND'&&intval($e['target'])===intval(FaBGetState()['defender']))--$n;
    return $n;
}
function FaBEVRDefense(int $p,object $o): int {
    $n=FaBWTRIsAttackAction($o)?FaBEVRCount($p,'IRONHIDE'):0;$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));
    if(FaBWTRBase($o->CardID)==='wax_on'&&$a&&FaBWTRIsAttackAction($a['object'])&&is_numeric(CardCost($a['object']->CardID))&&intval(CardCost($a['object']->CardID))===0)$n+=2;return $n;
}
function FaBEVRAfterMove(int $p,object $o,string $from,string $to): void {
    if($to==='Graveyard')foreach(FaBLiveSeats() as $seat)FaBEVRAdd($seat,'GRAVEYARD');
    if($to==='Arena'){
        if(FaBHasType($o,'Aura')&&$from==='')FaBEVRAdd($p,'AURAS');
        $b=FaBWTRBase($o->CardID);$n=['dissolution_sphere'=>1,'signal_jammer'=>1,'teklo_pounder'=>3][$b]??0;if($n)FaBSetObjectCounter($o,'STEAM',$n);
        if($b==='nerves_of_steel')foreach(FaBChoiceRefs($p,'Equipment') as $r){$e=FaBIdentityFromMZ($r)['object'];if(FaBHasType($e,'Chest'))FaBSetObjectCounter($e,'DEFENSE',max(0,intval(FaBObjectCounters($e)['DEFENSE']??0)-1));}
        if($b==='runeblood_incantation')FaBSetObjectCounter($o,'VERSE',4-intval(CardPitch($o->CardID)));
    }
}
function FaBEVRBoost(int $p,int $uid): void {
    foreach(FaBMONArena($p,'teklo_pounder') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($o)['EVR_TURN']??-1)===intval(GetTurnNumber()))continue;
        FaBSetObjectCounter($o,'EVR_TURN',intval(GetTurnNumber()));$n=intval(FaBObjectCounters($o)['STEAM']??0);if($n>0){FaBTagUID($uid,'WTR_POWER:2');FaBSetObjectCounter($o,'STEAM',$n-1);if($n===1)FaBMONDestroy(intval($o->UniqueID));}}
}
function FaBEVRStart(int $p): void {
    if(FaBCRUHero($p,'bravo_star_of_the_show'))FaBRunSourceMacro('StartTurn',$p,'bravo_star_of_the_show',['mzID'=>FaBChoiceRefs($p,'Hero')[0]]);
    if(FaBCRUHero($p,'valda_brightaxe')&&count(FaBMONArena($p,'seismic_surge'))>=3)FaBEVRAdd($p,'VALDA');
    foreach(FaBOpponents($p) as $seat)if(FaBPENLifeMore($seat,$p))foreach(FaBCRUEquipment($seat,'silver_palms') as $r)FaBRunSourceMacro('ResolveAbility',$seat,'silver_palms',['mzID'=>$r]);
    foreach(FaBLiveSeats() as $seat)FaBWTRSetEffects($seat,array_values(array_filter(FaBWTREffects($seat),fn($e)=>intval($e['expiresAtStartOf']??0)!==$p)));
    foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);
        if($b==='pyroglyphic_protection')FaBMONDestroy(intval($o->UniqueID));
        if(in_array($b,['signal_jammer','dissolution_sphere','runeblood_incantation'],true)){$key=$b==='runeblood_incantation'?'VERSE':'STEAM';$n=intval(FaBObjectCounters($o)[$key]??0);if($n>0){FaBSetObjectCounter($o,$key,$n-1);if($key==='VERSE')FaBARCCreateRunes($p,1);}else FaBMONDestroy(intval($o->UniqueID));}
    }
}
function FaBEVREnd(int $p): void {
    if(FaBELEArsenalSpace($p))foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasKeyword($o,'Heave')){FaBRunSourceMacro('ResolveAbility',$p,'rubble_raiser_blue',['mzID'=>$r]);break;}}
    foreach(FaBMONArena($p,'talisman_of_balance') as $r){$more=false;foreach(FaBOpponents($p) as $seat)if(count(FaBChoiceRefs($seat,'Arsenal'))>count(FaBChoiceRefs($p,'Arsenal')))$more=true;if($more){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));if(FaBELEArsenalSpace($p)){ $top=FaBChoiceRefs($p,'Deck')[0]??'';if($top!==''){$o=FaBMoveChoice($p,$top,'Deck','Arsenal');if($o)$o->FaceDown=1;}}}}
    if(FaBEVRCount($p,'REVEL'))foreach(FaBMONArena($p,'runechant') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    if(FaBEVRCount($p,'OATH'))foreach(FaBChoiceRefs($p,'Weapons') as $r)FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'POWER',0);
}
function FaBEVRPrevent(int $p,int $n,string $type,string $ref=''): int {
    $f=FaBIdentityFromMZ($ref);$uid=$f?intval($f['object']->UniqueID):0;$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='EVR_STEADFAST'&&(intval($e['sourceUID'])===$uid||($f&&intval($e['sourceUID'])===intval(FaBObjectCounters($f['object'])['WEAPON_UID']??FaBObjectCounters($f['object'])['MON_SOURCE_UID']??0)))){$used=min($n,intval($e['amount']));$n-=$used;$e['amount']-=$used;if($e['amount']<=0)continue;}$left[]=$e;}FaBWTRSetEffects($p,$left);
    if($n===1&&FaBMONArena($p,'dissolution_sphere'))return 0;
    if($type==='ARCANE')foreach(FaBMONArena($p,'pyroglyphic_protection') as $r)$n=max(0,$n-FaBWTRPitchValue(FaBIdentityFromMZ($r)['object']->CardID,[3,2,1]));return $n;
}
function FaBEVRDamaged(int $source,int $p,int $n,string $type): void {
    if($n<=0)return;if($type==='PHYSICAL')FaBEVRAdd($source,'PHYSICAL',$n);
    foreach(FaBMONArena($p,'nerves_of_steel') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    if($source!==$p&&$n===2&&FaBMONArena($source,'talisman_of_warfare')){foreach(FaBMONArena($source,'talisman_of_warfare') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Arsenal') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));}
}
function FaBEVRWeapons(int $p,string $type): string {$refs=[];foreach(FaBLiveSeats() as $seat)$refs=array_merge($refs,FaBChoiceRefs($seat,'Weapons'));return implode('&',array_filter($refs,fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],$type)));}
function FaBEVRCostTargets(int $p,string $kind): string {
 $out=[];foreach(['Weapons','Equipment','Arena'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);
 if(($kind==='Copper'&&$b==='copper')||($kind==='Coins'&&in_array($b,['copper','silver','gold'],true))||($kind==='Cash'&&($z!=='Arena'||(FaBHasType($o,'Item')&&!FaBHasType($o,'Token')))))$out[]=$r;
 }return implode('&',$out);
}
function FaBEVRPayObjects(int $p,int $uid,string $refs): void {$n=0;$value=0;$coins=['copper'=>0,'silver'=>0,'gold'=>0];foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p)continue;$id=$f['object']->CardID;if(isset($coins[$id]))++$coins[$id];FaBMONDestroy(intval($f['object']->UniqueID));++$n;}FaBARCSetCard($uid,'evrDestroyed',$n);FaBARCSetCard($uid,'evrCoinValue',intdiv($coins['copper'],4)+intdiv($coins['silver'],2)+$coins['gold']);}
function FaBEVRSearchItems(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Deck'),fn($r)=>preg_match('/Amulet|Potion|Talisman/',CardName(FaBIdentityFromMZ($r)['object']->CardID))));}
function FaBEVRNumbers(int $max): string {return implode('&',range(0,max(0,$max)));}
function FaBEVRSetX(int $uid,int $x,int $base): void {FaBARCSetCard($uid,'evrX',$x);$s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)===$uid){$f=FaBFindUID($uid);$p=intval($s['pendingPayment']['player']);$printed=$f&&$f['object']->CardID==='imposing_visage_blue'?3:($f?intval(CardCost($f['object']->CardID)):0);$s['pendingPayment']['cost']=max(0,($f?FaBCardCost($f['object'],$p):0)-$printed+$base+$x);FaBSetState($s);}}
function FaBEVRScour(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Aura']),fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Token')||CardCost(FaBIdentityFromMZ($r)['object']->CardID)==='0'));}
function FaBEVRDestroyChoices(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f){FaBMONDestroy(intval($f['object']->UniqueID));++$n;}}return $n;}
function FaBEVRCopyAura(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBWTRCreateArena($p,$f['object']->CardID);if($o){FaBSetObjectCounter($o,'EVR_TOKEN',1);}}
function FaBEVRBrew(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Hand',['base'=>'crazy_brew']),FaBChoiceRefs($p,'Arena',['base'=>'crazy_brew'])));}
function FaBEVRLifeParty(int $p,int $uid,string $r): void {$f=FaBIdentityFromMZ($r);$modes=[];if($f){if($f['zone']==='Hand')FaBDiscardChoice($p,$r);else FaBMONDestroy(intval($f['object']->UniqueID));FaBEVRSetX($uid,0,0);$modes=[0,1,2];}else $modes=[EngineRandomInt(0,2)];foreach($modes as $m)FaBTagUID($uid,['EVR_PARTY','WTR_POWER:2','GO_AGAIN'][$m]);}
function FaBEVRBloodModes(array $uses): string {$out=[];foreach(['Power','Go_again','Extra_attack'] as $i=>$name)if($uses[$i]<2)$out[]=$name;return implode('&',$out);}
function FaBEVRBloodModeIndex(array $uses,int $index): int {$out=[];foreach($uses as $i=>$n)if($n<2)$out[]=$i;return $out[$index]??0;}
function FaBEVRBloodWeapon(string $r,int $mode): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=$f['object'];if($mode===0)FaBWTRTag($o,'WTR_POWER:1');if($mode===1)FaBWTRTag($o,'GO_AGAIN');if($mode===2)FaBSetObjectCounter($o,'EVR_EXTRA_ATTACK_TURN',intval(GetTurnNumber()));}
function FaBEVRBanishOnly(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';if($r!=='')FaBMoveChoice($p,$r,'Deck','Banish');}
function FaBEVRAddDefender(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'CombatChain',$p);if($o){$o->Role='DEFENSE';$o->FromZone='Deck';$o->ChainLink=intval(FaBGetState()['chainLink']);Defended($p,FaBFindUID(intval($o->UniqueID))['mzID'],$p);}}
function FaBEVRSources(): string {$refs=FaBChoiceRefs(1,'Stack');foreach(FaBLiveSeats() as $p)foreach(['Hero','Weapons','Equipment','Arena','CombatChain'] as $z)$refs=array_merge($refs,FaBChoiceRefs($p,$z));return implode('&',$refs);}
function FaBEVRPrivateHand(int $p,int $target): array {$uids=[];foreach(FaBChoiceRefs($target,'Hand') as $r){$o=AddTemp($p,CardID:FaBIdentityFromMZ($r)['object']->CardID);$uids[]=intval($o->UniqueID);}return $uids;}
function FaBEVRForgetHand(array $uids): void {foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['zone']==='Temp')$f['object']->removed=true;}}
function FaBEVRUIDRefs(array $uids): string {$out=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f)$out[]=$f['mzID'];}return implode('&',$out);}
function FaBEVRHit(int $p,object $o,int $n): void {
 $tags=(array)$o->TurnEffects;$r=FaBFindUID(intval($o->UniqueID))['mzID'];
 if(FaBEVRCount($p,'HIGH_STRIKER')){FaBEVRCreate($p,'copper',FaBEVRCount($p,'HIGH_STRIKER'));FaBEVRClear($p,'HIGH_STRIKER');}
 if(FaBWTRIsWeapon($o)&&FaBEVRCount($p,'OUTLAND')){FaBEVRCreate($p,'copper',FaBEVRCount($p,'OUTLAND'));FaBEVRClear($p,'OUTLAND');}
 if(in_array('EVR_TWISTERS',$tags,true))FaBWTRAddEffect($p,'NEXT_ATTACK',1);
 if(in_array('EVR_PARTY',$tags,true))FaBCRUGainLife($p,2);
 if(in_array('EVR_ASSERT',$tags,true))FaBEVRBanishTop($p,'AA');
 if(in_array('EVR_NO_PREVENT',$tags,true)&&FaBFaiHeroHit())FaBWTRAddEffect(intval(FaBGetState()['defender']),'EVR_NO_PREVENT',1,['source'=>$p]);
 if(FaBWTRIsAttackAction($o))foreach(FaBCRUEquipment($p,'mask_of_the_pouncing_lynx') as $ref)FaBRunSourceMacro('Hit',$p,'mask_of_the_pouncing_lynx',['mzID'=>$ref,'amount'=>$n]);
 if(FaBWTRIsAttackAction($o)&&FaBEVRCount($p,'SMASH')&&FaBFaiHeroHit()){FaBEVRClear($p,'SMASH');FaBRunSourceMacro('ResolveAbility',$p,'smashing_good_time_red',['mzID'=>$r]);}
}
function FaBEVRDestroyed(int $p,object $o): void {
 $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$r=FaBFindUID($uid)['mzID']??'';
 if(!HasNoAbilities($o)&&in_array($b,['coalescence_mirage','phantasmal_haze','miraging_metamorph'],true))FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>$r]);
 if(in_array('EVR_VEILED',(array)($o->TurnEffects??[]),true))DoDrawCard($p,1);
 if(FaBHasType($o,'Illusionist')&&FaBHasType($o,'Aura')&&(!FaBHasType($o,'Token')||$b==='haze_bending')){
  $refs=FaBMONArena($p,'haze_bending');if($b==='haze_bending')$refs[]=$r;
  foreach(array_unique($refs) as $ref){$f=FaBIdentityFromMZ($ref);if(!$f)continue;$a=$f['object'];if(intval(FaBObjectCounters($a)['EVR_TURN']??-1)!==intval(GetTurnNumber())){FaBSetObjectCounter($a,'EVR_TURN',intval(GetTurnNumber()));FaBWTRCreateArena($p,'spectral_shield');}}
 }
}
function FaBEVRSmallDeck(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Deck',['attackAction'=>true]),fn($r)=>intval(CardPower(FaBIdentityFromMZ($r)['object']->CardID))<=2));}
function FaBEVRCremate(int $p,string $name): void {foreach(FaBOpponents($p) as $seat)foreach(FaBChoiceRefs($seat,'Graveyard') as $r){$o=FaBIdentityFromMZ($r)['object'];if(strcasecmp(CardName($o->CardID),str_replace('_',' ',$name))===0||$o->CardID===$name)FaBMoveChoice($seat,$r,'Graveyard','Banish');}}
function FaBEVRShatterTargets(): string {$s=FaBGetState();$n=max(0,FaBAttackPower($s)-FaBDefenseValue($s));return implode('&',array_filter(FaBChoiceRefs(intval($s['defender']),'CombatChain'),function($r)use($s,$n){$o=FaBIdentityFromMZ($r)['object'];return intval($o->ChainLink)===intval($s['chainLink'])&&($o->FromZone??'')==='Equipment'&&FaBCurrentDefense($o,intval($s['defender']))<$n;}));}
function FaBEVRDrawAmount(int $p,int $n): int {if(GetCurrentPhase()==='MAIN'&&$p!==intval(GetTurnPlayer()))foreach(FaBMONArena(intval(GetTurnPlayer()),'talisman_of_tithes') as $r){if($n<=0)break;FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));--$n;}return $n;}
function FaBEVRDrew(int $p,int $n): void {
 if($n<=0)return;
 if(GetCurrentPhase()==='MAIN')foreach(FaBOpponents($p) as $seat)if((FaBCRUHero($seat,'valda_brightaxe')||FaBCRUHero($seat,'valda_seismic_impact'))&&!FaBSEAHeroCreationBlocked($seat))FaBEVRCreate($seat,'seismic_surge',$n);
 $ref=(string)DecisionQueueController::GetVariable('mzID');$f=FaBIdentityFromMZ($ref);
 if(GetCurrentPhase()==='MAIN'&&$f&&FaBHasType($f['object'],'Action'))foreach(FaBCRUEquipment($p,'earthlore_bounty') as $r)FaBEVRCreate($p,'seismic_surge',$n);
}
function FaBEVRFractalValue(object $o,string $stat): int {
 $n=0;foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain',['attackAction'=>true]) as $r){$a=FaBIdentityFromMZ($r)['object'];if($a->CardID==='fractal_replication_red'||!FaBHasType($a,'Illusionist'))continue;$n=max($n,intval($stat==='POWER'?CardPower($a->CardID):CardDefense($a->CardID)));}return $n;
}
function FaBEVRFractalCopy(int $p,object $o): void {
 $ids=[];foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'CombatChain',['attackAction'=>true]) as $r){$a=FaBIdentityFromMZ($r)['object'];if($a->CardID!=='fractal_replication_red'&&FaBHasType($a,'Illusionist'))$ids[]=$a->CardID;}
 $c=FaBObjectCounters($o);$c['EVR_COPIED']=$ids;$o->Counters=$c;$keywords=[];foreach($ids as $id)$keywords=array_merge($keywords,FaBKeywords($id));$c=FaBObjectCounters($o);$c['_overrides']['granted_keywords']=array_values(array_unique($keywords));$o->Counters=$c;
}
function FaBEVRUnpreventable(int $p,int $target): bool {foreach(FaBWTREffects($target) as $e)if(($e['type']??'')==='EVR_NO_PREVENT'&&intval($e['source'])===$p)return true;return false;}
function FaBEVRPitch(int $p,int $n,bool $consume): int {if($n!==1)return $n;$refs=FaBMONArena($p,'talisman_of_recompense');if(!$refs)return $n;if($consume)FaBMONDestroy(intval(FaBIdentityFromMZ($refs[0])['object']->UniqueID));return 3;}
function FaBEVRFractalDispatch(int $p,object $o,string $macro,array $params): void {if($o->CardID!=='fractal_replication_red')return;foreach((array)(FaBObjectCounters($o)['EVR_COPIED']??[]) as $id){$f=FaBFindUID(intval($o->UniqueID));if(!$f)break;$params['mzID']=$f['mzID'];FaBRunSourceMacro($macro,$p,$id,$params);}}
function FaBEVRHeaveChoices(int $p): string {if(!FaBELEArsenalSpace($p))return '';return implode('&',array_filter(FaBChoiceRefs($p,'Hand'),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Heave')&&FaBAvailablePitch($p,intval(FaBIdentityFromMZ($r)['object']->UniqueID))>=3));}
function FaBEVRHeavePitch(int $p,int $uid): string {return implode('&',array_filter(explode('&',FaBARCPitchChoices($p)),fn($r)=>$r!==''&&intval(FaBIdentityFromMZ($r)['object']->UniqueID)!==$uid));}
function FaBEVRVerseCounters($o): int {return intval(FaBObjectCounters($o)['VERSE']??0);}
function FaBEVRLethalSource(int $p): bool {
 $s=FaBGetState();$life=intval(GetHealth($p));$a=FaBFindUID(intval($s['attackUID']));if($a&&FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>=$life)return true;
 foreach(GetStack() as $o){if(!is_object($o)||!empty($o->removed)||intval(FaBARCCard(intval($o->UniqueID),'target'))!==$p)continue;
  $text=(string)CardFunctional_text_plain($o->CardID);if(!preg_match('/Deal (\d+) arcane damage/i',$text,$m))continue;$base=intval($m[1]);
  if(FaBWTRBase($o->CardID)==='emeritus_scolding'&&intval($o->Controller)!==intval(GetTurnPlayer()))$base+=2;
  if(FaBARCArcaneAmount(intval($o->Controller),intval($o->UniqueID),$base,$p)>=$life)return true;
 }return false;
}
