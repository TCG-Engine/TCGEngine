<?php
// Theme 'beastpitch' (owner 2026-11-08, Tarfful Blue SWU Forge read: docs/superpowers/swuforge-research/2026-11-08_tarfful-blue_bot-feature-report.md).
// Shared reads for 'beastearly' and 'spacepitch'. A "pitch-line" leader is one whose front side reads "Action [N resources, Exhaust, discard
// a card from your hand]: Create a …" (HMW_010 Tarfful) — matched on the printed text, as 'villainpitch' (_SWUBotPitchActionValue) does.
const SWU_BOT_BEASTPITCH_ACTION_RE = '/Action \[(\d+) resources?, Exhaust, discard a card from your hand\]: Create a/i';
// "Ground aggro": an aggro read whose units are mostly ground. Utrecht space shares: Wicket Green Splash 24%, Talzin Mystic Monastery 10%,
// Ahsoka Alliance Outpost 7% read ground; Ahsoka Yellow 40%, Boba Endor 43%, Vader JTL Yellow 83% do not.
const SWU_BOT_GROUND_AGGRO_SPACE_SHARE = 0.25;

// My undeployed pitch-line leader, before the flip turn (fewer than 6 resources, as 'villainpitch'). NULL otherwise.
function _SWUBotBeastPitchLeaderHome(int $seat): ?object {
    if (count(array_filter(GetResources($seat), fn($o) => $o !== null && empty($o->removed))) >= SWUBotLeverNum('PITCH_BEFORE_RESOURCES', SWU_BOT_PITCH_BEFORE_RESOURCES)) return null;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $deployed = $l->Deployed ?? null;
        if (!empty($deployed) && strval($deployed) !== 'false') continue;
        if (preg_match(SWU_BOT_BEASTPITCH_ACTION_RE, strval(CardText(strval($l->CardID ?? ''))))) return $l;
    }
    return null;
}

// The 'villainpitch' case (an off-aspect Villainy card in hand AND Anakin still wanting one): that owner line (2026-10-08) keeps its own pricing.
function _SWUBotBeastPitchHasVillainyPitch(int $seat): bool {
    if (!_SWUBotWantsAspectInDiscard($seat, 'Villainy')) return false;
    foreach (GetHand($seat) as $o) if ($o !== null && empty($o->removed) && _SWUBotOffAspectOf($seat, strval($o->CardID ?? ''), 'Villainy')) return true;
    return false;
}

// An aggro opponent (SWUBotOpponentReadsAggro) whose expected + shown units are at most SWU_BOT_GROUND_AGGRO_SPACE_SHARE space. With no
// read of their units at all, false: a space deck must not be mistaken for a ground one.
function _SWUBotGroundAggroOpponent(int $seat): bool {
    if (!SWUBotOpponentReadsAggro($seat)) return false;
    $ground = 0.0; $space = 0.0;
    foreach (SWUBotOpponentCardModel($seat) as $m) {
        $cid = strval($m['cid'] ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit')) continue;
        $n = max(floatval($m['expected'] ?? 0), floatval($m['shown'] ?? 0));
        if (strval(CardArena($cid)) === 'Space') $space += $n; else $ground += $n;
    }
    if ($ground + $space <= 0.0) return false;
    return $space / ($ground + $space) <= SWU_BOT_GROUND_AGGRO_SPACE_SHARE;
}

// A space unit whose printed keyword is Sentinel (ASH_109 T-6 Shuttle 1974): against ground aggro it guards an arena nobody attacks in.
function _SWUBotIsSpaceSentinel(string $cid): bool {
    global $Sentinel_Cards;
    return str_contains(strval(CardType($cid)), 'Unit') && strval(CardArena($cid)) === 'Space' && isset($Sentinel_Cards[$cid]);
}
