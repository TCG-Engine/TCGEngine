<?php
require_once __DIR__.'/sup_test.php';
$failures=[];
foreach([2,4] as $p){
    $dynReset($p);
    GetHero($p)[0]->CardID='pleiades';FaBSUPCrowd($p,true);
    $check(count(FaBMONArena($p,'confidence'))===1&&FaBSUPCount($p,'CHEER')===1,'Pleiades crowd reward used the wrong seat.');
    $check(count(FaBMONArena(1,'confidence'))===0,'Crowd reward leaked to an opponent.');
    GetHero($p)[0]->CardID='tuffnut';FaBSUPCrowd($p,true);$check(count(FaBMONArena($p,'toughness'))===1,'Tuffnut cheer did not create Toughness.');
    GetHero($p)[0]->CardID='kayo_strong_arm';FaBSUPCrowd($p,false);$check(count(FaBMONArena($p,'vigor'))===1,'Kayo boo did not create Vigor.');
    GetHero($p)[0]->CardID='lyath_goldmane';FaBSUPCrowd($p,false);$check(count(FaBMONArena($p,'might'))===1,'Lyath boo did not create Might.');
    $o=AddHand($p,CardID:'wrecking_ball_red',Owner:$p,Controller:$p);
    $check(FaBCurrentDefense($o,$p)===intval(ceil(intval(CardDefense($o->CardID))/2)),'Lyath did not halve base defense.');
    $a=$attack($p,'mocking_blow_red');$base=intval(ceil(intval(CardPower($a->CardID))/2));
    $check(FaBAttackPower(FaBGetState())===$base+4,'Lyath must halve base, not the boo bonus.');
    FaBTagUID(intval($a->UniqueID),'SUP_BASE_6');$check(FaBAttackPower(FaBGetState())===10,'Kayo base replacement did not preserve modifiers.');

    $dynReset($p);GetHero($p)[0]->CardID='kayo_strong_arm';$o=AddHand($p,CardID:'mocking_blow_red',Owner:$p,Controller:$p);
    $check(FaBHVYOwnedPower($p,$o)===intval(CardPower($o->CardID)),'SUP Kayo inherited Heavy Hitters Kayo power.');
    AddHealth($p,29);$check(FaBSUPAllLife($p,true),'Lowest life check failed.');AddHealth(1,29);$check(!FaBSUPAllLife($p,true),'Tied life counted as lower than every hero.');
    if($p===4){AddHealth(1,30);AddHealth(3,20);$check(!FaBSUPAllLife($p,true),'Lowest life ignored nonadjacent live seat.');FaBEliminateSeat(3);$check(FaBSUPAllLife($p,true),'Eliminated seat affected lowest life.');}

    $dynReset($p);$o=FaBWTRCreateArena($p,'act_of_glory_red');$u=intval($o->UniqueID);
    $check(FaBObjectCounters($o)['SUSPENSE']===2,'Suspense did not enter with two counters.');
    FaBSUPCounter($ref($o),-1);$check(FaBFindUID($u)['zone']==='Arena'&&FaBObjectCounters($o)['SUSPENSE']===1,'Suspense left before zero.');
    FaBSUPCounter($ref($o),-1);$check(FaBFindUID($u)['zone']==='Arena','Suspense destruction skipped its response window.');
    FaBSUPCounter($ref($o),1);FaBSUPZero($u);$check(FaBFindUID($u)['zone']==='Arena','Restoring suspense did not stop zero-counter trigger.');
    FaBSUPCounter($ref($o),-1);FaBSUPZero($u);$check(FaBFindUID($u)['zone']==='Graveyard','Zero suspense failed to destroy aura.');
    FaBRunSourceMacro('ResolveAbility',$p,'act_of_glory_red',['mzID'=>FaBDTDSource($u),'rosEvent'=>'supLeave','rosSource'=>$u]);
    $check(FaBSUPCount($p,'NEXT')===6,'Act of Glory leave trigger lost its controller or value.');

    $dynReset($p);FaBWTRCreateArena($p,'toughness');FaBSUPStart($p);$check(count(FaBMONArena($p,'toughness'))===1,'Toughness expired on its own turn.');
    FaBSUPStart(1);$check(count(FaBMONArena($p,'toughness'))===0&&FaBSUPCount($p,'TOUGHNESS')===1,'Toughness did not expire on opponent turn.');
    $attack(1,'head_jab_red',$p);$d=AddCombatChain($p,CardID:'wounding_blow_red',Owner:$p,Controller:$p,Role:'DEFENSE',ChainLink:1,FromZone:'Hand');FaBSUPDefended($p,$d);
    $check(FaBCurrentDefense($d,$p)===4&&FaBSUPCount($p,'TOUGHNESS')===0,'Toughness failed to buff and consume once.');

    $dynReset($p);FaBWTRCreateArena($p,'confidence');FaBSUPStart($p);$a=$attack($p,'head_jab_red');FaBSUPPlayed($p,$a,'Hand');
    AddCombatChain(1,CardID:'wounding_blow_red',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1);AddCombatChain(1,CardID:'wounding_blow_blue',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1);
    $check(!FaBSUPBlockLegal(1,(object)['CardID'=>'sink_below_red']),'Confidence allowed a third non-block defense reaction.');
    $check(FaBSUPBlockLegal(1,(object)['CardID'=>'darling_of_the_crowd_yellow']),'Confidence incorrectly prohibited Block cards.');

    $dynReset($p);$attack($p,'smash_with_big_rock_yellow');$d=AddCombatChain(1,CardID:'wounding_blow_red',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1);FaBWTRTag($d,'WTR_DEFENSE:4');
    $check(FaBCurrentDefense($d,1)===3,'Smash with Big Rock allowed defense gain.');
    $dynReset($p);$attack($p,'a_good_clean_fight_red');$h=AddHand(1,CardID:'sink_below_red',Owner:1,Controller:1);$e=AddEquipment(1,CardID:'ironrot_helm',Owner:1,Controller:1);
    $check(HasNoAbilities($h)&&!HasNoAbilities($e),'Clean Fight suppression did not exempt equipment.');
    if($p===4){$other=AddHand(2,CardID:'sink_below_red',Owner:2,Controller:2);$check(!HasNoAbilities($other),'Clean Fight suppressed a nontarget hero.');}

    $dynReset($p);$a=$attack($p,'head_jab_red');FaBSUPBait($p,1);$bait=FaBIdentityFromMZ(FaBMONArena(1,'bait')[0])['object'];
    $check($bait->Owner===$p&&$bait->Controller===1,'Bait lost creator ownership.');
    $check(!FaBSUPBaitLocked(1,$bait)&&FaBSUPBaitLocked(1,(object)['CardID'=>'head_jab_red','Owner'=>1]),'Bait lock prevented itself or allowed owned cards.');

    $dynReset($p);AddDeck($p,CardID:'overturn_the_results_blue');AddDeck(1,CardID:'crippling_crush_red');$c=FaBHVYClash($p,1);
    $check($c['winner']===$p&&FaBSUPCount($p,'BOO')===1,'Overturn did not replace a losing clash.');
    $dynReset($p);AddDeck($p,CardID:'rapturous_applause_red');AddDeck(1,CardID:'head_jab_blue');$c=FaBHVYClash($p,1);FaBHVYClashWon($c,$p);
    $check(FaBSUPCount($p,'CHEER')===1,'Winning Rapturous Applause did not cheer.');
    $dynReset($p);AddDeck($p,CardID:'head_jab_blue');AddDeck(1,CardID:'crippling_crush_red');FaBSUPAdd($p,'SWITCH',1,['victim'=>1]);$c=FaBHVYClash($p,1);
    $check($c['winner']===$p&&FaBSUPCount($p,'SWITCH')===0,'Switcheroo did not swap decks once.');

    $dynReset($p);FaBWTRCreateArena($p,'what_happens_next_blue');$o=AddHand($p,CardID:'crippling_crush_red',Owner:$p,Controller:$p);
    $check(FaBSUPCost($p,$o)===-1,'What Happens Next failed to reduce first qualifying cost.');FaBSUPPlayed($p,$o,'Hand');$check(FaBSUPCost($p,$o)===0,'What Happens Next discounted twice.');
    $dynReset($p);FaBWTRCreateArena($p,'to_be_continued_blue');$check(FaBSUPPrevent($p,3)===2&&FaBSUPPrevent($p,3)===3,'To Be Continued did not prevent only first damage.');
    $dynReset($p);FaBSUPAdd($p,'ARROW_PREVENT',3);$check(FaBSUPArcanePrevent($p,2,0)===0&&FaBSUPArcanePrevent($p,2,0)===2,'Mage Hunter prevention carried unused shield to another event.');
    $dynReset($p);FaBWTRCreateArena(1,'parched_terrain_red');FaBCRUGainLife($p,3);$check(GetHealth($p)===30,'Parched Terrain allowed another hero to gain life.');
    $dynReset($p);$token=FaBWTRCreateArena(1,'toughness');$u=intval($token->UniqueID);FaBSUPSteal($p,$ref($token));$f=FaBFindUID($u);
    $check($f['player']===$p&&$f['object']->Owner===1&&FaBHVYCount($p,'CONTROLLED_toughness')===1,'Stealing Toughness failed to preserve owner or control history.');
    $dynReset($p);GetHero($p)[0]->CardID='pleiades';FaBWTRCreateArena(1,'preach_modesty_blue');FaBSUPCrowd($p,true);
    $check(FaBSUPCount($p,'CHEER')===1&&!FaBMONArena($p,'confidence'),'Preach Modesty must suppress the token, not cheering.');
    $dynReset($p);$o=FaBWTRCreateArena($p,'act_of_glory_red');FaBSetObjectCounter($o,'SUSPENSE',1);$s=FaBGetState();$s['dtdStarting']=true;FaBSetState($s);FaBSUPStart($p);
    $check(FaBFindUID(intval($o->UniqueID))['zone']==='Graveyard'&&FaBSUPCount($p,'NEXT')===6&&FaBStackTop()===null,'Start-phase suspense offered an action-phase response window.');
    $dynReset($p);GetHero($p)[0]->CardID='tuffnut';FaBSUPTapOnly($p);$check(!FaBSUPReadyAtStart(GetHero($p)[0]),'Tapped SUP hero would ready before its end phase.');FaBSUPReadyAtEnd($p);$check(GetHero($p)[0]->Status===2,'SUP hero failed to ready at end phase.');
    FaBSUPTapHero($ref(GetHero($p)[0]));FaBSUPReadyAtEnd($p);$check(GetHero($p)[0]->Status===1&&!FaBSUPReadyAtStart(GetHero($p)[0]),'Turn Heads failed to skip the next end phase.');FaBSUPReadyAtEnd($p);$check(GetHero($p)[0]->Status===2,'Turn Heads prevented more than one end-phase ready.');
    $dynReset($p);$source=$attack(1,'head_jab_red',$p);FaBSUPSourcePrevention($p,$ref($source));$check(FaBSUPArcanePrevent(1,4,intval($source->UniqueID))===0&&FaBSUPArcanePrevent($p,4,intval($source->UniqueID))===2,'Source prevention was not shared across recipients.');
    $dynReset($p);$o=AddEquipment($p,CardID:'golden_gait',Owner:$p,Controller:$p);$check(in_array($ref($o),explode('&',FaBHVYGold($p)),true),'Golden equipment did not count as Gold.');
    $dynReset($p);FaBSUPAdd($p,'ENCORE');FaBSUPAdd($p,'ENCORE');$o=AddHand($p,CardID:'act_of_glory_red');FaBSUPPlayed($p,$o,'Graveyard');$check(FaBSUPCount($p,'ENCORE')===1,'One graveyard play consumed two Encore permissions.');
    $dynReset($p);$source=$attack(1,'head_jab_red',$p);$ally=FaBWTRCreateArena($p,'ashwing');FaBSUPSourcePrevention($p,$ref($source));FaBSUPAdd($p,'ARROW_PREVENT',3);
    $check(FaBUPRDeal(1,intval($source->UniqueID),intval($ally->UniqueID),4)===0&&FaBSUPCount($p,'ARROW_PREVENT')===3,'Source prevention failed for an ally or consumed a hero-only shield.');
    if($p===4){$dynReset($p);$far=FaBWTRCreateArena(2,'might');$near=FaBWTRCreateArena(1,'might');$targets=explode('&',FaBSUPAuraTargets($p));$check(!in_array($ref($far),$targets,true)&&in_array($ref($near),$targets,true),'Target aura selection ignored UPF adjacency.');}
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "SUP rule outcomes passed in duels and UPF.\n";
