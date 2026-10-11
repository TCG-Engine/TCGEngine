<?php
require_once __DIR__.'/dyn_rules_test.php';
$failures=[];
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$resolveIAR=function(){
    for($i=0;$i<30;++$i){$o=FaBStackTop();if(!$o)break;
        if(empty($o->Params['rosTrigger']))break;
        $o->removed=true;FaBROSResolveTrigger(intval($o->Controller),$o);
        if(FaBHasPendingDecision())break;
    }
};
foreach([2,4] as $p){
    $dynReset($p);$other=$p===4?2:1;
    $a=$attack($p,'demonbound_gloomblade_red');$r=FaBWTRCreateArena($other,'runechant_of_envy_yellow');
    $before=intval(GetHealth($other));
    $check(in_array($ref($r),explode('&',FaBIARRunechants()),true),'Usurp omitted a non-adjacent opponent Runechant.');
    $check(FaBIARUsurp($p,intval($a->UniqueID),$ref($r)),'Usurp cost failed.');
    $check(in_array('WTR_POWER:2',(array)$a->TurnEffects,true)&&FaBIARCount($p,'USURPED')===1,'Usurp did not grant power/history to the attack controller.');
    $check(FaBARCRunechants($other)===0,'Runechant destruction reward resolved during cost payment.');
    FaBIARPlayed($p,$a,'Hand');$resolveIAR();
    $check(intval(GetHealth($other))===$before+1&&FaBARCRunechants($other)===1,'Usurp/destruction rewards went to the wrong seat.');
    $check(FaBARCRunechants($p)===0,'Usurping opponent Runechant created a token for attacker.');

    $dynReset($p);$r=FaBWTRCreateArena($p,'runechant_of_pride_yellow');$a=$attack($p,'demonbound_gloomblade_red');
    $check(FaBARCRunechants($p)===1,'Named Runechant did not count toward Runechant discounts.');
    FaBIARUsurp($p,intval($a->UniqueID),$ref($r));FaBIARPlayed($p,$a,'Hand');$resolveIAR();
    $check(in_array('WTR_POWER:1',(array)$a->TurnEffects,true),'Pride lost the usurping attack UID across its trigger.');

    $dynReset($p);$z=FaBWTRCreateArena($p,'restless_outlaw_red');GetHero($p)[0]->CardID='malice';
    $z->Damage=max(0,FaBUPRHealth($z)-1);FaBIAREnd($p);
    $check(!FaBMONArena($p,'restless_outlaw'),'Decay did not kill a damaged ally before healing.');
    $dead=FaBFindUID(intval($z->UniqueID));$check($dead&&$dead['zone']==='Banish'&&!empty($dead['object']->FaceDown),'Malice did not banish the dead zombie face-down.');
    $check(count(FaBChoiceRefs($p,'Banish',['base'=>'corrupted_corpse']))===2,'Outlaw plus Malice did not create two Corpses.');
    $corpse=FaBWTRCreateArena($p,'corrupted_corpse');$before=count(FaBChoiceRefs($p,'Banish'));
    FaBMONDestroy(intval($corpse->UniqueID));
    $check(FaBFindUID(intval($corpse->UniqueID))===null&&count(FaBChoiceRefs($p,'Banish'))===$before,'Incarnate incorrectly died or triggered Malice.');

    $dynReset($p);$card=AddBanish($p,CardID:'gorging_shadowbeast_red',Owner:$p,Controller:$p);
    $gate=AddStack(CardID:'gate_to_iarathael',Controller:$p,Kind:'ABILITY');
    FaBIARStorePermission($p,intval($gate->UniqueID),$ref($card),'Gate');FaBIARGrantPermission($p,intval($gate->UniqueID));
    $check(FaBIARPermission($p,$card,'Banish')&&!FaBIARPermission(1,$card,'Banish'),'Gate permission is not seat-specific.');
    SetTurnNumber(11);$check(!FaBIARPermission($p,$card,'Banish'),'Gate permission leaked into another UPF turn.');
    SetTurnNumber(10);$card->FaceDown=1;$check(!FaBIARBanishPlayable($p,$card),'Face-down card retained play permission.');

    $dynReset($p);GetHero($p)[0]->CardID='viserai_between_worlds';AddDeck($p,CardID:'ghostly_visit_red');AddDeck($p,CardID:'void_wraith_blue');
    FaBARCCreateRunes($p,3);$check(FaBStackCount()===1,'Creating three Runechants was treated as three creation events.');$resolveIAR();
    $check(GetHero($p)[0]->CardID==='viserai_usurper'&&count(FaBChoiceRefs($p,'Banish'))===1,'Viserai did not banish once and traverse.');
    FaBIARTraverse($p,'viserai_between_worlds');$check(GetHero($p)[0]->CardID==='viserai_usurper','Duplicate traverse flipped the hero back.');
    $a=$attack($p,'gorging_shadowbeast_red');FaBIARPlayed($p,$a,'Hand');$check(in_array('GO_AGAIN',(array)$a->TurnEffects,true),'Usurper did not grant go again to first blood-debt attack.');

    $dynReset($p);$a=$attack($p,'bloodfrenzy_gloomblade_red');
    FaBIARAdd($p,'DAMAGE:1');$check(FaBIARGoAgain($p,$a),'Bloodfrenzy missed damage to defending hero.');
    if($p===4){$s=FaBGetState();$s['defender']=3;FaBSetState($s);$check(!FaBIARGoAgain($p,$a),'Damage to one opponent granted Bloodfrenzy against another.');}
    $dynReset($p);$a=$attack($p,'ravenous_rabble_red');GetHero($p)[0]->CardID='levia';$armor=AddEquipment(1,CardID:'dark_arcanite_helm',Owner:1,Controller:1);
    $check(in_array($ref($armor),FaBIARShadowResistRefs(1,intval($a->UniqueID)),true),'Shadow Resist did not recognize a generic attack controlled by a Shadow hero.');
    GetHero($p)[0]->CardID='ira_crimson_haze';$check(!FaBIARShadowResistRefs(1,intval($a->UniqueID)),'Shadow Resist allowed non-Shadow hero damage.');

    $dynReset($p);$ally=FaBWTRCreateArena($p,'restless_steed_red');$m=FaBWTRCreateArena($p,'mark_of_pathstone_blue');FaBARCSetCard(intval($m->UniqueID),'iarBound',intval($ally->UniqueID));
    $a=$attack($p,'restless_steed_red');FaBSetObjectCounter($a,'MON_SOURCE_UID',intval($ally->UniqueID));
    $check(FaBIARPower($p,$a)===1,'Bound Mark power did not follow the ally attack source.');
    $before=intval(GetHealth($p));FaBIARBoundTriggers(intval($ally->UniqueID));$resolveIAR();$check(intval(GetHealth($p))===$before+1,'Bound Mark hit trigger missing.');

    $dynReset($p);$h=GetHero(1)[0];FaBIARWindLock(1);SetTurnNumber(11);FaBIARWindPhase(1);$check(HasNoAbilities($h),'Wind Slicer did not suppress during victim next action phase.');FaBIARWindEnd(1);$check(!HasNoAbilities($h),'Wind Slicer suppressed end-phase abilities.');
    $dynReset($p);$o=AddHand($p,CardID:'soul_of_existence');$check(intval(CardPitch($o->CardID))===4,'Purple resource card does not pitch for four.');

    $dynReset($p);GetHero($p)[0]->CardID='baalghor_omen_of_the_end';$o=AddPitch($p,CardID:'darkest_hour_red',Owner:$p,Controller:$p);
    FaBIARPitched($p,intval($o->UniqueID));$check(FaBFindUID(intval($o->UniqueID))['zone']==='Pitch','Baalghor banished during cost payment.');$resolveIAR();
    $check(FaBFindUID(intval($o->UniqueID))['zone']==='Banish','Baalghor pitch trigger did not resolve.');

    $dynReset($p);$old=FaBWTRCreateArena($p,'blasmophet_the_insatiable_hunger');$old->Damage=5;
    $choices=FaBIARBlasmophet($p);$check(count(explode('&',$choices))===2,'Unique did not offer both Blasmophets.');FaBIARUniqueClear($p,$ref($old));
    $check(count(FaBMONArena($p,'blasmophet_the_insatiable_hunger'))===1,'Unique did not clear the selected copy.');

    $dynReset($p);AddActionPoints($p,1);$demon=FaBWTRCreateArena($p,'blasmophet_the_insatiable_hunger');$card=AddBanish($p,CardID:'darkest_hour_red',Owner:$p,Controller:$p);
    $check(DoPlayCard($p,$ref($card)),'Blasmophet could not announce a blood-debt action.');
    $check((GetDecisionQueue($p)[0]->Tooltip??'')==='Choose_Blasmophet_permission','Blasmophet permission was not declared.');
    if(GetDecisionQueue($p))$answer($p,$ref($demon));
    $check(intval(FaBARCCard(intval($demon->UniqueID),'iarPlayTurn',-1))===intval(GetTurnNumber()),'Blasmophet did not consume its declared permission.');
    $check((FaBFindUID(intval($card->UniqueID))['zone']??'')==='Stack','Declaring permission failed to resume card play.');

    $dynReset($p);$hand=&GetHand($p);$hand=[];AddResources($p,0);$card=AddHand($p,CardID:'harbinger_of_destruction_red',Owner:$p,Controller:$p);
    $check(!FaBIARCanPlay($p,FaBFindUID(intval($card->UniqueID))),'Harbinger allowed itself to pay its mandatory additional cost.');

    $dynReset($p);GetHero($p)[0]->CardID='malice';$ally=FaBWTRCreateArena($p,'restless_outlaw_red');$ally->Damage=99;
    $debt=FaBMONBloodDebt($p);FaBIAREnd($p);$life=intval(GetHealth($p));FaBMONEnd($p,$debt);
    $check(intval(GetHealth($p))===$life-$debt,'Decay-created Corpses acquired blood debt triggers retroactively.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "IAR outcome checks passed in duels and UPF.\n";
