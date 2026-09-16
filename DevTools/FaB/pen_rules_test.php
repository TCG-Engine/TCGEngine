<?php
require_once __DIR__.'/dyn_rules_test.php';
$failures = [];
$baseDynReset=$dynReset;
$dynReset=function(int $p)use($baseDynReset){$baseDynReset($p);if($p===3){SetSeatOrder('123');SetLiveSeats('123');}};
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
foreach ([2,3,4] as $p) {
    $dynReset($p);
    $targets = explode('&', FaBPENTargets($p, true));
    $check(in_array($ref(GetHero($p)[0]), $targets, true), 'Any-hero targeting omitted self.');
    if ($p === 4) $check(!in_array($ref(GetHero(2)[0]), $targets, true), 'Single target crossed UPF adjacency.');

    $dynReset($p);
    foreach (FaBLiveSeats() as $s) AddHand($s,CardID:$s===1?'crush_confidence_red':'zap_blue');
    $macro($p,'pound_of_flesh_blue');
    foreach (FaBLiveSeats() as $s) {
        $check(count(GetDecisionQueue($s))>0, 'Pound of Flesh did not ask seat '.$s.'.');
        $answer($s,$ref(GetHand($s)[0]));
    }
    foreach (FaBLiveSeats() as $s) {
        $check(GetHealth($s)===($s===1?30:29), 'Pound of Flesh applied the wrong loss to seat '.$s.'.');
        $check(count(FaBChoiceRefs($s,'Banish'))===1, 'Pound of Flesh missed a banish.');
    }

    $dynReset($p);
    $a=$attack($p,'concoct_disorder_red');
    foreach(FaBLiveSeats() as $s) { AddDeck($s,CardID:'zap_blue'); AddArsenal($s,CardID:'zap_red'); }
    FaBPENConcoct(intval($a->UniqueID));
    foreach(FaBLiveSeats() as $s) $check(count(FaBChoiceRefs($s,'Arsenal'))===2,'Forced arsenal placement incorrectly used capacity.');
    $check(in_array('GO_AGAIN',$a->TurnEffects,true),'Concoct Disorder did not gain go again.');

    $dynReset($p);
    foreach(FaBLiveSeats() as $s) AddArsenal($s,CardID:$s===1?'zap_yellow':'zap_red',FaceDown:1);
    FaBPENOpenChests($p);
    $check(count(FaBMONArena($p,'gold'))===2,'Break Open the Chests did not find another seat yellow.');
    foreach(FaBLiveSeats() as $s) $check(GetArsenal($s)[0]->FaceDown===0,'Arsenal not revealed.');

    $dynReset($p);
    $macro($p,'cloud_cover_red');
    $check(DoDamage(1,'',$p,1,'PHYSICAL')===0,'Cloud Cover failed to prevent first event.');
    $check(DoDamage(1,'',$p,2,'PHYSICAL')===2,'Cloud Cover incorrectly carried unused prevention.');

    $dynReset($p);
    $o=AddHand($p,CardID:'submerge_red');
    $check(!CanPlayCard($p,$ref($o)),'Submerge allowed an unpayable additional cost.');
    $fifth=AddHand($p,CardID:'zap_yellow');
    foreach(['zap_red','zap_blue','zap_red','zap_blue','zap_red'] as $id)AddDeck($p,CardID:$id);
    FaBPENFifth($p,$ref($fifth));
    $check(GetDeck($p)[4]->CardID==='zap_yellow','Fifth-from-top insertion had wrong index.');

    $dynReset($p);
    $a=$attack($p,'become_the_bottle_red');$b=AddCombatChain(1,CardID:'surging_strike_red',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1);
    FaBPENCopyName(intval($a->UniqueID),$ref($b));
    $check(FaBOUTNames($a)===['Surging Strike'],'Become the Bottle retained its old name.');
    FaBPENColor(intval($a->UniqueID),3);
    $check(FaBMSTObjectColor($p,$a)===3,'Chosen color not used.');
    $o=FaBMoveUID(intval($a->UniqueID),'Graveyard',$p);
    $check(FaBMSTObjectColor($p,$o)===1&&FaBOUTNames($o)===['Become the Bottle'],'Temporary changes leaked into graveyard.');

    $dynReset($p);
    $o=FaBWTRCreateArena($p,'by_the_book_blue');AddDeck(1,CardID:'zap_red');
    DoDrawCard(1,1);$check(FaBHandCount(1)===0,'By the Book missed another hero.');
    FaBPENActionPhase($p);DoDrawCard(1,1);$check(FaBHandCount(1)===1,'By the Book persisted after action phase trigger.');

    $dynReset($p);
    $macro($p,'future_sight_blue');
    $check(count(FaBMONArena($p,'sigil_of_fate'))===1,'Future Sight token missing.');
    FaBPENActionPhase($p);
    $check(count(FaBMONArena($p,'sigil_of_fate'))===0 && count(FaBChoiceRefs($p,'Stack'))===1,'Sigil of Fate did not queue its leave trigger.');
}
foreach([2,3,4] as $p) {
    $dynReset($p);
    $bow=AddWeapons($p,CardID:'farflight_longbow',Owner:$p,Controller:$p);
    $arrow=AddHand($p,CardID:'ridge_rider_shot_red');
    $targets=FaBProfessorAttackTargets($p,intval($arrow->UniqueID));
    $check(count($targets)===$p-1,'Farflight did not reach every opposing hero.');
    $plain=AddHand($p,CardID:'crush_confidence_red');
    $check(count(FaBProfessorAttackTargets($p,intval($plain->UniqueID)))===min(2,$p-1),'Farflight leaked onto non-arrows.');

    $dynReset($p);
    $boo=FaBWTRCreateArena($p,'boo_resident_spook_yellow');$boo->Status=1;
    $check(!FaBHasKeyword($boo,'Spellvoid 2'),'Tapped Boo retained Spellvoid.');
    $boo->Status=2;$check(FaBHasKeyword($boo,'Spellvoid 2'),'Ready Boo lost Spellvoid.');
    $gloves=AddEquipment($p,CardID:'gloves_of_azure_waves',Owner:$p,Controller:$p);
    $check(!FaBHasKeyword($gloves,'Blade Break'),'Azure Waves gained Blade Break without high tide.');
    AddPitch($p,CardID:'zap_blue');AddPitch($p,CardID:'zap_blue');
    $check(FaBHasKeyword($gloves,'Blade Break'),'Azure Waves failed high tide.');
    $touch=AddEquipment($p,CardID:'touch_of_reality',Owner:$p,Controller:$p);
    $check(!FaBMSTWardActive($touch),'Unactivated Touch of Reality has ward.');
    FaBPENTouch(intval($touch->UniqueID),3);
    $check(FaBMSTWardActive($touch)&&FaBMSTWard($p,$touch)===3,'Touch of Reality ward X not active.');

    $dynReset($p);
    $vizier=AddEquipment($p,CardID:'mbrio_base_vizier',Owner:$p,Controller:$p);
    $driver=FaBWTRCreateArena($p,'hyper_driver_red');FaBSetObjectCounter($driver,'STEAM',2);
    $used=[];$choices=FaBDTDPreventionChoices($p,3,[],'ARCANE');
    $check(in_array($ref($driver),explode('&',$choices),true),'Vizier not offered for arcane damage.');
    $check(!in_array($ref($driver),explode('&',FaBDTDPreventionChoices($p,3,[],'PHYSICAL')),true),'Vizier offered for physical damage.');
    $check(FaBDTDPreventionPay($p,$ref($driver),3,$used)===1&&intval(FaBObjectCounters($driver)['STEAM'])===1,'Vizier did not spend exactly one steam.');
    $check(!in_array($ref($driver),explode('&',FaBDTDPreventionChoices($p,2,$used,'ARCANE')),true),'Vizier replacement reused within the same event.');

    $dynReset($p);
    $plate=AddEquipment($p,CardID:'solray_plating',Owner:$p,Controller:$p);$soulCard=AddSoul($p,CardID:'zap_yellow');$used=[];
    $check(FaBDTDPreventionPay($p,$ref($soulCard),2,$used)===1,'Solray did not prevent damage.');
    $check(FaBFindUID(intval($soulCard->UniqueID))['zone']==='Banish','Solray did not banish soul cost.');
    $check(!FaBMSTWardActive($plate),'Solray incorrectly gained ward.');
    FaBPENEnd();$check(FaBFindUID(intval($plate->UniqueID))['zone']==='Graveyard','Solray survived its delayed destruction.');

    $dynReset($p);
    $sword=AddWeapons($p,CardID:'dawnblade',Owner:$p,Controller:$p);
    FaBSetObjectCounter($sword,'POWER',2);FaBPENSharpen($p,$ref($sword),3);
    $check(intval(FaBObjectCounters($sword)['POWER'])===3,'Sharpen did not add counter.');
    FaBPENEnd();$check(empty(FaBObjectCounters($sword)['POWER']),'Sharpen did not remove all counters in end phase.');

    $dynReset($p);
    $wrap=AddEquipment($p,CardID:'havoc_wrap',Owner:$p,Controller:$p);FaBPENAbilityPaid($wrap);
    $check(FaBPENHavocCost()===-1&&!FaBSEACanUntap($wrap),'Havoc Wrap did not stay tapped or reduce costs globally.');
    FaBPENStart($p);$check(FaBFindUID(intval($wrap->UniqueID))['zone']==='Graveyard','Havoc Wrap survived its next start.');

    $dynReset($p);
    $crossers=AddEquipment($p,CardID:'line_crossers',Owner:$p,Controller:$p);
    $check(GetHealth(1)===GetHealth($p)&&FaBPENLifeMore($p,1)&&!FaBPENLifeMore(1,$p),'Line Crossers did not preserve asymmetric comparison at equal life.');
    $check(FaBARCLowerLife(1),'Existing lower-life rule ignored Line Crossers.');

    $dynReset($p);
    $original=GetHero($p)[0]->CardID;$health=GetHealth($p);
    FaBPENEmbody($p,'Bravo, Star of the Show');
    $check(GetHero($p)[0]->CardID==='bravo_star_of_the_show'&&GetHealth($p)===$health,'Embody changed life or failed to copy hero.');
    FaBPENStart($p);$check(GetHero($p)[0]->CardID===$original,'Embody did not expire on the controller next turn.');

    $dynReset($p);
    $a=$attack($p,'lunar_mirage_red');$def=AddCombatChain(1,CardID:'crush_confidence_red',Owner:1,Controller:1,Role:'DEFENSE',ChainLink:1);
    FaBPENLunar($def);$check($a->CardID==='crush_confidence_red','Lunar Mirage did not copy six-power defender.');
    $moved=FaBMoveUID(intval($a->UniqueID),'Graveyard',$p);$check($moved->CardID==='lunar_mirage_red','Lunar Mirage copy escaped combat chain.');

    $dynReset($p);
    $a=$attack($p,'doubling_season_red');$before=FaBAttackPower(FaBGetState());FaBTagUID(intval($a->UniqueID),'WTR_POWER:2');
    $check(FaBAttackPower(FaBGetState())===$before+3,'Doubling Season did not increase a positive power modifier.');
    FaBWTRCreateArena($p,'channel_mount_heroic_red');
    FaBWTRCreateArena($p,'channel_mount_heroic_red');
    FaBELEAdd($p,'CHAIN_POWER',1);
    FaBELEAdd($p,'CHAIN_POWER',1);
    $check(FaBELEPower($p,$a)===12,'Doubling Season did not replace each continuous power gain independently.');

    $dynReset($p);
    GetHero($p)[0]->CardID='kayo_armed_and_dangerous';
    $a=AddHand($p,CardID:'doubling_season_red',Owner:$p,Controller:$p);
    $check(FaBHVYPowerOutsideChain($p,$a)===2,'Doubling Season did not replace Kayo power gain outside the chain.');
    $a->FaceDown=true;
    $check(FaBHVYPowerOutsideChain($p,$a)===1,'Face-down Doubling Season incorrectly replaced a power gain.');

    $dynReset($p);
    $cradle=FaBWTRCreateArena($p,'channel_galcias_cradle_blue');$ally=FaBWTRCreateArena(1,'yendurai');
    FaBPENCradle(intval($cradle->UniqueID),$ref($ally));$check(FaBUPRFrozen($ally),'Cradle did not freeze target.');
    FaBMONDestroy(intval($cradle->UniqueID));$check(!FaBUPRFrozen($ally),'Cradle freeze survived its source.');

    $dynReset($p);
    $a=AddDeck($p,CardID:'zap_red');$b=AddHand($p,CardID:'zap_blue');FaBWTRAddEffect(1,'PEN_TOPSY',1);
    FaBARCToDeck($p,intval($b->UniqueID),true);$check(FaBChoiceRefs($p,'Deck')[0]===$ref($a),'Topsy Turvy did not replace top placement.');

    $dynReset($p);
    $steel=AddGraveyard($p,CardID:'smoldering_steel_red');
    FaBHVYToken($p,'frostbite',3,1);
    $check(FaBHasPendingDecision(),'Frostbite replacement was not optional.');$answer($p,$ref($steel));
    $check(count(FaBMONArena($p,'frostbite'))===0&&FaBFindUID(intval($steel->UniqueID))['zone']==='Banish','Smoldering Steel failed to replace the entire batch.');

    $dynReset($p);
    $digits=AddEquipment($p,CardID:'mbrio_base_digits',Owner:$p,Controller:$p,Status:2);$cog=FaBWTRCreateArena($p,'golden_cog');$cog->Status=2;if(GetDecisionQueue($p))$answer($p,'1');
    $check(FaBARCActivate($p,FaBFindUID(intval($digits->UniqueID)),0),'Digits activation was unavailable.');
    $answer($p,$ref($cog));$top=FaBStackTop();$check($top!==null,'Digits payment lost ability stack.');if($top)DoResolveCard($p,$ref($top));
    $check($digits->Status===1&&$cog->Status===1&&in_array('WTR_DEFENSE:1',(array)$digits->TurnEffects,true),'Digits did not pay both taps before gaining defense.');

    $dynReset($p);
    $bow=AddWeapons($p,CardID:'farflight_longbow',Owner:$p,Controller:$p,Status:2);$arrow=AddHand($p,CardID:'ridge_rider_shot_red');
    $check(FaBARCActivate($p,FaBFindUID(intval($bow->UniqueID)),0),'Farflight activation failed.');$top=FaBStackTop();if($top)DoResolveCard($p,$ref($top));$answer($p,$ref($arrow));
    $check($bow->Status===1&&FaBFindUID(intval($arrow->UniqueID))['zone']==='Arsenal'&&!FaBFindUID(intval($arrow->UniqueID))['object']->FaceDown,'Farflight missed tap or face-up arrow loading.');

    $dynReset($p);
    $a=$attack($p,'crush_confidence_red');$cost=AddHand($p,CardID:'zap_red');FaBWTRAddEffect($p,'PEN_CHEAT_LOSS',1);FaBHVYWager($p,intval($a->UniqueID),1,['gold']);
    $s=FaBGetState();$s['attackHit']=false;FaBSetState($s);FaBHVYResolveWagers($p,intval($a->UniqueID));$answer($p,$ref($cost));
    $check(count(FaBMONArena($p,'gold'))===1&&count(FaBMONArena(1,'gold'))===0&&!FaBARCEffect($p,'PEN_CHEAT_LOSS'),'Cheating Scoundrel failed to replace wager loss.');

    $dynReset($p);
    $fealty=FaBWTRCreateArena($p,'fealty');AddEquipment($p,CardID:'dynastic_diadem',Owner:$p,Controller:$p);
    FaBPENDestroy(1,intval($fealty->UniqueID));$check(FaBFindUID(intval($fealty->UniqueID))['zone']==='Arena','Diadem failed to protect Fealty from another hero.');
    FaBPENDestroy($p,intval($fealty->UniqueID));$check(!FaBMONArena($p,'fealty'),'Diadem prevented its controller from destroying Fealty.');
}
// The affected hero chooses among simultaneous replacement effects in UPF.
$dynReset(4);$v2=AddEquipment(2,CardID:'vestige_of_flagellation',Owner:2,Controller:2);$v3=AddEquipment(3,CardID:'vestige_of_flagellation',Owner:3,Controller:3);
FaBCRUGainLife(1,2);$check(count(GetDecisionQueue(1))>0,'Multiple Vestiges did not offer a replacement choice.');$answer(1,$ref($v3));
$check(GetHealth(1)===30&&GetHealth(2)===30&&GetHealth(3)===28&&count(FaBMONArena(3,'vigor'))===2,'Vestige affected the wrong UPF hero.');
$dynReset(4);$remote=FaBWTRCreateArena(2,'runechant');$near=FaBWTRCreateArena(1,'runechant');$grave=AddGraveyard(2,CardID:'zap_yellow');
$check(!in_array($ref($remote),explode('&',FaBPENPermanents(4,'Aura')),true)&&in_array($ref($near),explode('&',FaBPENPermanents(4,'Aura')),true),'Permanent targets ignored adjacency.');
$check(!in_array($ref($grave),explode('&',FaBPENOpposingGraves(4,'Yellow')),true),'Graveyard targets ignored adjacency.');
$check(in_array($ref($remote),explode('&',FaBPENPermanents(4,'Aura',true,false,false)),true),'Untargeted destruction incorrectly applied adjacency.');
$bow=AddWeapons(4,CardID:'farflight_longbow',Owner:4,Controller:4);$arrow=AddHand(4,CardID:'ridge_rider_shot_red');$s=FaBGetState();$s['combatOpen']=true;$s['defender']=2;FaBSetState($s);
$targets=FaBProfessorAttackTargets(4,intval($arrow->UniqueID));$check(count($targets)===1&&$targets[0]['player']===2,'Farflight bypassed combat-chain focus.');
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "PEN targeted rules passed in duels and UPF.\n";
