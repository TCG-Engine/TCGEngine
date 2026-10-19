<?php
// LEVER 'fatiguefeed' (theme 'controlread', report G5; owner 2026-10-19: "when i'm playing soft control into hard control … always let them
// draw when there's a choice to either damage my unit or draw … the early pressure with my units is valuable"). Dedra SEC_010 "Choose an
// enemy unit. Its controller may deal N damage to it. If they don't, draw a card." YES = take the damage (feature 'dedra',
// BotFallback.php:1169). Ninin answered NO 6 of 6; Dedra decked out and lost to exactly 9 fatigue (54ae). Soft control (rank 3) vs hard
// control only — an explicit exception to the never-gift ruling.
SWUBotLeverRegister('controlread', 'fatiguefeed', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'YESNO' || intval($W['archetypeRank'] ?? 0) !== 3 || !_SWUBotLvOppIsHardControl($seat)) return null;
        if (!preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#\d+$/', $head, $m)) return null;
        if (!preg_match("/Its controller may deal \\d+ damage to it\\. If they don't, draw a card/i", strval(CardText($m[1])))) return null;
        return $c === 'NO' ? 0.1 : 0.0;
    },
]);
