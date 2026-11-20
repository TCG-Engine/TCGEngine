<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function cbCheck($ok,string $label): void {global $checks;++$checks;if(!$ok)throw new RuntimeException($label);}
function cbBoard(string $id='sv08-036'): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetTurnNumber(6);SetTurnPlayer(1);SetFirstPlayer(2);SetCurrentPhase('MAIN');SetRandomState(42);
    foreach([1,2] as $seat){
        AddPlayerTurns($seat,3);AddSetupReady($seat,true);
        $a=PokeAdd($seat,'Active',$seat===1?$id:'me02-084');$a->Controller=$seat;$a->EnteredTurn=1;$a->Energy=['mee-002'];
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-002');
        for($i=0;$i<20;++$i)PokeAdd($seat,'Deck','mee-002');
    }
    PokeSetVar('deckKey:1','ceruledge-ex');
}
function cbField(string $id): void {$b=PokeAdd(1,'Bench',$id);$b->Controller=1;$b->EnteredTurn=1;}
function cbChoice(): array {return PokeBotChoose(PokeObservation(1));}
function cbPlay(string $id): void {$c=PokeAdd(1,'Hand',$id);PokeApplyAction(['player'=>1,'type'=>'trainer','source'=>PokeRef(1,'Hand',$c->mzIndex)]);}
function cbAnswer(): void {PokeStateImport(PokeStateExport());PokeApplyAction(cbChoice());}

$deck=PokeNamedDeck('ceruledge-ex');
cbCheck(!PokeValidateDeck($deck),'Registered 60-card deck validates');
cbCheck(PokeDetectDeck(array_reverse($deck))==='ceruledge-ex','Imported list selects Ceruledge policy');
cbBoard();$view=PokeObservation(1);cbCheck(PokeBotChoose($view)===PokeCeruledgeChoose($view),'Main bot dispatcher routes to dedicated policy');
$ctx=PokeCeruledgeContext($view);$active=$view['players'][1]['Active'][0];$target=$view['players'][2]['Active'][0];
cbCheck(PokeCeruledgeDamage($ctx,$active,0,$target)===30,'Abyssal Flames starts at 30');
foreach(['mee-002','sv08-191'] as $id)PokeAdd(1,'Discard',$id);
$ctx=PokeCeruledgeContext(PokeObservation(1));cbCheck(PokeCeruledgeDamage($ctx,$active,0,$target)===70,'Damage forecast counts basic and Special Energy');
$target['id']='base1-44';cbCheck(PokeCeruledgeDamage($ctx,$active,0,$target)===140,'Forecast applies Fire weakness');
cbBoard('me02-020');$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeDamage($ctx,$ctx['field'][0],0,$ctx['enemy']['Active'][0])===0,'Infernal Slash requires four Fire in hand');
for($i=0;$i<4;++$i)PokeAdd(1,'Hand','mee-002');$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeDamage($ctx,$ctx['field'][0],0,$ctx['enemy']['Active'][0])===220,'Infernal Slash forecasts payment');

cbBoard('me02-019');cbField('me02-019');PokeAdd(1,'Hand','sv08-036');
cbCheck(cbChoice()['type']==='evolve','Evolves Charcadet before attacking');
cbBoard('me02-019');cbField('me02-019');PokeAdd(1,'Hand','me02-020');
cbCheck(cbChoice()['type']!=='evolve','Does not strand a line as unpowered non-ex Ceruledge');
for($i=0;$i<4;++$i)PokeAdd(1,'Hand','mee-002');
cbCheck(cbChoice()['type']==='evolve','Can use the non-ex attacker with its four-card payment');
cbBoard();cbField('me02-019');GetZoneObject('p1Active-0')->Energy=[];PokeAdd(1,'Hand','mee-002');
cbCheck(cbChoice()['type']==='attach'&&cbChoice()['target']==='p1Active-0','Powers active Ceruledge first');
cbBoard();cbField('me02-019');PokeAdd(1,'Hand','mee-002');
cbCheck(cbChoice()['type']==='attach'&&cbChoice()['target']==='p1Bench-0','Powers replacement after the active has Energy');
cbBoard();cbField('me02-019');PokeAdd(1,'Hand','sv08-191');
cbCheck(cbChoice()['type']==='attach'&&cbChoice()['target']==='p1Active-0','Uses Enriching Energy draw on an evolved attacker');

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','sv08-036');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','mee-002');
PokeAdd(1,'Deck','me02-019');PokeAdd(1,'Deck','sv08-036');
cbPlay('me01-131');$options=PokeDecisionOptions(1);$value=cbChoice()['value'];
$ids=[];foreach(explode('&',$value) as $ref)$ids[]=GetZoneObject($ref)->CardID;
cbCheck($ids===['mee-002','mee-002'],'Ultra Ball discards surplus Energy instead of the evolution or last attachment');
cbAnswer();cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='me02-019','Ultra Ball searches a second basic line before redundant evolution');
cbAnswer();
cbBoard();cbField('me02-019');PokeAdd(1,'Discard','sv08-036');PokeAdd(1,'Discard','mee-002');
cbPlay('me02.5-196');cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='sv08-036','Night Stretcher recovers a missing evolution');cbAnswer();
cbBoard();GetZoneObject('p1Active-0')->Energy=[];PokeAdd(1,'Discard','mee-002');
cbPlay('me02.5-196');cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='mee-002','Night Stretcher recovers a missing attachment');cbAnswer();
cbBoard();cbField('me02-019');PokeAdd(1,'Discard','me02.5-196');PokeAdd(1,'Discard','sv08-036');PokeAdd(1,'Hand','me02.5-196');
cbCheck(cbChoice()['type']==='trainer','Recovery score handles discarded recovery Trainers without recursion');

cbBoard();cbField('me02-019');$GLOBALS['p1Deck']=[];
PokeAdd(1,'Hand','mee-002');
foreach(['mee-002','mee-002','mee-002','mee-002','sv08-036','me02-019'] as $id)PokeAdd(1,'Deck',$id);
cbPlay('sv08.5-107');$value=cbChoice()['value'];$ids=[];foreach(explode('&',$value) as $ref)$ids[]=GetZoneObject($ref)->CardID;
sort($ids);cbCheck($ids===['me02-019','sv08-036'],'Explorer keeps the line and evolution, discarding Energy');
cbAnswer();cbCheck(count(array_filter(PokeObjects(1,'Discard'),fn($c)=>$c->CardID==='mee-002'))===4,'Explorer resolves Energy into the discard pile across saved choices');
cbBoard();cbField('me02-019');cbPlay('me04-080');AddEnergyUsed(1,true);
GetZoneObject('p1Bench-0')->EnteredTurn=6;
PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv08-036');
cbCheck(cbChoice()['type']==='ability','Uses Prism Tower to discard surplus Energy');
PokeApplyAction(cbChoice());cbAnswer();
cbCheck(PokeCount(1,'Discard')===2&&PokeCount(1,'Hand')===2,'Prism Tower spends fodder and draws');
cbBoard();cbPlay('me04-080');GetZoneObject('p1Active-0')->Energy=[];
PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','mee-002');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeActionScore($ctx,['type'=>'ability','source'=>'Stadium-0','index'=>0,'player'=>1])<0,'Prism Tower preserves the last attachment after its first discard');

cbBoard();cbField('me02-019');for($i=0;$i<4;++$i)PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv06-145');PokeAdd(1,'Hand','me02.5-192');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeTrainerScore($ctx,'sv06-145')>PokeCeruledgeTrainerScore($ctx,'me02.5-192'),'Carmine is preferred over shuffling Energy back with Lillie');
cbBoard();cbField('me02-019');for($i=0;$i<14;++$i)PokeAdd(1,'Discard','mee-002');
PokeAdd(1,'Hand','sv06-145');GetZoneObject('p2Active-0')->Damage=100;AddPrizeClaims(1,0);$GLOBALS['p1Prizes']=[];PokeAdd(1,'Prizes','mee-002');
cbCheck(cbChoice()['type']==='attack','Winning attack takes priority over draw');
cbBoard();cbField('me02-019');for($i=0;$i<5;++$i)PokeAdd(1,'Discard','mee-002');
$b=PokeAdd(2,'Bench','me02.5-142');$b->Controller=2;$b->Damage=100;PokeAdd(1,'Hand','me02.5-183');
cbCheck(cbChoice()['type']==='trainer','Boss takes an available two-Prize knockout');PokeApplyAction(cbChoice());
cbCheck(cbChoice()['value']==='p2Bench-0','Boss chooses the forecast knockout target');cbAnswer();

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv06-145');$before=cbChoice();
PokeAdd(2,'Hand','sv08-036');$GLOBALS['p2Deck']=array_reverse($GLOBALS['p2Deck']);$GLOBALS['p2Prizes'][0]->CardID='sv08-036';
cbCheck(cbChoice()===$before,'Hidden opposing identities do not change the decision');
cbBoard();cbField('me02-019');PokeAdd(1,'Hand','sv06-145');$view=PokeObservation(1);$before=PokeCeruledgeChoose($view);$GLOBALS['p1Deck']=[];$GLOBALS['p1Prizes']=[];
cbCheck(PokeCeruledgeChoose($view)===$before,'Policy uses the supplied observation, not live hidden own deck or Prizes');

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','me02-089');PokeAdd(1,'Hand','sv08.5-107');PokeAdd(1,'Hand','sv06-145');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeTrainerScore($ctx,'me02-089')>PokeCeruledgeTrainerScore($ctx,'sv08.5-107'),'Firebreather starts the seven-Energy discard plan before generic draw');
cbCheck(PokeCeruledgeTrainerScore($ctx,'me02-089')>PokeCeruledgeTrainerScore($ctx,'sv06-145'),'Does not discard Firebreather with an empty-Fire Carmine');
AddEnergyUsed(1,true);$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeSearchScore($ctx,'mee-002')<PokeCeruledgeSearchScore($ctx,'sv06-145'),'Explorer discards Fire when the attachment is already secured');
AddEnergyUsed(1,false);$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeSearchScore($ctx,'mee-002')>0,'Still keeps an Energy when an attachment is needed');
for($i=0;$i<17;++$i)PokeAdd(1,'Discard','mee-002');$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeTrainerScore($ctx,'me02-089')<0,'Does not seek seven Energy when public information shows too few remain');
cbBoard();cbField('me02-019');GetZoneObject('p1Bench-0')->EnteredTurn=6;cbPlay('me04-080');
AddEnergyUsed(1,true);
for($i=0;$i<4;++$i)PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','sv06-145');
cbCheck(cbChoice()['type']==='ability'&&cbChoice()['source']==='Stadium-0','Uses Prism Tower before Carmine discards the same hand');

// Slow-trace regressions: do not consume the resource that would restart the hand.
cbBoard();cbField('sv08-036');cbPlay('me04-080');
PokeAdd(1,'Hand','me02-089');PokeAdd(1,'Hand','me01-131');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeDiscardScore($ctx,'me02-089')<0,'Last useful Firebreather is not expendable just because it is in hand');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='me02-089','Prism does not discard Firebreather and Ultra Ball to draw a single card');
cbBoard();cbField('sv08-036');cbPlay('me04-080');
PokeAdd(1,'Hand','sv08.5-107');for($i=0;$i<3;++$i)PokeAdd(1,'Hand','me02.5-192');
PokeApplyAction(['type'=>'ability','source'=>'Stadium-0','index'=>0,'player'=>1]);
$selected=explode('&',cbChoice()['value']);
cbCheck(count($selected)===2&&GetZoneObject($selected[0])->CardID==='me02.5-192'&&GetZoneObject($selected[1])->CardID==='me02.5-192','Prism spends duplicate draw cards while preserving Explorer');

cbBoard('sv07-118');GetZoneObject('p1Active-0')->Energy=[];cbField('sv08-036');GetZoneObject('p1Bench-0')->Energy=['mee-002'];
for($i=0;$i<5;++$i)PokeAdd(1,'Discard','mee-002');PokeAdd(1,'Hand','mee-002');
cbCheck(cbChoice()['type']==='attach'&&cbChoice()['target']==='p1Active-0','Pays Fan Rotom retreat before over-powering the Bench');
PokeApplyAction(cbChoice());cbCheck(cbChoice()['type']==='retreat'&&cbChoice()['target']==='p1Bench-0','Switches from Rotom to the stronger Ceruledge once retreat is paid');
cbBoard();GetZoneObject('p1Active-0')->Energy=[];PokeAdd(1,'Hand','sv08-191');PokeAdd(1,'Hand','mee-002');
cbCheck(cbChoice()['type']==='attach'&&GetZoneObject(cbChoice()['source'])->CardID==='mee-002','A needed Fire attachment beats Colorless Enriching Energy draw');

cbBoard();cbField('sv08-036');GetZoneObject('p1Bench-0')->Energy=['mee-002'];
PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','me01-131');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeFireReserve($ctx)===0,'Fully powered visible board does not reserve a redundant attachment');
cbCheck(cbChoice()['type']==='trainer','Ultra Ball can discard two Fire without waiting for a third');
PokeApplyAction(cbChoice());$selected=explode('&',cbChoice()['value']);
cbCheck(GetZoneObject($selected[0])->CardID==='mee-002'&&GetZoneObject($selected[1])->CardID==='mee-002','Both surplus Fire go into the discard pile');

cbBoard();cbField('sv08-036');for($i=0;$i<5;++$i)PokeAdd(1,'Discard','mee-002');
$b=PokeAdd(2,'Bench','me02-019');$b->Controller=2;
PokeAdd(1,'Hand','me02.5-183');PokeAdd(1,'Hand','sv08.5-107');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='sv08.5-107','Ordinary one-Prize gust does not consume the draw Supporter');

// Multi-turn resources: the final draw Supporter must survive optional costs.
cbBoard();cbField('sv08-036');cbPlay('me04-080');AddSupporterUsed(1,true);
PokeAdd(1,'Hand','me02.5-192');PokeAdd(1,'Hand','me02.5-192');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(!PokeCeruledgeSafeDiscards($ctx),'Sequential costs cannot discard both remaining draw Supporters');
cbCheck(cbChoice()['type']!=='ability','Prism does not convert next-turn draw access into one random card');
PokeAdd(1,'Hand','sv07-118');PokeApplyAction(['type'=>'ability','source'=>'Stadium-0','index'=>0,'player'=>1]);
$selected=explode('&',cbChoice()['value']);$chosenIds=array_map(fn($ref)=>GetZoneObject($ref)->CardID,$selected);
sort($chosenIds);cbCheck($chosenIds===['me02.5-192','sv07-118'],'Prism can discard a duplicate draw card and fodder while retaining a draw route');
cbAnswer();cbCheck(count(array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='me02.5-192'))>=1,'The saved cost choice retains next-turn Lillie');

cbBoard();cbField('me02-019');GetZoneObject('p1Bench-0')->EnteredTurn=6;AddSupporterUsed(1,true);
PokeAdd(1,'Hand','me01-131');PokeAdd(1,'Hand','me02.5-192');PokeAdd(1,'Hand','sv08.5-107');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeTrainerScore($ctx,'me01-131')<0,'Ultra Ball does not burn both future draw Supporters for a backup evolution');
cbCheck(cbChoice()['type']==='attack','Keeps future draw access instead of forcing the unsafe search');

cbBoard();cbField('sv08-036');GetZoneObject('p1Bench-0')->Energy=['mee-002'];
for($i=0;$i<8;++$i)PokeAdd(1,'Hand','mee-002');PokeAdd(1,'Hand','me02.5-192');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='me02.5-192','Lillie refreshes a large Energy-heavy hand when no discard draw Supporter is available');
PokeAdd(1,'Hand','me01-131');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='me01-131','Uses safe Ultra Ball Energy discards before Lillie shuffles the hand');

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','sv08-164');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='sv08-164','Blender is a supported direct Energy-discard route');
PokeApplyAction(cbChoice());$selected=explode('&',cbChoice()['value']);
cbCheck(count($selected)===5&&count(array_filter($selected,fn($ref)=>GetZoneObject($ref)->CardID==='mee-002'))===5,'Blender selects five Energy rather than useful Pokemon or Trainers');
cbAnswer();cbCheck(PokeCount(1,'Discard')===6,'Blender resolves its Energy discards across save/load');
cbBoard();GetZoneObject('p1Active-0')->Energy=[];$GLOBALS['p1Deck']=[];
PokeAdd(1,'Deck','mee-002');for($i=0;$i<5;++$i)PokeAdd(1,'Deck','sv08-036');
cbPlay('sv08-164');cbCheck(cbChoice()['value']==='-','Blender preserves the last deck Fire when an attachment is missing');
cbAnswer();cbCheck(PokeCount(1,'Deck')===6,'Failing the optional Blender search does not discard useful non-Energy cards');

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','sv10.5b-084');
$GLOBALS['p1Deck']=[];foreach(['mee-002','mee-002','sv06-145','me02.5-192','me02-089','mee-002','mee-002','mee-002'] as $id)PokeAdd(1,'Deck',$id);
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='sv10.5b-084','Pokégear is supported as an Item route to a Supporter');
PokeApplyAction(cbChoice());cbCheck(!GetSupporterUsed(1),'Pokégear does not spend the turn Supporter');
cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='me02-089','Pokégear finds Firebreather when the hand needs Fire Energy');
cbAnswer();cbCheck(count(array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='me02-089'))===1&&PokeCount(1,'TempZone')===0,'Pokégear returns only the chosen Supporter and clears the look-at zone');

cbBoard();cbField('me02-019');PokeAdd(1,'Hand','me02.5-207');PokeAdd(1,'Hand','sv06-145');
PokeAdd(1,'Deck','sv08-164');PokeAdd(1,'Deck','me04-080');
cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='me02.5-207','Petrel prioritizes tutoring Blender over generic draw');
PokeApplyAction(cbChoice());cbCheck(GetSupporterUsed(1),'Petrel consumes the Supporter for the turn');
cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='sv08-164','Petrel chooses Blender over other Trainer candidates');
cbAnswer();cbCheck(cbChoice()['type']==='trainer'&&GetZoneObject(cbChoice()['source'])->CardID==='sv08-164','Fetched Blender is playable immediately despite the spent Supporter');
PokeApplyAction(cbChoice());cbAnswer();
cbCheck(count(array_filter(PokeObjects(1,'Discard'),fn($c)=>$c->CardID==='mee-002'))===5,'Petrel-to-Blender puts five Energy into discard across serialized decisions');
cbBoard();PokeAdd(1,'Hand','me02.5-207');PokeAdd(1,'Hand','sv08-164');
cbCheck(GetZoneObject(cbChoice()['source'])->CardID==='sv08-164','Does not spend Petrel when Blender is already in hand');
cbBoard();PokeAdd(1,'Hand','me02.5-207');PokeAdd(1,'Discard','sv08-164');
cbCheck(!PokeCeruledgeNeedsBlender(PokeCeruledgeContext(PokeObservation(1))),'Does not search again for a publicly discarded Blender');
cbBoard();PokeAdd(1,'Hand','me02.5-207');AddSupporterUsed(1,true);
cbCheck(PokeCeruledgeDiscardScore(PokeCeruledgeContext(PokeObservation(1)),'me02.5-207')<0,'Preserves Petrel for next-turn Blender access after spending the Supporter');
cbBoard();SetFirstPlayer(1);AddPlayerTurns(1,1);$petrel=PokeAdd(1,'Hand','me02.5-207');
cbCheck(!PokeCanPlayTrainer(1,PokeRef(1,'Hand',$petrel->mzIndex)),'Petrel retains the normal going-first Supporter restriction');

// Losing traces: protect a newly benched evolution line and avoid a lone Rotom.
cbBoard('sv07-118'); cbField('me02-019'); GetZoneObject('p1Bench-0')->EnteredTurn=GetTurnNumber();
PokeAdd(1,'Hand','sv08-036');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeActionScore($ctx,['type'=>'retreat','target'=>'p1Bench-0'])<0,'Does not expose a fresh unpowered Charcadet merely because its evolution is held');
cbBoard('sv07-118'); cbField('sv08-036'); GetZoneObject('p1Bench-0')->Energy=['mee-002'];
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeActionScore($ctx,['type'=>'retreat','target'=>'p1Bench-0'])>0,'Still retreats to a powered attacker');
cbBoard('sv07-118'); PokeAdd(1,'Hand','sv07-118');
cbCheck(cbChoice()['type']==='bench','Benches a spare Rotom rather than risk losing its only Pokemon');
PokeApplyAction(cbChoice());
cbCheck(count(PokeObjects(1,'Bench'))===1,'Survival bench uses normal engine action');
PokeAdd(1,'Hand','sv07-118');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeActionScore($ctx,['type'=>'bench','source'=>'p1Hand-0'])<0,'Does not fill the Bench with further spare Rotom');

// Search experiments must actually find the missing evolution line.
cbBoard('sv07-118'); PokeAdd(1,'Deck','me02-019'); PokeAdd(1,'Deck','me02-019');
cbPlay('sv05-144'); cbAnswer();
cbCheck(count(array_filter(PokeObjects(1,'Bench'),fn($c)=>$c->CardID==='me02-019'))===2,'Poffin establishes two Charcadet through normal search resolution');
cbBoard(); cbField('sv07-118'); PokeAdd(1,'Deck','me02-019'); PokeAdd(1,'Deck','me02-019');
cbPlay('sv05-144');
cbCheck(count(explode('&',cbChoice()['value']))===1,'Poffin chooses only one backup when one evolution line and Rotom are established');
cbAnswer(); cbCheck(count(array_filter(PokeObjects(1,'Bench'),fn($c)=>$c->CardID==='me02-019'))===1,'Optional second Poffin pick does not create a third evolution line');
cbBoard('sv07-118'); PokeAdd(1,'Deck','me02-019'); cbPlay('me03-081');
cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='me02-019','Poke Pad finds missing Charcadet'); cbAnswer();
cbCheck(cbChoice()['type']==='bench','Benches Charcadet found with Poke Pad');
cbBoard('me02-019'); PokeAdd(1,'Deck','sv08-036'); PokeAdd(1,'Hand','sv10.5w-084'); PokeAdd(1,'Hand','me02-089');
cbCheck(GetZoneObject(cbChoice()['source'])->CardID==='sv10.5w-084','Hilda prioritizes a missing evolution over fetching more fuel');
PokeApplyAction(cbChoice());
cbCheck(GetZoneObject(cbChoice()['value'])->CardID==='sv08-036','Hilda selects Ceruledge ex'); cbAnswer(); cbAnswer();
cbCheck(cbChoice()['type']==='evolve','Fetched evolution is played immediately');
cbBoard(); PokeAdd(1,'Hand','sv10.5w-084');
cbCheck(cbChoice()['type']!=='trainer','Does not spend Hilda without an evolution target on the board');

// Explorer must value the second keep in the context of its first keep.
cbBoard('sv07-118'); $GLOBALS['p1Deck']=[];
foreach(['me02-019','sv08-036','me02-019','me01-131','sv06-145','mee-002'] as $id)PokeAdd(1,'Deck',$id);
for($i=0;$i<20;++$i)PokeAdd(1,'Deck','mee-002');
cbPlay('sv08.5-107');
$kept=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',cbChoice()['value']));sort($kept);
cbCheck($kept===['me02-019','sv08-036'],'Explorer keeps a complete Charcadet/Ceruledge pair instead of two unevolved Basics');
cbAnswer(); cbCheck(count(array_filter(PokeObjects(1,'Hand'),fn($c)=>$c->CardID==='sv08-036'))===1,'Explorer evolution survives serialized resolution');
cbBoard('sv07-118'); $GLOBALS['p1Deck']=[];
foreach(['me02-019','sv08-036','sv08-164','sv06-145','mee-002','mee-002'] as $id)PokeAdd(1,'Deck',$id);
for($i=0;$i<20;++$i)PokeAdd(1,'Deck','mee-002');
cbPlay('sv08.5-107');
$kept=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',cbChoice()['value']));sort($kept);
cbCheck($kept===['me02-019','sv08-164'],'Explorer preserves useful Blender priority when a complete pair also appears');
cbAnswer();

// Legacy ACE SPEC experiment: use the card rather than silently discard it.
cbBoard(); cbField('me02-019'); PokeAdd(1,'Hand','sv06-167'); PokeAdd(1,'Hand','mee-002'); PokeAdd(1,'Hand','sv06-145');
cbCheck(cbChoice()['type']==='attach'&&GetZoneObject(cbChoice()['source'])->CardID==='sv06-167'&&cbChoice()['target']==='p1Active-0','Attaches unused Legacy to Ceruledge before Carmine');
PokeApplyAction(cbChoice());
cbCheck(PokeHasAttackEnergy(GetZoneObject('p1Active-0'),['Fire']),'Legacy pays the Fire attack requirement');
$ctx=PokeCeruledgeContext(PokeObservation(1));
cbCheck(PokeCeruledgeDiscardScore($ctx,'sv06-167')<0,'Protects unused Legacy from optional discard costs');
PokeDealAttackDamage(2,'p2Active-0',999); PokeResolveKnockouts();
cbCheck(PokeVar('legacyEnergyUsed:1',false)&&GetPrizeClaims(2)===1,'Legacy Ceruledge knockout awards one Prize and consumes reduction');
cbBoard(); cbField('me02-019'); PokeSetVar('legacyEnergyUsed:1',true); PokeAdd(1,'Hand','sv06-167');
cbCheck(cbChoice()['type']==='attach'&&cbChoice()['target']==='p1Bench-0','Spent Legacy powers an unpowered backup instead of overattaching to the active');

$games=0;$attacks=0;
foreach(['dhelmise-v2','brisbane-lopunny','ceruledge-ex'] as $opponent)foreach([1,42,77] as $seed)foreach([1,2] as $first){
    $result=PokeSimulateGame($seed,$first,1500,'ceruledge-ex',$opponent,null,null,function($a)use(&$attacks){if($a['player']===1&&$a['type']==='attack')++$attacks;});
    cbCheck($result['status']==='complete',"Complete match vs $opponent, seed $seed, first $first: ".$result['reason']);++$games;
}
cbCheck($attacks>0,'Ceruledge attacks in completed games');
echo "PASS $checks Ceruledge bot checks; $games complete matches; $attacks Ceruledge attacks\n";
