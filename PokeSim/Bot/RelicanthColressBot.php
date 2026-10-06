<?php
require_once __DIR__.'/RelicanthDrawBot.php';
/** Same fossil replacement plan, with Colress/Spiky access and legal Stamp use.
 * Normalize equivalent printings only in this policy's observation copy.
 */
function PokeRelicanthColressChoose(array $view): ?array {
    $aliases=['30th-126'=>'me03-081','sv08.5-114'=>'sv07-139',
        'me02.5-192'=>'me01-119','me02.5-183'=>'me01-114'];
    $seat=$view['viewer'];
    foreach (['Hand','Discard','TempZone'] as $zone) {
        foreach ($view['players'][$seat][$zone] as &$card) $card['id']=$aliases[$card['id']]??$card['id'];
        unset($card);
    }
    if ($view['decision']) {
        foreach ($view['decision']['choices'] as &$choice) {
            if (isset($choice['card'])) $choice['card']=$aliases[$choice['card']]??$choice['card'];
        }
        unset($choice);
    }
    $ctx=PokeBotContext($view);
    if ($view['decision']) {
        $decision=$view['decision'];
        if (str_contains($decision['prompt'], 'Colress: choose an Energy')) {
            $choices=$decision['choices'];
            usort($choices, fn($a,$b)=>($b['card']==='sv09-159')<=>($a['card']==='sv09-159'));
            return ['type'=>'decision','player'=>$seat,'value'=>$choices[0]['value']??'-'];
        }
        return ['type'=>'decision','player'=>$seat,'value'=>PokeRelicanthDrawDecision($ctx,$decision)];
    }
    $score=static function(array $action) use ($ctx): float {
        $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
        if ($action['type']==='trainer' && ($card['id']??'')==='sv06-155') {
            $useful=array_filter($ctx['own']['Discard'],fn($c)=>
                ($c['id']==='me05-017'&&PokeRelicanthNeedsAttacker($ctx))
                || (CardType($c['id'])==='Energy'&&CardEnergyType($c['id'])==='Normal'&&PokeRelicanthNeedsEnergyAccess($ctx)));
            return $useful?210:-20;
        }
        if ($action['type']==='trainer' && ($card['id']??'')==='me04-082') {
            return $ctx['enemy']['handCount']>3?80:-20;
        }
        if ($action['type']==='trainer' && ($card['id']??'')==='sv08.5-107') {
            return PokeRelicanthDrawNeedsDraw($ctx)||count($ctx['own']['Hand'])<=3?150:-20;
        }
        if ($action['type']==='trainer' && ($card['id']??'')==='sv06-165') {
            return PokeRelicanthDrawNeedsDraw($ctx)||$ctx['enemy']['handCount']>2?230:-20;
        }
        $score=PokeRelicanthDrawActionScore($ctx,$action);
        if ($action['type']==='attach' && ($card['id']??'')==='sv09-159' && $score>0) $score+=10;
        return $score;
    };
    $actions=$view['actions'];
    usort($actions, fn($a,$b)=>$score($b)<=>$score($a));
    return $actions[0]??null;
}
