<?php
// LEVER 'doomedsoak' (theme 'controlplan', report G4 — Plan 2 check). Indirect / split damage I assign onto MY OWN units: a unit that dies
// anyway (SWUBotUnitIsDoomed: <= 2 HP left, no Shield, an enemy in its arena that hits that hard) is still priced at its full loss value, so
// the damage went to my base instead. Boba Fett JTL vs Hemlock DV (smoke sm002): Droid Missile Platform's 3 and Planetary Bombardment's 8
// indirect all went to base while a 1-HP Imperial Door Technician (When Defeated: heal 2) sat in reach of a Mortar Trooper — and died to it
// next anyway. Ninin put indirect on units that died next round (54ae R12: 6 of 8). Here a share on a doomed unit of mine costs what
// SACRIFICING it costs (SWUBotSacrificeCost: fodder value, When Defeated paybacks counted), and a non-lethal share on it costs ~nothing.
SWUBotLeverRegister('controlplan', 'doomedsoak', [
    'splitown' => function (float $part, int $seat, array $v, int $amt, array $W): ?float {
        if (!SWUBotUnitIsDoomed($v)) return null;
        if ($amt >= intval($v['remaining'])) return -SWUBotSacrificeCost($v) - 0.01 * $amt;
        return -0.01 * $amt;
    },
]);
