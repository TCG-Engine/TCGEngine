<?php
// Root runs only after reviewed two-file application. No game/bootstrap includes.
declare(strict_types=1);
$repo=$argv[1]??'C:/xampp/htdocs/TCGEngine';
$mst=$repo.'/FaBSim/Custom/MSTCards.php';$dyn=$repo.'/FaBSim/Custom/DYNRuntime.php';
$dtd=$repo.'/FaBSim/Custom/DTDCards.php';$game=$repo.'/FaBSim/Custom/GameLogic.php';
$arc=$repo.'/FaBSim/Custom/ARCCards.php';$wtr=$repo.'/FaBSim/Custom/WTRCards.php';
$componentCatalogFile=$repo.'/FaBSim/GeneratedCode/cardArrayCache.json';
$expected=[[$mst,'42f119459e4aec25ccba37a43a6891b5ba123195a23e58402bdd2723948b9a45'],
 [$dyn,'85e28425ebd6a99541d5984041e14d573a59109de1097b21cafb2de8e8b82be7'],
 [$dtd,'059938626cc5e4e1675d231f9bb0f689a22ef70c7be5748224b2f13eedb7bddd'],
 [$game,'e37edf98374de00c1d1adb7f7884496bc053dd1eec4bf8882bee6c4a075d1b0e'],
 [$arc,'5bde46f342529508e33d983cc4a2475debbe2cfb2ba05a6c78a00da2f861672b'],
 [$wtr,'2b8c672f47413d475c8cc23fbbc3530c60b2e7f0f41f5bba8e52ffc5166a3ea4'],
 [$componentCatalogFile,'30baa146eef4535a3142554d86cb891f04542aba5e3a666e3e2d54f038c0c8d3']];
foreach($expected as [$file,$sha])if(!hash_equals($sha,hash_file('sha256',$file)))throw new RuntimeException('Exact reviewed source hash required: '.$file);
$extractor=$repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
if(hash_file('sha256',$extractor)!=='ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199')throw new RuntimeException('Extractor drift.');
require $extractor;
$GLOBALS['component_checks']=0;$GLOBALS['component_function_hashes']=[];$GLOBALS['catalog']=[];
foreach(json_decode(file_get_contents($componentCatalogFile),true,512,JSON_THROW_ON_ERROR)['cardArray'] as $c)$GLOBALS['catalog'][$c['id']]=$c;
foreach(['FaBMSTWard','FaBMSTWardActive','FaBMSTWardRefs','FaBMSTHidden'] as $fn)ComponentLoadFunction($mst,$fn);
ComponentLoadFunction($dyn,'FaBDYNWard');
foreach(['FaBDTDPreventionChoices','FaBDTDPreventionPay'] as $fn)ComponentLoadFunction($dtd,$fn);
foreach(['HasNoAbilities','FaBObjectCounters','FaBHasType'] as $fn)ComponentLoadFunction($game,$fn);
foreach(['FaBARCCard','FaBARCSetCard'] as $fn)ComponentLoadFunction($arc,$fn);
ComponentLoadFunction($wtr,'FaBWTRBase');

// Explicit controlled boundaries: in-memory board/state, data access, unrelated
// suppression and prevention families, blue/holo conditions and physical destroy.
// No serialized fixture, priority, native resource payment or EOT cleanup claim.
function FaBGetState():array{return $GLOBALS['s'];}
function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function GetTurnNumber():int{return $GLOBALS['turn'];}
function GetTurnPlayer():int{return $GLOBALS['turn_player'];}
function GetHealth(int $p):int{return 20;}
function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2,3,4],true);}
function CardFunctional_text_plain(string $id):string{return $GLOBALS['catalog'][$id]['functional_text_plain'];}
function CardPitch(string $id):string{return $GLOBALS['catalog'][$id]['pitch'];}
function FaBTypes($value):array{return $GLOBALS['catalog'][is_object($value)?$value->CardID:(string)$value]['types'];}
function EffectiveCardType($value):array{return FaBTypes($value);}
function FaBMPGSuppressed($o):bool{return false;}
function FaBSUPSuppressed($o):bool{return false;}
function FaBWTRHeroActive(int $p):bool{return true;}
function FaBMSTBlue(int $p):int{return $GLOBALS['blue'];}
function FaBOMNHolo(object $o):bool{return $GLOBALS['holo'];}
function FaBChoiceRefs(int $p,string $zone,array $filters=[]):array{
 $out=[];$index=0;
 foreach($GLOBALS['board'] as $f)if($f['player']===$p&&$f['zone']===$zone){$out[]='p'.$p.$zone.'-'.$index++;}
 return $out;
}
function FaBIdentityFromMZ(string $r):?array{
 if(!preg_match('/^p([1-4])(Arena|Equipment)-([0-9]+)$/',$r,$m))return null;
 $p=intval($m[1]);$zone=$m[2];$index=intval($m[3]);$rows=array_values(array_filter($GLOBALS['board'],fn($f)=>$f['player']===$p&&$f['zone']===$zone));
 return isset($rows[$index])?array_merge($rows[$index],['mzID'=>$r]):null;
}
function FaBCRUEquipment(int $p,string $id):array{return array_values(array_filter(FaBChoiceRefs($p,'Equipment'),fn($r)=>FaBIdentityFromMZ($r)['object']->CardID===$id));}
function FaBMONDestroy(int $uid):void{
 if(!isset($GLOBALS['board'][$uid]))throw new RuntimeException('Destroy boundary received missing UID.');
 $GLOBALS['destroyed'][]=$uid;unset($GLOBALS['board'][$uid]);
}
function FaBROSShelters(int $p):array{return [];}
function FaBDTDForcefield(int $p,array $used=[]):bool{return false;}
function FaBDTDForcefieldUID(int $p,array $used=[]):int{return 0;}
function FaBDTDCount(int $p,string $key):int{return 0;}
function FaBDTDClear(int $p,string $key):void{}
function FaBPENPreventionChoices(int $p,string $type,array $used=[]):array{return [];}
function FaBPENPreventionPay(int $p,string $ref,array &$used=[]):int{return 0;}
function FaBIARShadowResistRefs(int $p,int $source=0):array{return [];}
function FaBIARShadowResistPay(int $p,string $ref,int $source=0):int{return 0;}
function Seed(string $id='empyrean_rapture',string $zone='Equipment'):object{
 if(!isset($GLOBALS['catalog'][$id]))throw new RuntimeException('Unknown actual card fixture data: '.$id);
 $GLOBALS['turn']=2;$GLOBALS['turn_player']=1;$GLOBALS['blue']=0;$GLOBALS['holo']=false;$GLOBALS['s']=['arcCards'=>[]];$GLOBALS['destroyed']=[];
 $o=(object)['UniqueID'=>5,'CardID'=>$id,'Owner'=>4,'Controller'=>4,'Location'=>$zone,'Counters'=>[],'TurnEffects'=>[],'FaceDown'=>0,'Status'=>2];
 $GLOBALS['board']=[5=>['player'=>4,'zone'=>$zone,'object'=>$o]];return $o;
}
function PayWard():int{$used=[];return FaBDTDPreventionPay(4,'p4Equipment-0',3,$used,99);}
$tests=[];
function CaseOf(string $name,callable $fn):void{Seed();$fn();$GLOBALS['tests'][]=$name;}

CaseOf('unactivated_rapture_has_no_intrinsic_ward',function(){
 $o=$GLOBALS['board'][5]['object'];
 ComponentCheck($GLOBALS['catalog'][$o->CardID]['card_keywords']===[],'Actual card data distinguishes printed own keywords from the activation effect.');
 ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Inactive Rapture has no Ward despite the word in its activation text.');
 ComponentCheck(FaBMSTWardRefs(4)===[]&&FaBDTDPreventionChoices(4,3,[],'ARCANE',99)==='','No interactive Ward offer before activation.');
 ComponentCheck(PayWard()===0&&$GLOBALS['destroyed']===[],'Invalid inactive Ward payment cannot destroy or prevent.');
 ComponentCheck(FaBDYNWard(4,3)===3&&$GLOBALS['destroyed']===[],'Direct damage does not destroy inactive Rapture or prevent damage.');
});
CaseOf('resolved_grant_uses_shared_interactive_ward',function(){
 $o=$GLOBALS['board'][5]['object'];$o->TurnEffects=['DTD_WARD:1'];
 ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===1,'The actual resolved activation tag grants Ward1.');
 ComponentCheck(FaBMSTWardRefs(4)===['p4Equipment-0']&&FaBDTDPreventionChoices(4,3,[],'ARCANE',99)==='p4Equipment-0','Interactive consumer offers the active Ward object exactly once.');
 ComponentCheck(PayWard()===1&&$GLOBALS['destroyed']===[5]&&FaBMSTWardRefs(4)===[],'Interactive pay returns exact1 and destroys the physical UID once.');
});
CaseOf('direct_and_unpreventable_damage_use_same_grant',function(){
 $o=$GLOBALS['board'][5]['object'];$o->TurnEffects=['DTD_WARD:1'];
 ComponentCheck(FaBDYNWard(4,3)===2&&$GLOBALS['destroyed']===[5],'Direct damage prevents1 and destroys activated Rapture exactly once.');
 $o=Seed();$o->TurnEffects=['DTD_WARD:1'];
 ComponentCheck(FaBDYNWard(4,3,true)===3&&$GLOBALS['destroyed']===[5],'Unpreventable damage destroys active Ward but prevents zero.');
 Seed();ComponentCheck(FaBDYNWard(4,3,true)===3&&$GLOBALS['destroyed']===[],'Unpreventable damage does not destroy unactivated Rapture.');
});
CaseOf('suppression_blocks_both_shared_and_legacy_consumers',function(){
 $o=$GLOBALS['board'][5]['object'];$o->TurnEffects=['DTD_WARD:1','NO_ABILITIES'];
 ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Real NO_ABILITIES suppresses the gained Ward.');
 ComponentCheck(FaBDTDPreventionChoices(4,3)===''&&FaBDYNWard(4,3)===3&&$GLOBALS['destroyed']===[],'No obsolete direct-damage fallback bypasses source suppression.');
 $o=Seed();$o->TurnEffects=['DTD_WARD:1'];$o->Counters=['_overrides'=>['NO_ABILITIES'=>1]];
 ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0&&FaBDYNWard(4,3)===3,'Counter override suppression is retained.');
 $o=Seed();$o->TurnEffects=['DTD_WARD:1'];$o->FaceDown=1;
 ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0&&FaBDYNWard(4,3)===3,'Existing actual hidden-equipment suppression is retained.');
});
CaseOf('tag_removal_not_activation_counter_controls_presence',function(){
 $o=$GLOBALS['board'][5]['object'];$o->Counters=['ARC_USED_0'=>2];
 ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Once-used metadata alone is not a Ward grant.');
 $o->TurnEffects=['DTD_WARD:1'];ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===1,'Resolved tag activates Ward.');
 $o->TurnEffects=[];ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Removing the grant restores no Ward; actual EOT cleanup is not simulated.');
});
CaseOf('existing_current_pen_ward_precedence_and_expiry',function(){
 $o=$GLOBALS['board'][5]['object'];FaBARCSetCard(5,'penWard',['turn'=>2,'amount'=>3]);
 ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===3,'An existing current-turn explicit penWard grant remains effective.');
 $o->TurnEffects=['DTD_WARD:1'];ComponentCheck(FaBMSTWard(4,$o)===3,'Existing penWard amount precedence is unchanged.');
 FaBARCSetCard(5,'penWard',['turn'=>1,'amount'=>3]);$o->TurnEffects=[];ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'An expired explicit grant is not active.');
 $o->TurnEffects=['DTD_WARD:1'];ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===1,'Stale penWard does not mask a live resolved tag.');
});
CaseOf('printed_static_and_zero_value_ward_preserved',function(){
 $o=Seed('spectral_shield','Arena');ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===1,'Printed static Ward1 is unchanged.');
 ComponentCheck(FaBDYNWard(4,3)===2&&$GLOBALS['destroyed']===[5],'Static Ward still prevents and destroys through the real direct consumer.');
 $o=Seed('manifestation_of_miragai_blue','Arena');$o->Counters=['POWER'=>0];
 ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Ward presence is distinct from its zero amount.');
 ComponentCheck(FaBDYNWard(4,3)===3&&$GLOBALS['destroyed']===[5],'Zero-valued Ward still destroys without prevention; loop terminates.');
});
CaseOf('named_variable_ward_values_unchanged',function(){
 $o=Seed('haze_shelter_red','Arena');ComponentCheck(FaBMSTWard(4,$o)===1,'Haze Red existing no-blue amount retained.');
 $GLOBALS['blue']=1;ComponentCheck(FaBMSTWard(4,$o)===4,'Haze Red existing blue amount retained.');
 $o=Seed('three_visits_red','Arena');$GLOBALS['blue']=2;ComponentCheck(FaBMSTWard(4,$o)===6,'Existing three-times-blue calculation retained.');
 $o=Seed('rage_specter_blue','Arena');ComponentCheck(FaBMSTWard(4,$o)===1,'Existing off-turn Rage amount retained.');$GLOBALS['turn_player']=4;ComponentCheck(FaBMSTWard(4,$o)===6,'Existing own-turn Rage amount retained.');
 $o=Seed('holo_shield_red','Arena');ComponentCheck(FaBMSTWard(4,$o)===1,'Existing unholo amount retained.');$GLOBALS['holo']=true;ComponentCheck(FaBMSTWard(4,$o)===4,'Existing holo/pitch amount retained.');
});
CaseOf('existing_meridian_and_touch_grants_retained',function(){
 $o=Seed('meridian_pathway');$o->TurnEffects=['MST_WARD3'];ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===3,'Existing named MST_WARD3 grant retained.');
 $o=Seed('touch_of_reality');ComponentCheck(!FaBMSTWardActive($o),'Existing unactivated Touch guard retained.');
 FaBARCSetCard(5,'penWard',['turn'=>2,'amount'=>4]);FaBARCSetCard(5,'penTouchTurn',2);ComponentCheck(FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===4,'Existing active Touch current-turn grant retained.');
 FaBARCSetCard(5,'penWard',['turn'=>1,'amount'=>4]);FaBARCSetCard(5,'penTouchTurn',1);ComponentCheck(!FaBMSTWardActive($o)&&FaBMSTWard(4,$o)===0,'Existing Touch expiry metadata retained.');
});
CaseOf('legacy_mini_forcefield_is_not_changed',function(){
 $o=Seed('mini_forcefield_red','Arena');$o->Counters=['STEAM'=>2];
 ComponentCheck(FaBDYNWard(4,3)===1&&$GLOBALS['destroyed']===[5],'Existing special mini-forcefield direct branch remains intact.');
});
echo json_encode(['status'=>'PASS','groups'=>count($tests),'checks'=>$GLOBALS['component_checks'],'tests'=>$tests,'function_hashes'=>$GLOBALS['component_function_hashes'],
 'scope'=>'Exact actual MST Ward presence/value/refs/hidden, DYN direct consumer, DTD choice/pay consumers, real HasNoAbilities/counters/type predicate, ARC per-UID state and WTR base helper extracted from guarded source. Board/state/type metadata and unrelated suppression/prevention/blue/holo/destroy boundaries are controlled. No game/bootstrap imports or persistent writes. Actual four-live activation/payment/response window/source movement remains separate native proof.',
 'limits'=>['No generic Ward parser or catalog migration; non-Rapture conditional printed-text matching is deliberately unchanged and not broadly certified.','Component tag removal proves metadata interpretation only; actual end-turn cleanup is not executed.','Variable-case checks preserve existing code values with controlled blue/holo inputs, not native history/branch coverage.','Destruction double records exact physical UID/removal; native recorder witnesses separately prove public zones/ownership/serialization.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
