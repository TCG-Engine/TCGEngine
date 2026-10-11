<?php
// Preserve the originating hero across decision-queue continuations. Modesty
// suppresses creation, while the rest of that hero ability still resolves.
function FaBPrepareHeroCreationCode(string $code,string $cardID): string {
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
