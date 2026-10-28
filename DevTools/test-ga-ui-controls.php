<?php
if (PHP_SAPI !== 'cli') exit;
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
require 'GrandArchiveSim/Custom/UIProjection.php';
require 'GrandArchiveSim/Custom/UIControls.php';
$gameName='ga_ui_controls_'.getmypid(); $dir='GrandArchiveSim/Games/'.$gameName;
RegressionEnsureDir($dir); $checks=0;
function controlCheck($ok,$label) { global $checks; if(!$ok) throw new RuntimeException($label); ++$checks; }
try {
 copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt',$dir.'/Gamestate.txt');
 $playerID=1; ParseGamestate('./GrandArchiveSim/'); $currentPlayer=1;
 $saved=file_get_contents($dir.'/Gamestate.txt');
 $main=GAUIBuildMainActions(1);
 controlCheck(in_array('end-main',array_column($main,'id'),true),'End main phase offered');
 controlCheck(GAUIBuildMainActions(2)===[] && GAUIBuildMainActions(null)===[],'Only acting seat has main controls');
 foreach ([
  ['YESNO','', ['YES'],'YES'],
  ['MZMODAL','1|2|Draw&Heal',['1','0'],'1,0'],
  ['MZMULTICHOOSE','1|2|myHand',['0'],null],
  ['MZMAYCHOOSE','myHand',[],'PASS'],
  ['MZCHOOSE','myHand',['0'],null],
  ['NUMBERCHOOSE','-2|5','3','3'],
  ['TWOSIDEDSLIDER','0|3|myField-0|theirField-0','2','2'],
  ['NAMECARD','', 'Fireball','Fireball'],
  ['OPTIONCHOOSE','North&South',['1'],'South'],
  ['CHOOSEZONE','myDeck&myHand',['0'],'myDeck'],
  ['ICONCHOICE','Yes&No|metadata',['1'],'No'],
  ['MZSPLITASSIGN','3|myField-0&theirField-0',['0'=>2,'1'=>1],'myField-0:2,theirField-0:1'],
  ['MZREARRANGE','Top=duplicate,duplicate;Bottom=', ['0'=>['1'],'1'=>['0']],'Top=duplicate;Bottom=duplicate'],
 ] as [$type,$param,$input,$expected]) {
  $p1DecisionQueue=[new DecisionQueue("$type $param 1 Choose_now 0",'DecisionQueue',1,0)];
  // Empty params need assignment, not the serialized space-delimited constructor.
  $p1DecisionQueue[0]->Type=$type; $p1DecisionQueue[0]->Param=$param;
  $controls=GAUIBuildControls(1); $control=$controls['decision'] ?? null;
  controlCheck($control!==null,'Projects '.$type);
  $answer=GAUIResolveDecision($control,$input);
  controlCheck($expected===null ? is_string($answer) && str_starts_with($answer,'myHand-') : $answer===$expected,'Encodes '.$type);
  controlCheck(GAUIBuildControls(2)===[] && GAUIBuildControls(null)===[],'Private prompt restricted '.$type);
  controlCheck(!str_contains(json_encode(GAUIPublicControls($controls)),'_answer'),'Opaque options '.$type);
 }
 $multi=['type'=>'MZMULTICHOOSE','minimum'=>1,'maximum'=>2,'budget'=>3,'options'=>[
  ['id'=>'0','_answer'=>'myHand-0','weight'=>2],['id'=>'1','_answer'=>'myHand-1','weight'=>2]]];
 foreach([[],['0','0'],['99'],['0','1'],[0],'0'] as $bad) controlCheck(GAUIResolveDecision($multi,$bad)===null,'Reject malformed/out-of-budget multi');
 controlCheck(GAUIResolveDecision($multi,['0'])==='myHand-0','Valid budget choice');
 $number=['type'=>'NUMBERCHOOSE','minimum'=>0,'maximum'=>3];
 foreach(['4','-1','1.5','1e0',[],null] as $bad) controlCheck(GAUIResolveDecision($number,$bad)===null,'Reject invalid number');
 $p1DecisionQueue[0]->Type='MZCHOOSE'; $p1DecisionQueue[0]->Param='theirHand-0';
 $options=GAUIBuildControls(1)['decision']['options'];
 controlCheck($options[0]['printedText']==='' && str_contains($options[0]['label'],'Face-down card'),'Opponent private choice is anonymous');
 $p1DecisionQueue[0]->Param='myHand-0@Activate-0@Omen';
 $control=GAUIBuildControls(1)['decision'];
 controlCheck(GAUIResolveDecision($control,['0'])==='myHand-0@Activate-0@Omen','Compound ability survives opaque mapping');
 $p1DecisionQueue[0]->Param='unsupported-invalid';
 controlCheck(GAUIBuildControls(1)===[],'Unsupported pool fails closed');
 controlCheck(file_get_contents($dir.'/Gamestate.txt')===$saved,'Control reads do not persist state');
 $reveals=GAUIPublicReveals('REVEAL:'.CardNameToID('Fireball'));
 controlCheck(count($reveals)===1 && $reveals[0]['name']==='Fireball','Public reveal event becomes a card reference');
 foreach(['SECRET_PRIVATE_FLASH','REVEAL:<script>','REVEAL:myHand-0&SECRET'] as $bad) controlCheck(GAUIPublicReveals($bad)===[],'No arbitrary flash data disclosed');
 echo "$checks live-control checks passed\n";
} finally {
 foreach(glob($dir.'/*') ?: [] as $file) if(is_file($file)) unlink($file);
 rmdir($dir);
}
