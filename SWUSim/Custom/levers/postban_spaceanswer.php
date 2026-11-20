<?php
// LEVER 'spaceanswer' (theme 'postban'; Force Fam post-ban team read 2026-11-12, lever G5; owner: "bot lever too") — every style keeps its
// space answers in hand once the opponent's ships outnumber mine. Evidence (SWU Forge, post-ban): Maul LOF lost to Luke JTL's A-Wings
// (d506b3b0), Tarfful to Boba's ships (48de427e), Lando to Kazuda (551ad67e), Hemlock DV to Vader JTL (3138bf3b; Hemlock 3-6 vs space-heavy
// decks, 25-6 vs the rest). Today only the control wing protects Hyperspace Disaster (BotResourcing.php ~829/871, vs an aggro leader); the
// midrange/aggro wings resource a space answer like any other card. Change: while the opponent has >= 2 space units and more than I do,
// a card in hand that damages/defeats a space unit, or a space unit with >= 2 power, keeps the key-card bonus (+150, the 'keep' feature's
// scale for the non-control wings). Hook: 'keep' (resourcing only — spending the answer on a ground target is not covered).

const SWU_BOT_LV_SPACEANSWER_KEEP = 150.0;
const SWU_BOT_LV_SPACEANSWER_MIN_SHIPS = 2;

function _SWUBotLvSpaceAnswerCard(string $cid): bool {
    if (preg_match('/(defeat|damage to)[^.]{0,40}space units?/i', strval(CardText($cid)))) return true;
    return str_contains(strval(CardType($cid)), 'Unit') && _SWUBotPlayArena($cid) === 'Space' && intval(CardPower($cid)) >= 2;
}

SWUBotLeverRegister('postban', 'spaceanswer', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $theirs = count(array_filter(SWUBotUnits(SWUBotOpponent($seat)), fn($u) => $u['arena'] === 'Space'));
        if ($theirs < SWU_BOT_LV_SPACEANSWER_MIN_SHIPS) return null;
        $mine = count(array_filter(SWUBotUnits($seat), fn($u) => $u['arena'] === 'Space'));
        if ($theirs <= $mine || !_SWUBotLvSpaceAnswerCard($cid)) return null;
        _SWUBotFired('spaceanswer', $seat);
        return $keep + SWU_BOT_LV_SPACEANSWER_KEEP;
    },
]);
