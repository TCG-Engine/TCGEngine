<?php
// Preserve the originating hero across decision-queue continuations. Modesty
// suppresses creation, while the rest of that hero ability still resolves.
function FaBPrepareCapturedDamageSourceCode(string $code,string $cardID): string {
 if(!in_array($cardID,['bloodrot_pox','burn_them_all_red'],true))return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if($cardID==='bloodrot_pox'&&hash_equals('181ded38ed21f427bb81331a910b94b46ca40bb57d746755b60d5abb63ab58b9',$hash))return $code;
 if($cardID==='bloodrot_pox'&&hash_equals('567b8f774c0aa100da68f65cb944247b21266ed9a1dbe35cfd4d8eece4560aab',$hash))return $code;
 if($cardID==='burn_them_all_red'&&hash_equals('94c7309801f627765a9f3d8f7b987fab46b797031209afb66d4a533b815786a1',$hash))return $code;
 if($cardID==='bloodrot_pox'&&hash_equals('27209c1d4b3f765df3b674bdb3dddda2a61743c686470d37e18e24e5ab3fab91',$hash))return <<<'CAPTURED_BLOODROT_POX_RESOLVEABILITY'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$poxDamageSource = FaBCaptureDamageSourceRecord($uid, 'pox', $player);
FaBMONDestroy($uid);
$poxDamageSource['departed'] = FaBFindUID($uid) === null;
$paid = false;
 if (FaBAvailablePitch($player) >= 3) {
 $mode = await $player.Modal(1, 1, "Decline&Pay_three_to_avoid_damage", "Optional_payment");
 if ($mode === '1') {
 while (intval(GetResources($player)) < 3) {
 $refs = FaBARCPitchChoices($player);
 $chosen = 'PASS';
 if ($refs !== '') {
 $chosen = await $player.MZChoose($refs, "Pitch_to_pay");
 
}
FaBARCPitchForEffect($player, $chosen);
 
}
 AddResources($player, intval(GetResources($player)) - 3);
 $paid = true;
 
}
 
}
if (!$paid) {
 FaBCapturedPoxDamage($player, $poxDamageSource, $player, 2, 'GENERIC');
 
}

CAPTURED_BLOODROT_POX_RESOLVEABILITY;
 if($cardID==='bloodrot_pox'&&hash_equals('320598fab0e25e6208e0d0bdd31470925c327e43c2f96e579db36932ff2f1c1a',$hash))return <<<'CAPTURED_BLOODROT_POX_ENDTURN'
$refs = FaBOUTEndTargets($player);
 while ($refs !== '') {
 $refs = FaBOUTEndTargets($player);
 $chosen = 'PASS';
 if ($refs !== '') {
 $chosen = await $player.MZChoose($refs, "Choose_end_phase_trigger");
 
}
$uid = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);
 $token = FaBIdentityFromMZ($chosen)['object']->CardID;
 $poxDamageSource = FaBCaptureDamageSourceRecord($uid, 'pox', $player);
FaBMONDestroy($uid);
$poxDamageSource['departed'] = FaBFindUID($uid) === null;
 if ($token === 'ponder') {
 DoDrawCard($player, 1);
 
}
 if ($token === 'bloodrot_pox') {
 $paid = false;
 if (FaBAvailablePitch($player) >= 3) {
 $mode = await $player.Modal(1, 1, "Decline&Pay_three_to_avoid_damage", "Optional_payment");
 if ($mode === '1') {
 while (intval(GetResources($player)) < 3) {
 $refs = FaBARCPitchChoices($player);
 $chosen = 'PASS';
 if ($refs !== '') {
 $chosen = await $player.MZChoose($refs, "Pitch_to_pay");
 
}
FaBARCPitchForEffect($player, $chosen);
 
}
 AddResources($player, intval(GetResources($player)) - 3);
 $paid = true;
 
}
 
}
if (!$paid) {
 FaBCapturedPoxDamage($player, $poxDamageSource, $player, 2, 'GENERIC');
 
}
 
}
 if ($token === 'inertia') {
 $uids = FaBOUTInertiaStage($player);
 if (count($uids) > 0) {
 $param = FaBARCOrderParam($uids, 'Bottom');
 $order = await $player.Rearrange($param);
 FaBARCFinishOrder($player, $uids, $order);
 
}
 
}
 $refs = FaBOUTEndTargets($player);
 
}

CAPTURED_BLOODROT_POX_ENDTURN;
 if($cardID==='burn_them_all_red'&&hash_equals('179d6f6044483a67e999358bf4517902e9f9894cef2a3e883a3300ce4fbcddc6',$hash))return <<<'CAPTURED_BURN_THEM_ALL_RED_RESOLVEABILITY'
$uid = FaBCapturedBurnSourceUID($player, $mzID);
$targets = FaBOpponents($player);
 for ($i = 0; $i < count($targets); ++$i) {
 $targetUID = FaBUPRHeroUID($targets[$i]);
 $packet = 1;
 $packetTarget = $targetUID;
 $packetSource = $uid;
 $f = FaBFindUID($packetTarget);
 $dealt = 0;
 if ($f !== null && $packet > 0) {
 $victim = intval($f['player']);
 $isHero = $f['zone'] === 'Hero';
 $packet = FaBCapturedBurnDamageBonus($player, $mzID, $packetSource, $packet, 'ARCANE' );
 $used = [];
 if ($isHero) {
 $barriers = FaBUPRBarrierChoices($victim, $used, $packetSource, $mzID);
 while ($packet > 0 && $barriers !== '') {
 $barrier = await $victim.MZMayChoose($barriers, "Choose_arcane_barrier");
 if ($barrier === 'PASS') {
 break;
 
}
 $barrierUID = intval(FaBIdentityFromMZ($barrier)['object']->UniqueID);
 $cost = FaBUPRBarrierValue($barrier);
 while (intval(GetResources($victim)) < $cost) {
 $pitchRefs = FaBARCPitchChoices($victim);
 $pitched = await $victim.MZChoose($pitchRefs, "Pitch_for_arcane_barrier");
 FaBARCPitchForEffect($victim, $pitched);
 
}
 AddResources($victim, intval(GetResources($victim)) - $cost);
 $prevented = min($packet, $cost);
 $packet = max(0, $packet - $cost);
 $used[] = $barrierUID;
 if (FaBUPRAlluvionReady($barrierUID, $prevented)) {
 $mode = await $victim.Modal(1, 1, "Add_energy&Decline", "Alluvion_Constellas");
 FaBUPRAlluvion($barrierUID, $mode === '0');
 
}
 $barriers = FaBUPRBarrierChoices($victim, $used, $packetSource, $mzID);
 
}
 $voidRefs = FaBMONSpellvoidRefs($victim);
 while ($packet > 0 && $voidRefs !== '' && !FaBUPRUnpreventable($packetSource, $mzID)) {
 $void = await $victim.MZMayChoose($voidRefs, "Destroy_Spellvoid");
 if ($void === 'PASS') {
 break;
 
}
 $packet = max(0, $packet - FaBMONSpellvoid($victim, $void));
 $voidRefs = FaBMONSpellvoidRefs($victim);
 
}
 $usedQuell = [];
 $quell = FaBUPRQuellChoices($victim, $usedQuell);
 while ($packet > 0 && $quell !== '' && !FaBUPRUnpreventable($packetSource, $mzID)) {
 $item = await $victim.MZMayChoose($quell, "Quell_damage");
 if ($item === 'PASS') {
 break;
 
}
 $usedQuell[] = intval(FaBIdentityFromMZ($item)['object']->UniqueID);
 while (intval(GetResources($victim)) < 1) {
 $pitchRefs = FaBARCPitchChoices($victim);
 $pitched = await $victim.MZChoose($pitchRefs, "Pitch_for_Quell");
 FaBARCPitchForEffect($victim, $pitched);
 
}
 $packet = max(0, $packet - FaBUPRQuell($victim, $item));
 $quell = FaBUPRQuellChoices($victim, $usedQuell);
 
}
 
}
 $dealt = FaBCapturedBurnDeal($player, $mzID, $packetSource, $packetTarget, $packet, 'ARCANE');
 
}
 
}

CAPTURED_BURN_THEM_ALL_RED_RESOLVEABILITY;
 if($cardID==='burn_them_all_red'&&hash_equals('ec0e0902187f6eed7d65fba92bf83a037c07e640d8769dc02854bf36c2a7bcd7',$hash))return $code;
 throw new RuntimeException('Captured Pox/Burn authored source changed.');
}

function FaBPrepareHighRollerCode(string $code,string $cardID): string {
 if(!in_array($cardID,['high_roller_red','high_roller_yellow','high_roller_blue'],true))return $code;
 $hash=hash('sha256',trim($code));
 if(in_array($hash,['429ba36ab928863a5169f0af83cab436f4fce9864de27d5e48e4e3c82858cfaf', '116bdfd44c079501c56b1e651f6c444f7513a66dbb66e674a327a99c53ac3558'],true))return $code;
 if(!hash_equals('ef42e422ec261f1998db026874aff65aa5f7b300a5e38cd82466c4889b6e1369',$hash))throw new RuntimeException('High Roller source changed; review before regeneration.');
 return <<<'HIGH_ROLLER_CAPTURED_RESOLVE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
 FaBEVRResolveHighRoller($player, $uid);
HIGH_ROLLER_CAPTURED_RESOLVE;
}

// Resolution happens while this Instant is still on the stack (CR 5.3.4-7).
// Remove only the old, exact self-in-graveyard compensation for this cycle.
function FaBPrepareCountYourBlessingsCode(string $code,string $cardID): string {
 $base=['count_your_blessings_red'=>3,'count_your_blessings_yellow'=>2,'count_your_blessings_blue'=>1][$cardID]??null;
 if($base===null)return $code;
 $current="FaBCRUGainLife(\$player, ".$base." + count(FaBChoiceRefs(\$player, 'Graveyard', ['base'=>'count_your_blessings'])));";
 $legacy=substr($current,0,-2).' - 1);';
 $normalized=trim(str_replace("\r\n","\n",$code));
 if($normalized===$current)return $code;
 if($normalized!==$legacy)throw new RuntimeException('Count Your Blessings source changed; review before regeneration.');
 return $current."\n";
}

/** Exact old Snap Shot play-time body only; its WHEN is owned by native Attack. */
function FaBPrepareSnapShotAttackCode(string $code,string $cardID): string {
 if(!in_array($cardID,['snap_shot_red','snap_shot_yellow','snap_shot_blue'],true))return $code;
 $normal=trim(str_replace("\r\n","\n",$code));
 if(hash_equals('653c7bce2feb0b7b0bc8fcd3b62f26f45c81193b5dd5cdac84eb56f5e8a4d7ca',hash('sha256',$normal)))return '/* Attack trigger is queued by FaBELEAttack. */'."\n";
 if(str_contains($normal,"'SNAP'"))throw new RuntimeException('Snap Shot legacy grant source changed; review before regeneration.');
 return $code;
}

function FaBPrepareHeroCreationCode(string $code,string $cardID): string {
 $code=FaBPrepareSurayaEarnedAttackCode($code,$cardID);
 $code=FaBPrepareEmergingEntryCode($code,$cardID);
 $code=FaBPrepareSnapShotAttackCode($code,$cardID);
 $code=FaBPrepareCountYourBlessingsCode($code,$cardID);
 $code=FaBPrepareHighRollerCode($code,$cardID);
 $code=FaBPrepareReadEndCode($code,$cardID);
 $code=FaBPrepareCapturedTearCode($code,$cardID);
 $code=FaBPrepareBloodrotGenericDamageCode($code,$cardID);
 $code=FaBPrepareCapturedDamageSourceCode($code,$cardID);
 $code=FaBPrepareBloodtornCostCode($code,$cardID);
 $code=FaBPrepareDejaVuPitchCode($code,$cardID);
 $code=FaBPrepareDeclaredEffectCostCode($code,$cardID);
 $code=FaBPrepareHaloResolutionCode($code,$cardID);
 $code=FaBPrepareSpreadingPlagueOwnerCode($code,$cardID);
 $code=FaBPrepareSmashingCode($code,$cardID);
 $code=FaBPrepareBIOSItemCode($code,$cardID);
 $code=FaBPrepareMillenniumEntryCode($code,$cardID);
 $code=FaBPrepareOverLoopHitCode($code,$cardID);
 $code=FaBPrepareQuantumProcessorItemCode($code,$cardID);
 $code=FaBPrepareIlluminateHitCode($code,$cardID);
 $code=FaBPrepareSurfaceEntryCode($code,$cardID);
 $code=FaBPrepareVolatileFluxorPowerCode($code,$cardID);
 $code=FaBPrepareFigmentEruditionEntryCode($code,$cardID);
 $code=FaBPrepareCaptainModeCode($code,$cardID);
 $code=FaBPrepareTipTargetCode($code,$cardID);
 $code=FaBPrepareMemorialTargetCode($code,$cardID);
 $code=FaBPrepareEbonResolutionCode($code,$cardID);
 $code=FaBPrepareAetherslingTargetCode($code,$cardID);
 $code=FaBPrepareArcanicTriggerCode($code,$cardID);
 $code=FaBPrepareEbbingFragmentCode($code,$cardID);
 $code=FaBPrepareGenisFrosthavenOwnerCode($code,$cardID);
 $code=FaBPrepareAssemblyItemEntryCode($code,$cardID);
 // Local, idempotent corrections run before the hero-only preprocessing below.
 // Boulderhold applies to the next Guardian attack action from any legal play zone.
 // Keep the shared Arsenal-only consumer for other cards and remote authored data intact.
 if($cardID==='gauntlet_of_boulderhold')$code=str_replace(
  "FaBMPGNext(\$mpgPlayer, 2, '', true, true);",
  "FaBMPGNext(\$mpgPlayer, 2, '', false, true);",$code);
 if($cardID==='flying_high_blue')$code=str_replace("'SEA_RED_POWER'","'SEA_BLUE_POWER'",$code);
 if($cardID==='flying_high_yellow')$code=str_replace("'SEA_RED_POWER'","'SEA_YELLOW_POWER'",$code);
 // Preserve an explicitly supplied creator-owner across Smoldering's serialized choice.
 // Remote authored code remains unchanged; this local activation transform is idempotent.
 if(in_array($cardID,['smoldering_scales','smoldering_steel_red'],true)&&!str_contains($code,'penFrostOwner')){
  $code=str_replace('$creator = intval(DecisionQueueController::GetVariable("penFrostCreator"));',
   '$creator = intval(DecisionQueueController::GetVariable("penFrostCreator"));' . "\n " . '$owner = DecisionQueueController::GetVariable("penFrostOwner"); $owner = is_numeric($owner) ? intval($owner) : null;',$code);
  $code=str_replace('FaBPENFinishFrost($player, $chosen, $number, $creator, $action, $wager);',
   'FaBPENFinishFrost($player, $chosen, $number, $creator, $action, $wager, $owner);',$code);
 }
 static $heroes=null;
 if($heroes===null){$heroes=[];$catalog=json_decode(file_get_contents(__DIR__.'/../GeneratedCode/cardArrayCache.json'),true);foreach($catalog['cardArray']??[] as $c)if(in_array('Hero',$c['types']??[],true))$heroes[$c['id']]=true;}
 if(!isset($heroes[$cardID]))return $code;
 $helpers=['FaBHVYToken','FaBWTRCreateArena','FaBARCCreateRunes','FaBMSTTiger','FaBMSTShield','FaBEVOCreateDriver','FaBSEACreateCard'];
 $tokens=token_get_all('<?php '.$code);array_shift($tokens);$out='';
 for($i=0;$i<count($tokens);$i++){$t=$tokens[$i];if(is_array($t)&&$t[0]===T_STRING&&in_array($t[1],$helpers,true)){$j=$i+1;while(isset($tokens[$j])&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)$j++;if(($tokens[$j]??null)==='('){$out.='FaBSEAHeroCreate('.var_export($t[1],true).', ';$i=$j;continue;}}$out.=is_array($t)?$t[1]:$t;}
 return $out;
}
// Add the common, interactive prevention window before damage in saved FaB abilities.
// Other roots retain their authored code verbatim. Calls nested inside expressions
// are deliberately not rewritten; supported calls are standalone/assigned statements.
function FaBPrepareDamageCode(string $code): string {
 $code=FaBPrepareOMNBarrierCode($code);
 $code=FaBPreparePENDestroyCode($code);
 $code=FaBPreparePENLifeCode($code);
 $code=FaBPrepareSUPClashCode($code);
 $tokens=token_get_all('<?php '.$code);array_shift($tokens);$offsets=[];$offset=0;foreach($tokens as $t){$offsets[]=$offset;$offset+=strlen(is_array($t)?$t[1]:$t);}
 $edits=[];$names=['DoDamage','FaBARCDealArcane','FaBELEDealArcane','FaBUPRDeal','FaBMONArcanicDealArcane','FaBCapturedPoxDamage','FaBCapturedBurnDeal'];
 for($i=0;$i<count($tokens);++$i){$t=$tokens[$i];if(!is_array($t)||$t[0]!==T_STRING||!in_array($t[1],$names,true))continue;
  $start=$i;$j=$i-1;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;
  if($j>=0&&$tokens[$j]==='='){$j--;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;if($j<0||!is_array($tokens[$j])||$tokens[$j][0]!==T_VARIABLE)continue;$start=$j;$j--;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;}
  if($j>=0&&!in_array($tokens[$j],[';','{','}'],true))continue;
  $open=$i+1;while($open<count($tokens)&&is_array($tokens[$open])&&$tokens[$open][0]===T_WHITESPACE)++$open;if(($tokens[$open]??null)!=='(')continue;
  $args=[];$arg='';$depth=1;$end=$open+1;
  for(;$end<count($tokens);++$end){$t2=$tokens[$end];$text=is_array($t2)?$t2[1]:$t2;if(!is_array($t2)){if(in_array($t2,['(','[','{'],true))++$depth;if(in_array($t2,[')',']','}'],true))--$depth;if($depth===0){$args[]=trim($arg);break;}if($t2===','&&$depth===1){$args[]=trim($arg);$arg='';continue;}}$arg.=$text;}
  $semi=$end+1;while($semi<count($tokens)&&is_array($tokens[$semi])&&$tokens[$semi][0]===T_WHITESPACE)++$semi;if(($tokens[$semi]??null)!==';')continue;
  $name=$tokens[$i][1];$actor=$args[0];$type="'ARCANE'";$sourceRef='';
  if($name==='FaBCapturedPoxDamage'){$source="intval((".$args[1].")[\"uid\"] ?? 0)";$sourceRef="FaBCapturedPoxSourceRef(intval(".$actor."), ".$args[1].")";$victim=$args[2];$amount=$args[3];$type=$args[4]??"'GENERIC'";}
  elseif($name==='FaBCapturedBurnDeal'){$source=$args[2];$sourceRef=$args[1];$victim="intval(FaBFindUID(".$args[3].")[\"player\"] ?? 0)";$amount=$args[4];$type=$args[5]??"'ARCANE'";}
  elseif($name==='DoDamage'){$source="intval(FaBIdentityFromMZ(".$args[1].")[\"object\"]->UniqueID ?? 0)";$victim=$args[2];$amount=$args[3];$type=$args[4]??"'PHYSICAL'";}
  elseif($name==='FaBUPRDeal'){$source=$args[1];$victim="intval(FaBFindUID(".$args[2].")[\"player\"] ?? 0)";$amount=$args[3];$type=$args[4]??"'ARCANE'";}
  elseif($name==='FaBMONArcanicDealArcane'){$source="FaBMONArcanicDamageSourceUID(intval(".$actor."), (string)DecisionQueueController::GetVariable(\"mzID\"))";$victim=$args[1];$amount='max(0, ('.$args[2].') - ('.($args[3]??'0').'))';}
  else{$source=$name==='FaBELEDealArcane'?($args[4]??'0'):"intval(FaBIdentityFromMZ((string)DecisionQueueController::GetVariable(\"mzID\"))[\"object\"]->UniqueID ?? 0)";$victim=$args[1];$amount='max(0, ('.$args[2].') - ('.($args[3]??'0').'))';}
  $gate=$name==='FaBCapturedBurnDeal'?"(FaBFindUID(".$args[3].")[\"zone\"] ?? '') === 'Hero'":($name==='FaBUPRDeal'?"(FaBFindUID(".$args[2].")[\"zone\"] ?? '') === 'Hero'":'true');
  if($sourceRef!==''){
   $effectActor=$actor;
   $actor="FaBDamageSourceActor((string)(".$sourceRef."), intval(".$source."), intval(".$effectActor."))";
   $gate="(".$gate.") && FaBDamageSourceView((string)(".$sourceRef."), intval(".$source.")) !== null";
  }
  $prefix='';
  // Capture all values before the await; no live zone object crosses the frame.
  $prefix.="\n\$dtdSource = $source; \$dtdVictim = intval($victim); \$dtdPacket = intval($amount); \$dtdType = $type; \$dtdActor = intval($actor);\n";
  if($sourceRef!=='')$prefix.="\n\$dtdSourceRef = $sourceRef;\n";
  $prefix.="if ($gate ) {\n".FaBPreventionAwaitCode($sourceRef!==''?'$dtdSourceRef':'')."\n}\n";
  $edits[]=[$offsets[$start],$prefix];$i=$semi;
 }
 foreach(array_reverse($edits) as [$at,$prefix])$code=substr($code,0,$at).$prefix.substr($code,$at);return $code;
}
// Spellbane Sigil chooses X in every set's saved barrier flow, before pitching.
function FaBPrepareOMNBarrierCode(string $code): string {
 if(str_contains($code,'FaBOMNVariableBarrier'))return $code;
 return preg_replace_callback('/(\$\w+)\s*=\s*FaBUPRBarrierValue\((\$\w+)\);/',function($m){
  return $m[0].'
if (FaBOMNVariableBarrier('.$m[2].')) {
'
   .'$omnBarrierSeat = intval(FaBIdentityFromMZ('.$m[2].')["player"]);
'
   .'$omnBarrierMax = FaBAvailablePitch($omnBarrierSeat);
'
   .'$omnBarrierX = await $omnBarrierSeat.NumberChoose(1, $omnBarrierMax, "Choose_arcane_barrier_X");
'
   .$m[1].' = intval($omnBarrierX);
}
';
 },$code);
}
function FaBPreparePENLifeCode(string $code): string {
 foreach(['GetHealth\((\$\w+)\)','intval\(GetHealth\((\$\w+)\)\)'] as $a)foreach(['GetHealth\((\$\w+)\)','intval\(GetHealth\((\$\w+)\)\)'] as $b){
  $code=preg_replace_callback('/'.$a.'\s*([<>])(?![=])\s*'.$b.'/',fn($m)=>'FaBPENLifeMore('.($m[2]==='>'?$m[1]:$m[3]).', '.($m[2]==='>'?$m[3]:$m[1]).')',$code);
 }
 return $code;
}
function FaBPreparePENDestroyCode(string $code): string {
 $tokens=token_get_all('<?php '.$code);array_shift($tokens);$out='';
 for($i=0;$i<count($tokens);$i++){
  $t=$tokens[$i];
  if(is_array($t)&&$t[0]===T_STRING&&$t[1]==='FaBMONDestroy'){
   $j=$i+1;while(isset($tokens[$j])&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)$j++;
   if(($tokens[$j]??null)==='('){$out.='FaBPENDestroy($player, ';$i=$j;continue;}
  }
  $out.=is_array($t)?$t[1]:$t;
 }
 return $out;
}
// Switcheroo's replacement finishes (including the losing hero's discard) before
// the original clash grants its prizes or Victor offers a new clash.
function FaBPrepareSUPClashCode(string $code): string {
 return preg_replace_callback('/(\$[A-Za-z_][A-Za-z0-9_]*)\s*=\s*FaBHVYClash\([^;]*\);/',function($m){return $m[0].'
 $supLoser = FaBSUPSwitchLoser('.$m[1].');
 if ($supLoser > 0) {
   $supHand = implode("&", FaBChoiceRefs($supLoser, "Hand"));
   if ($supHand !== "") {
     $supDiscard = await $supLoser.MZChoose($supHand, "Discard_for_The_Old_Switcheroo");
     FaBDiscardChoice($supLoser, $supDiscard);
   }
 }
';},$code);
}
function FaBPreventionAwaitCode(string $sourceRef=''): string {$code= <<<'CODE'
$dtdLeft = $dtdPacket;
$dtdUsed = [];
$dtdUnpreventable = FaBDTDUnpreventable($dtdActor, $dtdSource, $dtdType);
$dtdChoices = FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType, $dtdSource);
while ($dtdLeft > 0 && $dtdChoices !== '') {
 $dtdMandatory = FaBDTDForcefield($dtdVictim, $dtdUsed) || count(FaBMSTWardRefs($dtdVictim)) > 0 || ($dtdType === 'ARCANE' && count(FaBROSShelters($dtdVictim)) > 0);
 if ($dtdMandatory) {
  $dtdChosen = await $dtdVictim.MZChoose($dtdChoices, "Choose_damage_prevention");
 } else {
  $dtdChosen = await $dtdVictim.MZMultiChoose($dtdChoices, 0, 1, "Choose_damage_prevention");
 }
 if ($dtdChosen === '-') { break; }
 $dtdReduction = FaBDTDPreventionPay($dtdVictim, $dtdChosen, $dtdLeft, $dtdUsed, $dtdSource);
 if (!$dtdUnpreventable) { $dtdLeft = max(0, $dtdLeft - $dtdReduction); }
 if (!$dtdUnpreventable) { $dtdLeft = FaBMSTPrevent($dtdVictim, $dtdLeft, FaBDTDSource($dtdSource)); }
 $dtdChoices = FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType, $dtdSource);
}
FaBDTDStorePrevention($dtdVictim, $dtdSource, $dtdPacket - $dtdLeft);
CODE;

 if($sourceRef==='')return $code;
 return str_replace([
 'FaBDTDUnpreventable($dtdActor, $dtdSource, $dtdType)',
 'FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType, $dtdSource)',
 'FaBDTDPreventionPay($dtdVictim, $dtdChosen, $dtdLeft, $dtdUsed, $dtdSource)',
 'FaBDTDSource($dtdSource)'
 ],[
 'FaBDTDUnpreventable($dtdActor, $dtdSource, $dtdType, '.$sourceRef.')',
 'FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType, $dtdSource, '.$sourceRef.')',
 'FaBDTDPreventionPay($dtdVictim, $dtdChosen, $dtdLeft, $dtdUsed, $dtdSource, '.$sourceRef.')',
 'FaBDTDSource($dtdSource, '.$sourceRef.')'
 ],$code);
}

/** Bridge only known legacy PrepareCard bodies; do not overwrite authored edits. */
function FaBPrepareDeclaredEffectCostCode(string $code, string $cardID): string {
    // Celestial alone reserves three Soul identities; Prism's one-Soul body and
    // Beacon/Soul Harvest remain outside this exact-source bridge.
    if ($cardID === 'celestial_cataclysm_yellow') {
        $normalized = trim(str_replace("\r\n", "\n", $code));
        if (hash_equals('a7740872213321e1bf983f99d6a6e1f8fa2762f30fc36eed93aed76f14a163a9', hash('sha256', $normalized))) return $code;
        if (!hash_equals('571076fa5b224ce56d67798c6da9573464c3a941bf81676e20e9b83b6f996deb', hash('sha256', $normalized))) {
            throw new RuntimeException('Celestial additional-cost preparation source changed; review before regeneration.');
        }
        return <<<'FAB064_CELESTIAL'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);

$targets = implode('&', FaBChoiceRefs($player, 'Soul'));

$chosen = await $player.MZMultiChoose($targets, 3, 3, "Banish_to_pay");

if (!FaBDeclarePreparedEffectCost($player, $uid, 'BANISH_THREE_SOUL', $chosen)) {

    return;


}

FaBFinishPreparedCard($uid);

FAB064_CELESTIAL;
    }
    // Only the exact reviewed Nimble PrepareCard sources migrate; unknown authored
    // bodies stop generation, and fixed bodies are accepted byte-for-byte.
    if (in_array($cardID, ['nimble_strike_red','nimble_strike_yellow','nimble_strike_blue'], true)) {
        $normalized = trim(str_replace("\r\n", "\n", $code));
        if (hash_equals('72c12f74261623dc36316592d885c0ad0b12d1ec14b1a0a2f4ef7061a376b627', hash('sha256', $normalized))) return $code;
        if (!hash_equals('621b6fcf929bf5a7d8e35651b8709872490b839e60ac8e1d23837717084c56ab', hash('sha256', $normalized))) {
            throw new RuntimeException('Nimble additional-cost preparation source changed for '.$cardID.'; review before regeneration.');
        }
        return <<<'FAB062_NIMBLE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$chosen = 'PASS';
$targets = implode('&', FaBChoiceRefs($player, 'Graveyard', ['base'=>'nimblism']));
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, "You_may_banish_nimblism");
}
if (!FaBDeclarePreparedEffectCost($player, $uid, 'BANISH_NIMBLISM', $chosen)) {
    return;
}
FaBFinishPreparedCard($uid);
FAB062_NIMBLE;
    }
    // Existing Chane ResolveAbility: creation is now its native activation cost.
    if (in_array($cardID, ['chane','chane_bound_by_shadow'], true)
        && (str_contains($code, 'soul_shackle') || str_contains($code, "'CHANE'"))) {
        $normalized = trim(str_replace("\r\n", "\n", $code));
        if (hash_equals('de7e8a75ed89202a82385dbc5ed2d9743d9575c506196387e8d7aa8df29b9726', hash('sha256', $normalized))) return $code;
        if (!hash_equals('656c822edd0582ab1eeeb51738fd63f0d5892009a9ef883105636dcfe41a26b6', hash('sha256', $normalized))) {
            throw new RuntimeException('Chane creation-cost source changed for '.$cardID.'; review before regeneration.');
        }
        return "FaBMONAdd(\$player, 'CHANE', 0);\n";
    }
    if (in_array($cardID, ['prism','prism_sculptor_of_arc_light'], true) && str_contains($code, 'FaBFinishPreparedCard')) {
        $normalized = trim(str_replace("\r\n", "\n", $code));
        if (hash_equals('d077d00e63ef5ad9898b986d3f71a8a5e244d163fc60e09d3ac4bf57bc3b7775', hash('sha256', $normalized))) return $code;
        if (!hash_equals('2bd5f8ac1e2603882ff23f4df639671d8876eaca62fe5d6690a146c51b726555', hash('sha256', $normalized))) {
            throw new RuntimeException('Prism Soul-cost preparation source changed for '.$cardID.'; review before regeneration.');
        }
        return <<<'FAB039_PRISM_SOUL'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);

$chosen = 'PASS';
 $targets = FaBMONSoul($player);

if ($targets !== "") {
 $chosen = await $player.MZChoose($targets, "Banish_from_soul");
 
}
 if (!FaBDeclarePrismSoulCost($player, $uid, $chosen ?? '')) {
 return;
 
}

FaBFinishPreparedCard($uid);
FAB039_PRISM_SOUL;
    }
    if (!in_array($cardID, ['tome_of_harvests_blue','rouse_the_ancients_blue'], true)
        || !str_contains($code, 'FaBFinishPreparedCard')) return $code;
    if (str_contains($code, 'FaBDeclarePreparedEffectCost')) return $code;
    $known = ['tome_of_harvests_blue'=>'622e99a025ec41f1f5d0170627374afc534cb6908196f6fee93c1c7d3fd4b9dd', 'rouse_the_ancients_blue'=>'6010bda0ceaeaa60f0ab0e286bbeb03fb13906e09e7a5a1384f6413cec2648ae'];
    $normalized = trim(str_replace("\r\n", "\n", $code));
    if (!hash_equals($known[$cardID], hash('sha256', $normalized))) {
        throw new RuntimeException('Additional-cost preparation source changed for '.$cardID.'; review before regeneration.');
    }
    if ($cardID === 'tome_of_harvests_blue') return <<<'FAB039_TOME'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$chosen = 'PASS';
 $targets = implode('&', FaBChoiceRefs($player, 'Arsenal'));

if ($targets !== "") {
 $chosen = await $player.MZChoose($targets, "Bottom_arsenal_as_cost");

}
 if (!FaBDeclarePreparedEffectCost($player, $uid, 'ARSENAL_BOTTOM', $chosen)) {
 return;

}
 FaBFinishPreparedCard($uid);
FAB039_TOME;
    return <<<'FAB039_ROUSE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);

$maximum = count(FaBChoiceRefs($player, 'Hand', ['attackAction'=>true]));
 $targets = implode('&', FaBChoiceRefs($player, 'Hand', ['attackAction'=>true]));

$chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Declare_optional_reveal_thirteen_cost");
 if (!FaBDeclarePreparedEffectCost($player, $uid, 'REVEAL_THIRTEEN', $chosen)) {
 return;

}

FaBFinishPreparedCard($uid);
FAB039_ROUSE;
}

/** Local compatibility for the exact known remote source, without backend writes. */
function FaBPrepareDejaVuPitchCode(string $code,string $cardID): string {
    if($cardID!=='potion_of_deja_vu_blue'||!str_contains($code,'FaBARCStageRefs'))return $code;
    $normalized=trim(str_replace("\r\n","\n",$code));
    if(hash_equals('521d15d696b70fe496255c295ec3c478e4a7b2c5f29e2d9494ce65a547592e1d',hash('sha256',$normalized)))return $code;
    if(!hash_equals('5bfef40b938784f407d1b2f93f2a47f7d6a5dfd07a0757b817924d5d3f01c424',hash('sha256',$normalized)))throw new RuntimeException('Potion of Deja Vu staging source changed; review before regeneration.');
    return <<<'FAB_DEJA_VU_PITCH'
$uids = FaBARCStageRefs($player, implode('&', FaBChoiceRefs($player, 'Pitch')), 'Pitch');
 if (count($uids) > 0) {
 $orderParam = FaBARCOrderParam($uids, 'Top');
 $order = await $player.Rearrange($orderParam);
 FaBARCFinishOrder($player, $uids, $order);

}
FAB_DEJA_VU_PITCH;
}

/** Exact local compatibility bridge; remote CardEditor stays read-only. */
function FaBPrepareBloodtornCostCode(string $code,string $cardID): string {
    if($cardID!=='bloodtorn_bodice'||!str_contains($code,'FaBFinishPreparedCard'))return $code;
    $normalized=trim(str_replace("\r\n","\n",$code));
    if(hash_equals('a10f18e5b78e841f9fd917d830d22147deb1536ea9a698842d8ff9564ceb6419',hash('sha256',$normalized)))return $code;
    if(!hash_equals('0a2906eda3610539c683d9bdcb9aba32ee28c98d8b7b9f11a2b6be470b5240bf',hash('sha256',$normalized)))throw new RuntimeException('Bloodtorn Bodice preparation source changed; review before regeneration.');
    return <<<'FAB_BLOODTORN_DECLARED_AURA'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$chosen = "-";
 $refs = FaBROSRefs($player, 'Arena', 'Aura');
 if ($refs !== "") {
 $chosen = await $player.MZChoose($refs, "Pay_additional_cost");

}
if (!FaBDeclareBodiceAuraCost($player, $uid, $chosen)) {
 return;

}
FaBFinishPreparedCard($uid);
FAB_BLOODTORN_DECLARED_AURA;
}

/** Bridge only Plague's exact known legacy producer; preserve unrelated authored code. */
function FaBPrepareSpreadingPlagueOwnerCode(string $code,string $cardID): string {
    if ($cardID !== 'spreading_plague_yellow') return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    if ($normalized === '') return $code;
    $sourceHash = hash('sha256', $normalized);
    if (hash_equals('0c08109ed66bb923552e5fea8d2b1fbed4639fc33e056f740a60b492cc5ad64e', $sourceHash)) return $code;
    if (!hash_equals('622763af972387ea5a23c63c339cea78d33d0f2402b5be3cf0886bdb1b17fc36', $sourceHash)) {
        throw new RuntimeException('Spreading Plague token-owner source changed; review before regeneration.');
    }
    return <<<'FAB_PLAGUE_CREATOR_OWNER'
$victim = intval(FaBGetState()['defender']);
$n = count(FaBOUTDefenders());
 FaBHVYToken($victim, 'bloodrot_pox', $n, null, null, false, $player);
FAB_PLAGUE_CREATOR_OWNER;
}

/** Exact Halo-only legacy bridge; unknown authored bodies require review. */
function FaBPrepareHaloResolutionCode(string $code, string $cardID): string {
    if ($cardID !== 'halo_of_illumination') return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    $hash = hash('sha256', $normalized);
    // Canonical corrected ResolveAbility and inert legacy PrepareCard are idempotent.
    if (hash_equals('b18606d63ae26bdf935c1139785ab23818058e48936bf77e2cb5d5aa6d3b5172', $hash)
        || hash_equals('5611cec6341f3c0df36be9f0ff1151a6ebc0b0701d74d0d9d14e7911651cddbf', $hash)) return $code;
    if (hash_equals('3b7604c40164833085873c6a76c7d2a90d434a23fb7a5424949e66094fcce5b8', $hash)) return <<<'FAB_HALO_SAFE_PREPARE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
 FaBFinishPreparedCard($uid);
FAB_HALO_SAFE_PREPARE;
    if (hash_equals('012a6580c18bcfcd61bc67fb394a3ac77384bd83254995253efb94ce573ecf5d', $hash)) return <<<'FAB_HALO_RESOLVE'
$chosen = 'PASS';
 $targets = implode('&', FaBChoiceRefs($player, 'Hand'));

if ($targets !== "") {
 $chosen = await $player.MZChoose($targets, "Put_card_into_soul");
 
}
 $f = FaBIdentityFromMZ($chosen ?? '');
 if ($f !== null && $f['player'] === $player && $f['zone'] === 'Hand') {
 $light = FaBHasType($f['object'], 'Light');
 $moved = FaBMoveChoice($player, $chosen, 'Hand', 'Soul');
 if ($moved !== null && $light) {
 DoDrawCard($player, 1);
 
}
 
}
FAB_HALO_RESOLVE;
    throw new RuntimeException('Halo of Illumination source changed; review before regeneration.');
}

/** Exact Smashing origin/delayed-hit bridge; unknown authored bodies require review. */
function FaBPrepareSmashingCode(string $code, string $cardID): string {
    if (!in_array($cardID, ['smashing_good_time_red','smashing_good_time_yellow','smashing_good_time_blue'], true)) return $code;
    $hash=hash('sha256', trim(str_replace("\r\n", "\n", $code)));
    if (hash_equals('f2f27918cd2faebf83c29854982abacde72ab3d534d1dca58c906d25c473c3ab', $hash)) return $code;
    if (hash_equals('e56cdb25e62ee05b64fc18e0f1eb009c4416f3b1bd5611a366dabed55658ce98', $hash)) return <<<'FAB_SMASH_CAPTURED_VICTIM'
$target = intval(DecisionQueueController::GetVariable('evrSmashingVictim'));
 $chosen = 'PASS';
 $targets = $target > 0 ? FaBEVRTargets($player, 'Item', $target) : '';

if ($targets !== "") {
 $chosen = await $player.MZMayChoose($targets, "Destroy_item");
 
}
 if ($chosen !== 'PASS') {
 FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID));
 
}
FAB_SMASH_CAPTURED_VICTIM;
    $known=[
        'smashing_good_time_red' => ['legacy'=>'d9457672fb63d959bea5cd831f87a7a2341437a5521f1e58c9836535cae85be7','fixed'=>'9fecf652d2c135be7e3c117bc28f36f4eb3d27696ade78cc1c3d5b9195f6548b','body'=><<<'FAB_SMASH_RED'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
FaBEVRRegisterSmashing($player, $uid);
 if ((string)DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') {
 FaBWTRAddEffect($player, 'SUP_NEXT_AA', 3);
 
}
FAB_SMASH_RED
        ],
        'smashing_good_time_yellow' => ['legacy'=>'8b53de85724b82a74dba32dae4b49d8da31ca1627e21055cfd104fe0bc01e199','fixed'=>'5b6ad0e05e86033b8fec51fc1f8ad19763ac61e53faeefcf531d0cf4e614c7b3','body'=><<<'FAB_SMASH_YELLOW'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
FaBEVRRegisterSmashing($player, $uid);
 if ((string)DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') {
 FaBWTRAddEffect($player, 'SUP_NEXT_AA', 2);
 
}
FAB_SMASH_YELLOW
        ],
        'smashing_good_time_blue' => ['legacy'=>'d9e2cd1a209669c3915820792213c0b2379b847d3d201e88db77ff14159130a4','fixed'=>'7e8f65cd996a6302c43a5045bf804f318955ec61e67cd3575d5084a4d4c14178','body'=><<<'FAB_SMASH_BLUE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
FaBEVRRegisterSmashing($player, $uid);
 if ((string)DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') {
 FaBWTRAddEffect($player, 'SUP_NEXT_AA', 1);
 
}
FAB_SMASH_BLUE
        ],
    ];
    $entry=$known[$cardID];
    if (hash_equals($entry['fixed'], $hash)) return $code;
    if (hash_equals($entry['legacy'], $hash)) return $entry['body'];
    throw new RuntimeException('Smashing Good Time source changed for '.$cardID.'; review before regeneration.');
}
/** Exact BIOS producer bridge; leave unrelated bodies byte-for-byte unchanged. */
function FaBPrepareBIOSItemCode(string $code,string $cardID): string {
 if($cardID!=='bios_update_red')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(hash_equals('3ac55e0c6ba8a3b6ff0d30f7008be14b5ecf6ccb080e33927bbbb54d88989a9a',$hash))return $code;
 if(!hash_equals('1d820f653562a45a98f61a63cfb3c30e48b7bc5592fefec9d3d97724eb19b3d3',$hash))throw new RuntimeException('BIOS Update source changed; review before regeneration.');
 return <<<'FAB_BIOS_ITEM_SOURCE'
FaBDYNAdd($player, 'BIOS_POWER');
 FaBDYNArmBIOSItem($player, $mzID);
FAB_BIOS_ITEM_SOURCE;
}

function FaBPrepareMillenniumEntryCode(string $code,string $cardID): string {
 if($cardID!=='channel_the_millennium_tree_red')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(in_array($hash,['4332ac4cafb5f12b751d251456196f36096d3fdd6a4557791d5b08e847ccc38c','de77d02d9d88dd5bd88ab5892ac874dcfe6e071d1d8be1efffb4f5a59065a444'],true))return $code;
 if(!hash_equals('9e3832aed2f615030967a13b8fdde7c6d34577fbbb15651bcd6681e67724abca',$hash))throw new RuntimeException('Channel the Millennium Tree source changed; review before regeneration.');
 return '/* Entry amp is queued by FaBROSAfterMove. */';
}

/** Exact current-WHEN-hit Over Loop bridge; Boost preparation remains byte-identical. */
function FaBPrepareOverLoopHitCode(string $code,string $cardID): string {
 if(!in_array($cardID,['over_loop_red','over_loop_yellow','over_loop_blue'],true))return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(in_array($hash,['e4dda0e656b613600d11e63326d3ee487eb144f0cde3fcef960c0fd054bbaa20','90aef6b53c9ea4f97e643741c0213afbc95e600cdcd152f7f929a74008b646bb'],true))return $code;
 if(!hash_equals('7ed74fd005aaf939878828979433a80374aea0115a076e2d3d7e2dc5e622b68a',$hash))throw new RuntimeException('Over Loop source changed; review before regeneration.');
 return 'FaBARCQueueOverLoopHit($player, $mzID, intval($amount));';
}

/** Explicit opt-in Quantum class contract; other Item callers unchanged. */
function FaBPrepareQuantumProcessorItemCode(string $code,string $cardID): string {
 if($cardID!=='quantum_processor_yellow')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(in_array($hash,['d64309b9039f73872f37dc32183ac326b29f59219c189a72c7674a88179c3fbd','03dcffa38e081e6120ad689887dfadb1a3e1ce6d4c0b758c01705396bf5341c5','3958d8b3e70b7c278bd761c34198302bebe664cae8730b808c62d38bda07cd04'],true))return $code;
 if(!hash_equals('ff28070ac7ba37c200c79d944b52ac6d6f18781022c065f232834efb09bcd7d0',$hash))throw new RuntimeException('Quantum Processor source changed; review before regeneration.');
 return '$refs = FaBEVORefs($player, \'Hand\', \'mechCheap\', false);
 $chosen = \'PASS\';
 if ($refs !== \'\') {
 $chosen = await $player.MZMayChoose($refs, "Put_item_into_arena");
 
}
FaBEVOPutItem($player, $chosen, \'Mechanologist\');
';
}

/** Exact current-WHEN Illuminate bridge; unrelated Soul movement stays unchanged. */
function FaBPrepareIlluminateHitCode(string $code,string $cardID): string {
 if(!in_array($cardID,['illuminate_red','illuminate_yellow','illuminate_blue'],true))return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(hash_equals('cca75d6f1c15aeaac6f46b3c9d9e898b1e1898f5d9ecd0f0fc5941d5983eeb8a',$hash))return $code;
 if(!hash_equals('b5cee6a80c7d080cb0cc0d5b308a61f340e8e7d17e924a1a807380ed3d56508a',$hash))throw new RuntimeException('Illuminate source changed; review before regeneration.');
 return 'FaBMONQueueIlluminateHit($player, $mzID, intval($amount));';
}

/** Aethersling only: preserve remote authored source and lower its exact old target prefix locally. */
function FaBPrepareAetherslingTargetCode(string $code, string $cardID): string {
    if ($cardID !== 'aethersling_red') return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    $hash = hash('sha256', $normalized);
    // Fixed Resolve and the exact local canonical Prepare are idempotent.
    if (in_array($hash, ['758ee4f2f2dc41179d16de8065e83d05bc169f9e54ac03e421fc757c6c8b40c7','3344d1842b616cc2d90863d77ed116f8c972a85921cf90bda49bf8c3104c1650'], true)) return $code;
    if (!hash_equals('fe459b98aa670140cbc4a0cd559dfa8bfa0459336e0e4911dbb1eef7c54b21b3', $hash)) {
        throw new RuntimeException('Aethersling declaration source changed; review before regeneration.');
    }
    $oldPrefix = <<<'AETHERSLING_OLD_TARGET_PREFIX'
$uid = FaBPENUID($mzID);
$chosen = "-";
$refs = FaBPENTargets($player, false);
if ($refs !== "") {
$chosen = await $player.MZChoose($refs, "Choose_arcane_target");
}
$targetUID = FaBPENUID($chosen);
AETHERSLING_OLD_TARGET_PREFIX;
    $newPrefix = <<<'AETHERSLING_DECLARED_TARGET_PREFIX'
$uid = FaBPENUID($mzID);
$targetUID = FaBOMNAetherslingTargetUID($player, $uid);
AETHERSLING_DECLARED_TARGET_PREFIX;
    if (!str_starts_with($normalized, $oldPrefix)) throw new RuntimeException('Aethersling legacy target prefix changed.');
    return $newPrefix . substr($normalized, strlen($oldPrefix));
}

/** Six finite attack-trigger Resolve bodies; preserve the complete prevention tail. */
function FaBPrepareArcanicTriggerCode(string $code,string $cardID): string {
    if(!in_array($cardID,['arcanic_crackle_red','arcanic_crackle_yellow','arcanic_crackle_blue','vexing_malice_red','vexing_malice_yellow','vexing_malice_blue'],true))return $code;
    $normalized=trim(str_replace("\r\n","\n",$code));$hash=hash('sha256',$normalized);
    $vexing=in_array($cardID,['vexing_malice_red','vexing_malice_yellow','vexing_malice_blue'],true);
    $fixedHash=$vexing?'3dd221a15d20192d220ddd54acef86bbea8ce74f3055849fb6f73d5a51544996':'a94ce3b82bbec9ebaa2c0e556329d1e7b1818f11d8f7b716b2bb15141f865573';
    $oldHash=$vexing?'dde3194b70bc098c046c02c52387aef42bb8fc664f1b1f6bec58112db0931aee':'139bbcdadb9e86d3cf46d8066a6ac637ef22e17ae58d020d251d72b8ee4c2d78';
    if(hash_equals($fixedHash,$hash))return $code;
    if(!hash_equals($oldHash,$hash))throw new RuntimeException('Attack arcane trigger source changed; review before regeneration: '.$cardID);
    $oldPrefix=<<<'ARCANIC_OLD_TARGET_PREFIX'
$caster = $player;

$chosen = 'PASS';
 $targets = FaBARCHeroTargets($player, false);

if ($targets !== "") {
 $chosen = await $player.MZChoose($targets, "Choose_arcane_target");
 
}

$target = FaBARCTargetSeat($caster, $chosen, false);
ARCANIC_OLD_TARGET_PREFIX;
    $newPrefix=<<<'ARCANIC_DECLARED_TARGET_PREFIX'
$caster = $player;

$target = FaBMONArcanicTargetSeat($caster, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID ?? 0));
ARCANIC_DECLARED_TARGET_PREFIX;
    $oldPrefix=str_replace("\r\n","\n",$oldPrefix)."\n\n";$newPrefix=str_replace("\r\n","\n",$newPrefix)."\n\n";
    if(!str_starts_with($normalized,$oldPrefix))throw new RuntimeException('Arcanic Crackle target prefix changed.');
    $tail=substr($normalized,strlen($oldPrefix));
    if(substr_count($tail,'FaBARCDealArcane(')!==1)throw new RuntimeException('Arcanic Crackle damage leaf changed.');
    return $newPrefix.str_replace('FaBARCDealArcane(','FaBMONArcanicDealArcane(',$tail);
}

/** Ebbing only: capture its secondary WHEN; never author the connected backend. */
function FaBPrepareEbbingFragmentCode(string $code, string $cardID): string {
    if (!in_array($cardID, ['ebbing_arcstride_red','ebbing_arcstride_yellow','ebbing_arcstride_blue'], true)) return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    $hash = hash('sha256', $normalized);
    if (hash_equals('d267ba5389c2a9fb2d05e084e0df3454ead57828e1467000aac17f2595cee9c3', $hash)) return $code;
    if (!hash_equals('7fe4dd00409c5816184773fb66890060536b7a4dc7889483ffd7b6d364e8406f', $hash)) throw new RuntimeException('Unknown Ebbing Fragment source body; stop generation.');
    return <<<'FAB_EBBING_CAPTURED_SECONDARY'
if (DecisionQueueController::GetVariable("rosEvent") === "omnFragment") {
$uid = intval(DecisionQueueController::GetVariable("rosSource"));
$attack = FaBFindUID($uid);
$state = FaBGetState();
if ($attack !== null && $attack['zone'] === 'CombatChain'
&& ($attack['object']->Role ?? '') === 'ATTACK'
&& intval($state['attackUID'] ?? 0) === $uid
&& intval($attack['object']->ChainLink ?? 0) === intval($state['chainLink'] ?? 0)
&& in_array($attack['object']->CardID, ['ebbing_arcstride_red','ebbing_arcstride_yellow','ebbing_arcstride_blue'], true)) {
FaBOMNFragment($player, $uid);
$attack = FaBFindUID($uid);
if ($attack !== null && $attack['zone'] === 'CombatChain'
&& ($attack['object']->Role ?? '') === 'ATTACK'
&& intval(FaBGetState()['attackUID'] ?? 0) === $uid
&& !HasNoAbilities($attack['object'])) {
$controller = intval($attack['object']->Controller ?? 0);
if (FaBSeatIsLive($controller)) {
FaBROSQueue($controller, $attack['object']->CardID, $uid,
['rosEvent'=>'omnEbbingFragmented', 'omnEbbingLink'=>intval($attack['object']->ChainLink),
'omnEbbingTurn'=>intval(GetTurnNumber()),
'omnEbbingPlayOrder'=>intval(FaBObjectCounters($attack['object'])['FAB_PLAY_ORDER'] ?? 0)]);
}
}
}
}
FAB_EBBING_CAPTURED_SECONDARY;
}

/** Exact legacy Genis/Frosthaven bodies only; foreign authored code passes unchanged. */
function FaBPrepareGenisFrosthavenOwnerCode(string $code, string $cardID): string {
    $legacyHashes = [
        'genis_wotchuneed' => 'f9fd5c376ac02376f047bd0d2bfa5248b156246bc78fa6540a3d02421c899daf',
        'frosthaven_sheath_red' => 'fb2aeb88a7e0faf88aeff85f7b25a2a45e8a622b030feb83799a2922f191f8ba',
    ];
    if (!isset($legacyHashes[$cardID])) return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    if (!hash_equals($legacyHashes[$cardID], hash('sha256', $normalized))) return $code;
    if ($cardID === 'genis_wotchuneed') return <<<'FAB_GENIS_PRESERVED_CASTER'
$caster = $player;
$seats = FaBOpponents($caster);
 $i = 0;
 $silver = 0;
 while ($i < count($seats)) {
 $target = $seats[$i];
 $chosen = 'PASS';
 $targets = implode('&', FaBChoiceRefs($target, 'Hand'));

if ($targets !== "") {
 $chosen = await $target.MZMayChoose($targets, "Bottom_card_and_draw");
 
}
 if ($chosen !== 'PASS') {
 FaBARCToDeck($target, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), false);
 DoDrawCard($target, 1);
 FaBWTRCreateArena($caster, 'silver');
 $silver = $silver + 1;
 
}
 $i = $i + 1;
 
}
 if ($silver === 0) {
 DoDrawCard($caster, 1);
 
}
FAB_GENIS_PRESERVED_CASTER;
    return <<<'FAB_FROSTHAVEN_CREATOR_OWNER'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
if (FaBPENBond($uid, 'Ice')) {
 $victim = intval(FaBGetState()['attacker']);
 FaBHVYToken($victim, 'frostbite', 1, $player, null, false, $player);
 
}
FAB_FROSTHAVEN_CREATOR_OWNER;
}

/** Exact legacy Assembly callback only; retain normal Item initialization after its physical move. */
function FaBPrepareAssemblyItemEntryCode(string $code, string $cardID): string {
    if ($cardID !== 'assembly_module_blue') return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    if (!hash_equals('799280d55467501d133f6a51a9d377a69efcebd4d13f94d32147d813cb30d825', hash('sha256', $normalized))) return $code;
    return <<<'FAB_ASSEMBLY_NORMAL_ITEM_ENTRY'
$chosen = "-";
 $refs = FaBStageSearch($player, ['base'=>'hyper_driver']);
 if ($refs !== "") {
 $chosen = await $player.MZChoose($refs, "Find_Hyper_Driver");
 
}
$item = FaBMoveChoice($player, $chosen, 'Temp', 'Arena');
if ($item !== null) {
 $entered = FaBFindUID(intval($item->UniqueID));
 if ($entered !== null && $entered['zone'] === 'Arena' && $entered['player'] === $player && FaBWTRBase($entered['object']->CardID) === 'hyper_driver' && FaBHasType($entered['object'], 'Item') && !FaBHasType($entered['object'], 'Token')) {
  FaBARCEnterItem($player, $entered['object']);
 }
}
 FaBFinishSearch($player);
FAB_ASSEMBLY_NORMAL_ITEM_ENTRY;
}

/** Exact two legacy Bloodrot bodies only; foreign authored edits remain unchanged. */
/** Exact Tear legacy body only; preserve its actual generated discard await. */
/** Exact known Read StartTurn bodies only; unrelated/unknown authoring stays untouched. */
function FaBPrepareReadEndCode(string $code,string $cardID): string {
 $old=['read_the_ripples_red'=>'44d9a55db423b069e26d5aef75f5bd1ae3bdafad49aa10e65aceedb669351d76', 'read_the_ripples_yellow'=>'4656267e5fcc26047624f6876ab03edc02ea3f6f6389b7878a05570ada35b84a', 'read_the_ripples_blue'=>'3fa0d2e3c0958b3650ee69b2bdad129343650a046dd7245869120eb48c803c2b'];$new=['read_the_ripples_red'=>'66cbfb2c59eae7f9738cd37c50dc705723009f4c776311bf089371da3037c99e', 'read_the_ripples_yellow'=>'f6e1cd15c90d9ac6518633a200999c13b7861bd6ccf6910cdcac5ccbdd95bf84', 'read_the_ripples_blue'=>'87490d1bbcbce0213b51b4903b3d1705f75c2ef39f5033919f957c93d6673905'];
 if(!isset($old[$cardID]))return $code;
 $normalized=trim(str_replace("\r\n","\n",$code));$hash=hash('sha256',$normalized);
 if(hash_equals($new[$cardID],$hash))return $code;
 if(!hash_equals($old[$cardID],$hash))return $code;
 $before=<<<'READ_OLD_SOURCE'
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
FaBMONDestroy($uid);
READ_OLD_SOURCE;
 $after=<<<'READ_CAPTURED_SOURCE'
$uid = FaBUPRReadEndSourceUID($player, $mzID);
if ($uid <= 0) return;
FaBUPRDestroyReadEndSource($player, $uid);
READ_CAPTURED_SOURCE;
 return str_replace($before,$after,$normalized);
}

function FaBPrepareCapturedTearCode(string $code,string $cardID): string {
 if($cardID!=='tear_asunder_blue')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(hash_equals('23a041e5bd17b16615b173faf1c3f341164ded0a1514f0dc14b416ebec3dbe94',$hash))return str_replace(["\$target = intval(FaBGetState()['defender']);","\$maximum = min(2, FaBHandCount(\$target));"],["\$target = FaBELECapturedTearVictim(\$player);","\$maximum = \$target > 0 ? min(2, FaBHandCount(\$target)) : 0;"],$code);
 return $code;
}

function FaBPrepareBloodrotGenericDamageCode(string $code, string $cardID): string {
    if ($cardID !== 'bloodrot_pox') return $code;
    $normalized = trim(str_replace("\r\n", "\n", $code));
    $hash = hash('sha256', $normalized);
 if(hash_equals('181ded38ed21f427bb81331a910b94b46ca40bb57d746755b60d5abb63ab58b9',$hash))return $code;
 if(hash_equals('567b8f774c0aa100da68f65cb944247b21266ed9a1dbe35cfd4d8eece4560aab',$hash))return $code;

    if (in_array($hash, ['27209c1d4b3f765df3b674bdb3dddda2a61743c686470d37e18e24e5ab3fab91','320598fab0e25e6208e0d0bdd31470925c327e43c2f96e579db36932ff2f1c1a'], true)) return $code;
    if (!in_array($hash, ['217156cfb43a1f6df93467ccc2ea8b5777000d4b91ab0ad32ddb43b55dddb0de','aac93ab65c7bfdc5be6ed6dc22a015062f53720ceba8f38f4a1ff7c8a7efdf7d'], true)) return $code;
    return str_replace("DoDamage(\$player, '', \$player, 2, 'PHYSICAL');", "DoDamage(\$player, '', \$player, 2, 'GENERIC');", $code);
}

/** Named Surface entry-WHEN bridge; owner-start ResolveAbility stays unchanged. */
function FaBPrepareSurfaceEntryCode(string $code,string $cardID): string {
 if($cardID!=='surface_shaking_blue')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(in_array($hash,['9239faa9ce15327b3e9c49f9c113938b33db5c5b4ece4b32fb0f3979b7978780','82032264511c01794c3c4aa29da92774c03968c883496fbb0473eaad3a3735f6'],true))return $code;
 if(!hash_equals('dff02840fbae34aeb0d08dac556239f5fe5cb39e15b51774de5b654d8a4a69e8',$hash))throw new RuntimeException('Surface Shaking source changed; review before regeneration.');
 return '$seaPlayer = $player;' . "\n" . '/* Entry Seismic Surges are queued by FaBROSAfterMove. */';
}

/** Exact legacy Fluxor pitch modifiers only; Red, Hit and foreign bodies remain unchanged. */
function FaBPrepareVolatileFluxorPowerCode(string $code,string $cardID): string {
 if(!in_array($cardID,['volatile_fluxor_blue','volatile_fluxor_yellow'],true))return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!hash_equals('054ec415c948b16c908129e9fe528d2e684a2ed155cadf8f9e189758ace1842c',$hash))return $code;
 $bonus=$cardID==='volatile_fluxor_blue'?1:2;
 return str_replace('return FaBOMNInstantLink($player) ? 3 : 0;', 'return FaBOMNInstantLink($player) ? '.$bonus.' : 0;', $code);
}

/** Exact Figment of Erudition entry body only; all other authored code stays unchanged. */
function FaBPrepareFigmentEruditionEntryCode(string $code,string $cardID): string {
 if($cardID!=='figment_of_erudition_yellow')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!hash_equals('1ab63ad609ddfd48c3c1c3e02640f8f9b35474fb85a90f3f338039fb7b62c849',$hash))return $code;
 return '/* Entry Ponder is queued by FaBROSAfterMove. */';
}

/** Exact two legacy Ebon bodies only; the existing ARC lifetime is unchanged. */
function FaBPrepareEbonResolutionCode(string $code,string $cardID): string {
 if($cardID!=='ebon_fold')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(hash_equals('e3b950bd2b3467f9211d984cfb905e981dff3df03c1da7b2b9433e48f500370f',$hash))return <<<'FAB_EBON_PREPARE_COST'
FaBMONEbonFinishPreparation($player, $mzID);
FAB_EBON_PREPARE_COST;
 if(hash_equals('012a6580c18bcfcd61bc67fb394a3ac77384bd83254995253efb94ce573ecf5d',$hash))return <<<'FAB_EBON_RESOLUTION_EFFECT'
$ebonPlayer = intval($player);
 $ebonAbilityUID = intval(DecisionQueueController::GetVariable("arcAbilityUID"));
 $ebonChoices = FaBMONEbonResolutionChoices($ebonPlayer, $ebonAbilityUID);
 if ($ebonChoices !== "") {
 $ebonChosen = await $ebonPlayer.MZChoose($ebonChoices, "Banish_a_card_from_hand");
 FaBMONEbonResolveChoice($ebonPlayer, $ebonAbilityUID, $ebonChosen);
 
}
FAB_EBON_RESOLUTION_EFFECT;
 return $code;
}

/** Exact legacy Captain Red resolution only; declaration lives in canonical/native Prepare. */
function FaBPrepareCaptainModeCode(string $code,string $cardID): string {
 if($cardID!=='captains_call_red')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!hash_equals('7cf9586723d9ec63aa4c183a234110c640c37a480fdbf697b3bb18866deed341',$hash))return $code;
 return 'FaBMONResolveCaptainMode($player, $mzID);';
}

/** Exact legacy Memorial Blue resolution only; declaration stays in native/canonical Prepare. */
function FaBPrepareMemorialTargetCode(string $code,string $cardID): string {
 if($cardID!=='memorial_ground_blue')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!hash_equals('c2b9b622e34b00bb0b10eb46114b35208ca37980d211e715f744c81f30fc12cc',$hash))return $code;
 return 'FaBMONResolveMemorialTarget($player, $mzID);';
}

/** Exact Tip Off Blue legacy effect; declared Hero is owned by its live managed ABI. */
function FaBPrepareTipTargetCode(string $code,string $cardID): string {
 if($cardID!=='tip_off_blue')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!hash_equals('6226193be35ae52fad642541ff62a2c0941612908f2c14660e6217a5f34dd81d',$hash))return $code;
 return 'FaBHNTResolveTipTarget($player, intval(DecisionQueueController::GetVariable("arcAbilityUID")));';
}

/** One locally maintained missing Jarl macro; conflicting authored service rows fail closed. */
function FaBLocalRepairMacroAbilities(string $macro,array $abilities): array {
 $abilities=FaBLocalCreatorRepairAbilities($macro,$abilities);
 $abilities=FaBLocalLessonsRepairAbilities($macro,$abilities);
 if($macro!=='Defended')return $abilities;
 $path=__DIR__.'/../../DevTools/FaB/sup_abilities.json';
 if(!is_file($path))throw new RuntimeException('Missing canonical local FaB repair source');
 $rows=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$matches=[];$identities=[];
 if(!is_array($rows))throw new RuntimeException('Malformed canonical local FaB repair source');
 foreach($rows as $row)if(is_array($row)&&($row['cardId']??'')==='unforgetting_unforgiving_red')$identities[]=$row;
 if(count($identities)!==1)throw new RuntimeException('Expected one canonical Unforgetting identity');
 foreach($identities[0]['abilities']??[] as $ability)if(($ability['macroName']??'')==='Defended')$matches[]=$ability;
 if(count($matches)!==1||($matches[0]['isImplemented']??false)!==true
  ||!is_string($matches[0]['abilityCode']??null)||trim($matches[0]['abilityCode'])==='')throw new RuntimeException('Expected one implemented nonempty canonical Unforgetting Defended repair macro');
 $code=$matches[0]['abilityCode'];$normalize=fn($s)=>trim(str_replace("\r\n","\n",$s));$present=[];
 foreach($abilities as $ability)if(($ability['card_id']??'')==='unforgetting_unforgiving_red')$present[]=$ability;
 if(count($present)>1)throw new RuntimeException('Duplicate authored Unforgetting Defended macros');
 if($present){
  if($normalize((string)$present[0]['ability_code'])!==$normalize($code)||trim((string)($present[0]['prereq_code']??''))!=='')throw new RuntimeException('Authored Unforgetting Defended macro conflicts with local repair');
  return $abilities;
 }
 $abilities[]=['card_id'=>'unforgetting_unforgiving_red','macro_name'=>'Defended','ability_code'=>$code,'ability_type'=>'macro','is_implemented'=>1];
 return $abilities;
}

/** Exact legacy entry payload migrates to its earned targeted layer. */
function FaBPrepareEmergingEntryCode(string $code,string $cardID): string {
 if(!in_array($cardID,['emerging_avalanche_red','emerging_avalanche_yellow','emerging_avalanche_blue'],true))return $code;
 $normal=trim(str_replace("\r\n","\n",$code));
 if(in_array(hash('sha256',$normal),['a746627f256b62fba6441aa3ab7011c2b5eb69e6c0a3566374f60f40af83e57d'],true))return "/* Entry trigger parameters and payload are owned by the native Emerging layer. */\n";
 if(str_contains($normal,'FaBELEFrost'))throw new RuntimeException('Emerging legacy entry source changed; review before regeneration.');
 return $code;
}

/** Exact legacy Erudition attack body migrates without an unsafe, unused source lookup. */
function FaBPrepareSurayaEarnedAttackCode(string $code,string $cardID): string {
 if($cardID!=='suraya_archangel_of_erudition')return $code;
 $hash=hash('sha256',trim(str_replace("\r\n","\n",$code)));
 if(!in_array($hash,['e60950a96f44db40452609a93002c9039372682ee413a14af918dacc0a6729c4','1ce7c53db13275326ef41b1a3d622b374be1b60472cdaa3746cdecef5f4de6a0'],true))
  throw new RuntimeException('Suraya Erudition provider body changed; review before regeneration.');
 $defs=json_decode(file_get_contents(__DIR__.'/../../DevTools/FaB/dtd_abilities.json'),true,512,JSON_THROW_ON_ERROR);
 $rows=array_values(array_filter($defs,fn($r)=>($r['cardId']??'')===$cardID));
 if(count($rows)!==1)throw new RuntimeException('Expected one local Suraya Erudition definition.');
 $abilities=array_values(array_filter($rows[0]['abilities']??[],fn($a)=>($a['macroName']??'')==='AttackDeclared'));
 if(count($abilities)!==1)throw new RuntimeException('Expected one local Suraya Erudition attack ability.');
 $local=(string)($abilities[0]['abilityCode']??'');
 if(!hash_equals('1ce7c53db13275326ef41b1a3d622b374be1b60472cdaa3746cdecef5f4de6a0',hash('sha256',trim(str_replace("\r\n","\n",$local)))))
  throw new RuntimeException('Local Suraya Erudition body changed; review before regeneration.');
 return $hash==='1ce7c53db13275326ef41b1a3d622b374be1b60472cdaa3746cdecef5f4de6a0'?$code:$local;
}


/** Only supplement the missing, exact local Lessons Learned ResolveCard definition. */
function FaBLocalLessonsRepairAbilities(string $macro,array $abilities): array {
    if ($macro!=='ResolveCard') return $abilities;
    $path=__DIR__.'/../../DevTools/FaB/mpw_repair_abilities.json';
    $rows=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    if (!is_array($rows) || count($rows)!==1 || ($rows[0]['cardId']??'')!=='lessons_learned_blue'
        || count($rows[0]['abilities']??[])!==1) throw new RuntimeException('Malformed local Lessons Learned source');
    $entry=$rows[0]['abilities'][0];$code=(string)($entry['abilityCode']??'');
    $normalize=fn($s)=>trim(str_replace("\r\n","\n",$s));
    if (($entry['macroName']??'')!=='ResolveCard' || ($entry['isImplemented']??false)!==true
        || !hash_equals('a349b7993041a2516235218c5ae4b4dac1795500c0d9a2ae3e382e9273895d8b',hash('sha256',$normalize($code))))
        throw new RuntimeException('Local Lessons Learned source changed; review before generation');
    $present=array_values(array_filter($abilities,fn($a)=>($a['card_id']??'')==='lessons_learned_blue'));
    if (count($present)>1) throw new RuntimeException('Duplicate Lessons Learned ResolveCard definitions');
    if ($present) {
        if ($normalize((string)$present[0]['ability_code'])!==$normalize($code)
            || trim((string)($present[0]['prereq_code']??''))!=='')
            throw new RuntimeException('Authored Lessons Learned conflicts with local repair');
        return $abilities;
    }
    $abilities[]=['card_id'=>'lessons_learned_blue','macro_name'=>'ResolveCard','ability_code'=>$code,'ability_type'=>'macro','is_implemented'=>1];
    return $abilities;
}

/** Exact local authoring migrations for creator identity; reject unknown remote bodies. */
function FaBLocalCreatorRepairAbilities(string $macro, array $abilities): array {
    $profiles = [
        'codex_of_bloodrot_yellow' => ['ResolveCard','out_abilities.json','3d7d14d2267e74b84b337a4779983d504fb32b5a8f0f88ec0dc8f531364c02c2','e291a65e90cf2986a46e2081e76fe27a19d2a6d76cc5491ffff12dade5bf07fd'],
        'mindstate_of_tiger_blue' => ['StartTurn','dyn_abilities.json','ca9117740402e4d9d75765d1cde070c50e12cde33025df3713ffa2ff125d1bf5','8470f22195bbe595529aa2be9f52c87d28a6eef4d3db3818c34827acbf86fd2c'],
        'song_of_larinkmorth_white_blue' => ['ResolveCard','pen_abilities.json','b0b0d917bdaf1a7c106c1d0cc81ba6a8b055775672090f8b8ab058940a27da81','9ca8261f3669e784f396465304f78cb3967d1101ac91f5200f98cee663fdadbe']
    ];
    foreach ($profiles as $id => $profile) {
        if ($macro !== $profile[0]) continue;
        $defs = json_decode(file_get_contents(__DIR__.'/../../DevTools/FaB/'.$profile[1]), true, 512, JSON_THROW_ON_ERROR);
        $rows = array_values(array_filter($defs, fn($r) => ($r['cardId'] ?? '') === $id));
        if (count($rows) !== 1) throw new RuntimeException('Creator repair canonical identity changed: '.$id);
        $bodies = array_values(array_filter($rows[0]['abilities'] ?? [], fn($r) => ($r['macroName'] ?? '') === $macro));
        if (count($bodies) !== 1 || ($bodies[0]['isImplemented'] ?? false) !== true) throw new RuntimeException('Creator repair canonical macro changed: '.$id);
        $code = $bodies[0]['abilityCode'];
        $hash = fn($s) => hash('sha256', trim(str_replace("\r\n", "\n", $s)));
        if (!hash_equals($profile[3], $hash($code))) throw new RuntimeException('Creator repair canonical body changed: '.$id);
        $found = [];
        foreach ($abilities as $i => $a) if (($a['card_id'] ?? '') === $id) $found[] = $i;
        if (count($found) !== 1) throw new RuntimeException('Creator repair expected one authored macro: '.$id);
        $i = $found[0];
        if (trim((string)($abilities[$i]['prereq_code'] ?? '')) !== '' || !in_array($hash($abilities[$i]['ability_code']), [$profile[2], $profile[3]], true)) throw new RuntimeException('Creator repair authored source conflicts: '.$id);
        $abilities[$i]['ability_code'] = $code;
    }
    return $abilities;
}
