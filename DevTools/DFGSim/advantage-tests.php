<?php
declare(strict_types=1);
require_once __DIR__ . '/../../DFGSim/Bot/HeuristicBot.php';
$checks = 0; $failures = 0;
function checkAdv(bool $ok, string $message): void { global $checks, $failures; ++$checks; if (!$ok) { ++$failures; echo "FAIL: $message\n"; } }
function rejectsAdv(callable $fn): bool { try { $fn(); } catch (InvalidArgumentException|LogicException $e) { return true; } return false; }
function revealAdv(int $main, int $enemy, array $owned = [], array $spend = [], ?string $extra = null, array $enemyBonus = []): array {
    $s = DFGNewGame(DFGCatalog()[$main]['color'], DFGCatalog()[$enemy]['color']);
    $s['players'][0]['advantages'] = $owned;
    $s = DFGApply($s, 0, ['type'=>'commit','main'=>$main,'advantages'=>$spend] + ($extra === null ? [] : ['extraSwitch'=>$extra]));
    return DFGApply($s, 1, ['type'=>'commit','main'=>$enemy,'bonuses'=>$enemyBonus]);
}
function resolveAdv(array $s): array { while ($s['phase'] === 'reactions') $s = DFGApply($s,$s['reactionTurn'],['type'=>'pass']); return $s; }
checkAdv(DFGNewGame()['players'][0]['advantages'] === [], 'matches begin without tokens');
$s = DFGNewGame(); $s['players'][0]['advantages'] = ['Red','Yellow','Blue']; $original = $s;
foreach ([['main'=>1,'advantages'=>['Red']], ['main'=>2,'advantages'=>['Purple']], ['main'=>2,'advantages'=>['Red','Red']], ['main'=>5,'advantages'=>['Red','Yellow','Blue']], ['main'=>5,'advantages'=>['Yellow']], ['main'=>5,'advantages'=>['Red'],'extraSwitch'=>'Green'], ['main'=>5,'advantages'=>['Yellow'],'extraSwitch'=>'Red'], ['main'=>5,'advantages'=>'Red'], ['main'=>5,'advantages'=>[[]]]] as $action) {
    checkAdv(rejectsAdv(fn()=>DFGApply($s,0,['type'=>'commit']+$action)), 'reject unavailable, malformed, duplicate, wrong-card, excessive, and invalid Yellow spending');
}
checkAdv($s === $original, 'rejected spends do not change tokens or commitments');
$allBurned = $s; $allBurned['players'][0]['discarded'] = DFGAvailable($s['players'][0],'Green');
checkAdv(rejectsAdv(fn()=>DFGApply($allBurned,0,['type'=>'commit','main'=>5,'advantages'=>['Yellow'],'extraSwitch'=>'Green'])), 'Yellow cannot unlock an exhausted hand');
$s = DFGApply($s,0,['type'=>'commit','main'=>5,'advantages'=>['Yellow','Red'],'extraSwitch'=>'Green']);
checkAdv($s['players'][0]['advantages'] === ['Red','Yellow','Blue'], 'tokens remain public and unchanged before reveal');
$view = DFGObserve($s,1);
checkAdv($view['commitments'][0] === ['hidden'=>true] && !str_contains(json_encode($view['log']),'spend'), 'token choices and Yellow destination stay hidden before reveal');
checkAdv($view['players'][0]['advantages'] === ['Red','Yellow','Blue'], 'opponent can see held tokens');
$s = DFGApply($s,1,['type'=>'commit','main'=>31]);
checkAdv($s['players'][0]['advantages'] === ['Blue'], 'only committed tokens are consumed at reveal');
checkAdv(DFGObserve($s,1)['commitments'][0]['extraSwitch'] === 'Green', 'token choices become public on reveal');
$stats = DFGComputeRound($s['commitments']);
checkAdv($stats[0]['attack'] === 6 && $stats[0]['block'] === 2, 'Red plus Yellow grants Red attack and the two-color efficiency bonus');
$s = resolveAdv($s);
checkAdv(DFGSwitchOptions($s,0) === ['Blue','Purple','Green'], 'Yellow adds exactly one chosen switch option');
$s = DFGApply($s,0,['type'=>'switch','color'=>'Green']); $s = DFGApply($s,1,['type'=>'switch','color'=>'Red']);
checkAdv($s['players'][0]['color'] === 'Green' && $s['players'][0]['advantages'] === ['Blue'], 'remaining tokens persist when changing hands');

checkAdv(count(DFGCatalog()) === 36 && count(array_filter(DFGCatalog(),fn($c)=>$c['advantage']['earn'] !== null)) === 16, '36-card set now has sixteen objective cards');
foreach ([[4,3,[], 'Yellow'],[8,3,[], 'Orange'],[13,32,[], 'Green'],[22,32,[], 'Blue'],[27,1,[2], 'Red'],[31,1,[], 'Purple'],[3,14,[], 'Green'],[11,16,[], 'Yellow'],[15,3,[6], 'Blue'],[24,18,[], 'Orange'],[25,32,[], 'Purple'],[33,34,[], 'Red'],[12,32,[], 'Orange'],[34,14,[], 'Green'],[6,23,[], 'Red']] as [$main,$enemy,$bonus,$color]) {
    $s = revealAdv($main,$enemy,[],[],null,$bonus);
    checkAdv(!$s['players'][0]['advantages'], 'objectives cannot award tokens before damage');
    $s = resolveAdv($s);
    checkAdv($s['players'][0]['advantages'] === [$color], DFGCatalog()[$main]['name'].' earns its specified token');
    $s = resolveAdv(revealAdv($main,$enemy,[$color],[],null,$bonus));
    checkAdv($s['players'][0]['advantages'] === [$color], 'each color is capped at one token');
    $other=DFGNewGame(DFGCatalog()[$enemy]['color'],DFGCatalog()[$main]['color']);
    $other=DFGApply($other,0,['type'=>'commit','main'=>$enemy,'bonuses'=>$bonus]);
    $other=DFGApply($other,1,['type'=>'commit','main'=>$main]);
    checkAdv(resolveAdv($other)['players'][1]['advantages'] === [$color], 'objective is symmetric for player two');
}
foreach ([[4,2],[8,19],[13,31],[22,31],[27,1]] as [$main,$enemy]) checkAdv(resolveAdv(revealAdv($main,$enemy))['players'][0]['advantages'] === [], 'failed objectives give no token');
foreach ([[3,2],[11,1],[15,19],[24,19],[25,1],[33,1],[12,14],[34,19],[34,31],[6,31],[6,1]] as [$main,$enemy]) checkAdv(resolveAdv(revealAdv($main,$enemy))['players'][0]['advantages'] === [], DFGCatalog()[$main]['name'].' does not earn on failed counterplay');
$s=DFGNewGame('Green','Yellow');
$s=DFGApply($s,0,['type'=>'commit','main'=>24,'bonuses'=>[23]]);$s=DFGApply($s,1,['type'=>'commit','main'=>18]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'Measured Advance rejects bonus-assisted damage');
$s=revealAdv(24,18);$s=DFGApply($s,0,['type'=>'react','card'=>23]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'Measured Advance rejects own reaction burns');
$s=revealAdv(24,18);$s=DFGApply($s,0,['type'=>'pass']);$s=DFGApply($s,1,['type'=>'react','card'=>17]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === ['Orange'], 'opponent reactions do not invalidate an efficient hit');
$s=revealAdv(3,18);$s=DFGApply($s,0,['type'=>'pass']);$s=DFGApply($s,1,['type'=>'react','card'=>17]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'clean counter checks final incoming damage after reactions');
$s=revealAdv(15,3);$s=DFGApply($s,0,['type'=>'react','card'=>16]);$s=DFGApply($s,1,['type'=>'react','card'=>6]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'a reaction burn cannot substitute for the opponent bonus commitment');
checkAdv(resolveAdv(revealAdv(11,3))['players'][0]['advantages'] === ['Yellow'], 'low-attack objective includes the printed attack 3 boundary');
checkAdv(resolveAdv(revealAdv(33,3))['players'][0]['advantages'] === ['Red'], 'heavy-guard objective includes the printed block 5 boundary');
$s=revealAdv(25,1,[],[],null,[2]);$s=DFGApply($s,0,['type'=>'react','card'=>26]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'high-attack objective uses printed attack, not bonus-modified attack');
checkAdv(resolveAdv(revealAdv(33,15,[],[],null,[13]))['players'][0]['advantages'] === [], 'heavy-guard objective uses printed block, not bonus-modified block');
$s=revealAdv(34,14);$s=DFGApply($s,0,['type'=>'pass']);$s=DFGApply($s,1,['type'=>'react','card'=>16]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'Iron Guard must still deal damage after an opposing block reaction');
$s=revealAdv(6,18);$s=DFGApply($s,0,['type'=>'pass']);$s=DFGApply($s,1,['type'=>'react','card'=>17]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'Covering Step loses its objective when a reaction causes final incoming damage');
$s = DFGNewGame('Purple','Red');
$s = DFGApply($s,0,['type'=>'commit','main'=>31,'bonuses'=>[32]]);
$s = DFGApply($s,1,['type'=>'commit','main'=>3]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'Haymaker objective requires no bonus burn even when it deals enough damage');
$s = revealAdv(4,3);
$s = DFGApply($s,0,['type'=>'pass']); $s = DFGApply($s,1,['type'=>'react','card'=>6]);
checkAdv(resolveAdv($s)['players'][0]['advantages'] === [], 'objectives use final damage after reactions, not reveal estimates');
checkAdv(rejectsAdv(fn()=>DFGApply(DFGNewGame(),0,['type'=>'commit','main'=>2,'advantages'=>['Red']])), 'an objective cannot fund a spend in the same round');
foreach ([2=>'Red',9=>'Orange',18=>'Yellow',20=>'Green',30=>'Blue',32=>'Purple'] as $main=>$color) {
    $s = revealAdv($main,1,[$color],[$color]);
    $without=$s['commitments']; unset($without[0]['advantages']);
    $a=DFGComputeRound($without); $b=DFGComputeRound($s['commitments']);
    $expected=DFGCatalog()[$main]['advantage']['spend']['changes'];
    foreach($expected as $change) checkAdv($b[0][$change['stat']]-$a[0][$change['stat']] === $change['amount'], 'specialized spenders apply their printed payoff');
    checkAdv(!$s['players'][0]['advantages'], 'specialized spending consumes the token');
}
$s=revealAdv(5,1,['Red','Purple'],['Red','Purple']);
checkAdv(DFGComputeRound($s['commitments'])[0]['attack'] === 7, 'two attack colors plus combo bonus add three attack');
$s=revealAdv(5,1,['Green','Blue'],['Green','Blue']);$stats=DFGComputeRound($s['commitments']);
checkAdv($stats[0]['attack'] === 5 && $stats[0]['block'] === 4, 'two defensive colors also earn the combo attack bonus');
$s=revealAdv(5,1,['Orange','Yellow'],['Orange','Yellow'],'Orange');$stats=DFGComputeRound($s['commitments']);
checkAdv($stats[0]['pierce'] === 1 && $stats[0]['attack'] === 5, 'Orange pierces and Yellow counts toward the two-color bonus');
$plain=resolveAdv(revealAdv(5,1)); checkAdv(DFGSwitchOptions($plain,0) === ['Blue','Purple'], 'extra Yellow switch is not available without spending');
$s=DFGNewGame(); $s['players'][0]['advantages']=DFG_COLORS;
foreach(DFGSpendVariants(['color'=>'Green','discarded'=>[],'advantages'=>['Yellow']],5) as $variant) checkAdv(($variant['extraSwitch']??null)!=='Red', 'future-hand bot planning cannot use Yellow to stay in the main card’s color');
foreach(DFGEnumeratePlans(DFGObserve($s,0),0) as $plan) checkAdv(!rejectsAdv(fn()=>DFGApply($s,0,['type'=>'commit']+$plan)), 'all enumerated bot spends are legal');
$left=DFGApply($s,0,['type'=>'commit','main'=>5,'advantages'=>['Red','Yellow'],'extraSwitch'=>'Green']);
$right=DFGApply($s,0,['type'=>'commit','main'=>2,'advantages'=>['Red']]); $ra=123;$rb=123;
checkAdv(DFGBotChoose(DFGObserve($left,1),1,$ra) === DFGBotChoose(DFGObserve($right,1),1,$rb), 'bot cannot infer hidden spending from token balances or commitments');
$earned=resolveAdv(revealAdv(4,3)); $earned['players'][0]['color']='Yellow';
$options=DFGEnumeratePlans(DFGObserve($earned,0),0);
checkAdv(count(array_filter($options,fn($p)=>$p['main']===18 && ($p['advantages']??[])===['Yellow'])) > 0, 'bot can plan a payoff using an earned cross-hand token');
echo "$checks advantage checks, $failures failures.\n";
exit($failures?1:0);
