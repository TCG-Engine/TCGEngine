<?php
function FaBHVYAbilityRows(): array {
 $r=[];
 foreach(['agile_windup','mighty_windup','vigorous_windup'] as $b)foreach(['red','yellow','blue'] as $c)$r[$b.'_'.$c]=[['INSTANT',0,false,false,false,0,'Discard to create token']];
 $r['ripple_away_blue']=[['INSTANT',0,false,false,false,0,'Discard to reduce action tokens']];
 foreach(['kassai','kassai_of_the_golden_sand'] as $id)$r[$id]=[['ACTION',0,false,true,true,0,'Banish two red and two yellow cards']];
 foreach(['flat_trackers'=>'Agility','gauntlet_of_might'=>'Might','vigor_girth'=>'Vigor','knucklehead'=>'Roll for intellect','monstrous_veil'=>'Draw then discard'] as $id=>$label)$r[$id]=[['ACTION',0,true,$id!=='knucklehead',false,0,$label]];
 $r['balance_of_justice']=[['INSTANT',0,true,false,false,0,'Draw a card']];
 $r['glory_seeker']=[['INSTANT',3,true,false,false,0,'Draw a card']];
 $r['sheltered_cove']=[['INSTANT',3,true,false,false,0,'Prevent two damage']];
 $r['good_time_chapeau']=[['ACTION',0,false,true,false,0,'Destroy Gold to wager Might and Vigor']];
 $r['hood_of_red_sand']=[['REACTION',1,true,false,false,0,'Banish red and yellow for sword hit draw']];
 $r['prized_galea']=[['REACTION',1,true,false,false,0,'Weapon wagers Gold']];
 $r['graven_call']=[['INSTANT',0,false,false,false,0,'Destroy two Silver to equip']];return $r;
}
function FaBHVYHandAbility(string $id): bool {return in_array(FaBWTRBase($id),['agile_windup','mighty_windup','vigorous_windup','ripple_away'],true);}
function FaBHVYSpecialZone(array $f): bool {return ($f['zone']==='Hand'&&FaBHVYHandAbility($f['object']->CardID))||($f['zone']==='Graveyard'&&$f['object']->CardID==='graven_call');}
function FaBHVYAbilityLegal(int $p,array $f,array $spec): bool {
 $id=$f['object']->CardID;
 if(FaBHVYHandAbility($id)&&$f['zone']!=='Hand')return false;
 if($id==='graven_call')return $f['zone']==='Graveyard'&&count(FaBChoiceRefs($p,'Arena',['base'=>'silver']))>=2;
 if($id==='balance_of_justice'&&!array_filter(FaBOpponents($p),fn($s)=>FaBDTDCount($s,'DRAWN')>=2))return false;
 if($id==='good_time_chapeau'&&FaBHVYGold($p)==='')return false;
 $each=in_array($id,['kassai','kassai_of_the_golden_sand'],true)?2:($id==='hood_of_red_sand'?1:0);
 if($each)foreach([1,2] as $pitch)if(count(FaBChoiceRefs($p,'Graveyard',['pitch'=>$pitch]))<$each)return false;
 if($id==='hood_of_red_sand'&&FaBHVYAttackTargets($p,'sword')==='')return false;
 if($id==='prized_galea'&&FaBHVYAttackTargets($p,'weapon')==='')return false;return true;
}
function FaBHVYPrepareAbility(int $p,object $o): bool {return in_array($o->CardID,['kassai','kassai_of_the_golden_sand','hood_of_red_sand','graven_call','good_time_chapeau'],true)&&FaBRunSourceMacro('PrepareCard',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))])>0;}
function FaBHVYPaid(int $p,object $o): void {if(FaBHVYHandAbility($o->CardID)){FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);FaBWTRCardDiscarded($p,$o->CardID);}}
function FaBHVYKassai(int $p): bool {foreach(GetHero($p) as $h)if(empty($h->removed)&&in_array($h->CardID,['kassai','kassai_of_the_golden_sand'],true)&&FaBWTRHeroActive($p))return true;return false;}
function FaBHVYAbilityCost(int $p,array $spec): int {return FaBHVYKassai($p)&&FaBDTDCount($p,'DRAWN')&&FaBHasType($spec['cardID']??'','Sword')?-1:0;}
function FaBHVYControlled(int $p,string $token): bool {return FaBHVYCount($p,'CONTROLLED_'.$token)>0||count(FaBMONArena($p,$token))>0;}
function FaBHVYCost(int $p,object $o): int {return match(FaBWTRBase($o->CardID)){'rising_energy'=>FaBDTDCount($p,'DRAWN')?-1:0,'primed_to_fight'=>FaBHVYControlled($p,'vigor')?-1:0,default=>0};}
function FaBHVYPlayed(int $p,object $o,string $from): void {
 if(!FaBWTRIsAttackAction($o)&&!FaBWTRIsWeapon($o))return;
 $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')!=='HVY_NEXT_ATTACK'){$left[]=$e;continue;}
 $classes=array_filter(explode('|',$e['classes']??''));if($classes&&!array_filter($classes,fn($c)=>FaBHasType($o,$c))){$left[]=$e;continue;}
 if(intval($e['amount']))FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));if(!empty($e['tag']))FaBWTRTag($o,$e['tag']);}FaBWTRSetEffects($p,$left);
 if(FaBWTRBase($o->CardID)==='performance_bonus'&&$from==='Arsenal')FaBWTRTag($o,'GO_AGAIN');
 if(FaBWTRIsAttackAction($o)&&str_contains((string)CardName($o->CardID),'Herald')){if(!FaBHVYCount($p,'HERALD_PLAYED'))FaBWTRTag($o,'HVY_FIRST_HERALD');FaBHVYAdd($p,'HERALD_PLAYED');}
}
function FaBHVYDown(int $p): bool {$v=intval(FaBGetState()['defender']);return $v!==$p&&GetHealth($p)<GetHealth($v)&&FaBHVYControlCount($p,'Equipment')<FaBHVYControlCount($v,'Equipment')&&FaBHVYControlCount($p,'Token')<FaBHVYControlCount($v,'Token');}
function FaBHVYPower(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;
 if(FaBHasKeyword($o,'Combo'))$n+=FaBHVYCount($p,'COMBO_POWER');
 if($b==='primed_to_fight'&&FaBHVYControlled($p,'might'))++$n;
 if(in_array($b,['rising_power','high_riser'],true)&&FaBDTDCount($p,'DRAWN'))++$n;
 if($b==='ball_breaker'&&FaBCRUCount($p,'DISCARD_SIX'))++$n;
 
 if($b==='show_no_mercy'&&FaBHandCount(intval(FaBGetState()['defender']))===0)$n+=3;
 if($b==='beast_mode'&&FaBHVYCount($p,'INTIMIDATED'))$n+=2;
 return $n;
}
function FaBHVYGoAgain(int $p,object $o): bool {
 $b=FaBWTRBase($o->CardID);if(in_array($b,['graven_call','cintari_sellsword'],true))return true;if(FaBHasType($o,'Assassin')&&FaBHVYCount($p,'ASSASSIN_CHAIN'))return true;if($b==='rising_speed'&&FaBDTDCount($p,'DRAWN'))return true;
 if(in_array('HVY_ENGAGED',(array)($o->TurnEffects??[]),true)&&FaBHVYActionBlock(FaBGetState()))return true;
 if($b==='hot_streak'&&FaBHVYCount($p,'HOT_STREAK_'.intval(FaBObjectCounters($o)['WEAPON_UID']??$o->UniqueID)))return true;
 if(count(array_intersect(['HVY_FIRST_HERALD','HVY_FIRST_ANGEL'],(array)($o->TurnEffects??[])))>0&&FaBChoiceRefs($p,'Weapons',['base'=>'luminaris_angels_glow'])&&FaBChoiceRefs($p,'Pitch',['pitch'=>2]))return true;
 return false;
}
function FaBHVYOverpower(int $p,object $o,array $s): bool {return (FaBWTRBase($o->CardID)==='over_the_top'&&FaBHVYAbove(intval($o->UniqueID)))||(FaBWTRBase($o->CardID)==='down_but_not_out'&&FaBARCCard(intval($o->UniqueID),'hvyDown'))||(FaBWTRBase($o->CardID)==='pay_up'&&count(FaBChoiceRefs(intval($s['defender']),'Arena',['base'=>'gold']))>0);}
function FaBHVYDefense(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));
 if(in_array($b,['bloodied_oval','headliner_helm','grandstand_legplates','stadium_centerpiece','ticket_puncher'],true))$n+=FaBHVYLowerLife($p);
 foreach(['beckon_applause'=>['agility','vigor'],'raw_meat'=>['agility','might'],'stand_ground'=>['might','vigor']] as $id=>$tokens)if($b===$id)foreach($tokens as $t)if(FaBMONArena($p,$t))++$n;
 if($b==='boast')$n+=2*FaBHVYCount($p,'CLASH_WINS');
 if($b==='parry_blade'&&$a&&FaBWTRIsWeapon($a['object']))$n+=2;
 if(FaBWTRIsAttackAction($o))$n+=3*count(FaBMONArena($p,'stacked_in_your_favor'));
 if($a&&FaBWTRBase($a['object']->CardID)==='lay_down_the_law'&&FaBAttackPower($s)>=13&&!FaBHasType($o,'Equipment'))--$n;return $n;
}
function FaBHVYBlockLegal(int $p,array $f): bool {
 $s=FaBGetState();$a=intval($s['attacker']);return match($f['object']->CardID){'confront_adversity'=>FaBHVYCount($p,'DESTROYED_vigor')>0,'embrace_adversity'=>FaBHVYCount($a,'DESTROYED_might')>0,'overcome_adversity'=>FaBHVYCount($a,'DESTROYED_agility')>0,'face_adversity'=>FaBDTDCount($a,'DRAWN')>0,default=>true};
}
function FaBHVYCanPlay(int $p,array $f): bool {
 $b=FaBWTRBase($f['object']->CardID);$s=FaBGetState();
 if($b==='fatal_engagement'&&!FaBHVYActionBlock($s))return false;
 if($b==='take_the_upper_hand'&&!(array)FaBARCCard(intval($s['attackUID']),'hvyWagers',[]))return false;
 if($b==='shift_the_tide_of_battle'&&FaBHVYAttackTargets($p,'warriorAbove')==='')return false;return true;
}
function FaBHVYDestroyed(int $p,object $o): void {if(in_array($o->CardID,['agility','might','vigor'],true)){FaBHVYAdd($p,'CONTROLLED_'.$o->CardID);FaBHVYAdd($p,'DESTROYED_'.$o->CardID);}}
function FaBHVYStart(int $p): void {
 foreach(['agility','might','vigor'] as $t)if(FaBMONArena($p,$t))FaBHVYAdd($p,'CONTROLLED_'.$t);
 foreach(['might','vigor'] as $t)foreach(FaBMONArena($p,$t) as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));if($t==='might')FaBWTRAddEffect($p,'NEXT_ATTACK',1);else AddResources($p,intval(GetResources($p))+1);}
 foreach(['big_bop','bigger_than_big','stacked_in_your_favor'] as $b)foreach(FaBMONArena($p,$b) as $r){$o=FaBIdentityFromMZ($r)['object'];FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);}
}
function FaBHVYDefendGroup(int $p,array $uids): void {
 $yellow=0;$six=false;foreach($uids as $uid){$f=FaBFindUID(intval($uid));if(!$f)continue;$yellow+=intval(CardPitch($f['object']->CardID))===2?1:0;if(FaBMONBasePower($p,$f['object'])>=6)$six=true;}
 foreach($uids as $uid){$f=FaBFindUID(intval($uid));if(!$f||HasNoAbilities($f['object']))continue;$id=$f['object']->CardID;
 if($id==='golden_glare'&&$yellow>=2)FaBHVYToken($p,'gold',1,$p,false);
 if($id==='apex_bonebreaker'&&$six)FaBHVYToken($p,'might',1,$p,false);}
 $a=FaBFindUID(intval(FaBGetState()['attackUID']));if($a&&$a['object']->CardID==='hot_streak'&&FaBHVYActionBlock(FaBGetState()))FaBHVYAdd(intval(FaBGetState()['attacker']),'HOT_STREAK_'.intval(FaBObjectCounters($a['object'])['WEAPON_UID']??0));
}
function FaBHVYHit(int $p,object $o,int $n): void {
 if($n<=0||!FaBFaiHeroHit())return;
 if(FaBWTRIsWeapon($o)&&FaBHVYCount($p,'KASSAI_GOLD')){FaBHVYClear($p,'KASSAI_GOLD');FaBHVYToken($p,'gold',1,$p,false);}
 if(FaBWTRIsWeapon($o))foreach(FaBCRUEquipment($p,'grains_of_bloodspill') as $r)FaBRunSourceMacro('ResolveAbility',$p,'grains_of_bloodspill',['mzID'=>$r]);
 if(FaBHasType($o,'Warrior')&&FaBHVYCount($p,'COMMANDING')&&FaBHVYActionBlock(FaBGetState()))FaBHVYDestroyArsenal(intval(FaBGetState()['defender']));
}
