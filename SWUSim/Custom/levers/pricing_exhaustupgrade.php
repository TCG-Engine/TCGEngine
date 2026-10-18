<?php
// LEVER 'exhaustupgrade' (theme 'pricing', G3-27 exhaust-upgrade-timing). SEC_069 Nimble Prowess ("When Played: You may exhaust a unit in attached
// unit's arena") had no effect in 62% of Leia's plays and 70% of Chewie's — round-1 plays with no enemy unit at all.
// play — an exhaust-on-play upgrade waits unless an enemy READY unit (a swing it would deny this round) shares an arena with a friendly unit
//        that could host it.
// (The analyst's "or it blocks a lethal line" exception is not modelled; the 'exhaustlock' proposal is the price, this is the hold.)

const SWU_BOT_LV_EXHAUSTUPGRADE_RE = '/When Played: (?:You may )?exhaust (?:a|an enemy) unit in attached unit\'s arena/i';

SWUBotLeverRegister('pricing', 'exhaustupgrade', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!str_contains(strval(CardType($cid)), 'Upgrade') || !preg_match(SWU_BOT_LV_EXHAUSTUPGRADE_RE, strval(CardText($cid)))) return null;
        $mine = [];
        foreach (SWUBotUnits($seat) as $u) $mine[$u['arena']] = true;
        foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && isset($mine[$e['arena']])) return null;
        return min($v, -0.4);
    },
]);
