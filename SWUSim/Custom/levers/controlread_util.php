<?php
// Shared helpers for the 'controlread' levers (Hemlock DV human-game read, report 2026-10-18; owner rulings 2026-10-19).
// No lever registers here — this file only defines helpers the controlread_* levers call.

const SWU_BOT_LV_HARDCONTROL_RANK = 3.5;   // SWU_BOT_OPPONENT_STYLES mean rank at/above which a leader+base reads hard control
// Owner 2026-10-19: a defeated leader is gone as a unit for the rest of the game, except these three, which can come out again.
const SWU_BOT_LV_REDEPLOY_LEADERS = ['JTL_013', 'JTL_014', 'ASH_018'];

// Any opponent whose leader + base usually plays hard control in the meta (public knowledge: leader and base are face up).
function _SWUBotLvOppIsHardControl(int $seat): bool {
    if (!function_exists('SWUBotLeaderBaseStyleRank')) return false;
    foreach (SWUBotOpponents($seat) as $o) {
        $leader = strval((GetLeader($o)[0] ?? null)->CardID ?? '');
        $base = strval((GetBase($o)[0] ?? null)->CardID ?? '');
        $r = SWUBotLeaderBaseStyleRank($leader, $base);
        if ($r !== null && $r >= SWU_BOT_LV_HARDCONTROL_RANK) return true;
    }
    return false;
}

// A hard-control opponent is KNOWN to hold Nebula Ignition (JTL_080, "Defeat each unit that isn't upgraded"): a copy was seen in
// their hand and has not gone public since (SWUBotOpponentCardModel's 'known', BotNameCard.php).
function _SWUBotLvNebulaSeen(int $seat): bool {
    if (!_SWUBotLvOppIsHardControl($seat) || !function_exists('SWUBotOpponentCardModel')) return false;
    $m = SWUBotOpponentCardModel($seat);
    return intval($m['Nebula Ignition']['known'] ?? 0) > 0;
}
