<?php
// LEVER 'healblocked' (theme 'attrition'). Ninin vs Axium (Hemlock mirror) R7, replayed: with MY OWN HMW_159 General Grievous ("Bases can't be
// healed.") in play, the bot cast Lost and Forgotten ("Defeat a non-leader unit. If you do, heal 3 damage from your base") and the engine
// logged "couldn't heal P1's base (General Grievous)". The engine locks every base heal at one funnel (_SWUBasesCantBeHealed, CombatLogic.php:
// TWI_132 Confederate Tri-Fighter, HMW_159, either side's); the bot's heal pricing never asked. While the lock is on:
//   healcap — no base healing is worth having (the "When an enemy unit is defeated: Heal N" engines: Chimaera, Iden);
//   play    — a card's flat base-heal tag value goes.
SWUBotLeverRegister('attrition', 'healblocked', [
    'healcap' => function (int $cap, int $seat): ?int {
        return (function_exists('_SWUBasesCantBeHealed') && _SWUBasesCantBeHealed() && $cap > 0) ? 0 : null;
    },
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!function_exists('_SWUBasesCantBeHealed') || !_SWUBasesCantBeHealed()) return null;
        if (!preg_match('/heal \d+ damage from (?:your|a|each|that) base|heal \d+ damage from your base/i', strval(CardText($cid)))) return null;
        $tags = SWUBotCardTags($cid); $cut = 0.0;
        foreach (['heal', 'heal-friendly-base-spread'] as $t) if (in_array($t, $tags, true)) $cut += floatval($W[$t] ?? 0.0);
        return $cut > 0.0 ? $v - $cut : null;
    },
]);
