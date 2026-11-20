<?php
// LEVER 'strikekill' (theme 'postban'; Force Fam post-ban team read 2026-11-12, lever G4). A "deal damage equal to power" strike (tag
// power-strike: Craving Power LOF_091 and kin) is cast by tier-A/B humans only when it kills: 9/9 kills when power >= remaining HP
// (a185c77e, 4975b873 x3, e031c666, a908d52d …); the soften-only casts were errors (a5178a20 R7, c4309352 R14, 5a4d727e R3 — that game was
// lost). p71 'cravingstrike' adds the kill bonus, but with no kill the card keeps its flat damage-enemy-unit value, and the bot's
// post-ban Krennic fixture still cast it soften-only ~10% of the time (traces: 89% killed, n 65). Here the play is held while no enemy
// unit dies to the strike (SWUBotPowerStrikeBestKill over SWUBotPowerStrikePower). Hook: 'play'. (Not covered: the host pick.)

const SWU_BOT_LV_STRIKEKILL_HOLD = -0.4;

SWUBotLeverRegister('postban', 'strikekill', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!in_array('power-strike', SWUBotCardTags($cid), true)) return null;
        if (SWUBotPowerStrikeBestKill($seat, SWUBotPowerStrikePower($seat, $cid)) > 0.0) return null;
        _SWUBotFired('strikekill', $seat);
        return min($v, SWU_BOT_LV_STRIKEKILL_HOLD);
    },
]);
