<?php
// LEVER 'strikeupgradekeep' (theme 'resourcing'; ASH lever hunt G2-17) — midrange keeps a power-strike upgrade it has the big body for.
// Evidence: Piett resourced Craving Power LOF_091 ("When Played: Deal damage to an enemy unit equal to attached unit's power") 139x (38 in
// openings), played in 3% of games, 89% WR when played; Gideon 21 resourced / 17% played / 79% WR; Vader Y Splash 30 of 48 sightings resourced.
// The shipped 'cravinganswer' (p43) makes it an answer only in the control wing's tiered path. Change: on the plain resourcer, for rank >= 2, a
// power-strike upgrade that damages an enemy unit (the 'cravinganswer' tag test) takes the key-card keep (feature 'keep': +150, control +50)
// while I control a unit with power >= 5 or hold one in hand — its play value already becomes a kill (SWUBotPowerStrikeCanKill).
// Hook: 'keep'.

const SWU_BOT_LV_STRIKEUPGRADEKEEP_POWER = 5;

function _SWUBotLvStrikeupgradekeepBig(int $seat): bool {
    foreach (SWUBotUnits($seat) as $v) if (intval($v['power']) >= SWU_BOT_LV_STRIKEUPGRADEKEEP_POWER) return true;
    foreach (_SWUBotHandCards($seat) as $h) if (str_contains(strval(CardType($h)), 'Unit') && intval(CardPower($h)) >= SWU_BOT_LV_STRIKEUPGRADEKEEP_POWER) return true;
    return false;
}

SWUBotLeverRegister('resourcing', 'strikeupgradekeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $rank = SWUBotStyleRank(strval($ctx['style'] ?? ''));
        if ($rank < 2 || !SWUBotFeatureOn('keep') || !str_contains(strval(CardType($cid)), 'Upgrade')) return null;
        // The tiered resourcer (control vs aggro) already counts it as an answer ('cravinganswer'); this is the plain path.
        if ($rank >= 3 && (SWUBotProposalOn('resourcing2') || (SWUBotFeatureOn('resourcing3') && SWUBotOpponentIsAggroLeader($seat)))) return null;
        $tags = SWUBotCardTags($cid);
        if (!in_array('power-strike', $tags, true) || !in_array('damage-enemy-unit', $tags, true) || SWUBotIsKeyCard($seat, $cid)) return null;
        if (!_SWUBotLvStrikeupgradekeepBig($seat)) return null;
        return $keep + (($rank >= 3 && !SWUBotProposalOn('keepequal')) ? 50.0 : 150.0);
    },
]);
