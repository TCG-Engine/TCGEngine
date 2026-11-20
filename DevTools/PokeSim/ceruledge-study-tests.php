<?php
require __DIR__.'/ceruledge-deck-study.php';
$checks=0;
function studyCheck(bool $ok,string $message): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
foreach(cerStudyVariants() as $name=>$changes){
    $deck=cerStudyDeck($changes);
    studyCheck(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'Valid candidate '.$name);
}
$deck=cerStudyDeck(cerStudyVariants()['rotom-to-fire-energy']);
foreach(cerBudewVariants() as $name=>$changes){
    $candidate=cerStudyDeck($changes,'budew');$counts=array_column($candidate,'count','id');
    studyCheck(!PokeValidateDeck($candidate),'Budew fork validates: '.$name);
    if($name!=='baseline')studyCheck(!isset($counts['sv07-118'])&&!isset($counts['me04-080']),'Budew fork removes Rotom and Prism: '.$name);
}
foreach(cerAceVariants() as $name=>$changes){
    $candidate=cerStudyDeck($changes,'ace');$counts=array_column($candidate,'count','id');
    studyCheck(!PokeValidateDeck($candidate)&&$counts['mee-002']===22&&$counts['sv07-118']===4,'ACE candidate preserves Fire and Rotom: '.$name);
}
foreach(cerSearchVariants() as $name=>$changes){
    $candidate=cerStudyDeck($changes,'search'); $counts=array_column($candidate,'count','id');
    studyCheck(!PokeValidateDeck($candidate)&&$counts['mee-002']===22&&$counts['sv07-118']===4&&$counts['me02.5-192']===3&&$counts['me03-079']===1,'Search candidate preserves approved core: '.$name);
}
foreach(cerEnergyVariants() as $name=>$changes){
    $candidate=cerStudyDeck($changes,'energy');$counts=array_column($candidate,'count','id');
    studyCheck(!PokeValidateDeck($candidate)&&$counts['sv07-118']===4,'Valid Energy candidate preserves four Rotom: '.$name);
}
$promoted=array_column(PokeNamedDeck('ceruledge-ex'),'count','id');
studyCheck($promoted['me02.5-192']===3&&$promoted['me03-079']===1,'Selectable deck uses approved three Lillie / one Naveen');
studyCheck($promoted['mee-002']===22&&$promoted['sv07-118']===4&&$promoted['me02.5-183']===1&&$promoted['me02-089']===2&&isset($promoted['sv08-164'])&&!isset($promoted['me02-020'])&&!isset($promoted['sv08-191']),'Selectable baseline is the approved 22-Fire list');
foreach(cerRefineVariants() as $name=>$changes){
    $candidate=cerStudyDeck($changes,'refine');$counts=array_column($candidate,'count','id');
    studyCheck(!PokeValidateDeck($candidate)&&$counts['sv07-118']===4,'Valid refinement preserves four Rotom: '.$name);
}
studyCheck(PokeDetectDeck($deck)==='ceruledge-ex','Candidate imports retain the dedicated Ceruledge policy');
studyCheck(PokeDetectDeck(cerStudyDeck(cerStudyVariants()['blender']))==='ceruledge-ex','Blender candidate imports use the dedicated policy');
PokeCreateGame(PokeNamedDeck('brisbane-lopunny'),$deck,30001,1);
$view=PokeObservation(2);
studyCheck($view['players'][2]['initialBasicFire']===22,'Candidate policy knows its actual starting Fire count');
studyCheck(!isset($view['players'][1]['initialBasicFire']),'Starting deck count metadata is limited to the viewer');
PokeStateImport(PokeStateExport());studyCheck(PokeObservation(2)['players'][2]['initialBasicFire']===22,'Starting deck count survives save/load');
$game=cerStudyGame(cerStudyDeck([]),'brisbane-lopunny',30001,1);
studyCheck($game['complete'],'Study completes a game through the normal legality gate');
$summary=cerStudySummary([$game]);
studyCheck($summary['games']===1&&$summary['completed']===1,'Summary retains unsuccessful setup attempts in its denominator');
echo "PASS $checks Ceruledge study checks\n";
