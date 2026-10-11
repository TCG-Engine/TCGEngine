<?php
require __DIR__.'/relicanth-packages.php';
$checks=0;
function consistencyCheck(bool $ok,string $why): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($why);}
foreach([20,1] as $cards){
    InitializeGamestate();$GLOBALS['playerID']=2;$GLOBALS['currentPlayer']=2;
    SetCurrentPhase('MAIN');SetTurnPlayer(2);SetFirstPlayer(1);SetTurnNumber(6);SetRandomState(42);
    foreach([1,2] as $seat){
        AddPlayerTurns($seat,3);AddSetupReady($seat,true);
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-003');
        for($i=0;$i<20;++$i)PokeAdd($seat,'Deck','mee-003');
        PokeAdd($seat,'Bench','me05-072')->Controller=$seat;
    }
    $active=PokeAdd(1,'Active','me05-017');$active->Controller=1;$active->Energy=['mee-003'];
    $enemy=PokeAdd(2,'Active','me05-039');$enemy->Controller=2;$enemy->Energy=['mee-005'];
    GetZoneObject(PokeFirstRef(1,'Active'))->Tool='sv06-158';
    foreach(array_slice(PokeObjects(1,'Deck'),$cards) as $card)$card->Remove();
    $before=PokeCount(1,'Deck');$turn=GetTurnNumber();
    $attacks=array_values(array_filter(PokeLegalActions(2),fn($a)=>$a['type']==='attack'));
    consistencyCheck((bool)$attacks,'Opponent can attack instrumented Helmet board');
    PokeApplyAction($attacks[0]);
    $drawn=relicanthExperimentHelmetDrawCount($before,$turn,'me05-017',PokeVar('log',[]));
    consistencyCheck($drawn===min(2,$cards),'Count only Helmet draws, capped by remaining deck');
    if($cards===20)consistencyCheck(PokeCount(1,'Hand')===3&&$before-PokeCount(1,'Deck')===3,'Attack callback also includes automatic turn draw');
    else consistencyCheck(PokeCount(1,'Hand')===1&&GetWinner()===2,'Helmet uses last card before automatic deck-out');
    consistencyCheck(relicanthExperimentHelmetDrawCount($before,$turn+1,'me05-017',PokeVar('log',[]))===0,'Other-turn damage cannot count as a Helmet trigger');
}
$base=PokeNamedDeck('relicanth-v3-meta-tune');
foreach([['sv07-139'=>-4],['me02.5-190'=>-3],['sv06-158'=>-3],['sv07-129'=>-1,'me02.5-190'=>-1],['sv07-129'=>-2,'sv07-130'=>-2]] as $changes){
    $deck=relicanthExperimentDeck($base,$changes);
    consistencyCheck(count($deck)===60&&!PokeValidateDeck($deck),'Draw/fossil ablation remains an implemented 60-card experiment');
    consistencyCheck(count(array_filter($deck,fn($c)=>str_starts_with($c['id'],'experiment-blank-')))===-array_sum($changes),'Every removed slot is a blank');
}
echo "$checks consistency diagnostic checks passed\n";
