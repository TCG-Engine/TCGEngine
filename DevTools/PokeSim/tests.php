<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
set_error_handler(function($severity,$message,$file,$line) { if (error_reporting() & $severity) throw new ErrorException($message,0,$severity,$file,$line); });
$checks = 0;
function check($value, $message): void { global $checks; ++$checks; if (!$value) throw new RuntimeException($message); }
function fixture($own = 'me05-039', $enemy = 'me05-039'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN'); SetTurnNumber(4); SetTurnPlayer(1); SetFirstPlayer(2); SetRandomState(42);
    foreach ([1,2] as $seat) {
        AddPlayerTurns($seat,2); AddSetupReady($seat,true);
        $obj=PokeAdd($seat,'Active',$seat===1?$own:$enemy);$obj->Controller=$seat;$obj->Energy=['mee-005'];
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-005');
        for($i=0;$i<15;++$i)PokeAdd($seat,'Deck','mee-005');
    }
}
function play($id): void { $obj=PokeAdd(1,'Hand',$id);PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>PokeRef(1,'Hand',$obj->mzIndex)]); }
function answer($value, $player=1): void {
    // Every answer crosses a JSON snapshot, as it does in the local HTTP app.
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
    PokeApplyAction(['type'=>'decision','player'=>$player,'value'=>$value]);
}
function choice($id,$player=1): string { foreach (PokeDecisionOptions($player)['choices'] as $c) if ($c['card']===$id)return $c['value'];throw new RuntimeException("Missing choice $id"); }
$deck=PokeParseDeckText(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt'));
check(count($deck)===20 && !PokeValidateDeck($deck),'All 20 deck printings must be implemented');
fixture();$o=GetZoneObject('p1Active-0');$o->Damage=20;$o->Energy[]='me03-088';$o->Conditions=['Poisoned'=>10];$o->Evolutions=['me05-005'];
$state=PokeStateExport();PokeStateImport(json_decode(json_encode($state),true));check(PokeStateExport()===$state,'Snapshot must preserve zones, energies, evolutions and conditions');
fixture();$before=PokeStateExport();try{PokeApplyAction(['type'=>'attack','player'=>2,'source'=>'p2Active-0','index'=>0]);throw new RuntimeException('Illegal action accepted');}catch(InvalidArgumentException $e){}check(PokeStateExport()===$before,'Illegal action must not mutate');
fixture('me05-005','me05-005');PokePlaceDamageCounters('p1Active-0','p2Active-0',1);check(GetZoneObject('p2Active-0')->Damage===0,'Hide n Sneak stops attack effects');
PokeSetCondition('p1Active-0','p2Active-0','Poisoned','Ability');check(!GetZoneObject('p2Active-0')->Conditions,'Hide n Sneak stops ability effects');
PokeDealAttackDamage(1,'p1Active-0',20);check(GetZoneObject('p2Active-0')->Damage===20,'Hide n Sneak allows damage');
fixture('me05-005');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetZoneObject('p2Active-0')->Damage===10 && GetTurnPlayer()===2,'Furtive Drop places one counter and ends turn');
fixture('me05-006');for($i=0;$i<6;++$i)PokeAdd(1,'Discard','me05-005');$bench=PokeAdd(2,'Bench','me05-039');$bench->Controller=2;
PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetZoneObject('p2Active-0')->Damage===40&&GetZoneObject('p2Bench-0')->Damage===40,'Matcha Spin spreads counters');
fixture('me05-039','me05-005');for($i=0;$i<4;++$i)PokeAdd(1,'Discard','me05-006');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetWinner()===1,'Vengeful Anchor gains 140 damage and wins for no Pokémon');
fixture('me05-034');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetTurnPlayer()===1,'Puppet Pull waits for search before ending turn');answer(choice('mee-005'));check(PokeCount(1,'Hand')===1&&GetTurnPlayer()===2&&GetZoneObject('p2Active-0')->Damage===80,'Puppet Pull resolves across snapshot');
check(!array_filter(PokeObservation(2)['log'],fn($entry)=>$entry['event']==='reveal-search'),'Puppet Pull does not reveal its unrestricted search');
fixture('me05-034');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);answer('PASS');check(GetTurnPlayer()===2,'Optional PASS normalizes to a resumable skip');
foreach (['me02.5-207'=>'sv08-164','me02.5-209'=>'me02.5-207','me03-072'=>'mee-005','me03-081'=>'me05-006','sv08-165'=>'me02.5-183'] as $trainer=>$target) {
    fixture(); if($trainer==='sv08-165') { SetFirstPlayer(2);AddPlayerTurns(1,1); }
    PokeAdd(1,'Deck',$target); play($trainer); answer(choice($target));check(PokeCount(1,'Hand')===1,"$trainer search resolves");
}
fixture();AddPlayerTurns(1,2);$bell=PokeAdd(1,'Hand','sv08-165');check(!PokeCanPlayTrainer(1,'p1Hand-0'),'Call Bell only works on second player first turn');
fixture();PokeAdd(1,'Hand','me05-005');PokeAdd(1,'Hand','me05-006');PokeAdd(1,'Deck','me05-039');play('me02.5-213');
check(PokeDecisionOptions(1)['min']===2,'Ultra Ball requires two discards');$cs=array_column(PokeDecisionOptions(1)['choices'],'value');answer(implode('&',$cs));answer(choice('me05-039'));check(PokeCountHideSneak(1)===2&&PokeCount(1,'Hand')===1,'Ultra Ball pays cost and searches across two awaits');
fixture();PokeAdd(1,'Hand','me02.5-213');PokeAdd(1,'Hand','me05-005');PokeAdd(1,'Hand','me05-006');PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);$cs=array_column(PokeDecisionOptions(1)['choices'],'value');answer(implode('&',$cs));answer('-');check(PokeCountHideSneak(1)===2&&PokeCount(1,'Hand')===0,'Pending indices survive a removed card before candidates and optional skip resumes');
fixture();for($i=0;$i<5;++$i)PokeAdd(1,'Deck','me05-006');play('sv08-164');$cs=array_filter(PokeDecisionOptions(1)['choices'],fn($c)=>$c['card']==='me05-006');answer(implode('&',array_column($cs,'value')));check(PokeCountHideSneak(1)===5,'Brilliant Blender discards five');
fixture();PokeAdd(1,'Discard','me05-006');play('me02.5-196');answer(choice('me05-006'));check(PokeCount(1,'Hand')===1,'Night Stretcher recovers Pokémon');
fixture();PokeAdd(1,'Discard','mee-005');PokeAdd(1,'Discard','mee-005');play('sv10.5w-082');answer(implode('&',array_column(PokeDecisionOptions(1)['choices'],'value')));check(PokeCount(1,'Hand')===2,'Energy Retrieval recovers two basics');

fixture();
PokeAdd(1, 'Hand', 'me02-094');
PokeAdd(1, 'Discard', 'mee-005');
check(!PokeCanPlayTrainer(1, 'p1Hand-0'), 'Wondrous Patch requires a Benched Psychic Pokémon even with a Psychic Active');

fixture();
PokeAdd(1, 'Hand', 'me02-094');
PokeAdd(1, 'Bench', 'me05-034')->Controller = 1;
PokeAdd(1, 'Bench', 'me05-006')->Controller = 1;
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
PokeAdd(1, 'Discard', 'mee-002');
PokeAdd(1, 'Discard', 'me03-088');
PokeAdd(2, 'Discard', 'mee-005');
check(!PokeCanPlayTrainer(1, 'p1Hand-0'), 'Wondrous Patch requires Basic Psychic Energy in the player’s own discard');
PokeAdd(1, 'Discard', 'mee-005');
$patchAction = ['type'=>'trainer', 'player'=>1, 'source'=>'p1Hand-0'];
PokeApplyAction($patchAction);
$decision = PokeDecisionOptions(1);
check($decision['min'] === 1 && array_column($decision['choices'], 'value') === ['p1Discard-2'], 'Wondrous Patch only offers Basic Psychic Energy from the player’s discard');
answer('p1Discard-2');
$decision = PokeDecisionOptions(1);
check($decision['min'] === 1 && array_column($decision['choices'], 'value') === ['p1Bench-0'], 'Wondrous Patch only offers the player’s Benched Psychic Pokémon, including evolutions');
answer('p1Bench-0');
check(GetZoneObject('p1Bench-0')->Energy === ['mee-005'] && !GetEnergyUsed(1) && PokeDecisionOptions(1) === null
    && array_values(array_map(fn($card) => $card->CardID, PokeObjects(1, 'Discard'))) === ['mee-002', 'me03-088', 'me02-094'],
    'Wondrous Patch attaches the chosen Energy and preserves the manual attachment');

fixture();$b=PokeAdd(2,'Bench','me05-005');$b->Controller=2;play('me02.5-183');answer(choice('me05-005'));check(GetZoneObject(PokeFirstRef(2,'Active'))->CardID==='me05-005','Boss orders switches opponent');
fixture();PokeAdd(1,'Hand','mee-005');play('me02.5-192');check(PokeCount(1,'Hand')===8,'Lillie draws eight at six prizes');
fixture();$prizes=&GetPrizes(2);$prizes=array_slice($prizes,0,3);PokeAdd(2,'Hand','me05-006');PokeAdd(2,'Hand','me05-005');play('me04-082');check(PokeCount(2,'Hand')===3&&GetZoneObject(PokeFirstRef(2,'Hand'))->CardID==='mee-005','Special Red Card puts hand on bottom before drawing');
fixture();play('sv09-144');check(PokeVar('blackBelt:1')===GetTurnNumber(),'Black Belt turn effect saved');
fixture('me05-039','sv01-086');PokeBlackBelt(1);PokeDealAttackDamage(1,'p1Active-0',30);check(GetZoneObject('p2Active-0')->Damage===70,"Black Belt adds 40 against Pokémon ex");
fixture();$d=&GetDeck(1);$d=[];PokeAdd(1,'Deck','me02.5-207');for($i=0;$i<10;++$i)PokeAdd(1,'Deck','mee-005');play('sv10.5b-084');check(PokeCount(1,'TempZone')===7,'Pokégear inspects top seven');answer(choice('me02.5-207'));check(PokeCount(1,'Hand')===1&&PokeCount(1,'Deck')===10&&PokeCount(1,'TempZone')===0,'Pokégear restores unchosen cards');
fixture();PokeAdd(1,'Deck','me05-039');PokeAdd(1,'Deck','me05-039');$e=PokeAdd(1,'Hand','me03-088');PokeApplyAction(['type'=>'attach','player'=>1,'source'=>'p1Hand-0','target'=>'p1Active-0']);$cs=array_filter(PokeDecisionOptions(1)['choices'],fn($c)=>$c['card']==='me05-039');answer(implode('&',array_column($cs,'value')));check(PokeCount(1,'Bench')===2&&GetZoneObject('p1Active-0')->Energy===['mee-005','me03-088'],'Telepathic attaches and benches two');
fixture('me05-005');$ev=PokeAdd(1,'Hand','me05-006');GetZoneObject('p1Active-0')->Damage=10;check(PokeCanEvolve(1,$ev,GetZoneObject('p1Active-0')),'Evolution is legal later turn');PokeApplyAction(['type'=>'evolve','player'=>1,'source'=>'p1Hand-0','target'=>'p1Active-0']);check(GetZoneObject('p1Active-0')->Damage===10&&GetZoneObject('p1Active-0')->Evolutions===['me05-005'],'Evolution preserves damage and lineage');
fixture();$b=PokeAdd(1,'Bench','me05-005');$b->Controller=1;GetZoneObject('p1Active-0')->Energy=['mee-005','mee-005','mee-005'];GetZoneObject('p1Active-0')->Conditions=['Poisoned'=>10];PokeApplyAction(['type'=>'retreat','player'=>1,'target'=>'p1Bench-0','payment'=>[0,1,2]]);check(PokeCount(1,'Discard')===3&&GetZoneObject(PokeFirstRef(1,'Active'))->CardID==='me05-005'&&!GetZoneObject(PokeFirstRef(1,'Bench'))->Conditions,'Retreat pays and clears conditions');
fixture();$b=PokeAdd(2,'Bench','me05-039');$b->Controller=2;GetZoneObject('p2Active-0')->Damage=130;PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(PokeDecisionOptions(1)['type']==='MZMULTICHOOSE','KO queues Prize claim');answer(PokeDecisionOptions(1)['choices'][0]['value']);answer(PokeDecisionOptions(2)['choices'][0]['value'],2);check(PokeCount(1,'Prizes')===5&&PokeCount(2,'Active')===1&&GetTurnPlayer()===2,'KO prizes and promotion finish before next turn');
fixture();$d=&GetDeck(2);$d=[];PokeApplyAction(['type'=>'end','player'=>1]);check(GetWinner()===1,'Mandatory turn draw loses on empty deck');
PokeCreateGame($deck,$deck,42,1);$a=PokeStateExport();PokeCreateGame($deck,$deck,42,1);check(PokeStateExport()===$a,'Seeded reset is deterministic');
foreach (explode(' ', '30th-120 sv06-126 sv06-018 sv06-149 me06-080 me06-096 me06-097 sv10-168 me05-077 sv06.5-061 me06-089') as $id) {
    check(PokeCardImplemented($id), "$id passes deck validation's implementation gate");
}
$attack = ['type'=>'attack', 'player'=>1, 'source'=>'p1Active-0', 'index'=>0];

fixture('30th-120');
GetZoneObject('p1Active-0')->Energy = [];
foreach (['me06-080', 'me05-005', 'sv06-126'] as $id) PokeAdd(1, 'Bench', $id)->Controller = 1;
GetZoneObject('p1Bench-0')->Damage = 10;
PokeApplyAction($attack);
check(GetZoneObject('p2Active-0')->Damage === 60, 'Bouncy Circle counts maximum HP on the Bench');

fixture('sv06-126');
PokeAdd(1, 'Deck', 'sv06-018');
PokeApplyAction($attack);
check(count(PokeDecisionOptions(1)['choices']) === 1, 'Find a Friend only offers Pokémon');
answer(choice('sv06-018'));
check(GetZoneObject('p1Hand-0')->CardID === 'sv06-018' && GetTurnPlayer() === 2, 'Find a Friend takes the revealed Pokémon');

fixture('me06-080');
GetZoneObject('p1Active-0')->Energy = [];
PokeAdd(1, 'Bench', 'me05-005')->Controller = 1;
PokeAdd(1, 'Deck', 'sv08-191');
PokeApplyAction($attack);
answer(choice('sv08-191'));
answer(choice('me05-005'));
check(GetZoneObject('p1Bench-0')->Energy === ['sv08-191'] && !GetEnergyUsed(1) && !PokeCount(1, 'Hand') && GetTurnPlayer() === 2,
    'Bouncy-Bounce Charge attaches Special Energy from the deck without triggering hand-only effects');

fixture('sv06-018', 'me05-005');
GetZoneObject('p1Active-0')->Energy = ['sve-001'];
GetZoneObject('p1Active-0')->Conditions = ['Asleep'=>true];
GetZoneObject('p2Active-0')->Conditions = ['Poisoned'=>10];
foreach (['me05-005', 'sv06-126'] as $id) PokeAdd(1, 'Bench', $id)->Controller = 1;
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
play('sv06-149');
check(!GetZoneObject('p1Active-0')->Conditions && !GetZoneObject('p2Active-0')->Conditions, 'Festival Grounds cures both players’ powered Pokémon');
PokeSetCondition('p1Active-0', 'p2Active-0', 'Confused');
check(!GetZoneObject('p2Active-0')->Conditions, 'Festival Grounds prevents new Special Conditions');
PokeApplyAction($attack);
check(PokeDecisionOptions(1)['type'] === 'MZMULTICHOOSE', 'First Do the Wave resolves its knockout before Festival Lead');
answer(PokeDecisionOptions(1)['choices'][0]['value']);
check(PokeDecisionOptions(2)['type'] === 'MZCHOOSE' && !PokeDecisionOptions(1), 'Festival Lead waits for promotion');
answer(choice('me05-039', 2), 2);
check(PokeDecisionOptions(1)['type'] === 'YESNO' && !PokeLegalActions(1), 'Festival Lead offers only the optional follow-up');
answer('YES');
check(GetZoneObject('p2Active-0')->Damage === 40 && GetTurnPlayer() === 2, 'Festival Lead attacks the new Active exactly once');

fixture('sv06-018');
GetZoneObject('p1Active-0')->Energy = ['sve-001'];
PokeAdd(1, 'Bench', 'me05-005')->Controller = 1;
play('sv06-149');
PokeApplyAction($attack);
answer('NO');
check(GetZoneObject('p2Active-0')->Damage === 20 && GetTurnPlayer() === 2, 'Festival Lead can be declined');

fixture('sv06-018');
GetZoneObject('p1Active-0')->Energy = ['sve-001'];
PokeAdd(1, 'Bench', 'me05-005')->Controller = 1;
PokeApplyAction($attack);
check(!PokeDecisionOptions(1) && GetTurnPlayer() === 2, 'Festival Lead needs Festival Grounds');

fixture('sv06-126', 'me05-039');
GetZoneObject('p1Active-0')->Energy = [];
play('sv06-149');
PokeSetCondition('p2Active-0', 'p1Active-0', 'Poisoned');
check(!empty(GetZoneObject('p1Active-0')->Conditions), 'Festival Grounds permits conditions without attached Energy');
PokeAdd(1, 'Hand', 'sve-001');
PokeApplyAction(['type'=>'attach', 'player'=>1, 'source'=>'p1Hand-0', 'target'=>'p1Active-0']);
check(!GetZoneObject('p1Active-0')->Conditions, 'Attaching Energy under Festival Grounds cures conditions');

fixture();
foreach (['me06-096', 'me06-097', 'me05-077', 'sv10-168'] as $id) PokeAdd(1, 'Deck', $id);
play('me06-089');
$options = PokeDecisionOptions(1);
check($options['max'] === 3 && array_column($options['choices'], 'card') === ['me06-096', 'me06-097', 'me05-077'], 'Aarune offers both halves and Supporters, up to three');
answer(implode('&', array_column($options['choices'], 'value')));
check(PokeCount(1, 'Hand') === 3, 'Aarune moves selected cards into hand');

fixture('me05-039', '30th-120');
PokeAdd(1, 'Hand', 'me06-097');
check(!PokeCanPlayTrainer(1, 'p1Hand-0'), 'A single Legendary Summit half cannot be played');
PokeAdd(1, 'Hand', 'me06-097');
check(!PokeCanPlayTrainer(1, 'p1Hand-0'), 'Identical halves cannot form Legendary Summit');
play('me06-096');
check(array_column(GetStadium(), 'CardID') === ['me06-096', 'me06-097'] && PokeCount(1, 'Hand') === 1, 'Playing either half commits the pair as one Stadium');
PokeStateImport(json_decode(json_encode(PokeStateExport()), true));
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
PokeApplyAction($attack);
check(!PokeDecisionOptions(1) && PokeDecisionOptions(2)['type'] === 'MZCHOOSE' && PokeCount(1, 'Prizes') === 6,
    'Legendary Summit can make an attack-damage knockout worth zero Prizes');
answer(choice('me05-039', 2), 2);
PokeAdd(2, 'Hand', 'sv06-149');
PokeApplyAction(['type'=>'trainer', 'player'=>2, 'source'=>'p2Hand-1']);
check(array_column(PokeObjects(1, 'Discard'), 'CardID') === ['me06-096', 'me06-097'], 'Replacing Legendary Summit discards both pieces');

fixture('me05-039', '30th-120');
PokeAdd(1, 'Hand', 'me06-096');
play('me06-097');
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
PokePlaceDamageCounters('p1Active-0', 'p2Active-0', 3);
PokeResolveKnockouts();
check(PokeDecisionOptions(1)['min'] === 1, 'Legendary Summit leaves Prize rewards unchanged for attack-effect damage counters');

fixture();
for ($i = 0; $i < 6; ++$i) PokeAdd(1, 'Discard', 'me05-005');
PokeAdd(1, 'Discard', 'mee-005');
play('sv10-168');
$options = PokeDecisionOptions(1);
check($options['min'] === 1 && $options['max'] === 5 && count($options['choices']) === 6, 'Sacred Ash offers one to five Pokémon from the discard');
answer(implode('&', array_column(array_slice($options['choices'], 0, 5), 'value')));
check(PokeCount(1, 'Deck') === 20 && PokeCount(1, 'Discard') === 3, 'Sacred Ash shuffles the selected Pokémon back');

fixture();
PokeAdd(1, 'Discard', 'me03-088');
PokeAdd(1, 'Discard', 'mee-005');
play('sv06.5-061');
check(count(PokeDecisionOptions(1)['choices']) === 1, 'Night Stretcher excludes Special Energy');
answer(choice('mee-005'));
check(GetZoneObject('p1Hand-0')->CardID === 'mee-005', 'Night Stretcher returns Basic Energy to hand');

fixture('me05-039', 'me05-039');
PokeAdd(1, 'Hand', 'me05-077');
PokeAdd(1, 'Hand', 'mee-005');
check(!PokeCanPlayTrainer(1, 'p1Hand-0'), 'Gladion requires the last card in hand');
GetZoneObject('p1Hand-1')->Remove();
PokeApplyAction(['type'=>'trainer', 'player'=>1, 'source'=>'p1Hand-0']);
PokeApplyAction($attack);
check(GetZoneObject('p2Active-0')->Damage === 110 && GetTurnPlayer() === 2, 'Gladion adds 80 damage for a non-Rule-Box attacker');

fixture('sv01-086');
play('me05-077');
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
PokeDealAttackDamage(1, 'p1Active-0', 30);
check(GetZoneObject('p2Active-0')->Damage === 30, 'Gladion does not boost Rule Box Pokémon');
fixture();
play('me05-077');
PokeAdd(2, 'Bench', 'me05-039')->Controller = 2;
PokeDealAttackDamage(1, 'p1Active-0', 30, false, 'p2Bench-0');
check(GetZoneObject('p2Bench-0')->Damage === 30, 'Gladion does not boost damage to the Bench');
echo "PASS $checks checks\n";
