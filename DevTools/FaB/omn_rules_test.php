<?php
require_once __DIR__.'/dyn_rules_test.php';
$failures=[];
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
foreach ([2,4] as $p) {
    $dynReset($p);
    $targets=explode('&',FaBPENTargets($p,true));
    $check(in_array('p'.$p.'Hero-0',$targets,true),'OMN any target omitted self.');
    if($p===4)$check(in_array('p1Hero-0',$targets,true)&&in_array('p3Hero-0',$targets,true)&&!in_array('p2Hero-0',$targets,true),'OMN UPF adjacency ignores combat focus.');
    $instant=AddHand($p,CardID:'flash_bolt_red');
    FaBOMNDiscardPlayable($p,$ref($instant));
    $check(FaBOMNCount($p,'STARFALL')===1&&FaBOMNCount(1,'STARFALL')===0,'Starfall assigned to the wrong seat.');
    $check(FaBOMNPlayable(FaBFindUID(intval($instant->UniqueID))),'Oscilio did not grant graveyard permission.');
    SetTurnNumber(11);
    $check(!FaBOMNCount($p,'STARFALL')&&!FaBOMNPlayable(FaBFindUID(intval($instant->UniqueID))),'Starfall or permission escaped its turn.');

    $dynReset($p);
    $a=$attack($p,'shattering_flowtide_red');
    $before=FaBAttackPower(FaBGetState());
    $block=AddCombatChain(1,CardID:'sink_below_red',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1,FromZone:'Hand');
    FaBOMNDefended($block);
    $trigger=FaBStackTop();$check($trigger && ($trigger->Params['rosEvent']??'')==='omnFragment','Fragment did not trigger for a two-plus-defense card.');
    if($trigger){$trigger->removed=true;FaBROSResolveTrigger($p,$trigger);}
    $check(FaBAttackPower(FaBGetState())===$before-2,'Fragment did not reduce attack power by two.');
    $check(count(FaBMONArena($p,'lightning_flow'))===1&&count(FaBMONArena(1,'lightning_flow'))===0,'Fragment token went to the defending seat.');
    $zero=AddCombatChain(1,CardID:'ironrot_helm',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1,FromZone:'Equipment');
    $stackCount=FaBStackCount();FaBOMNDefended($zero);$check(FaBStackCount()===$stackCount,'One-defense equipment fragmented an attack.');
    FaBOMNDefended($block);$trigger=FaBStackTop();$trigger->removed=true;FaBROSResolveTrigger($p,$trigger);
    $check(FaBAttackPower(FaBGetState())===max(0,$before-4)&&count(FaBMONArena($p,'lightning_flow'))===2,'Repeated fragment did not stack.');

    $dynReset($p);
    foreach(['red'=>4,'yellow'=>3,'blue'=>2] as $color=>$ward){
        $o=FaBWTRCreateArena($p,'holo_shield_'.$color);
        $check(FaBMSTWard($p,$o)===1,'Normal holo shield Ward must be one.');
        FaBOMNBlink($p,$ref($o));$o=FaBFindUID(intval($o->UniqueID))['object'];
        $check(FaBOMNHolo($o)===1&&FaBMSTWard($p,$o)===$ward,'Holo Ward pitch scaling failed.');
        $check(!in_array($ref($o),explode('&',FaBOMNBlinkChoices($p)),true),'Aura with holo remains a legal blink cost.');
    }
    $check(FaBOMNCount($p,'HOLO_ENTER')===3,'Holo entry history missing.');
    $flow=FaBWTRCreateArena($p,'lightning_flow');FaBMONDestroy(intval($flow->UniqueID));
    $check(FaBOMNCount($p,'FLOW_DESTROYED')===1&&FaBOMNCount($p,'AURA_DESTROYED')===1,'Lightning Flow destruction history failed.');

    $dynReset($p);
    FaBOMNPrevention($p,3,'lightning_flow');
    $check(FaBOMNPrevent($p,1,'PHYSICAL')===0&&count(FaBMONArena($p,'lightning_flow'))===1,'Calmveil did not create its first token.');
    $check(FaBOMNPrevent($p,3,'ARCANE')===1&&count(FaBMONArena($p,'lightning_flow'))===1,'Calmveil created more than one token or lost remaining prevention.');

    $dynReset($p);
    $a=$attack($p,'dashing_flashfoot_yellow');$base=FaBAttackPower(FaBGetState());FaBTagUID(intval($a->UniqueID),'GO_AGAIN');
    $check(FaBAttackPower(FaBGetState())===$base+1,'Quickstrike failed to observe granted go again.');
    FaBOMNDamaged($p,1,$a);FaBOMNDamaged($p,1,$a);
    $check(FaBStackCount()===1,'First damage ability triggered twice.');
    $trigger=FaBStackTop();$trigger->removed=true;FaBROSResolveTrigger($p,$trigger);
    $check(count(FaBMONArena($p,'embodiment_of_lightning'))===1,'Quickstrike damage reward missing.');

    $dynReset($p);
    $a=$attack($p,'chain_of_brutality_red');FaBTagUID(intval($a->UniqueID),'OMN_QUICK_POWER');
    $printed=intval(CardPower($a->CardID));
    $check(FaBAttackPower(FaBGetState())===$printed+($printed>=6?1:0),'Quick Succession recursed through power-dependent go again.');

    $dynReset($p);
    $a=FaBWTRCreateArena(1,'ponder');FaBOMNStealAura($p,$ref($a));$check(FaBFindUID(intval($a->UniqueID))['player']===$p,'Temporary aura control did not change.');
    FaBOMNEnd();$check(FaBFindUID(intval($a->UniqueID))['player']===1,'Temporary aura was not returned to its original controller.');

    $dynReset($p);
    $sigil=FaBWTRCreateArena(1,'spellbane_sigil_blue');AddResources(1,2);
    $spell=AddStack(CardID:'flash_bolt_red',Controller:$p,Owner:$p,Kind:'INSTANT',SourceZone:'Hand');
    DoResolveCard($p,$ref($spell));$answer($p,'p1Hero-0');
    $check(!empty(GetDecisionQueue(1)),'Arcane barrier choice was not sent to the damaged hero.');
    $answer(1,$ref($sigil));
    $check((GetDecisionQueue(1)[0]->Type??'')==='NUMBERCHOOSE','Spellbane Sigil did not offer variable X.');
    $answer(1,'2');
    $check(GetHealth(1)===29&&GetResources(1)===0,'Spellbane Sigil did not prevent and charge exactly X.');

    $dynReset($p);AddActionPoints($p,0);
    $spell=AddStack(CardID:'aethersling_red',Controller:$p,Owner:$p,Kind:'ACTION',SourceZone:'Hand');
    DoResolveCard($p,$ref($spell));$answer($p,'p1Hero-0');$answer($p,'1');
    $check(GetActionPoints($p)===1&&GetHero($p)[0]->Status===1,'Aethersling failed to grant go again after its awaited tap.');

    $dynReset($p);GetHero($p)[0]->CardID='zyggy';GetHero($p)[0]->Status=2;
    $flow=FaBWTRCreateArena($p,'lightning_flow');$aura=FaBWTRCreateArena($p,'holo_shield_red');
    $check(FaBARCActivate($p,FaBFindUID(intval(GetHero($p)[0]->UniqueID)),0),'Zyggy activation was unavailable.');
    $answer($p,$ref($flow));$answer($p,$ref($aura));
    $check(GetHero($p)[0]->Status===1&&FaBFindUID(intval($aura->UniqueID))['zone']==='Banish'&&GetResources($p)===18,'Zyggy costs were not paid before responses.');
    $ability=FaBStackTop();DoResolveCard($p,'Stack-'.intval($ability->mzIndex));
    $returned=FaBFindUID(intval($aura->UniqueID));
    $check($returned&&$returned['zone']==='Arena'&&FaBOMNHolo($returned['object'])===1,'Zyggy did not return the banished aura with holo.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "OMN outcome checks passed in duels and UPF.\n";
