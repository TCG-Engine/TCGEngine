<?php
// LEVER 'arcanaidentity' (theme 'engines', G1-28 identity-arcana-same-host). The Armorer: when ASH_230 Improvised Identity ("Action: Search the
// top 3 cards … for a ground unit and discard it. Then, you may attack with this unit …") and ASH_084 Arcana Star Map ("If you would search a
// number of cards from your deck, search twice that number instead") were both played in a game, they shared a host only 22 of 48 times —
// Arcana doubles the Identity's search, 3 → 6. 'armorerpicks' (p42) chose which upgrade and host, not by synergy.
// decision — the host pick for an upgrade (ATTACH_UPGRADE, or the Armorer's "play an upgrade from your resources" host): Arcana's host value
// adds the extra cards it makes every "Search the top N" ability on that host see (the host's text and its upgrades'), and a searching
// upgrade's host value adds the doubling when the host already wears Arcana. Each extra card searched is worth SWU_BOT_LV_ARCANA_PER_CARD.
// The rest of the score is the shipped attach scorer, so the scale is unchanged ('identityreach''s term is added when that lever is on).
const SWU_BOT_LV_ARCANA_PER_CARD = 0.5;

// The upgrade being attached at this prompt, or ''.
function _SWUBotLvArcanaidentityUpgrade(array $ctx, string $head): string {
    $armorer = preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $am)
        && preg_match('/play an upgrade from your resources/i', strval(CardText($am[1])) . ' ' . strval(CardDeployText($am[1]) ?? ''));
    if ($head !== 'ATTACH_UPGRADE' && !$armorer) return '';
    $up = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? '');
    return ($up !== '' && str_contains(strval(CardType($up)), 'Upgrade')) ? $up : '';
}
// The cards "Search the top N" texts on a host see: its own text and its upgrades' (an upgrade's granted Action is in the upgrade's text).
function _SWUBotLvArcanaidentitySearchN(array $v, string $extra = ''): int {
    $texts = [strval(CardText(strval($v['cardID']))), strval(CardText($extra))];
    if (isset($v['obj'])) foreach (GetUpgradesOnUnit($v['obj']) as $s) $texts[] = strval(CardText(strval($s->CardID ?? '')));
    $n = 0;
    foreach ($texts as $t) if (preg_match_all('/Search the top (\d+) cards/i', $t, $mm)) foreach ($mm[1] as $k) $n += intval($k);
    return $n;
}
function _SWUBotLvArcanaidentityBonus(array $v, string $up): float {
    if (!isset($v['obj'])) return 0.0;
    $hasArcana = function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($v['obj'], 'ASH_084');
    if (preg_match('/search twice that number of cards instead/i', strval(CardText($up)))) {
        return $hasArcana ? 0.0 : SWU_BOT_LV_ARCANA_PER_CARD * _SWUBotLvArcanaidentitySearchN($v);
    }
    if ($hasArcana && preg_match('/Search the top (\d+) cards/i', strval(CardText($up)), $m)) return SWU_BOT_LV_ARCANA_PER_CARD * intval($m[1]);
    return 0.0;
}

SWUBotLeverRegister('engines', 'arcanaidentity', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!str_starts_with($c, 'my') || !preg_match('/Arena-\d+$/', $c)) return null;
        $up = _SWUBotLvArcanaidentityUpgrade($ctx, $head);
        if ($up === '' || !preg_match('/search twice that number|Search the top \d+ cards/i', strval(CardText($up)))) return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null || ($s = _SWUBotAttachScore($seat, $c, $up, $W)) === null) return null;
        $s += _SWUBotLvArcanaidentityBonus($v, $up);
        if (SWUBotProposalOn('identityreach') && function_exists('_SWUBotLvIdentityreachBonus')) $s += _SWUBotLvIdentityreachBonus($seat, $v, $up);
        return $s;
    },
]);
