<?php
require __DIR__.'/ceruledge-bottleneck-audit.php';
$checks=0;
function bottleneckCheck(bool $ok,string $message): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function bottleneckBoard(int $discard,int $hand,bool $powered=true): void {
    InitializeGamestate();$GLOBALS['playerID']=2;$GLOBALS['currentPlayer']=2;SetCurrentPhase('MAIN');SetTurnPlayer(2);SetFirstPlayer(1);SetTurnNumber(8);
    foreach([1,2] as $seat){AddPlayerTurns($seat,4);$c=PokeAdd($seat,'Active',$seat===2?'sv08-036':'me02-084');$c->Controller=$seat;$c->Energy=$seat===2&&!$powered?[]:['mee-002'];for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-002');for($i=0;$i<20;++$i)PokeAdd($seat,'Deck','mee-002');}
    for($i=0;$i<$discard;++$i)PokeAdd(2,'Discard','mee-002');for($i=0;$i<$hand;++$i)PokeAdd(2,'Hand','mee-002');
}
function bottleneckRead(): array {return cerBottleneckSnapshot(PokeObservation(2),['type'=>'end','player'=>2]);}
bottleneckBoard(15,0);$r=bottleneckRead();
bottleneckCheck($r['ready330']&&!$r['blockers']['noHandFire'],'Zero hand Energy is not a bottleneck when the attack is ready');
bottleneckCheck($r['exposed']===16,'Mass counts fifteen discarded and the attached Fire');
bottleneckBoard(10,5);PokeAdd(2,'Hand','sv06-145');$r=bottleneckRead();
bottleneckCheck($r['blockers']['enoughHandFuel']&&!$r['blockers']['insufficientExposedMass'],'Enough accessible fuel separates conversion from Energy access');
bottleneckCheck(in_array('sv06-145',$r['legalOutlets'],true),'Unspent Carmine is an available conversion outlet');
AddSupporterUsed(2,true);$r=bottleneckRead();
bottleneckCheck($r['blockers']['blockedCarmine']&&$r['blockers']['handFuelNoLegalOutlet'],'Used Supporter records the actual throughput constraint');
bottleneckBoard(15,1,false);$r=bottleneckRead();
bottleneckCheck(!$r['ready330']&&$r['blockers']['exButNoPoweredEx']&&!$r['blockers']['shortDiscard'],'Attachment failure is independent of discard damage');
$deck=cerStudyDeck([],'refine');$a=cerStudyGame($deck,'brisbane-lopunny',110001,1);$seen=0;
$b=cerStudyGame($deck,'brisbane-lopunny',110001,1,function($view,$action)use(&$seen){++$seen;});
bottleneckCheck($seen>0&&$a===$b,'Observational callbacks leave the deterministic result unchanged');
echo "PASS $checks bottleneck audit checks\n";
