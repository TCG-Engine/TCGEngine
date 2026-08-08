<?php
// SEC_077
// Retaliation
// Text: Defeat a unit that dealt damage to a base this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_077:0"] = function($player, $mzID = '') {
// Retaliation — "Defeat a unit that dealt damage to a base this phase."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEFEAT_UNIT',
                // "dealt DAMAGE to a base" — NOT "attacked a base", so BOTH per-unit markers count:
                //   SWU_DEALT_BASEDMG_{uid} — combat attack on a base (SHD_088/SHD_106 "attacked" flag).
                //   SWU_UNITDMGBASE_{uid}   — damaged a base by ANY route: Overwhelm spill, ability ping
                //                             (SWU_DMG_SRC), indirect. Set by the SEC_012 Cassian sites.
                // Reading only the first missed an Overwhelm attacker: it "attacked a unit", so the
                // attack-flag is deliberately not set, yet its excess damage did hit the base.
                // ⚠ KNOWN REMAINING GAP: both SWU_UNITDMGBASE_ sites are ENEMY-BASE-ONLY (SEC_012's
                // condition is "damaged an OPPONENT's base"), so a unit that damaged its OWN base with an
                // ability is still not matched. Closing that needs a new any-base marker armed at every
                // base-damage site — deferred rather than bolted on here.
                'extraFilter' => function($o) {
                    $ctrl = intval($o->Controller ?? 0); $uid = intval($o->UniqueID ?? 0);
                    return GlobalEffectCount($ctrl, 'SWU_DEALT_BASEDMG_' . $uid) > 0
                        || GlobalEffectCount($ctrl, 'SWU_UNITDMGBASE_' . $uid) > 0;
                },
                'prompt' => "Defeat_a_unit_that_damaged_a_base_this_phase",
            ]);
};
