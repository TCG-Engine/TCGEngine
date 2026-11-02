<?php
/** Fighting splash policy. Uses only the supplied own/public observation. */
function PokeCeruledgeFightingContext(array $view): array {
    $ctx=PokeCeruledgeContext($view);$known=$ctx['discardEnergy'];$knownFighting=$ctx['fighting'];
    foreach($ctx['own']['Hand'] as $c)if(CardType($c['id'])==='Energy')++$known;
    foreach($ctx['own']['Discard'] as $c)if(PokeCeruledgeIsFightingEnergy($c['id']))++$knownFighting;
    foreach($ctx['field'] as $c){$known+=count($c['energy']);foreach($c['energy'] as $id)if(PokeCeruledgeIsFightingEnergy($id))++$knownFighting;}
    // The starting deck supplies totals, not identities in the deck or Prizes.
    $ctx['unseenEnergy']=max(0,min($ctx['own']['deckCount'],($ctx['own']['initialEnergy']??21)-$known));
    $ctx['unseenFighting']=max(0,min($ctx['own']['deckCount'],($ctx['own']['initialBasicFighting']??11)-$knownFighting));
    $ctx['missingLuna']=!PokeBotFieldCount($ctx,['me01-074'])&&!isset($ctx['hand']['me01-074']);
    $ctx['missingSol']=!PokeBotFieldCount($ctx,['me01-075'])&&!isset($ctx['hand']['me01-075']);
    return $ctx;
}
function PokeCeruledgeFightingSearchScore(array $ctx,string $id): float {
    if($id==='sv08-164')return $ctx['discardEnergy']<15&&$ctx['unseenEnergy']>0?360:-20;
    if(PokeCeruledgeIsFightingEnergy($id)){
        if($ctx['lunarReady']&&$ctx['fighting']===0)return 410;
        if($ctx['fighting']===0&&PokeBotFieldCount($ctx,['me01-074','me01-075']))return 150;
        return -60;
    }
    return PokeCeruledgeSearchScore($ctx,$id);
}
/** Forecast a retreat with the previous Active moved to the Bench. */
function PokeCeruledgeFightingSwitchContext(array $ctx,array $target): array {
    $bench=array_values(array_filter($ctx['own']['Bench'],fn($c)=>$c['ref']!==$target['ref']));
    foreach($ctx['own']['Active'] as $active)$bench[]=$active;
    $ctx['own']['Bench']=$bench;$ctx['own']['Active']=[$target];
    $ctx['field']=array_merge([$target],$bench);return $ctx;
}
function PokeCeruledgeFightingBaseActionScore(array $ctx,array $action): float {
    $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
    $target=isset($action['target'])?PokeBotCard($ctx,$action['target']):null;
    $active=$ctx['own']['Active'][0]??null;$deck=$ctx['own']['deckCount'];
    // Keep two future turn draws after optional draw effects. Lillie returns
    // the remaining hand first, so count those cards in her projected deck.
    $draw=0;$drawDeck=$deck;
    if($action['type']==='ability'&&$card['id']==='me01-074')$draw=3;
    if($action['type']==='trainer'){
        if(in_array($card['id'],['sv06-145','me03-079'],true))$draw=5;
        if($card['id']==='sv08.5-107')$draw=6;
        if($card['id']==='me02.5-192'){
            $draw=$ctx['own']['prizeCount']===6?8:6;
            $drawDeck+=count($ctx['own']['Hand'])-1;
        }
    }
    if($draw&&$drawDeck-$draw<2)return -20;
    if($action['type']==='attach'&&$active&&$action['target']===$active['ref']&&count($active['energy'])<(int)CardRetreat($active['id'])){
        foreach($ctx['own']['Bench'] as $bench){
            $after=PokeCeruledgeFightingSwitchContext($ctx,$bench);
            if(PokeCeruledgeAttackValue($after,$bench)>PokeCeruledgeAttackValue($ctx,$active)+30){
                if($card['id']==='mee-002'&&$ctx['fighting']>0)return -20;
                return 380;
            }
        }
    }
    if($action['type']==='trainer')switch($card['id']){
        case 'me01-116':
            if($deck===0)return -20;
            if($ctx['lunarReady']&&$ctx['fighting']===0)return 310;
            return $ctx['missingLuna']||$ctx['missingSol']?290:-20;
        case 'me03-081':
            if($deck>0&&($ctx['missingLuna']||$ctx['missingSol']))return 285;
            break;
        case 'sv08-164':return PokeCeruledgeFightingSearchScore($ctx,$card['id']);
        case 'me01-131':
            if(!PokeCeruledgeSafeDiscards($ctx,'me01-131'))return -20;
            $base=PokeCeruledgeTrainerScore($ctx,$card['id']);
            if($ctx['missingLuna']||$ctx['missingSol'])return max(215,$base);
            if($ctx['discardEnergy']<15&&$deck>3&&$ctx['fighting']>=2)return max(160,$base);
            return $base;
        case 'sv06-145':
            if($deck>3&&$ctx['fire']+$ctx['fighting']>=2)return 180+min(60,10*($ctx['fire']+$ctx['fighting']));
            break;
        case 'me02.5-192':
            $draw=$ctx['own']['prizeCount']===6?8:6;
            if($deck<=6&&$deck+count($ctx['own']['Hand'])-1>$draw)return 400;
            break;
    }
    if($action['type']==='attach'&&$card['id']==='mee-002'&&in_array($target['id'],['me01-074','me01-075'],true)&&$ctx['fighting']>0)return -20;
    if($action['type']==='retreat'&&$active){
        $after=PokeCeruledgeFightingSwitchContext($ctx,$target);
        $gain=PokeCeruledgeAttackValue($after,$target)-PokeCeruledgeAttackValue($ctx,$active);
        return $gain>20?270+$gain/100:-20;
    }
    return PokeCeruledgeActionScore($ctx,$action);
}
/** Prefer completing the draw engine once a Ceruledge line is established. */
function PokeCeruledgeFightingOpeningPieces(array $ctx): array {
    return [PokeBotFieldCount($ctx,['me01-074'])+($ctx['hand']['me01-074']??0)>0,
        PokeBotFieldCount($ctx,['me01-075'])+($ctx['hand']['me01-075']??0)>0];
}
function PokeCeruledgeFightingOpeningSearchScore(array $ctx,string $id): float {
    [$luna,$sol]=PokeCeruledgeFightingOpeningPieces($ctx);
    if($ctx['lines']>0){
        if($id==='me01-074'&&!$luna)return 430;
        if($id==='me01-075'&&!$sol)return 430;
    }
    if($luna&&$sol&&$ctx['fighting']===0&&PokeCeruledgeIsFightingEnergy($id))return 440;
    return PokeCeruledgeFightingSearchScore($ctx,$id);
}
function PokeCeruledgeFightingOpeningDiscardScore(array $ctx,string $id): float {
    [$luna,$sol]=PokeCeruledgeFightingOpeningPieces($ctx);
    if(($luna||$sol)&&!($ctx['own']['lunarCycleUsed']??false)&&$ctx['fighting']<=1&&PokeCeruledgeIsFightingEnergy($id))return -180;
    return PokeCeruledgeDiscardScore($ctx,$id);
}
function PokeCeruledgeFightingOpeningSafeCost(array $ctx,string $source): bool {
    $remaining=$ctx['own']['Hand'];
    foreach($remaining as $i=>$card)if($card['ref']===$source){unset($remaining[$i]);--$ctx['hand'][$card['id']];break;}
    for($i=0;$i<2;++$i){
        usort($remaining,fn($a,$b)=>PokeCeruledgeFightingOpeningDiscardScore($ctx,$b['id'])<=>PokeCeruledgeFightingOpeningDiscardScore($ctx,$a['id']));
        $card=array_shift($remaining);if(!$card||PokeCeruledgeFightingOpeningDiscardScore($ctx,$card['id'])<=0)return false;
        --$ctx['hand'][$card['id']];if($card['id']==='mee-002')--$ctx['fire'];if(PokeCeruledgeIsFightingEnergy($card['id']))--$ctx['fighting'];
    }
    return true;
}
function PokeCeruledgeFightingActionScore(array $ctx,array $action): float {
    $base=PokeCeruledgeFightingBaseActionScore($ctx,$action);
    if($ctx['own']['turns']!==1||($ctx['own']['lunarCycleUsed']??false))return $base;
    $id=isset($action['source'])?(PokeBotCard($ctx,$action['source'])['id']??''):'';
    if($action['type']==='ability'&&$id==='me01-074'&&$base>=0)return 500;
    if($action['type']==='bench'&&in_array($id,['me01-074','me01-075'],true)&&!PokeBotFieldCount($ctx,[$id]))return 450;
    if($action['type']==='trainer'&&$ctx['own']['deckCount']>0){
        if($id==='me01-116'&&($ctx['missingLuna']||$ctx['missingSol']||$ctx['lunarReady']&&$ctx['fighting']===0))return 420;
        [$luna,$sol]=PokeCeruledgeFightingOpeningPieces($ctx);
        if($id==='me03-081'&&$ctx['lines']>0&&(!$luna||!$sol))return 410;
        if($id==='me01-131'){
            if(!PokeCeruledgeFightingOpeningSafeCost($ctx,$action['source']))return -20;
            if($ctx['lines']>0&&(!$luna||!$sol))return 400;
        }
    }
    return $base;
}
function PokeCeruledgeFightingDecision(array $ctx,array $decision): string {
    $prompt=$decision['prompt'];
    $search=str_contains($prompt,'Fighting Gong')||str_contains($prompt,'Ultra Ball: search')
        ||str_contains($prompt,'Search your deck')||str_contains($prompt,"Explorer's Guidance: keep");
    $discard=str_contains($prompt,'Ultra Ball: discard');
    if($ctx['own']['turns']===1&&!($ctx['own']['lunarCycleUsed']??false)&&($search||$discard)){
        $choices=$decision['choices'];$selected=[];$max=$decision['type']==='MZMULTICHOOSE'?$decision['max']:1;
        while($choices&&count($selected)<$max){
            $rank=fn($c)=>$discard?PokeCeruledgeFightingOpeningDiscardScore($ctx,$c['card']):PokeCeruledgeFightingOpeningSearchScore($ctx,$c['card']);
            usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));$choice=array_shift($choices);
            if(count($selected)>=$decision['min']&&$rank($choice)<0)break;
            $selected[]=$choice['value'];$id=$choice['card'];$delta=$discard?-1:1;$ctx['hand'][$id]=($ctx['hand'][$id]??0)+$delta;
            if($id==='mee-002')$ctx['fire']+=$delta;if(PokeCeruledgeIsFightingEnergy($id))$ctx['fighting']+=$delta;
        }
        return implode('&',$selected)?:'-';
    }
    return PokeCeruledgeFightingBaseDecision($ctx,$decision);
}
function PokeCeruledgeFightingBaseDecision(array $ctx,array $decision): string {
    $prompt=$decision['prompt'];
    if(str_contains($prompt,'Brilliant Blender')){
        $choices=array_values(array_filter($decision['choices'],fn($c)=>CardType($c['card']??'')==='Energy'));
        $fire=count(array_filter($choices,fn($c)=>in_array('Fire',PokeEnergyTypes($c['card']),true)));
        $fighting=count(array_filter($choices,fn($c)=>PokeCeruledgeIsFightingEnergy($c['card'])));
        // Preserve access to Fire and the first draw payment, using only cards
        // the search actually offers. Prefer excess Fighting as damage fuel.
        usort($choices,fn($a,$b)=>(int)PokeCeruledgeIsFightingEnergy($b['card'])<=>(int)PokeCeruledgeIsFightingEnergy($a['card']));
        $selected=[];
        foreach($choices as $c){
            if(count($selected)>=$decision['max'])break;
            if(in_array('Fire',PokeEnergyTypes($c['card']),true)){if($ctx['fire']===0&&$fire<=1)continue;--$fire;}
            if(PokeCeruledgeIsFightingEnergy($c['card'])){if($ctx['lunarReady']&&$ctx['fighting']===0&&$fighting<=1)continue;--$fighting;}
            $selected[]=$c['value'];
        }
        return implode('&',$selected)?:'-';
    }
    if(str_contains($prompt,'Fighting Gong')||str_contains($prompt,'Gather Strength')){
        $choices=$decision['choices'];$selected=[];$max=$decision['type']==='MZMULTICHOOSE'?$decision['max']:1;
        while($choices&&count($selected)<$max){
            $rank=fn($c)=>str_contains($prompt,'Gather Strength')?max(80,PokeCeruledgeFightingSearchScore($ctx,$c['card'])):PokeCeruledgeFightingSearchScore($ctx,$c['card']);
            usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));
            $c=array_shift($choices);if(count($selected)>=$decision['min']&&$rank($c)<0)break;
            $selected[]=$c['value'];$ctx['hand'][$c['card']]=($ctx['hand'][$c['card']]??0)+1;
            if($c['card']==='mee-002')++$ctx['fire'];
            if(PokeCeruledgeIsFightingEnergy($c['card']))++$ctx['fighting'];
        }
        return implode('&',$selected)?:'-';
    }
    return PokeCeruledgeDecision($ctx,$decision);
}
function PokeCeruledgeFightingChoose(array $view): ?array {
    $ctx=PokeCeruledgeFightingContext($view);
    if($view['decision'])return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeCeruledgeFightingDecision($ctx,$view['decision'])];
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeCeruledgeFightingActionScore($ctx,$b)<=>PokeCeruledgeFightingActionScore($ctx,$a));
    return $actions[0]??null;
}
