<?php
// Preserve the originating hero across decision-queue continuations. Modesty
// suppresses creation, while the rest of that hero ability still resolves.
function FaBPrepareHeroCreationCode(string $code,string $cardID): string {
 $code=FaBPrepareBloodtornCostCode($code,$cardID);
 $code=FaBPrepareDejaVuPitchCode($code,$cardID);
 $code=FaBPrepareDeclaredEffectCostCode($code,$cardID);
 // Local, idempotent corrections run before the hero-only preprocessing below.
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
 $edits=[];$names=['DoDamage','FaBARCDealArcane','FaBELEDealArcane','FaBUPRDeal'];
 for($i=0;$i<count($tokens);++$i){$t=$tokens[$i];if(!is_array($t)||$t[0]!==T_STRING||!in_array($t[1],$names,true))continue;
  $start=$i;$j=$i-1;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;
  if($j>=0&&$tokens[$j]==='='){$j--;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;if($j<0||!is_array($tokens[$j])||$tokens[$j][0]!==T_VARIABLE)continue;$start=$j;$j--;while($j>=0&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)--$j;}
  if($j>=0&&!in_array($tokens[$j],[';','{','}'],true))continue;
  $open=$i+1;while($open<count($tokens)&&is_array($tokens[$open])&&$tokens[$open][0]===T_WHITESPACE)++$open;if(($tokens[$open]??null)!=='(')continue;
  $args=[];$arg='';$depth=1;$end=$open+1;
  for(;$end<count($tokens);++$end){$t2=$tokens[$end];$text=is_array($t2)?$t2[1]:$t2;if(!is_array($t2)){if(in_array($t2,['(','[','{'],true))++$depth;if(in_array($t2,[')',']','}'],true))--$depth;if($depth===0){$args[]=trim($arg);break;}if($t2===','&&$depth===1){$args[]=trim($arg);$arg='';continue;}}$arg.=$text;}
  $semi=$end+1;while($semi<count($tokens)&&is_array($tokens[$semi])&&$tokens[$semi][0]===T_WHITESPACE)++$semi;if(($tokens[$semi]??null)!==';')continue;
  $name=$tokens[$i][1];$actor=$args[0];$type="'ARCANE'";
  if($name==='DoDamage'){$source="intval(FaBIdentityFromMZ(".$args[1].")[\"object\"]->UniqueID ?? 0)";$victim=$args[2];$amount=$args[3];$type=$args[4]??"'PHYSICAL'";}
  elseif($name==='FaBUPRDeal'){$source=$args[1];$victim="intval(FaBFindUID(".$args[2].")[\"player\"] ?? 0)";$amount=$args[3];$type=$args[4]??"'ARCANE'";}
  else{$source=$name==='FaBELEDealArcane'?($args[4]??'0'):"intval(FaBIdentityFromMZ((string)DecisionQueueController::GetVariable(\"mzID\"))[\"object\"]->UniqueID ?? 0)";$victim=$args[1];$amount='max(0, ('.$args[2].') - ('.($args[3]??'0').'))';}
  $gate=$name==='FaBUPRDeal'?"(FaBFindUID(".$args[2].")[\"zone\"] ?? '') === 'Hero'":'true';
  $prefix='';
  // Capture all values before the await; no live zone object crosses the frame.
  $prefix.="\n\$dtdSource = $source; \$dtdVictim = intval($victim); \$dtdPacket = intval($amount); \$dtdType = $type; \$dtdActor = intval($actor);\n";
  $prefix.="if ($gate ) {\n".FaBPreventionAwaitCode()."\n}\n";
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
function FaBPreventionAwaitCode(): string {return <<<'CODE'
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
}

/** Bridge only known legacy PrepareCard bodies; do not overwrite authored edits. */
function FaBPrepareDeclaredEffectCostCode(string $code, string $cardID): string {
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
