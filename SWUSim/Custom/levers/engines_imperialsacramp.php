<?php
// LEVER 'imperialsacramp' (theme 'engines', ASH lever hunt G2-37). Off by default: '@try-imperialsacramp' / '@try-engines'.
// ASH_103 Long Live the Empire ("Defeat a friendly Imperial unit. If you do, resource the top card of your deck"; Gideon lists 57%, 2.8
// copies) was played in 1% of games and resourced 10 of 14 sightings: its only tag is 'sacrifice' (weight 0), so it priced develop x 2.
// Priced as what it does: the ramp (W['develop'] per resource — the resource stays), the sacrificed Imperial's When Defeated (W['ability'],
// e.g. Moff Gideon's recursion, Door Technician's heal), and — with ASH_008 Moff Gideon's front Action ready ("If a friendly Imperial
// unit was defeated this phase, play a unit from your hand. It costs 1 resource less") and a unit it then plays — the switched-on
// Action (W['develop'] + W['ability']); minus the body, the cheapest Imperial by the sacrifice price.

SWUBotLeverRegister('engines', 'imperialsacramp', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($cid !== 'ASH_103') return null;
        $pick = null; $pickCost = null;
        foreach (SWUBotUnits($seat) as $u) {
            if (!empty($u['isLeader']) || !isset($u['obj']) || !TraitContains($u['obj'], 'Imperial')) continue;
            $sc = SWUBotSacrificeCost($u);
            if ($pickCost === null || $sc < $pickCost) { $pick = $u; $pickCost = $sc; }
        }
        if ($pick === null) return null;
        $gain = floatval($W['develop']);
        if (preg_match('/When Defeated:/i', strval(CardText($pick['cardID'])))) $gain += floatval($W['ability']);
        $gideon = _SWUBotLeaderFront($seat, 'ASH_008');
        if ($gideon !== null && _SWUBotLeaderReady($gideon) && ($o = _SWUBotHandObject($seat, $action)) !== null) {
            $left = SWUTotalPaymentCapacity($seat) - intval(SWUComputePlayCost($seat, $o));   // the ramped resource is not counted ready
            foreach (GetHand($seat) as $j => $q) {
                if ($q === null || !empty($q->removed) || $q === $o) continue;
                if (str_contains(strval(CardType(strval($q->CardID ?? ''))), 'Unit') && intval(SWUComputePlayCost($seat, $q)) - 1 <= $left) {
                    $gain += floatval($W['develop']) + floatval($W['ability']);
                    break;
                }
            }
        }
        return $v + $gain - $pickCost;
    },
]);
