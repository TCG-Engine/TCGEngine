<?php
// JTL_076
// Covering the Wing
// Text: Create an X-Wing token. You may give a Shield token to another unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_076:0"] = function($player, $mzID = '') {
// Covering the Wing — create an X-Wing token; you may give a Shield to ANOTHER
                          // unit (not the just-created X-Wing).
            global $playerID;
            $playerID = intval($player);
            $before = [];
            foreach (GetField(intval($player)) as $u) {
                if ($u !== null && empty($u->removed)) $before[] = intval($u->UniqueID ?? 0);
            }
            SWUCreateUnitToken(intval($player), 'JTL_T02'); // X-Wing (Space, 2/2)
            $xwUid = 0;
            foreach (GetField(intval($player)) as $u) {
                if (SWUObjGone($u)) continue;
                $uid = intval($u->UniqueID ?? 0);
                if (!in_array($uid, $before, true)) { $xwUid = $uid; break; }
            }
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $xwUid) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "You_may_give_a_Shield_to_another_unit", "Give_a_Shield_token", "GIVE_SHIELD");
            return;
};
