<?php
// Monarch's public-zone rules use seat IDs and persistent card UIDs throughout.
function FaBMONCount(int $p,string $key): int {return FaBARCEffect($p,'MON_'.$key);}
function FaBMONAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'MON_'.$key,$n,$data);}
function FaBMONHero(int $p,string $base): bool {
    foreach(FaBChoiceRefs($p,'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(!HasNoAbilities($o)&&str_starts_with($o->CardID,$base))return true;}return false;
}
function FaBMONArena(int $p,string $base): array {
    return array_values(array_filter(FaBChoiceRefs($p,'Arena',['base'=>$base]),fn($r)=>!HasNoAbilities(FaBIdentityFromMZ($r)['object'])));
}
function FaBMONSoul(int $p): string {return implode('&',FaBChoiceRefs($p,'Soul'));}
function FaBMONBasePower(int $p,object $o): int {return $o->CardID==='mutated_mass_blue'?FaBMONMass($p):intval(CardPower($o->CardID));}
function FaBMONBloodDebt(int $p): int {
    $n=0;foreach(GetBanish($p) as $o)if(is_object($o)&&empty($o->removed)&&empty($o->FaceDown)&&FaBHasKeyword($o,'Blood Debt'))++$n;return $n;
}
function FaBMONCharge(int $p,string $chosen): int {
    $n=0;foreach(explode('&',$chosen) as $ref){$f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||$f['zone']!=='Hand')continue;
        $light=FaBHasType($f['object'],'Light');FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);FaBMONAdd($p,'CHARGED');FaBBoltynCharged($p,$f['object']->CardID);if($light)++$n;
    }return $n;
}
function FaBMONBanishRandom(int $p,int $n): bool {
    $refs=FaBChoiceRefs($p,'Graveyard');$six=false;
    for($i=0;$i<$n&&count($refs);++$i){$index=EngineRandomInt(0,count($refs)-1);$f=FaBIdentityFromMZ($refs[$index]);array_splice($refs,$index,1);
        if(!$f)continue;$six=$six||FaBMONBasePower($p,$f['object'])>=6;FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);
    }return $six;
}
function FaBMONAfterMove(int $p,object $o,string $from,string $to): void {
    if($from==='Arena'&&$to!=='Arena'){
        $s=FaBGetState();$attack=FaBFindUID(intval($s['attackUID']??0));
        if(!empty($s['combatOpen'])&&$attack&&!FaBMONAttackSourceExists($attack['object'])){
            FaBCleanupResolvedLink($s);FaBCloseCombatChain();
        }
    }
    if($to==='Banish'){FaBMONAdd($p,'BANISHED');if(empty($o->FaceDown)&&FaBMONBasePower($p,$o)>=6){FaBMONAdd($p,'BANISHED_SIX');
        foreach(FaBCRUEquipment($p,'hooves_of_the_shadowbeast') as $ref)FaBRunSourceMacro('ResolveAbility',$p,'hooves_of_the_shadowbeast',['mzID'=>$ref]);}}
    if($to==='Soul'){FaBMONAdd($p,'SOUL_ADDED');FaBBoltynSoulAdded($p,$o);}
}
function FaBMONAttackSourceExists(object $attack): bool {
    $uid=intval(FaBObjectCounters($attack)['MON_SOURCE_UID']??0);
    if(!$uid)return true;
    $source=FaBFindUID($uid);return $source!==null&&($source['zone']==='Arena'||($source['object']->CardID==='nitro_mechanoid'&&$source['zone']==='Equipment'));
}
function FaBMONBanishPlayable(int $p,object $o): bool {
    if(!empty($o->FaceDown)||HasNoAbilities($o))return false;$b=FaBWTRBase($o->CardID);
    if(in_array($b,['bounding_demigon','unhallowed_rites'],true))return count(FaBARCPlayed($p,true))>0;
    if($b==='deep_rooted_evil')return FaBMONCount($p,'BANISHED_SIX')>0;
    if($b==='eclipse')return FaBMONCount($p,'DEBT_PLAYED')>=6;
    return in_array($b,['ghostly_visit','howl_from_beyond','piercing_shadow_vise','rift_bind','rifted_torment','rip_through_reality','seeds_of_agony','seeping_shadows','tome_of_torment','void_wraith','mutated_mass','shadow_of_ursur','invert_existence'],true);
}
function FaBMONCanPlay(int $p,array $f): bool {
    $b=FaBWTRBase($f['object']->CardID);$s=FaBGetState();
    if($f['zone']==='Banish'&&FaBMONCount($p,'NO_BANISH_PLAY')&&$p===intval(GetTurnPlayer()))return false;
    if(FaBMONLocked($p)&& (FaBHasType($f['object'],'Instant')||FaBHasType($f['object'],'Defense Reaction')))return false;
    if(in_array($b,['boneyard_marauder','convulsions_from_the_bellows_of_hell','dread_screamer','endless_maw','hungering_slaughterbeast','unworldly_bellow','writhing_beast_hulk'],true)&&count(FaBChoiceRefs($p,'Graveyard'))<3)return false;
    if($b==='soul_harvest'&&count(FaBChoiceRefs($p,'Graveyard'))<6)return false;
    if($b==='celestial_cataclysm'&&count(FaBChoiceRefs($p,'Soul'))<3)return false;
    if($b==='beacon_of_victory'&&!FaBChoiceRefs($p,'Soul'))return false;
    if($b==='blinding_beam'&&FaBMONCombatTargets()==='')return false;
    if($b==='memorial_ground'&&!FaBChoiceRefs($p,'Graveyard',['attackAction'=>true,'maxCost'=>3-intval(CardPitch($f['object']->CardID))]))return false;
    if($b==='spew_shadow'&&!FaBChoiceRefs($p,'Banish',['attackAction'=>true,'maxCost'=>3-intval(CardPitch($f['object']->CardID))]))return false;
    if($b==='graveling_growl'&&!FaBMONCount($p,'BANISHED_SIX'))return false;
    if($b==='doomsday'&&FaBMONBloodDebt($p)<6)return false;
    if($b==='eclipse'&&FaBMONCount($p,'DEBT_PLAYED')<6)return false;
    return true;
}
function FaBMONLessLife(int $p,string $talent=''): bool {
    foreach(FaBOpponents($p) as $other)if(intval(GetHealth($p))<intval(GetHealth($other))&&($talent===''||FaBHasType(GetHero($other)[0],$talent)))return true;return false;
}
function FaBMONCardPlayed(int $p,object $o,string $from): void {
    $b=FaBWTRBase($o->CardID);$aa=FaBWTRIsAttackAction($o);$left=[];
    if($from==='Banish'){FaBMONAdd($p,'BANISH_PLAYED');FaBARCSetCard(intval($o->UniqueID),'monFromBanish',true);}
    if(FaBHasKeyword($o,'Blood Debt'))FaBMONAdd($p,'DEBT_PLAYED');
    foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';$applies=false;$amount=intval($e['amount']??0);
        if($t==='MON_NEXT_SHADOW_BRUTE')$applies=$aa&&(FaBHasType($o,'Shadow')||FaBHasType($o,'Brute'));
        if($t==='MON_NEXT_SMALL')$applies=$aa&&intval(CardCost($o->CardID))<=intval($e['maxCost']??2);
        if($t==='MON_MINNOW')$applies=$aa&&intval(CardPower($o->CardID))<=3;
        if($t==='MON_PHANTASMIFY')$applies=$aa;
        if($t==='MON_TEAR'){$applies=$aa&&FaBHasType($o,'Brute');$amount=intval(CardPower($o->CardID));}
        if($t==='MON_DREAM')$applies=$aa&&FaBHasType($o,'Illusionist');
        if($t==='MON_CHANE')$applies=FaBHasType($o,'Action')&&(FaBHasType($o,'Shadow')||FaBHasType($o,'Runeblade'));
        if(!$applies){$left[]=$e;continue;}
        if($amount)FaBWTRTag($o,'WTR_POWER:'.$amount);
        if(!empty($e['goAgain'])||$t==='MON_CHANE')FaBWTRTag($o,'GO_AGAIN');
        if(!empty($e['dominate']))FaBWTRTag($o,'DOMINATE');
        if(!empty($e['seeds']))FaBWTRTag($o,'MON_SEEDS');
        if($t==='MON_PHANTASMIFY'){FaBWTRTag($o,'MON_ILLUSIONIST');FaBWTRTag($o,'MON_PHANTASM');}
        if($t==='MON_DREAM')FaBWTRTag($o,'MON_NO_PHANTASM');
    }FaBWTRSetEffects($p,$left);
    if($b==='adrenaline_rush'&&FaBMONLessLife($p))FaBCRUSelfTag($o,'WTR_POWER:3');
    if($b==='pound_for_pound'&&FaBMONLessLife($p))FaBWTRTag($o,'DOMINATE');
    if($b==='invigorating_light'&&!FaBChoiceRefs($p,'Soul'))FaBWTRTag($o,'MON_CLOSE_SOUL');
    if($b==='frontline_scout'&&$from==='Arsenal')FaBWTRTag($o,'GO_AGAIN');
    if($from==='Banish'){
        if($b==='bounding_demigon')FaBCRUSelfTag($o,'WTR_POWER:1');
        if($b==='rift_bind')FaBCRUSelfTag($o,'WTR_POWER:'.count(FaBARCPlayed($p,true)));
        $type=$aa?'AA':'NAA';if(FaBHasType($o,'Action')&&count(array_filter(FaBARCPlayed($p,!$aa),fn($id)=>$aa?FaBHasType($id,'Attack'):true))===1)
            foreach(FaBMONArena($p,'dimenxxional_crossroads') as $ref)FaBRunSourceMacro('ResolveAbility',$p,'dimenxxional_crossroads_yellow',['mzID'=>$ref]);
    }
}
function FaBMONWeapon(int $p,string $id): bool {return count(FaBChoiceRefs($p,'Weapons',['base'=>$id]))>0;}
function FaBMONPower(int $p,object $o): int {
    $n=0;$aa=FaBWTRIsAttackAction($o);$s=FaBGetState();$weapon=FaBWTRIsWeapon($o);
    if($aa)foreach(FaBOpponents($p) as $other)$n-=count(FaBMONArena($other,'parable_of_humility'));
    if(FaBMONCount($p,'CHARGED')&&FaBMONHero($p,'boltyn')||FaBMONCount($p,'CHARGED')&&FaBMONHero($p,'ser_boltyn')){
        foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$d=FaBIdentityFromMZ($r)['object'];if(intval($d->ChainLink)===intval($s['chainLink'])&&in_array($d->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBWTRIsAttackAction($d)){++$n;break;}}
    }
    $n+=FaBMONCount($p,'VANGUARD');
    if(FaBHasType(GetHero(intval($s['defender']))[0]??'','Shadow'))$n+=FaBMONCount($p,'RAY');
    if($weapon)$n+=FaBMONCount($p,'GALLANTRY')+FaBMONCount($p,'LUMINA');
    if(in_array('MON_SPEW',(array)$o->TurnEffects,true)&&FaBHasType(GetHero(intval($s['defender']))[0]??'','Light'))$n+=2;

    if($weapon&&FaBHasType($o,'Axe'))$n+=FaBMONCount($p,'SPILL');
    return $n;
}
function FaBMONOwnPower(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$n=0;
    if($b==='valiant_thrust'&&FaBMONCount($p,'CHARGED'))$n+=3;
    if($b==='raydn_duskbane'&&FaBMONCount($p,'CHARGED'))$n+=3;
    if($b==='galaxxi_black'&&FaBMONCount($p,'BANISH_PLAYED'))$n+=2;
    if($b==='piercing_shadow_vise'&&FaBCRUArcane($p,true))$n+=2;
    if($b==='tremor_of_iarathael'&&FaBMONCount($p,'BANISHED'))$n+=2;
    if($b==='yinti_yanti'&&FaBChoiceRefs($p,'Arena',['type'=>'Aura']))$n+=1;
    if($b==='stony_woottonhog'&&FaBWTRNonEquipmentBlockCount($s)<2)$n+=1;
    if($b==='surging_militia')$n+=FaBWTRNonEquipmentBlockCount($s);
    return $n;
}
function FaBMONGreaterBlock(array $s): bool {
    $power=FaBAttackPower($s);foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBMONDefendingPower(intval($s['defender']),$o)>=$power)return true;}return false;
}
function FaBMONLocked(int $p): bool {
    $s=FaBGetState();if(!FaBIsDefendingHero($p,$s))return false;$a=FaBFindUID(intval($s['attackUID']));
    return $a!==null&&$a['object']->CardID==='exude_confidence_red'&&!HasNoAbilities($a['object'])&&!FaBMONGreaterBlock($s);
}
function FaBMONGAgain(int $p,object $o,array $s): bool {
    $b=FaBWTRBase($o->CardID);
    if(in_array($b,['battlefield_blitz','take_flight'],true)&&FaBMONCount($p,'CHARGED'))return true;
    if($b==='rip_through_reality'&&FaBCRUArcane($p,true))return true;
    if($b==='out_muscle'&&!FaBMONGreaterBlock($s))return true;
    if($b==='pulping'&&FaBWTRNonEquipmentBlockCount($s)<2)return true;
    if($b==='zealous_belting')foreach(GetPitch($p) as $c)if(is_object($c)&&empty($c->removed)&&intval(CardPower($c->CardID))>intval(CardPower($o->CardID)))return true;
    if(in_array($b,['ursur_the_soul_reaper','soul_reaping'],true)&&count(FaBChoiceRefs(intval($s['defender']),'Soul'))>0)return true;
    if(FaBHasType($o,'Illusionist')){
        if(FaBWTRIsAttackAction($o)&&FaBMONArena($p,'ode_to_wrath'))return true;
        if(FaBMONWeapon($p,'luminaris'))foreach(GetPitch($p) as $c)if(is_object($c)&&empty($c->removed)&&intval(CardPitch($c->CardID))===2)return true;
        if(!empty(FaBObjectCounters($o)['MON_IRIS']))return true;
    }return false;
}
function FaBMONDefendingPower(int $p,object $o): int {
    $n=$o->CardID==='fractal_replication_red'?FaBEVRFractalValue($o,'POWER'):intval(CardPower($o->CardID));if($o->CardID==='mutated_mass_blue')$n=FaBMONMass($p);
    foreach((array)($o->TurnEffects??[]) as $tag)if(str_starts_with($tag,'WTR_POWER:'))$n+=intval(substr($tag,10));
    if(FaBWTRIsAttackAction($o)){
        foreach(FaBOpponents($p) as $other)$n-=count(FaBMONArena($other,'parable_of_humility'));
        $a=FaBFindUID(intval(FaBGetState()['attackUID']));if($a){if(FaBWTRBase($a['object']->CardID)==='herald_of_triumph')--$n;foreach((array)(FaBObjectCounters($a['object'])['EVR_COPIED']??[]) as $id)if(FaBWTRBase($id)==='herald_of_triumph')--$n;}
    }return max(0,$n);
}
function FaBMONMass(int $p): int {$costs=[];foreach(FaBChoiceRefs($p,'Pitch') as $r)$costs[]=(string)CardCost(FaBIdentityFromMZ($r)['object']->CardID);return count(array_unique($costs))*2;}
function FaBMONDefense(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$n=0;
    if($b==='yinti_yanti'&&FaBChoiceRefs($p,'Arena',['type'=>'Aura']))++$n;
    if($b==='impenetrable_belief')foreach(FaBOpponents($p) as $other)if(FaBMONCount($other,'BANISHED')>=3){$n+=2;break;}
    if($b==='mutated_mass')$n+=FaBMONMass($p);
    return $n;
}
function FaBMONStart(int $p): void {
    FaBBoltynStart($p);
    foreach(FaBChoiceRefs($p,'Arena') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(FaBHasType($o,'Ally'))$o->Damage=0;
        if(HasNoAbilities($o))continue;
        if($o->CardID==='soul_shackle'){$top=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($top);if($f)FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);}
        if($o->CardID==='genesis_yellow')FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$ref]);
    }
    if(intval(GetHealth($p))<=13)foreach(FaBCRUEquipment($p,'carrion_husk') as $r)FaBMoveUID(intval(FaBIdentityFromMZ($r)['object']->UniqueID),'Banish',$p);
}
function FaBMONEnd(int $p): void {
    FaBBoltynEnd();
    if(!(FaBMONHero($p,'levia')&&FaBMONCount($p,'BANISHED_SIX')))FaBARCLoseLife($p,FaBMONBloodDebt($p),$p);
    foreach(FaBCRUEquipment($p,'valiant_dynamo') as $ref)if(FaBCRUCount($p,'WEAPONS')>=2)FaBRunSourceMacro('ResolveAbility',$p,'valiant_dynamo',['mzID'=>$ref]);
    foreach(FaBLiveSeats() as $owner)if(FaBMONArena($owner,'great_library_of_solana'))foreach(FaBLiveSeats() as $seat){
        $yellow=0;foreach(FaBChoiceRefs($seat,'Pitch') as $r)if(intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===2)++$yellow;
        if($yellow>=2)FaBWTRAddEffect($seat,'INTELLECT',count(FaBMONArena($owner,'great_library_of_solana')));
    }
    foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Arena',['type'=>'Ally']) as $r)FaBIdentityFromMZ($r)['object']->Damage=0;
    foreach(explode('&',FaBMONWeaponChoices($p)) as $r){$f=FaBIdentityFromMZ($r);if(!$f)continue;$o=$f['object'];$g=FaBMONCount($p,'GLISTEN');if($g){FaBSetObjectCounter($o,'POWER',0);FaBSetObjectCounter($o,'MON_GLISTEN',0);}}
}
function FaBMONDestroy(int $uid): void {
    FaBELEDestroyHook($uid);
    $f=FaBFindUID($uid);if(!$f)return;$p=$f['player'];$o=clone $f['object'];
    // Snapshot eligible triggers before the source leaves (Retribution sees itself).
    $triggers=(FaBHasType($o,'Aura')||FaBWTRIsAttackAction($o))?FaBMONArena($p,'merciful_retribution'):[];
    $light=FaBHasType($o,'Light')&&!FaBHasType($o,'Token');$aa=FaBWTRIsAttackAction($o)&&FaBHasType($o,'Illusionist');
    FaBMoveUID($uid,'Graveyard',intval($o->Owner??$p));
    FaBEVRDestroyed($p,$o);FaBUPRDestroyed($p,$o);FaBDYNDestroyed($p,$o);
    foreach($triggers as $r){$a=FaBIdentityFromMZ($r);$triggerID=$a?$a['object']->CardID:'merciful_retribution_yellow';
        FaBRunSourceMacro('ResolveAbility',$p,$triggerID,['mzID'=>$r,'monDestroyedUID'=>$uid,'monLight'=>$light]);}

    if($aa)foreach(FaBCRUEquipment($p,'phantasmal_footsteps') as $r)FaBRunSourceMacro('ResolveAbility',$p,'phantasmal_footsteps',['mzID'=>$r]);
}
function FaBMONPhantasm(array $s,bool $resolve=true): bool {
    $f=FaBFindUID(intval($s['attackUID']));if(!$f||$f['zone']!=='CombatChain')return false;$o=$f['object'];
    if(HasNoAbilities($o)||in_array('MON_NO_PHANTASM',(array)$o->TurnEffects,true)||(!FaBHasKeyword($o,'Phantasm')&&!in_array('MON_PHANTASM',(array)$o->TurnEffects,true)))return false;
    foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$b=FaBIdentityFromMZ($r)['object'];
        if(intval($b->ChainLink)!==intval($s['chainLink'])||!in_array($b->Role,['DEFENSE','DEFENSE_REACTION'],true)||!FaBWTRIsAttackAction($b)||FaBHasType($b,'Illusionist')||FaBMONDefendingPower(intval($s['defender']),$b)<6)continue;
        if(!$resolve){AddStack(CardID:$o->CardID,Controller:intval($s['attacker']),Kind:'ABILITY',SourceZone:'CombatChain',SourceUniqueID:intval($o->UniqueID),Params:['uprPhantasm'=>intval($o->UniqueID)]);return false;}
        FaBUPRPhantasm(intval($s['attacker']),$o);FaBMONDestroy(intval($o->UniqueID));FaBCleanupResolvedLink($s);FaBCloseCombatChain();return true;
    }return false;
}
function FaBMONSpectra(array $target): bool {
    $f=FaBFindUID(intval($target['uid']??0));if(!$f||!FaBHasKeyword($f['object'],'Spectra'))return false;
    FaBMONDestroy(intval($f['object']->UniqueID));FaBCloseCombatChain();return true;
}
function FaBMONPrevent(int $p,int $amount): int {
    foreach(FaBMONArena($p,'spectral_shield') as $r){if($amount<=0)break;$o=FaBIdentityFromMZ($r)['object'];FaBMONDestroy(intval($o->UniqueID));--$amount;}return $amount;
}
function FaBMONDamage(int $source,int $p,int $amount,string $type,string $sourceMZ): void {
    if($amount<=0)return;
    $a=FaBIdentityFromMZ($sourceMZ);if($a&&$a['object']->CardID==='dread_scythe')FaBWTRAddEffect($p,'MON_NO_HEAL',1,['expiresAfterTurnOf'=>$p],true);
    if($type==='PHYSICAL'){FaBMONAdd($source,'PHYSICAL');FaBMONAdd($p,'PHYSICAL');}
    if($source!==$p&&in_array($p,FaBOpponents($source),true))FaBARCLoseLife($p,count(FaBMONArena($source,'ode_to_wrath')),$source);
}
function FaBMONLifeLost(int $p,int $amount): void {
    if($amount>0&&$p===intval(GetTurnPlayer()))foreach(FaBMONArena($p,'dimenxxional_crossroads') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBMONCloseMove(object $o,int $p): bool {
    if(in_array('MON_CLOSE_SOUL',(array)$o->TurnEffects,true)||$o->CardID==='soul_shield_yellow'){FaBMoveUID(intval($o->UniqueID),'Soul',$p);return true;}
    if($o->CardID==='carrion_husk'&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)){FaBMoveUID(intval($o->UniqueID),'Banish',$p);return true;}
    if((($o->FromZone??'')==='Arena'||$o->CardID==='nitro_mechanoid')&&($o->Role??'')==='ATTACK'){$o->removed=true;return true;}
    return false;
}
function FaBMONAttack(int $p,object $o): void {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();
    foreach((array)$o->TurnEffects as $tag)if($tag==='MON_SEEDS')FaBRunSourceMacro('ResolveAbility',$p,'seeds_of_agony_red',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
    if(in_array($b,['hatchet_of_body','hatchet_of_mind'],true)){
        $other=$b==='hatchet_of_body'?'hatchet_of_mind':'hatchet_of_body';if(FaBMONCount($p,'LAST_'.$other)){
            $uid=intval(FaBObjectCounters($o)['WEAPON_UID']??0);$w=FaBFindUID($uid);if($w)FaBWTRTag($w['object'],'WTR_POWER:1');}}
    FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!str_starts_with($e['type']??'','MON_LAST_'))));FaBMONAdd($p,'LAST_'.$b);
}
function FaBMONHit(int $p,object $o): void {
    if(!FaBFaiHeroHit())return;$s=FaBGetState();$target=intval($s['defender']);
    foreach((array)$o->TurnEffects as $tag){
        if($tag==='MON_SOUL_HIT'){FaBMONToSoul($p,intval($o->UniqueID));}
        if($tag==='MON_BOTTOM_HIT')FaBARCToDeck($p,intval($o->UniqueID),false);
        if($tag==='MON_PUPPET_HIT')FaBRunSourceMacro('Hit',$p,'shadow_puppetry_red',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']??'']);
        if($tag==='MON_DUSK_HIT'){$w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($w)FaBWTRTag($w['object'],'CRU_EXTRA_ATTACK');}
    }
    if(FaBWTRIsWeapon($o))for($i=0;$i<FaBMONCount($p,'LUMINA');++$i){$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)break;FaBRevealChoices($p,$r);if(FaBHasType($f['object'],'Light')){FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);FaBCRUGainLife($p,1);}else FaBARCToDeck($p,intval($f['object']->UniqueID),false);}
    foreach(FaBLiveSeats() as $owner)if(FaBMONCount($owner,'ECLIPSE_EXISTENCE')&&FaBHasType(GetHero($target)[0],'Light'))FaBRunSourceMacro('Hit',$owner,'eclipse_existence_blue',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']??'','amount'=>1]);
}
function FaBMONToSoul(int $p,int $uid): void {
    $f=FaBFindUID($uid);if(!$f)return;$s=FaBGetState();
    if($f['zone']==='CombatChain'&&intval($s['attackUID'])===$uid){$s['attackGoAgain']=FaBAttackHasGoAgain($s,$f['object']);FaBSetState($s);}
    FaBMoveUID($uid,'Soul',$p);
}
function FaBMONSelect(int $p,string $zone,string $kind): string {
    return implode('&',array_filter(FaBChoiceRefs($p,$zone),function($r)use($kind){$o=FaBIdentityFromMZ($r)['object'];return match($kind){
        'DEBT'=>FaBHasKeyword($o,'Blood Debt'),'NAA_DEBT'=>FaBHasKeyword($o,'Blood Debt')&&FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o),
        'SMALL_AA'=>FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=3,default=>false};}));
}
function FaBMONSetCost(int $uid,int $cost): void {$s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)===$uid){$s['pendingPayment']['cost']=max(0,$cost);FaBSetState($s);}}
function FaBMONSelectedDebt(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasKeyword($f['object'],'Blood Debt'))++$n;}return $n;}
function FaBMONDiscard(int $p,string $refs): void {foreach(explode('&',$refs) as $r)FaBDiscardChoice($p,$r);}
function FaBMONAbilityData(int $p,string $key) {return DecisionQueueController::GetVariable('monAbility_'.$key);}
function FaBMONDrawDiscard(int $p): bool {DoDrawCard($p,1);$ids=FaBDiscardRandom($p,1);$o=FaBFindUID(intval($ids[0]??0));return $o&&intval(CardPower($o['object']->CardID))>=6;}
function FaBMONCombatTargets(): string {
    $refs=[];$s=FaBGetState();foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain',['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['ATTACK','DEFENSE','DEFENSE_REACTION'],true))$refs[]=$r;}return implode('&',$refs);
}
function FaBMONAllGraves(): string {$out=[];foreach(FaBLiveSeats() as $p)$out=array_merge($out,FaBChoiceRefs($p,'Graveyard'));return implode('&',$out);}
function FaBMONBanishSelected(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f){FaBMoveUID(intval($f['object']->UniqueID),'Banish',$f['player']);++$n;}}return $n;}
function FaBMONStageHand(int $viewer,int $owner): array {
    $uids=[];foreach(FaBChoiceRefs($owner,'Hand') as $r){$uid=intval(FaBIdentityFromMZ($r)['object']->UniqueID);$uids[]=$uid;FaBMoveUID($uid,'Temp',$viewer,false);}return $uids;
}
function FaBMONRestoreHand(int $owner,array $uids,int $bottom=0): void {
    foreach($uids as $uid)if(FaBFindUID(intval($uid))){if(intval($uid)===$bottom)FaBARCToDeck($owner,intval($uid),false);else FaBMoveUID(intval($uid),'Hand',$owner,false);}
    if($bottom)DoDrawCard($owner,1);
}
function FaBMONRevealPower(string $chosen): int {$n=0;foreach(explode('&',$chosen) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBWTRIsAttackAction($f['object']))$n+=$f['object']->CardID==='mutated_mass_blue'?FaBMONMass($f['player']):intval(CardPower($f['object']->CardID));}return $n;}
function FaBMONGlisten(int $p,string $ref,int $n): void {FaBMONAdd($p,'GLISTEN',1,['expiresAfterTurnOf'=>$p]);$f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||!FaBWTRIsWeapon($f['object']))return;$o=$f['object'];FaBSetObjectCounter($o,'POWER',intval(FaBObjectCounters($o)['POWER']??0)+$n);FaBSetObjectCounter($o,'MON_GLISTEN',intval(FaBObjectCounters($o)['MON_GLISTEN']??0)+$n);}
function FaBMONSpellvoidRefs(int $p): string {
    $f=FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'));if($f&&FaBEVRUnpreventable(intval($f['player']),$p))return '';
    $out=[];foreach(['Equipment','Arena','CombatChain'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];if($zone==='CombatChain'&&($o->FromZone??'')!=='Equipment')continue;if(FaBHasKeyword($o,'Spellvoid'))$out[]=$r;}return implode('&',$out);
}
function FaBMONSpellvoid(int $p,string $r): int {
    if(!in_array($r,explode('&',FaBMONSpellvoidRefs($p)),true))return 0;$o=FaBIdentityFromMZ($r)['object'];$n=0;
    foreach(FaBKeywords($o) as $k)if(preg_match('/Spellvoid (\d+)/i',$k,$m))$n=max($n,intval($m[1]));FaBMONDestroy(intval($o->UniqueID));return $n;
}
function FaBMONHigherLight(int $p): bool {foreach(FaBOpponents($p) as $other)if(FaBHasType(GetHero($other)[0],'Light')&&intval(GetHealth($p))>intval(GetHealth($other)))return true;return false;}
function FaBMONHasBoth(string $refs): bool {$aa=false;$naa=false;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if(!$f)continue;$aa=$aa||FaBWTRIsAttackAction($f['object']);$naa=$naa||(FaBHasType($f['object'],'Action')&&!FaBWTRIsAttackAction($f['object']));}return $aa&&$naa;}
function FaBMONNonAttacks(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasType($f['object'],'Action')&&!FaBWTRIsAttackAction($f['object']))++$n;}return $n;}
function FaBMONExtraWeapons(int $p): void {foreach(FaBChoiceRefs($p,'Weapons') as $r)FaBWTRTag(FaBIdentityFromMZ($r)['object'],'CRU_EXTRA_ATTACK');}
function FaBMONFootstepsBlock(int $uid): void {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&!FaBHasType($a['object'],'Illusionist')&&FaBAttackPower($s)>=6)FaBTagUID($uid,'DESTROY_ON_CHAIN_CLOSE');
}
function FaBMONPitchValue(int $p,string $id): int {
    return max(0,intval(CardPitch($id)))+(FaBMONCount($p,'SOUL_ADDED')&&FaBHasType($id,'Light')&&FaBCRUEquipment($p,'vestige_of_sol')?1:0);
}
function FaBMONRetributionSoul(int $p,int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Graveyard')FaBMoveUID($uid,'Soul',$p);}
function FaBMONShadowCombatTarget(): bool {foreach(explode('&',FaBMONCombatTargets()) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasType($f['object'],'Shadow'))return true;}return false;}
function FaBMONWeaponChoices(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Weapons'),FaBChoiceRefs($p,'Arena',['type'=>'Weapon'])));}
function FaBMONDefended(object $block): void {
    if(!FaBWTRIsAttackAction($block))return;$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f)return;$o=$f['object'];
    $tags=(array)$o->TurnEffects;if(in_array('MON_PLOW_USED',$tags,true))return;$n=count(array_filter($tags,fn($t)=>$t==='MON_PLOW'));if(!$n)return;
    $w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($w){FaBWTRTag($w['object'],'WTR_POWER:'.$n);FaBWTRTag($o,'MON_PLOW_USED');}
}
function FaBMONPitchAfterCharge(int $p,array $excluded): int {
    $total=intval(GetResources($p));$vestige=count(FaBCRUEquipment($p,'vestige_of_sol'))>0;
    foreach(FaBChoiceRefs($p,'Hand') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(in_array($ref,$excluded,true)||FaBARCNamedProhibited($o->CardID))continue;
        $total+=max(0,intval(CardPitch($o->CardID)))+($vestige&&FaBHasType($o,'Light')?1:0);
    }return $total;
}
function FaBMONAffordableHand(int $p,string $kind='',bool $charge=false): string {
    $cost=intval(FaBGetState()['pendingPayment']['cost']??0);
    return implode('&',array_filter(FaBChoiceRefs($p,'Hand'),function($r)use($p,$kind,$cost,$charge){$o=FaBIdentityFromMZ($r)['object'];
        $remaining=$charge?FaBMONPitchAfterCharge($p,[$r]):FaBAvailablePitch($p)-FaBMONPitchValue($p,$o->CardID);
        return ($kind!=='DEBT'||FaBHasKeyword($o,'Blood Debt'))&&$remaining>=$cost;
    }));
}
// Reject a multi-card charge that would make the announced resource cost unpayable.
function GameValidateDecisionAnswer($player,$answer): bool {
    if(function_exists('SWUValidateDecisionAnswer')&&!SWUValidateDecisionAnswer($player,$answer))return false;
    $d=GetDecisionQueue(intval($player))[0]??null;$s=FaBGetState();
    if(!$d||!$s['pendingPayment']||intval($s['pendingPayment']['player'])!==intval($player))return true;
    if(!in_array($d->Tooltip,['Charge_your_soul','Charge_any_number'],true))return true;
    $chosen=array_values(array_filter(array_unique(explode('&',(string)$answer)),fn($r)=>in_array($r,FaBChoiceRefs(intval($player),'Hand'),true)));
    $remaining=$chosen?FaBMONPitchAfterCharge(intval($player),$chosen):FaBAvailablePitch(intval($player));
    return $remaining>=intval($s['pendingPayment']['cost']);
}
