<?php
declare(strict_types=1);

// Loaded by HeuristicBot.php. Search only receives DFGObserve(), never the live state.
function DFGProState(array $view): array {
    $state = $view;
    $state['log'] = []; $state['history'] = []; $state['seed'] = 1;
    if ($state['phase'] === 'planning') $state['commitments'] = [null, null];
    if ($state['phase'] === 'switching') $state['switches'] = [null, null];
    if ($state['phase'] === 'cancelling') {
        foreach ([0,1] as $seat) $state['cancelChoices'][$seat] = DFGCanCancel($state,$seat)
            ? null : $state['commitments'][$seat]['main'];
    }
    if ($state['phase'] === 'relocating') {
        foreach ([0,1] as $seat) {
            $state['relocations'][$seat]=null;
            if (!DFGRelocateOptions($state,$seat)) $state['relocations'][$seat]='keep';
        }
    }
    return $state;
}

// Deterministic regret matching for a simultaneous zero-sum payoff matrix.
// Return average strategies, their value, and a best-response exploitability bound.
function DFGProSolve(array $matrix, int $iterations = 160): array {
    $n = count($matrix); $m = count($matrix[0]);
    $regrets = [array_fill(0,$n,0.0), array_fill(0,$m,0.0)];
    $sums = $regrets;
    for ($t=0; $t<$iterations; ++$t) {
        $p=[];
        foreach ([0,1] as $seat) {
            $positive=array_map(fn($v)=>max(0.0,$v),$regrets[$seat]); $total=array_sum($positive);
            $p[$seat]=$total>0 ? array_map(fn($v)=>$v/$total,$positive) : array_fill(0,count($positive),1/count($positive));
            foreach ($p[$seat] as $i=>$v) $sums[$seat][$i]+=$v;
        }
        $rows=array_fill(0,$n,0.0); $cols=array_fill(0,$m,0.0); $value=0.0;
        foreach ($matrix as $i=>$row) foreach ($row as $j=>$v) {
            $rows[$i]+=$p[1][$j]*$v; $cols[$j]+=$p[0][$i]*$v;
            $value+=$p[0][$i]*$p[1][$j]*$v;
        }
        foreach ($rows as $i=>$v) $regrets[0][$i]+=$v-$value;
        foreach ($cols as $j=>$v) $regrets[1][$j]+=$value-$v;
    }
    $p=array_map(fn($values)=>array_map(fn($v)=>$v/$iterations,$values),$sums);
    $rows=array_fill(0,$n,0.0); $cols=array_fill(0,$m,0.0); $value=0.0;
    foreach ($matrix as $i=>$row) foreach ($row as $j=>$v) {
        $rows[$i]+=$p[1][$j]*$v; $cols[$j]+=$p[0][$i]*$v; $value+=$p[0][$i]*$p[1][$j]*$v;
    }
    return ['strategies'=>$p,'value'=>$value,'gap'=>max($rows)-min($cols)];
}

function DFGProSample(array $probabilities, int &$rng): int {
    $rng=(int)(($rng*48271)%2147483647); $target=$rng/2147483647;
    foreach ($probabilities as $i=>$p) { $target-=$p; if ($target<=0) return $i; }
    return array_key_last($probabilities);
}

function DFGProTerminal(array $state): ?float {
    if ($state['phase']!=='finished') return null;
    return $state['winner']==='draw' ? 0.0 : ($state['winner']===0 ? 1000.0 : -1000.0);
}

// Symmetric leaf evaluation: survival, held resources, reusable cards, and exhausted colors.
function DFGProPosition(array $state): float {
    $terminal=DFGProTerminal($state); if ($terminal!==null) return $terminal;
    $values=[];
    foreach ($state['players'] as $p) {
        $values[]= $p['life'] + 0.8*count($p['advantages']) + 0.7*($p['nextAttack']??0)
            - 0.7*($p['nextBlockPenalty']??0) + 0.80*count(DFGAvailable($p))
            - 1.5*count(array_filter(DFG_COLORS,fn($c)=>!DFGAvailable($p,$c)));
    }
    return $values[0]-$values[1];
}

function DFGProPlan(array $plan, array $player): array {
    if (!empty($player['nextAttack'])) $plan['momentum']=$player['nextAttack'];
    if (!empty($player['nextBlockPenalty'])) $plan['blockPenalty']=$player['nextBlockPenalty'];
    return $plan;
}

// Keep every main's best conservation plan and the best four bonus plans.
// A low-scoring main is never excluded entirely; bonus/spend variants are pruned.
function DFGProCandidates(array $state, int $seat): array {
    $ranking=DFGBotPlanRanking(DFGObserve($state,$seat),$seat); $chosen=[]; $seen=[]; $burns=0;
    foreach ($ranking as $entry) {
        $a=$entry['action']; $key=$a['main'].':'.(empty($a['bonuses'])?'save':'burn');
        if (isset($seen[$key])) continue;
        if (!empty($a['bonuses']) && $burns++>=4) continue;
        $seen[$key]=true; $chosen[]=$a;
    }
    return $chosen;
}

// Complete the revealed exchange through the authoritative engine. Rollout reactions
// use the tactical policy; the live Pro reaction decision searches replies separately.
function DFGProSettle(array $state): array {
    for ($step=0;$step<160;++$step) {
        if (in_array($state['phase'],['finished','switching','planning'],true)) return $state;
        if ($state['phase']==='reactions') {
            $seat=$state['reactionTurn']; $action=DFGBotReaction(DFGObserve($state,$seat),$seat);
        } else {
            $seat=null;
            foreach ([0,1] as $actor) {
                $options=match($state['phase']) {
                    'cancelling'=>DFGCancelOptions($state,$actor), 'stripping'=>DFGStripOptions($state,$actor),
                    'relocating'=>DFGRelocateOptions($state,$actor), 'routing'=>DFGCutOptions($state,$actor),
                    'directing'=>DFGForceOptions($state,$actor), default=>[]
                };
                if ($options) { $seat=$actor; break; }
            }
            if ($seat===null) throw new LogicException('Pro rollout has no actor');
            $view=DFGObserve($state,$seat); $view['difficulty']='tactical'; $rng=1;
            $action=DFGBotChoose($view,$seat,$rng);
        }
        $state=DFGApply($state,$seat,$action);
    }
    throw new LogicException('Pro rollout exceeded finite resources');
}

// A threatened player may need multiple burns before any single burn prevents defeat.
// Inspect bundles of up to three reactions instead of assuming greedy passes mean a kill.
function DFGProRescue(array $state, array $plans, array $reactions, int $seat): array {
    $stats=DFGComputeRound($plans,$reactions);
    if ($stats[1-$seat]['damage']<$state['players'][$seat]['life'] || DFGBurnLocked($state,$seat)) return $reactions;
    $used=array_merge($plans[$seat]['bonuses'],[$plans[$seat]['main']]);
    foreach ($reactions as $r) if ($r['seat']===$seat) $used[]=$r['card'];
    $cards=array_values(array_filter(DFGAvailable($state['players'][$seat],$state['players'][$seat]['color']),
        fn($id)=>!in_array($id,$used,true) && DFGCatalog()[$id]['reaction']!==''));
    // Bound unusually overfilled hands; prefer reactions that most improve this exchange.
    usort($cards,function($a,$b)use($plans,$reactions,$state,$seat){
        $value=fn($id)=>DFGCombatValue(DFGComputeRound($plans,[...$reactions,['seat'=>$seat,'card'=>$id]]),$state['players'],$seat);
        return $value($b)<=>$value($a);
    });
    $cards=array_slice($cards,0,8);$bundles=[[]];
    foreach ($cards as $card) foreach ($bundles as $bundle) if (count($bundle)<3) $bundles[]=[...$bundle,$card];
    $best=$reactions;$value=DFGCombatValue($stats,$state['players'],$seat);
    foreach ($bundles as $bundle) {
        if (!$bundle) continue;
        $trial=$reactions;
        foreach ($bundle as $id) $trial[]=['seat'=>$seat,'card'=>$id];
        $score=DFGCombatValue(DFGComputeRound($plans,$trial),$state['players'],$seat)-0.8*count($bundle);
        if ($score>$value) { $value=$score;$best=$trial; }
    }
    return $best;
}

// Include one promising bonus for each main at the endgame horizon. This is a
// shortlist, but allows an opponent to defend with a bonus rather than play main-only.
function DFGProHorizonBonuses(array $state, int $seat): array {
    if (DFGBurnLocked($state,$seat)) return [];
    $own=DFGAvailable($state['players'][$seat],$state['players'][$seat]['color']);
    $enemy=DFGAvailable($state['players'][1-$seat],$state['players'][1-$seat]['color']);$result=[];
    foreach ($own as $main) {
        $best=null;$bestScore=-INF;
        foreach ($own as $bonus) {
            if ($bonus===$main || DFGCatalog()[$bonus]['bonus']==='') continue;
            $plan=DFGProPlan(['main'=>$main,'bonuses'=>[$bonus]],$state['players'][$seat]);$score=0.0;
            foreach ($enemy as $other) {
                $pair=[];$pair[$seat]=$plan;$pair[1-$seat]=DFGProPlan(['main'=>$other,'bonuses'=>[]],$state['players'][1-$seat]);
                $s=DFGComputeRound($pair);$score+=$s[$seat]['damage']-$s[1-$seat]['damage'];
            }
            if ($score>$bestScore) { $bestScore=$score;$best=$plan; }
        }
        if ($best!==null) $result[]=$best;
    }
    return $result;
}

// Next-round horizon: mains, carry, token spends and endgame bonus/reaction reserves.
// Future lethal estimates remain bounded: only an actually resolved current win is 1000.
function DFGProFuture(array $state, array &$cache): float {
    $key=json_encode([$state['round'],$state['players']]);
    if (isset($cache[$key])) return $cache[$key];
    $plans=[];
    foreach ([0,1] as $seat) {
        $plans[$seat]=[]; $seen=[];
        foreach (DFGAvailable($state['players'][$seat],$state['players'][$seat]['color']) as $main) {
            foreach (DFGSpendVariants($state['players'][$seat],$main) as $variant) {
                // At this horizon equally sized flexible spends and extra routes have
                // identical immediate effects. Collapse them before matrix evaluation.
                $variantKey=$main.':'.count($variant['advantages']??[]);
                if (isset($seen[$variantKey])) continue;
                $seen[$variantKey]=true;
                $plans[$seat][]=DFGProPlan(['main'=>$main,'bonuses'=>[]]+$variant,$state['players'][$seat]);
            }
        }
        if (min(array_column($state['players'],'life'))<=12) $plans[$seat]=array_merge($plans[$seat],DFGProHorizonBonuses($state,$seat));
    }
    if (!$plans[0] || !$plans[1]) return $cache[$key]=(!$plans[0] && !$plans[1] ? 0.0 : (!$plans[0] ? -1000.0 : 1000.0));
    $matrix=[]; $position=DFGProPosition($state);
    foreach ($plans[0] as $i=>$a) foreach ($plans[1] as $j=>$b) {
        $pair=[$a,$b]; $stats=DFGComputeRound($pair);
        $reactions=[];
        if (min(array_column($state['players'],'life'))<=12) {
            $first=($state['first']+$state['round']-1)%2;
            foreach ([$first,1-$first,$first,1-$first] as $actor) $reactions=DFGProRescue($state,$pair,$reactions,$actor);
            $stats=DFGComputeRound($pair,$reactions);
        }
        $dead=[$stats[1]['damage'] >= $state['players'][0]['life'], $stats[0]['damage'] >= $state['players'][1]['life']];
        $burnDifference=count($a['bonuses'])-count($b['bonuses']);
        foreach ($reactions as $r) $burnDifference+=$r['seat']===0?1:-1;
        $estimate=$position + $stats[0]['damage']-$stats[1]['damage']
                + DFGStrategicValue($pair,$reactions,$stats,$state['players'],0)
                - 0.8*$burnDifference - 0.65*(count($a['advantages']??[])-count($b['advantages']??[]));
        $matrix[$i][$j]=($dead[0] || $dead[1])
            ? ($dead[0] && $dead[1] ? 0.0 : ($dead[0] ? -50.0 : 50.0))
            : 40.0*tanh($estimate/40.0);
    }
    return $cache[$key]=DFGProSolve($matrix,48)['value'];
}

function DFGProDestination(array $state, array &$cache): array {
    $options=[DFGSwitchOptions($state,0),DFGSwitchOptions($state,1)]; $matrix=[];
    foreach ($options[0] as $i=>$a) foreach ($options[1] as $j=>$b) {
        $trial=$state; $trial['switches']=[null,null];
        $trial=DFGApply($trial,0,['type'=>'switch','color'=>$a]);
        $trial=DFGApply($trial,1,['type'=>'switch','color'=>$b]);
        $matrix[$i][$j]=DFGProTerminal($trial) ?? DFGProFuture($trial,$cache);
    }
    return ['options'=>$options]+DFGProSolve($matrix);
}

function DFGProValue(array $state, array &$cache): float {
    $state=DFGProSettle($state);
    return DFGProTerminal($state) ?? ($state['phase']==='switching'
        ? DFGProDestination($state,$cache)['value'] : DFGProPosition($state));
}

function DFGProReaction(array $state, int $depth, array &$cache): array {
    if ($state['phase']!=='reactions' || $depth===0)
        return ['value'=>DFGProValue($state,$cache),'action'=>['type'=>'pass']];
    $seat=$state['reactionTurn']; $actions=[['type'=>'pass']];
    foreach (DFGReactionOptions($state,$seat) as $id) $actions[]=['type'=>'react','card'=>$id];
    $rank=[];
    foreach ($actions as $action) {
        $trial=DFGApply($state,$seat,$action);
        $rank[]=['state'=>$trial,'action'=>$action,'value'=>DFGProPosition(DFGProSettle($trial))];
    }
    usort($rank,fn($a,$b)=>($seat===0?1:-1)*($b['value']<=>$a['value']));
    // Search the best two burns plus pass, even when passing looks poor immediately.
    $beam=array_slice(array_values(array_filter($rank,fn($r)=>$r['action']['type']!=='pass')),0,2);
    foreach ($rank as $r) if ($r['action']['type']==='pass') $beam[]=$r;
    $best=null;
    foreach ($beam as $r) {
        $v=DFGProReaction($r['state'],$depth-1,$cache)['value'];
        if ($best===null || ($seat===0 ? $v>$best['value'] : $v<$best['value'])) $best=['value'=>$v,'action'=>$r['action']];
    }
    return $best;
}

function DFGProChoose(array $view, int $seat, int &$rng): array {
    $state=DFGProState($view); $cache=[];
    if ($state['phase']==='planning') {
        $plans=[DFGProCandidates($state,0),DFGProCandidates($state,1)]; $matrix=[];
        foreach ($plans[0] as $i=>$a) foreach ($plans[1] as $j=>$b) {
            $trial=DFGApply($state,0,$a); $trial=DFGApply($trial,1,$b);
            $matrix[$i][$j]=DFGProValue($trial,$cache);
        }
        $solution=DFGProSolve($matrix);
        return $plans[$seat][DFGProSample($solution['strategies'][$seat],$rng)];
    }
    if ($state['phase']==='switching') {
        $solution=DFGProDestination($state,$cache);
        return ['type'=>'switch','color'=>$solution['options'][$seat][DFGProSample($solution['strategies'][$seat],$rng)]];
    }
    if ($state['phase']==='reactions') {
        $search=DFGProReaction($state,3,$cache);
        if (($seat===0?1:-1)*$search['value']<=-900) {
            $rescue=DFGProRescue($state,$state['commitments'],$state['reactions'],$seat);
            if (count($rescue)>count($state['reactions'])) return ['type'=>'react','card'=>$rescue[count($state['reactions'])]['card']];
        }
        return $search['action'];
    }
    // Small revealed follow-up choices are evaluated by their resulting future position.
    $actions=match($state['phase']) {
        'relocating'=>array_map(fn($c)=>['type'=>'relocate','color'=>$c],array_merge(['keep'],$view['relocateOptions'])),
        'routing'=>array_map(fn($c)=>['type'=>'cut','color'=>$c],$view['cutOptions']),
        'directing'=>array_map(fn($c)=>['type'=>'direct','color'=>$c],$view['forceOptions']),
        'stripping'=>array_map(fn($c)=>['type'=>'strip','color'=>$c],$view['stripOptions']),
        'cancelling'=>array_map(fn($id)=>['type'=>'cancel','main'=>$id],$view['cancelOptions']), default=>[]
    };
    $best=null; $action=null;
    foreach ($actions as $a) {
        $value=DFGProValue(DFGApply($state,$seat,$a),$cache);
        if ($best===null || ($seat===0 ? $value>$best : $value<$best)) { $best=$value; $action=$a; }
    }
    if ($action===null) throw new LogicException('Pro bot has no legal action');
    return $action;
}
