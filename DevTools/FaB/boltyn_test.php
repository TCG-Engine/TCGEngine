<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('boltyn');
$original=FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/boltyn_source.json'),true));
$check(count($original['inventory'])===10,'Fabrary separate sideboard counts lost.');$original['inventory']=[];
$check($original===$deck&&count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Bot changed starting deck or has illegal UPF loadout.');
$resetBoltyn=function(int $seats=4)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}$p=$seats;$hero=&GetHero($p);$hero=[];AddHero($p,CardID:'boltyn',Owner:$p,Controller:$p);SetTurnPlayer($p);SetPriorityPlayer($p);};
$playBoltyn=function(int $p,string $ref)use($answer){if(!DoPlayCard($p,$ref))throw new RuntimeException('Illegal play '.$ref);if(GetDecisionQueue($p)&&str_contains(GetDecisionQueue($p)[0]->Tooltip,'attack_target'))$answer($p,'p1Hero-0');};
$resolveTop=function(){ $o=FaBStackTop();if(!$o)throw new RuntimeException('No layer');DoResolveCard(intval($o->Controller),FaBFindUID(intval($o->UniqueID))['mzID']);};
$attackBoltyn=function(int $p,int $defender,string $id='bolt_of_courage_red'){
    $o=AddCombatChain($p,CardID:$id,Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:1);
    $s=FaBGetState();$s['attackUID']=intval($o->UniqueID);$s['attacker']=$p;$s['defender']=$defender;$s['chainLink']=1;$s['combatOpen']=true;$s['window']='REACTION';$s['combatStep']='REACTION';$s['attackTarget']=['type'=>'HERO','player'=>$defender];FaBSetState($s);return $o;
};
foreach([2,4] as $seats){$p=$seats;
    $resetBoltyn($seats);AddHand($p,CardID:'duty_bound_blitz_red');$check(!CanPlayCard($p,'p'.$p.'Hand-0'),'Duty Bound lacks yellow-soul prerequisite.');
    AddHand($p,CardID:'beaming_bravado_red');AddHand($p,CardID:'banneret_of_salvation_yellow');
    $playBoltyn($p,'p'.$p.'Hand-1');$answer($p,'p'.$p.'Hand-2');$resolveTop();
    $s=FaBGetState();$check(FaBAttackPower($s)===4&&FaBMONCount($p,'YELLOW_SOUL')===1,'Yellow charge bonus missing.');
    AddHealth($p,10);OnHit($p,FaBFindUID(intval($s['attackUID']))['mzID'],1);$check(intval(GetHealth($p))===11,'Solflare did not gain life.');
    OnHit($p,FaBFindUID(intval($s['attackUID']))['mzID'],1);$check(intval(GetHealth($p))===11,'Solflare fired twice.');
    FaBCloseCombatChain();AddActionPoints($p,1);SetPriorityPlayer($p);$check(CanPlayCard($p,'p'.$p.'Hand-0'),'Duty Bound not unlocked by yellow soul.');

    $resetBoltyn($seats);AddHand($p,CardID:'light_the_way_yellow');AddHand($p,CardID:'banneret_of_salvation_yellow');
    $playBoltyn($p,'p'.$p.'Hand-0');$answer($p,'p'.$p.'Hand-1');$resolveTop();$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));
    $check(!FaBAttackHasGoAgain($s,$f['object']),'Light the Way gained go again before hitting.');OnHit($p,$f['mzID'],1);
    $check(FaBAttackHasGoAgain(FaBGetState(),$f['object']),'Light the Way hit did not grant go again.');

    $resetBoltyn($seats);$w=AddWeapons($p,CardID:'raydn_duskbane',Owner:$p,Controller:$p);AddHand($p,CardID:'edict_of_steel_red');
    $playBoltyn($p,'p'.$p.'Hand-0');$resolveTop();$answer($p,'p'.$p.'Weapons-0');
    $check(intval(FaBObjectCounters($w)['POWER']??0)===1&&count(FaBMONArena($p,'flurry'))===1,'Edict failed sharpen/Flurry.');
    FaBMONAdd($p,'CHARGED');SetPriorityPlayer($p);$check(FaBWTRActivate($p,'p'.$p.'Weapons-0'),'Raydn not activatable.');if($seats===4)$answer($p,'p1Hero-0');$resolveTop();
    $check(FaBAttackPower(FaBGetState())===4&&FaBCRUWeaponReady($w),'Flurry did not grant second Raydn attack.');FaBCloseCombatChain();
    AddActionPoints($p,1);SetPriorityPlayer($p);FaBWTRCreateArena($p,'flurry');FaBWTRActivate($p,'p'.$p.'Weapons-0');if($seats===4)$answer($p,'p1Hero-0');$resolveTop();
    $check(!FaBCRUWeaponReady($w),'Flurry incorrectly granted a third attack.');FaBCloseCombatChain();FaBMONEnd($p);$check(empty(FaBObjectCounters($w)['POWER']),'Sharpen counters survived end phase.');

    $resetBoltyn($seats);$attackBoltyn($p,1);AddHand($p,CardID:'roaring_beam_yellow');
    $playBoltyn($p,'p'.$p.'Hand-0');$resolveTop();$returned=FaBChoiceRefs($p,'Hand')[0];$answer($p,$returned);
    $check(count(FaBMONArena($p,'courage'))===1&&count(FaBChoiceRefs($p,'Soul'))===1&&FaBMONCount($p,'CHARGED')===1,'Roaring Beam return/mandatory charge failed.');

    $resetBoltyn($seats);$attackBoltyn(1,$p,'brutal_assault_red');$s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);SetPriorityPlayer($p);
    $helm=AddEquipment($p,CardID:'helm_of_unity',Owner:$p,Controller:$p);AddHand($p,CardID:'banneret_of_salvation_yellow');
    FaBDeclareBlock($p,'p'.$p.'Equipment-0');FaBDeclareBlock($p,'p'.$p.'Hand-0');FaBFinishDefendDeclaration(FaBGetState());$h=FaBFindUID(intval($helm->UniqueID));
    $check(FaBCurrentDefense($h['object'],$p)===2,'Unity failed to see the hand defender.');FaBCloseCombatChain();$h=FaBFindUID(intval($helm->UniqueID));
    $check($h['zone']==='Equipment'&&intval(FaBObjectCounters($h['object'])['DEFENSE']??0)===1,'Temper failed at chain close.');

    $resetBoltyn($seats);$touch=AddEquipment($p,CardID:'radiant_touch',Owner:$p,Controller:$p);AddSoul($p,CardID:'banneret_of_salvation_yellow');
    $check(FaBWTRActivate($p,'p'.$p.'Equipment-0'),'Radiant Touch cannot activate.');$answer($p,'p'.$p.'Soul-0');$resolveTop();
    $check(count(FaBChoiceRefs($p,'Banish'))===2&&FaBFindUID(intval($touch->UniqueID))['zone']==='Banish','Radiant Touch costs must banish both cards.');
    $check(DoDamage(1,'',$p,1,'PHYSICAL')===0&&DoDamage(1,'',$p,2,'ARCANE')===1,'Radiant Touch prevention failed.');
    $resolve($p,'toe_the_line_red');$check(DoDamage(1,'',$p,1,'PHYSICAL')===0&&count(FaBMONArena($p,'flurry'))===1&&DoDamage(1,'',$p,1,'PHYSICAL')===1,'Toe prevention did not apply to just one event.');
    FaBWTRCreateArena($p,'agility');FaBBoltynStart(1);$check(count(FaBMONArena($p,'agility'))===1,'Agility expired on another turn.');FaBBoltynStart($p);$check(!FaBMONArena($p,'agility'),'Agility did not expire on owner turn.');

    $resetBoltyn($seats);$boots=AddEquipment($p,CardID:'flat_trackers',Owner:$p,Controller:$p);$chest=AddEquipment($p,CardID:'garland_of_spring',Owner:$p,Controller:$p);
    $check(FaBWTRActivate($p,'p'.$p.'Equipment-0'),'Flat Trackers cannot activate.');$resolveTop();SetPriorityPlayer($p);
    $check(FaBFindUID(intval($boots->UniqueID))['zone']==='Graveyard'&&count(FaBMONArena($p,'agility'))===1&&intval(GetActionPoints($p))===1,'Flat Trackers cost/token/go again failed.');
    $check(FaBWTRActivate($p,'p'.$p.'Equipment-1'),'Garland cannot activate.');$resolveTop();
    $check(FaBFindUID(intval($chest->UniqueID))['zone']==='Graveyard'&&intval(GetResources($p))===1&&intval(GetActionPoints($p))===1,'Garland cost/resource/go again failed.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "Boltyn deck and card interactions passed in duel and UPF.\n";

foreach([['boltyn','ira'],['boltyn','boltyn','boltyn','boltyn'],['fai','professor','ira','boltyn']] as $profiles){
    $seats=count($profiles);$resetBoltyn($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260913);$bots=[];
    foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
        foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
    }
    $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$chargedTurns=[];$soulSpent=0;
    for($step=0;$step<8000&&!intval(GetWinner());++$step){
        $p=BotControllerPendingPlayerForClient();$before=$p?count(FaBChoiceRefs($p,'Soul')):0;
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
        if(FaBIsBoltynBot($p)){if(FaBMONCount($p,'CHARGED'))$chargedTurns[$p.':'.GetTurnNumber()]=true;if(count(FaBChoiceRefs($p,'Soul'))<$before)++$soulSpent;}
    }
    $check(intval(GetWinner())>0,'Bot game exceeded limit.');$check(count($chargedTurns)>0&&$soulSpent>0,'Boltyn did not charge and spend soul.');
    echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($chargedTurns)." charged turns; $soulSpent soul payments.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
