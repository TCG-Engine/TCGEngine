<?php
// Super Slam shared state. All participants are explicit live seats; never infer an opponent from 1/2.
function FaBSUPCount(int $p,string $key): int {return FaBARCEffect($p,'SUP_'.$key);}
function FaBSUPAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'SUP_'.$key,$n,$data);}
function FaBSUPClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='SUP_'.$key)));}
function FaBSUPCrowd(int $p,bool $cheer): void {
    if(!FaBSeatIsLive($p))return;
    FaBSUPAdd($p,$cheer?'CHEER':'BOO');
    if($cheer && FaBSUPAllLife($p,true))foreach(FaBCRUEquipment($p,'comeback_kicks') as $r)FaBROSQueue($p,'comeback_kicks',FaBPENUID($r));
    if(!FaBWTRHeroActive($p)||FaBSEAHeroCreationBlocked($p))return;
    $id=GetHero($p)[0]->CardID??'';
    $token=$cheer?(str_starts_with($id,'pleiades')?'confidence':(str_starts_with($id,'tuffnut')?'toughness':'')):(str_starts_with($id,'lyath_goldmane')?'might':(in_array($id,['kayo_strong_arm','kayo_underhanded_cheat'],true)?'vigor':''));
    if($token!=='')FaBHVYToken($p,$token,1,$p,false);
}
function FaBSUPAllLife(int $p,bool $less): bool {foreach(FaBOpponents($p) as $v)if($less?!FaBPENLifeMore($v,$p):!FaBPENLifeMore($p,$v))return false;return count(FaBOpponents($p))>0;}
function FaBSUPAuras(int $p): int {return count(FaBChoiceRefs($p,'Arena',['type'=>'Aura']));}
function FaBSUPSuspense(int $p,bool $removable=false): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['keyword'=>'Suspense']),fn($r)=>!$removable||intval(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['SUSPENSE']??0)>0));}
function FaBSUPCounter(string $ref,int $n): bool {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['zone']!=='Arena'||!FaBHasKeyword($f['object'],'Suspense'))return false;
    $o=$f['object'];$old=intval(FaBObjectCounters($o)['SUSPENSE']??0);if($old+$n<0)return false;
    FaBSetObjectCounter($o,'SUSPENSE',$old+$n);
    if($old+$n===0&&!HasNoAbilities($o))FaBSUPQueue($f['player'],$o->CardID,intval($o->UniqueID),'supZero');
    return true;
}
function FaBSUPZero(int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Arena'&&!HasNoAbilities($f['object'])&&intval(FaBObjectCounters($f['object'])['SUSPENSE']??0)===0)FaBMONDestroy($uid);}
function FaBSUPAfterMove(int $p,object $o,string $from,string $to,?object $source=null): void {
    if($from==='Soul'&&$to==='Banish'){$s=FaBGetState();$s['supSoulBanished'][$p]=intval($s['supSoulBanished'][$p]??0)+1;FaBSetState($s);}
    if($to==='Graveyard'&&$o->CardID==='kick_the_hornets_nest_yellow'&&intval($GLOBALS['fabEffectController']??$p)!==$p){foreach(['confidence','might','toughness','vigor'] as $t)FaBHVYToken($p,$t);}
    if($to==='Arena'&&$from!=='Arena'){
        if(FaBHasKeyword($o,'Suspense'))FaBSetObjectCounter($o,'SUSPENSE',2);
        if(in_array($o->CardID,['confidence','toughness','agility','might','vigor'],true))FaBHVYAdd($p,'CONTROLLED_'.$o->CardID);
        if(in_array(FaBWTRBase($o->CardID),['cheers','booze','concealed_object','in_the_palm_of_your_hand','up_on_a_pedestal','dramatic_pause'],true))FaBSUPQueue($p,$o->CardID,intval($o->UniqueID),'supEnter');
    }
    if($from==='Arena'&&$to!=='Arena'&&$source&&!HasNoAbilities($source)){
        $controller=intval($source->Controller??$p)?:$p;
        if(FaBHasKeyword($source,'Suspense')||in_array(FaBWTRBase($source->CardID),['cheers','booze'],true))FaBSUPQueue($controller,$source->CardID,intval($o->UniqueID),'supLeave');
    }
}
function FaBSUPStart(int $p): void {
    foreach(FaBLiveSeats() as $v){
        foreach(['confidence','toughness','agility','might','vigor'] as $t)if(FaBMONArena($v,$t))FaBHVYAdd($v,'CONTROLLED_'.$t);
        if($v!==$p)foreach(FaBMPGAuras($v,'toughness') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));FaBSUPAdd($v,'TOUGHNESS');}
    }
    foreach(FaBMPGAuras($p,'channel_the_tranquil_domain') as $r)FaBROSQueue($p,'channel_the_tranquil_domain_yellow',intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];if(HasNoAbilities($o))continue;$b=FaBWTRBase($o->CardID);
        if(FaBHasKeyword($o,'Suspense'))FaBSUPCounter($r,-1);
        if($b==='confidence'){FaBMONDestroy(intval($o->UniqueID));FaBSUPAdd($p,'CONFIDENCE');}
        if(in_array($b,['cheers','booze','two_steps_ahead'],true)){FaBMONDestroy(intval($o->UniqueID));if($b==='two_steps_ahead'){FaBHVYToken($p,'confidence');FaBHVYToken($p,'might',3);}}
    }
}
function FaBSUPBase(int $p,object $o,int $base,bool $power=true): int {
    if(FaBWTRHeroActive($p)&&str_starts_with(GetHero($p)[0]->CardID??'','lyath_goldmane'))$base=intval(ceil($base/2));
    if($power){if(in_array('SUP_BASE_6',(array)($o->TurnEffects??[]),true))$base=6;if(FaBWTRBase($o->CardID)==='big_bully'&&FaBSUPCount($p,'BOO'))$base*=2;}
    return $base;
}
function FaBSUPDefenders(string $type=''): array {
    $s=FaBGetState();$out=[];foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&($type===''||FaBHasType($o,$type)))$out[]=$r;}return $out;
}
function FaBSUPCombatChoices(bool $defense=true,bool $action=false): string {
    $refs=FaBSUPDefenders();if(!$defense){$f=FaBFindUID(intval(FaBGetState()['attackUID']));if($f)$refs[]=$f['mzID'];}
    return implode('&',array_filter($refs,fn($r)=>!$action||FaBHasType(FaBIdentityFromMZ($r)['object'],'Action')));
}
function FaBSUPTokens(int $p,string $names='',bool $all=false): string {
    $out=[];foreach($all?FaBLiveSeats():[$p] as $v)foreach(FaBChoiceRefs($v,'Arena',['type'=>'Token']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasType($o,'Aura')&&($names===''||in_array($o->CardID,explode('|',$names),true)))$out[]=$r;}return implode('&',$out);
}
function FaBSUPTokenNames(): int {$names=[];foreach(explode('&',FaBSUPTokens(0,'',true)) as $r){$f=FaBIdentityFromMZ($r);if($f)$names[]=$f['object']->CardID;}return count(array_unique($names));}
function FaBSUPPitchTop(int $p): bool {
    $r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return false;
    FaBRevealChoices($p,$r);$six=FaBHVYOwnedPower($p,$f['object'])>=6;$o=FaBMoveUID(intval($f['object']->UniqueID),'Pitch',$p);
    if($o){AddResources($p,intval(GetResources($p))+FaBMONPitchValue($p,$o->CardID));FaBRunSourceMacro('CardPitched',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))]);}return $six;
}
function FaBSUPNext(int $p,int $n,string $tag='',string $target=''): void {FaBSUPAdd($p,'NEXT',$n,['tag'=>$tag,'target'=>$target]);}
function FaBSUPPlayed(int $p,object $o,string $from): void {
    if($from==='Graveyard'&&FaBHasKeyword($o,'Suspense')&&FaBSUPCount($p,'ENCORE')){ $used=false;$left=[];foreach(FaBWTREffects($p) as $e){if(!$used&&($e['type']??'')==='SUP_ENCORE'){$used=true;if(--$e['amount']<=0)continue;}$left[]=$e;}FaBWTRSetEffects($p,$left);}
    if(intval(CardCost($o->CardID))>=1)FaBSUPAdd($p,'COST_ONE');
    if(!FaBHasType($o,'Attack')&&!FaBWTRIsWeapon($o))return;
    $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='SUP_NEXT'){if(empty($e['target']))FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));else FaBWTRTag($o,'SUP_TARGET:'.$e['target'].':'.intval($e['amount']));if($e['tag']!=='')FaBWTRTag($o,$e['tag']);}else $left[]=$e;}FaBWTRSetEffects($p,$left);
    if(FaBWTRIsAttackAction($o)&&FaBSUPCount($p,'CONFIDENCE')){FaBWTRTag($o,'SUP_CONFIDENCE');FaBSUPClear($p,'CONFIDENCE');}
    if($from==='Arsenal'&&FaBWTRBase($o->CardID)==='power_play')FaBCRUSelfTag($o,'WTR_POWER:5');
}
function FaBSUPDeclared(int $p,object $o): void {FaBSUPAdd($p,'ATTACKS');FaBARCSetCard(intval($o->UniqueID),'supFirst',FaBSUPCount($p,'ATTACKS')===1);}
function FaBSUPPower(int $p,object $o,int $current): int {
    $b=FaBWTRBase($o->CardID);$n=0;$s=FaBGetState();$v=intval($s['defender']);$hero=FaBFaiHeroHit()?(GetHero($v)[0]??null):null;
    if(FaBARCCard(intval($o->UniqueID),'supFirst'))$n+=count(FaBMPGAuras($p,'the_suspense_is_killing_me'));
    foreach((array)($o->TurnEffects??[]) as $tag){if($tag==='SUP_GRIT')$n+=count(FaBMONArena($p,'toughness'));if(str_starts_with($tag,'SUP_TARGET:')){$parts=explode(':',$tag);if($hero&&FaBHasType($hero,$parts[1]))$n+=intval($parts[2]);}}
    if(HasNoAbilities($o)||(FaBWTRIsAttackAction($o)&&FaBCRUCount($p,'SNAG')))return $n;
    if(in_array($b,['comeback_kid','empowering_ruckus'],true)&&FaBSUPCount($p,'CHEER'))++$n;
    if(in_array($b,['low_blow','mocking_blow'],true)&&FaBSUPCount($p,'BOO'))$n+=$b==='low_blow'?3:4;
    if(in_array($b,['goon_battery','goon_beatdown','goon_tactics','gang_robbery','battered_beaten_and_broken'],true)&&FaBSUPAuras($p)>=3)$n+=3;
    $pairs=['old_leather_and_vim'=>['toughness','vigor'],'offensive_behavior'=>['might','vigor'],'spew_obscenities'=>['confidence','might'],'uplifting_performance'=>['confidence','toughness']];
    if(isset($pairs[$b])&&(FaBMONArena($p,$pairs[$b][0])||FaBMONArena($p,$pairs[$b][1])))++$n;
    $types=['bash_brute'=>'Brute','bash_guardian'=>'Guardian','fight_dirty'=>'Revered','fight_fair'=>'Reviled'];if(isset($types[$b])&&FaBSUPDefenders($types[$b]))++$n;
    if($hero){if(in_array($b,['tame_the_beastly_behavior','turn_the_crowd_grateful'],true)&&FaBHasType($hero,'Reviled'))++$n;if($b==='turn_the_crowd_hateful'&&FaBHasType($hero,'Revered'))$n+=3;if($b==='challenge_the_alpha'&&FaBHasType($hero,'Brute'))$n+=2;}
    if($b==='show_of_strength')foreach(FaBSUPDefenders() as $r){$f=FaBIdentityFromMZ($r);if(FaBHVYOwnedPower($f['player'],$f['object'])>=6)--$n;}
    if($b==='unwavering_resolve'&&!FaBChoiceRefs($p,'Deck'))$n+=4;
    if($b==='flex_strength'&&$current+$n>=6)$n+=3;
    if(in_array($b,['cut_off_at_the_knees','cut_a_long_story_short','cut_the_small_talk','no_tall_tales','short_shrift','small_problem','wee_wrecking_ball'],true)&&$current+$n>FaBSUPBase($p,$o,intval(CardPower($o->CardID))))++$n;
    return $n;
}
function FaBSUPDefense(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$n=0;if(HasNoAbilities($o))return 0;
    if(in_array($b,['darling_of_the_crowd','turning_point'],true)&&FaBSUPCount($p,'CHEER'))$n+=$b==='turning_point'?3:1;
    if($b==='disdainful_delight'&&FaBSUPCount($p,'BOO'))++$n;
    if($b==='no_hero_stands_alone'&&FaBHVYCount($p,'CONTROLLED_toughness'))$n+=3;
    $pairs=['plate_of_tough_love'=>['confidence','toughness'],'strong_stomach_for_adversity'=>['confidence','might'],'tough_leather_boots'=>['toughness','vigor'],'laughing_knee_slappers'=>['might','vigor']];if(isset($pairs[$b])&&FaBMONArena($p,$pairs[$b][0])&&FaBMONArena($p,$pairs[$b][1]))$n+=2;
    if(FaBHasType($o,'Action')&&in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true))$n+=FaBSUPCount($p,'LYATH_DEFENSE');return $n;
}
function FaBSUPDefended(int $p,object $o): void {
    if(FaBHasType($o,'Action')&&FaBSUPCount($p,'TOUGHNESS')){FaBWTRTag($o,'WTR_DEFENSE:'.FaBSUPCount($p,'TOUGHNESS'));FaBSUPClear($p,'TOUGHNESS');}
    $f=FaBFindUID(intval(FaBGetState()['attackUID']));if(!$f||HasNoAbilities($f['object']))return;$a=$f['object'];$b=FaBWTRBase($a->CardID);
    if(in_array($b,['familiar_stench','familiar_story'],true)&&FaBHasType($o,$b==='familiar_stench'?'Brute':'Guardian')&&!FaBARCCard(intval($a->UniqueID),'supFamiliar')){FaBARCSetCard(intval($a->UniqueID),'supFamiliar',true);FaBHVYToken($f['player'],$b==='familiar_stench'?'vigor':'confidence');}
    if(in_array($b,['fix_the_match','reckless_stampede'],true))FaBROSQueue($f['player'],$a->CardID,intval($a->UniqueID),['rosEvent'=>'supClash','rosTarget'=>$p]);
}
function FaBSUPGoAgain(int $p,object $o): bool {
    if(HasNoAbilities($o))return false;$b=FaBWTRBase($o->CardID);
    return ($b==='buckwild'&&FaBHVYSix($p,'Pitch')!=='')||($b==='flex_speed'&&FaBAttackPower(FaBGetState())>=6)||($b==='jaws_of_victory'&&FaBSUPCount($p,'CHEER'))||($b==='unwavering_resolve'&&count(FaBSUPDefenders())>=3)||($b==='tempest_palm_gustwave'&&intval($o->ChainLink)>=3);
}
function FaBSUPBlockLegal(int $p,object $o): bool {
    $f=FaBFindUID(intval(FaBGetState()['attackUID']));if(!$f)return true;$a=$f['object'];
    if(!HasNoAbilities($a)&&FaBWTRBase($a->CardID)==='disturb_the_peace'&&FaBHasType($o,'Guardian')&&FaBHasType($o,'Aura'))return false;
    if(!in_array('SUP_CONFIDENCE',(array)$a->TurnEffects,true)||FaBHasType($o,'Block'))return true;
    $n=0;foreach(FaBSUPDefenders() as $r){$d=FaBIdentityFromMZ($r);if($d['player']===$p&&!FaBHasType($d['object'],'Block'))++$n;}return $n<2;
}
function FaBSUPCost(int $p,object $o): int {
    $n=0;if(!FaBSUPCount($p,'COST_ONE')&&intval(CardCost($o->CardID))>=1)$n-=count(FaBMPGAuras($p,'what_happens_next'));
    if(FaBHasType($o,'Defense Reaction'))foreach(FaBOpponents($p) as $v)$n+=FaBSUPCount($v,'ATAYA');
    return $n;
}
function FaBSUPPrevent(int $p,int $n): int {
    if($n<=0)return $n;foreach(FaBMPGAuras($p,'to_be_continued') as $r){$o=FaBIdentityFromMZ($r)['object'];$u=intval($o->UniqueID);if(FaBARCCard($u,'supPreventTurn',-1)!==intval(GetTurnNumber())){FaBARCSetCard($u,'supPreventTurn',intval(GetTurnNumber()));$n=max(0,$n-1);}}return $n;
}
function FaBSUPDisplaySuspense($o): int {return intval(FaBObjectCounters($o)['SUSPENSE']??0);}
function FaBSUPHeroType(int $p,string $types): bool {foreach(explode('|',$types) as $t)if(FaBHasType(GetHero($p)[0]??'', $t))return true;return false;}
function FaBSUPAttackType(string $type): bool {$f=FaBFindUID(intval(FaBGetState()['attackUID']));return $f&&FaBHasType($f['object'],$type);}
function FaBSUPChoicePower(int $p,string $ref): int {$f=FaBIdentityFromMZ($ref);return $f?FaBHVYOwnedPower($p,$f['object']):0;}
function FaBSUPHeroes(int $p,string $type=''): string {return implode('&',array_filter(explode('&',FaBDYNHeroTargets($p)),function($r)use($type){$f=FaBIdentityFromMZ($r);return $f&&($type===''||FaBHasType($f['object'],$type));}));}
function FaBSUPGuardianAuras(int $p): string {$out=[];foreach(FaBLiveSeats() as $v)if(FaBSUPHeroType($v,'Guardian'))$out=array_merge($out,FaBChoiceRefs($v,'Arena',['type'=>'Aura']));return implode('&',$out);}
function FaBSUPGrave(int $p,string $kind): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['attackAction'=>true]),function($r)use($kind){$o=FaBIdentityFromMZ($r)['object'];return $kind!=='pedestal'||FaBHasType($o,'Revered')||FaBHasType($o,'Guardian');}));}
function FaBSUPTop(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f)FaBARCToDeck(intval($f['object']->Owner??$p)?:$p,intval($f['object']->UniqueID),true);}
function FaBSUPPrime(int $p): void {if(FaBSUPHeroType($p,'Revered'))FaBSUPCrowd($p,true);if(FaBSUPHeroType($p,'Reviled'))FaBSUPCrowd($p,false);}
function FaBSUPBuffDefenders(int $n): void {foreach(explode('&',FaBSUPCombatChoices(true,true)) as $r)FaBDYNTag($r,'WTR_DEFENSE:'.$n);}
function FaBSUPRepeatTokens(int $p): void {foreach(['agility','confidence','might','toughness','vigor'] as $t)if(FaBHVYCount($p,'CONTROLLED_'.$t))FaBHVYToken($p,$t);}
function FaBSUPShowboat(int $p): void {$n=0;foreach(FaBOpponents($p) as $v)foreach(FaBChoiceRefs($v,'CombatChain') as $r)if(in_array(FaBIdentityFromMZ($r)['object']->Role??'',['DEFENSE','DEFENSE_REACTION'],true))++$n;FaBHVYToken($p,'might',$n);}
function FaBSUPTapHero(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Hero')return;FaBSEATap($r);FaBSetObjectCounter($f['object'],'SUP_SKIP_UNTAP',1);}
function FaBSUPClashCard(array $c,int $p): string {foreach($c['cards'] as $e)if(intval($e['player'])===$p)return FaBDTDSource(intval($e['uid']));return '';}
function FaBSUPClashResult(array $c): array {
    $override=[];foreach($c['cards'] as $e)if(intval($e['owner']??$e['player'])===intval($e['player'])&&$e['card']==='overturn_the_results_blue'&&intval($c['winner'])!==intval($e['player']))$override[]=intval($e['player']);
    // Both replacements cannot produce two winners; both heroes fail to win.
    if(count($override)===1){$c['winner']=$override[0];FaBSUPCrowd($override[0],false);}elseif(count($override)>1)$c['winner']=0;
    return $c;
}
function FaBSUPClashWon(array $c): void {foreach($c['cards'] as $e)if(intval($e['owner']??$e['player'])===intval($e['player'])&&intval($e['player'])===intval($c['winner'])){if(FaBWTRBase($e['card'])==='rapturous_applause')FaBSUPCrowd(intval($e['player']),true);if(FaBWTRBase($e['card'])==='unexpected_backhand'){foreach($c['seats'] as $v)if($v!==$e['player'])FaBROSQueue(intval($e['player']),$e['card'],intval($e['uid']),['rosEvent'=>'supBackhand','rosTarget'=>$v]);}}}
function FaBSUPRememberMiss(object $o,array $s): void {
    $tokens=['give_em_a_piece_of_your_mind'=>'vigor','shoot_your_mouth_off'=>'confidence','take_that'=>'might','whos_the_tough_guy'=>'toughness'];$b=FaBWTRBase($o->CardID);
    if(($o->Role??'')!=='ATTACK'||!isset($tokens[$b])||HasNoAbilities($o))return;
    foreach($s['attackTargets']??[$s['attackTarget']] as $t)if(($t['type']??'')==='HERO'){$v=intval($t['player']);$hit=isset($s['targetDamage'][(string)$v])?intval($s['targetDamage'][(string)$v]['damage'])>0:!empty($s['attackHit']);if(!$hit)FaBWTRTag($o,'SUP_MISS:'.$v.':'.$tokens[$b]);}
}
function FaBSUPClose(object $o): void {foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'SUP_MISS:')){$parts=explode(':',$tag);FaBHVYToken(intval($parts[1]),$parts[2]);}}
function FaBSUPTapCost(string $id): bool {return in_array($id,['pleiades','pleiades_superstar','tuffnut','tuffnut_bumbling_hulkster','kayo_strong_arm','kayo_underhanded_cheat','lyath_goldmane','lyath_goldmane_vile_savant','gauntlets_of_tyrannical_rex','concealed_object_blue','gallow_end_of_the_line_yellow'],true);}
function FaBSUPDiscardAbility(string $id): bool {return in_array($id,['wind_up_the_crowd_blue','outside_interference_blue','the_old_switcheroo_blue','light_up_the_leaves_red'],true);}
function FaBSUPAbilityRows(): array {
    $r=[];foreach(['pleiades','pleiades_superstar','tuffnut','tuffnut_bumbling_hulkster','concealed_object_blue','wind_up_the_crowd_blue','outside_interference_blue','the_old_switcheroo_blue','light_up_the_leaves_red','gallow_end_of_the_line_yellow'] as $id)$r[$id]=[['INSTANT',0,false,false,false,0,'Use instant ability']];
    foreach(['kayo_strong_arm','kayo_underhanded_cheat'] as $id)$r[$id]=[['INSTANT',4,false,false,false,0,'Set attack base power to six']];
    foreach(['lyath_goldmane','lyath_goldmane_vile_savant'] as $id)$r[$id]=[['INSTANT',2,false,false,false,0,'Boo and defend']];
    foreach(['hold_firm'=>2,'mightybone_knuckles'=>3,'overbearing_presence'=>3,'stand_strong'=>3,'punching_gloves'=>2] as $id=>$cost)$r[$id]=[['ACTION',$cost,true,true,false,0,'Use equipment']];
    $r['gauntlets_of_tyrannical_rex']=[['ACTION',1,false,true,false,0,'Empower next attack']];
    $r['helm_of_hindsight']=[['INSTANT',3,true,false,false,0,'Return attack to top']];
    $r['tiara_of_suspense']=[['INSTANT',0,true,false,false,0,'Add suspense counter']];
    $r['never_give_up_yellow']=[['INSTANT',2,false,false,false,0,'Bottom to bolster defense']];
    $r['mage_hunter_arrow_red']=[['INSTANT',0,true,false,false,0,'Prevent next arcane damage']];
    $r['backspin_thrust_red']=[['INSTANT',0,false,false,true,0,'Untap cog to enhance attack']];
    $r['bait']=[['REACTION',0,false,false,true,0,'Give Bait power and go again']];
    $r['adaptive_alpha_mold']=[['ACTION',0,false,false,false,0,'Change equipment zone']];return $r;
}
function FaBSUPSpecialZone(array $f): bool {
    $id=$f['object']->CardID;
    if(FaBSUPDiscardAbility($id))return $f['zone']==='Hand';
    if($id==='never_give_up_yellow')return $f['zone']==='Graveyard';
    if($id==='mage_hunter_arrow_red')return $f['zone']==='Arsenal'&&empty($f['object']->FaceDown);
    return $id==='backspin_thrust_red'&&$f['zone']==='CombatChain'&&($f['object']->Role??'')==='ATTACK'&&intval($f['object']->ChainLink)===intval(FaBGetState()['chainLink']);
}
function FaBSUPAbilityLegal(int $p,array $f): bool {
    $o=$f['object'];$id=$o->CardID;
    if(FaBSUPTapCost($id)&&!FaBSEACanTap($o))return false;
    if(in_array($id,['kayo_strong_arm','kayo_underhanded_cheat'],true)&&FaBSUPAttackAction($p)==='')return false;
    if((FaBSUPDiscardAbility($id)||in_array($id,['never_give_up_yellow','mage_hunter_arrow_red','backspin_thrust_red'],true))&&!FaBSUPSpecialZone($f))return false;
    if(str_starts_with($id,'pleiades')&&FaBSUPSuspense($p,true)==='')return false;
    if(in_array($id,['gauntlets_of_tyrannical_rex','overbearing_presence'],true)&&FaBHVYSix($p,'Pitch')==='')return false;
    if($id==='stand_strong'&&FaBSUPSuspense($p)==='')return false;
    if($id==='hold_firm'&&!FaBSUPAllLife($p,true))return false;
    if($id==='mightybone_knuckles'&&!FaBSUPAllLife($p,false))return false;
    if($id==='tiara_of_suspense'&&(!FaBSUPCount($p,'CHEER')||FaBSUPSuspense($p)===''))return false;
    if($id==='never_give_up_yellow'&&(!FaBSUPAllLife($p,true)||!FaBSUPCount($p,'CHEER')||FaBSUPCombatChoices(true,true)===''))return false;
    if($id==='bait'&&((FaBFindUID(intval(FaBGetState()['attackUID']))['object']->CardID??'')!=='bait'))return false;
    if($id==='light_up_the_leaves_red'&&FaBSUPEarthCost($p,intval($o->UniqueID))==='')return false;
    if($id==='gallow_end_of_the_line_yellow'&&FaBSEARefs($p,'watery','Hand')==='')return false;
    if($id==='backspin_thrust_red'&&FaBSUPTappedCogs($p)==='')return false;
    return true;
}
function FaBSUPPaid(int $p,object $o): void {if(FaBSUPTapCost($o->CardID))$o->Status=1;if(FaBSUPDiscardAbility($o->CardID))FaBDiscardChoice($p,FaBDTDSource(intval($o->UniqueID)));if($o->CardID==='never_give_up_yellow')FaBARCToDeck($p,intval($o->UniqueID),false);}
function FaBSUPTappedCogs(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena'),fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Cog')&&intval(FaBIdentityFromMZ($r)['object']->Status)===1));}
function FaBSUPAttackRef(): string {$f=FaBFindUID(intval(FaBGetState()['attackUID']));return $f['mzID']??'';}
function FaBSUPInventory(int $p): string {return implode('&',FaBChoiceRefs($p,'Inventory',['attackAction'=>true,'type'=>'Reviled']));}
function FaBSUPCrushArsenal(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal',['keyword'=>'Crush']),fn($r)=>!empty(FaBIdentityFromMZ($r)['object']->FaceDown)));}
function FaBSUPRevealCrush(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Arsenal')return;$f['object']->FaceDown=0;FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);}
function FaBSUPHyperDrivers(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Banish',['base'=>'hyper_driver']),fn($r)=>empty(FaBIdentityFromMZ($r)['object']->FaceDown)));}
function FaBSUPHitGas(int $p,string $refs): void {$n=0;foreach(explode('&',$refs) as $r)if(in_array($r,explode('&',FaBSUPHyperDrivers($p)),true)){$f=FaBIdentityFromMZ($r);$f['object']->FaceDown=1;++$n;}AddActionPoints($p,intval(GetActionPoints($p))+$n);if($n>=3)DoDrawCard($p,1);}
function FaBSUPTogetherSix(int $p,string $self): bool {foreach(FaBSUPDefenders() as $r){$f=FaBIdentityFromMZ($r);if($r!==$self&&$f['player']===$p&&FaBHVYOwnedPower($p,$f['object'])>=6)return true;}return false;}
function FaBSUPTogetherHand(int $p,int $uid): bool {foreach(FaBSUPDefenders() as $r){$f=FaBIdentityFromMZ($r);if(intval($f['object']->UniqueID)!==$uid&&$f['player']===$p&&($f['object']->FromZone??'')==='Hand')return true;}return false;}
function FaBSUPRevealStronger(int $p,int $n): string {return implode('&',array_filter(FaBChoiceRefs($p,'Hand'),fn($r)=>FaBSUPChoicePower($p,$r)>$n));}
function FaBSUPSongCount(int $p): int {$refs=array_slice(FaBChoiceRefs($p,'Deck'),0,4);FaBRevealChoices($p,implode('&',$refs));return count(array_filter($refs,fn($r)=>FaBSUPChoicePower($p,$r)>=6));}
function FaBSUPTruth(int $p,int $v,array $uids,int $color,bool $guess): bool {$f=FaBFindUID(intval($uids[0]??0));$matches=$f&&intval(CardPitch($f['object']->CardID))===$color;foreach(array_reverse($uids) as $u)FaBARCToDeck($p,intval($u),true);return $matches===$guess;}
function FaBSUPOwnSixAttack(int $p): bool {$f=FaBFindUID(intval(FaBGetState()['attackUID']));return $f&&$f['player']===$p&&FaBAttackPower(FaBGetState())>=6;}
function FaBSUPDifferentTokens(int $p,array $names): string {return implode('&',array_filter(explode('&',FaBSUPTokens($p)),function($r)use($names){$f=FaBIdentityFromMZ($r);return $f&&!in_array($f['object']->CardID,$names,true);}));}
function FaBSUPReturnOwner(string $r,string $zone): void {$f=FaBIdentityFromMZ($r);if($f)FaBMoveUID(intval($f['object']->UniqueID),$zone,intval($f['object']->Owner??$f['player'])?:$f['player']);}
function FaBSUPEnd(int $p): void {
    foreach(FaBMPGAuras($p,'concealed_object') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    foreach(FaBMPGAuras($p,'parched_terrain') as $r)FaBROSQueue($p,'parched_terrain_red',intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBSUPSuppressed(object $o): bool {
    if(in_array('Equipment',EffectiveCardType($o),true))return false;
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']??0));
    if(!$a||$a['object']->CardID!=='a_good_clean_fight_red'||in_array('NO_ABILITIES',(array)$a['object']->TurnEffects,true))return false;
    foreach($s['attackTargets']??[$s['attackTarget']??[]] as $t)if(($t['type']??'')==='HERO'&&intval($o->Owner??$o->Controller??0)===intval($t['player']))return true;return false;
}
function FaBSUPNoDefenseGain(): bool {$f=FaBFindUID(intval(FaBGetState()['attackUID']));return $f&&((!HasNoAbilities($f['object'])&&$f['object']->CardID==='smash_with_big_rock_yellow')||in_array('SUP_NO_DEFENSE_GAIN',(array)$f['object']->TurnEffects,true));}
function FaBSUPBaitLocked(int $p,object $o): bool {return intval($o->Owner??$p)===$p&&count(FaBMPGAuras($p,'bait'))>0;}
function FaBSUPBait(int $p,int $v): void {$o=FaBHVYToken($v,'bait',1,$p,true);if($o)$o->Owner=$p;}
function FaBSUPBaitResolved(object $o): void {if($o->CardID==='bait')FaBMONDestroy(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0));}
function FaBSUPSoulCount(int $p): int {return intval(FaBGetState()['supSoulBanished'][$p]??0);}
function FaBSUPSources(): string {$r=[];foreach(FaBLiveSeats() as $p)foreach(['Hero','Weapons','Equipment','Arena','CombatChain'] as $z)$r=array_merge($r,FaBChoiceRefs($p,$z));foreach(GetStack() as $i=>$o)if(empty($o->removed))$r[]='Stack-'.$i;return implode('&',$r);}
function FaBSUPSourcePrevention(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f)FaBSUPAdd($p,'SOURCE_PREVENT',6,['uid'=>intval($f['object']->UniqueID)]);}
function FaBSUPArcanePrevent(int $p,int $n,int $source,bool $hero=true): int {
    foreach(FaBLiveSeats() as $seat){$left=[];foreach(FaBWTREffects($seat) as $e){
        if($hero&&$seat===$p&&($e['type']??'')==='SUP_ARROW_PREVENT'&&$n>0){$n=max(0,$n-intval($e['amount']));continue;}
        if(($e['type']??'')==='SUP_SOURCE_PREVENT'&&intval($e['uid'])===$source){$used=min($n,intval($e['amount']));$n-=$used;$e['amount']-=$used;if($e['amount']<=0)continue;}
        $left[]=$e;
    }FaBWTRSetEffects($seat,$left);}return $n;
}
function FaBSUPAuraTargets(int $p,int $exclude=0): string {$r=[];foreach(array_merge([$p],FaBAdjacentOpponents($p)) as $v)foreach(FaBChoiceRefs($v,'Arena',['type'=>'Aura']) as $ref)if(intval(FaBIdentityFromMZ($ref)['object']->UniqueID)!==$exclude)$r[]=$ref;return implode('&',$r);}
function FaBSUPSand(int $uid,string $chosen,int $needed): void {$uids=FaBUPRUIDs($chosen);$f=FaBFindUID($uid);if(!$f)return;$p=$f['player'];if(count($uids)!==$needed){FaBMONDestroy($uid);return;}foreach($uids as $u){$x=FaBFindUID($u);if(!$x||$x['zone']!=='Graveyard'||$x['player']!==$p||intval(CardPitch($x['object']->CardID))!==1){FaBMONDestroy($uid);return;}}foreach($uids as $u)FaBMoveUID($u,'Banish',$p);}
function FaBSUPDawnblade(int $p): string {$r=FaBHVYAttackTargets($p,'weapon');$f=FaBIdentityFromMZ($r);return $f&&FaBWTRBase($f['object']->CardID)==='dawnblade'?$r:'';}
function FaBSUPDawnbladeModes(string $r): int {$f=FaBIdentityFromMZ($r);if(!$f)return 0;$u=intval(FaBObjectCounters($f['object'])['WEAPON_UID']??0);$w=FaBFindUID($u);return min(4,1+intval(FaBObjectCounters($w['object']??$f['object'])['POWER']??0));}
function FaBSUPHit(int $p,object $o): void {
    if(!FaBFaiHeroHit())return;$v=intval(FaBGetState()['defender']);
    if(in_array('SUP_SELLSWORD',(array)$o->TurnEffects,true))FaBHVYToken($p,'cintari_sellsword');
    if(FaBWTRIsAttackAction($o))foreach(FaBLiveSeats() as $owner)if(FaBSUPCount($owner,'TIME_FLIES')){FaBSUPClear($owner,'TIME_FLIES');FaBROSQueue($owner,'time_flies_when_youre_having_fun_red',0,['rosTarget'=>$v]);}
}
function FaBSUPGallowStops(int $p): bool {foreach(FaBOpponents($p) as $v)if(FaBSUPCount($v,'GALLOW'))return true;return false;}
function FaBSUPEncorePlayable(int $p,object $o): bool {return FaBSUPCount($p,'ENCORE')>0&&FaBHasKeyword($o,'Suspense');}
function FaBSUPMoldOptions(int $p,int $uid): string {$f=FaBFindUID($uid);$out=[];foreach(['Head','Chest','Arms','Legs'] as $slot)if(!$f||!FaBHasType($f['object'],$slot))$out[]=$slot;return implode('&',$out);}
function FaBSUPBeaconOptions(array $picked): string {$out=[];foreach(['courage','toughness','vigor'] as $t)if(count(array_filter($picked,fn($s)=>$s===$t))<3)$out[]=$t;return implode('&',$out);}
function FaBSUPDawnbladeApply(string $ref,string $modes): void {$tags=['WTR_POWER:1','GO_AGAIN','SUP_NO_DEFENSE_GAIN','UPR_UNPREVENTABLE'];foreach(explode(',',$modes) as $i)if(isset($tags[intval($i)]))FaBDYNTag($ref,$tags[intval($i)]);}
function FaBSUPEarthCost(int $p,int $uid): string {$s=FaBFindUID($uid);$exclude=intval($s['object']->SourceUniqueID??$uid);return implode('&',array_filter(FaBChoiceRefs($p,'Hand',['type'=>'Earth']),fn($r)=>intval(FaBIdentityFromMZ($r)['object']->UniqueID)!==$exclude));}
function FaBSUPAddSand(int $uid): int {$f=FaBFindUID($uid);if(!$f)return 0;$n=intval(FaBObjectCounters($f['object'])['SAND']??0)+1;FaBSetObjectCounter($f['object'],'SAND',$n);return $n;}
function FaBSUPHunterReveal(int $p,int $v,string $name): bool {$r=FaBChoiceRefs($v,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return false;FaBRevealChoices($v,$r);if(strcasecmp(CardName($f['object']->CardID),$name)!==0)return false;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$v);if($o)FaBSUPContract($v,$o,$p);return true;}
function FaBSUPHunterSearch(int $p,int $v,string $name): string {
    $out=[];foreach(['Hand','Deck','Arsenal'] as $z)foreach(FaBChoiceRefs($v,$z) as $r){$f=FaBIdentityFromMZ($r);if(strcasecmp(CardName($f['object']->CardID),$name)!==0)continue;$o=AddTemp($p,CardID:$f['object']->CardID);FaBARCSetCard(intval($o->UniqueID),'dynOriginal',intval($f['object']->UniqueID));$out[]='p'.$p.'Temp-'.$o->mzIndex;}return implode('&',$out);
}
function FaBSUPHunterFinish(int $p,int $v,string $chosen,string $search): void {foreach(explode('&',$chosen) as $r)if(in_array($r,explode('&',$search),true)){ $f=FaBDYNPrivateOriginal($r);if($f){$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$v);if($o)FaBSUPContract($v,$o,$p);}}foreach(explode('&',$search) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Temp')$f['object']->removed=true;}FaBShuffleDeck($v);}
function FaBSUPContract(int $owner,object $o,int $actor): void {
    if($actor===$owner||!FaBSeatIsLive($actor))return;foreach(FaBSUPDefenders() as $r){$f=FaBIdentityFromMZ($r);if($f['player']!==$actor||$f['object']->CardID!=='hunter_or_hunted_blue'||HasNoAbilities($f['object']))continue;$name=strval(FaBARCCard(intval($f['object']->UniqueID),'supContract'));if($name!==''&&strcasecmp(CardName($o->CardID),$name)===0)FaBHVYToken($actor,'silver');}
}
function FaBSUPSwitch(int $a,int $b): int {foreach([$a,$b] as $p){$left=[];$used=false;foreach(FaBWTREffects($p) as $e){if(!$used&&($e['type']??'')==='SUP_SWITCH'&&intval($e['victim'])===($p===$a?$b:$a)){$used=true;continue;}$left[]=$e;}if($used){FaBWTRSetEffects($p,$left);return $p;}}return 0;}
function FaBSUPGoldEquipment(int $p): array {$out=[];foreach(['golden_gait','golden_galea','golden_gauntlets','golden_heart_plate'] as $b)$out=array_merge($out,FaBCRUEquipment($p,$b));return $out;}
function FaBSUPSteal(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']===$p||$f['zone']!=='Arena')return;$id=$f['object']->CardID;FaBMPGStealAura($p,$r);if(in_array($id,['agility','confidence','might','toughness','vigor'],true))FaBHVYAdd($p,'CONTROLLED_'.$id);}
function FaBSUPSwitchLoser(array $clash): int {$p=intval($clash['switch']??0);return $p>0&&intval($clash['winner'])===$p?intval($clash['seats'][0]===$p?$clash['seats'][1]:$clash['seats'][0]):0;}
function FaBSUPReadyAtStart(object $o): bool {return !FaBHasType($o,'Hero')||(!FaBSUPTapCost($o->CardID)&&empty(FaBObjectCounters($o)['SUP_SKIP_UNTAP'])&&empty(FaBObjectCounters($o)['SUP_TAPPED']));}
function FaBSUPReadyAtEnd(int $p): void {$o=GetHero($p)[0]??null;if(!$o)return;$c=FaBObjectCounters($o);if(!empty($c['SUP_SKIP_UNTAP'])){FaBSetObjectCounter($o,'SUP_SKIP_UNTAP',0);FaBSetObjectCounter($o,'SUP_TAPPED',1);return;}if(FaBSEACanUntap($o)){$o->Status=2;FaBSetObjectCounter($o,'SUP_TAPPED',0);}}
function FaBSUPQueue(int $p,string $id,int $uid,string $event): void {
    if(!empty(FaBGetState()['dtdStarting']))FaBRunSourceMacro('ResolveAbility',$p,$id,['mzID'=>FaBDTDSource($uid),'rosEvent'=>$event,'rosSource'=>$uid]);
    else FaBROSQueue($p,$id,$uid,['rosEvent'=>$event]);
}
function FaBSUPTapOnly(int $p): void {$o=GetHero($p)[0]??null;if(!$o)return;$o->Status=1;FaBSetObjectCounter($o,'SUP_TAPPED',1);}
function FaBSUPAttackAction(int $p): string {$r=FaBHVYAttackTargets($p);$f=FaBIdentityFromMZ($r);return $f&&FaBWTRIsAttackAction($f['object'])?$r:'';}
function FaBSUPStealTargets(int $p,string $names): string {return implode('&',array_filter(explode('&',FaBSUPTokens($p,$names,true)),function($r)use($p){$f=FaBIdentityFromMZ($r);return $f&&$f['player']!==$p;}));}
function FaBSUPTruthPreview(int $p,array $uids): string {$f=FaBFindUID(intval($uids[0]??0));if(!$f)return '';$o=AddTemp($p,CardID:$f['object']->CardID);return 'p'.$p.'Temp-'.$o->mzIndex;}
