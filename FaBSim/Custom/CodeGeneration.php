<?php
// Add the common, interactive prevention window before damage in saved FaB abilities.
// Other roots retain their authored code verbatim. Calls nested inside expressions
// are deliberately not rewritten; supported calls are standalone/assigned statements.
function FaBPrepareDamageCode(string $code): string {
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
function FaBPreventionAwaitCode(): string {return <<<'CODE'
$dtdLeft = $dtdPacket;
$dtdUsed = [];
$dtdUnpreventable = FaBDTDUnpreventable($dtdActor, $dtdSource, $dtdType);
$dtdChoices = FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType);
while ($dtdLeft > 0 && $dtdChoices !== '') {
 $dtdMandatory = FaBDTDForcefield($dtdVictim, $dtdUsed) || count(FaBMSTWardRefs($dtdVictim)) > 0 || ($dtdType === 'ARCANE' && count(FaBROSShelters($dtdVictim)) > 0);
 if ($dtdMandatory) {
  $dtdChosen = await $dtdVictim.MZChoose($dtdChoices, "Choose_damage_prevention");
 } else {
  $dtdChosen = await $dtdVictim.MZMultiChoose($dtdChoices, 0, 1, "Choose_damage_prevention");
 }
 if ($dtdChosen === '-') { break; }
 $dtdReduction = FaBDTDPreventionPay($dtdVictim, $dtdChosen, $dtdLeft, $dtdUsed);
 if (!$dtdUnpreventable) { $dtdLeft = max(0, $dtdLeft - $dtdReduction); }
 if (!$dtdUnpreventable) { $dtdLeft = FaBMSTPrevent($dtdVictim, $dtdLeft, FaBDTDSource($dtdSource)); }
 $dtdChoices = FaBDTDPreventionChoices($dtdVictim, $dtdLeft, $dtdUsed, $dtdType);
}
FaBDTDStorePrevention($dtdVictim, $dtdSource, $dtdPacket - $dtdLeft);
CODE;
}
