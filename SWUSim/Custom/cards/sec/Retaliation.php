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
                //   SWU_DMGDBASE_{uid}_{baseOwner} — the any-base marker (armed at every base-damage site
                //                             regardless of whose base it was). Retaliation says "damaged
                //                             A BASE", not an opponent's, so a unit that pinged its OWN
                //                             base counts — which the two markers above cannot express,
                //                             both being ENEMY-BASE-ONLY (they exist for SEC_012 Cassian,
                //                             whose condition is "damaged an OPPONENT's base"). Any owner
                //                             matches here; the owner suffix is only meaningful to SEC_012.
                // ⚠ Scan EVERY seat's flags, not just the unit's current controller's. Both markers are
                // armed on whoever controlled the unit AT THE TIME it damaged the base, and they are keyed
                // by UniqueID — so a unit that damaged a base and then CHANGED CONTROL (Traitorous, No
                // Glory) keeps its mark under the old controller and would otherwise stop being a legal
                // target. The UID makes the cross-seat lookup unambiguous.
                'extraFilter' => function($o) {
                    $uid = intval($o->UniqueID ?? 0);
                    if ($uid <= 0) return false;
                    for ($p = 1; $p <= SeatCountForGame(); $p++) {
                        if (GlobalEffectCount($p, 'SWU_DEALT_BASEDMG_' . $uid) > 0
                            || GlobalEffectCount($p, 'SWU_UNITDMGBASE_' . $uid) > 0) return true;
                        // ⚠ $seat, NOT $o — $o is this closure's UNIT parameter, and the inner loop
                        // used to shadow it. Harmless only because $uid is read out before the loops;
                        // the next edit that touches $o after this point would silently get an int.
                        for ($seat = 1; $seat <= SeatCountForGame(); $seat++) {
                            if (GlobalEffectCount($p, 'SWU_DMGDBASE_' . $uid . '_' . $seat) > 0) return true;
                        }
                    }
                    return false;
                },
                'prompt' => "Defeat_a_unit_that_damaged_a_base_this_phase",
            ]);
};
