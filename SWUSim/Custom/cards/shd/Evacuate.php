<?php
// SHD_233
// Evacuate
// Text: Return each non-leader unit to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_233:0"] = function($player, $mzID = '') {
// Evacuate — "Return each non-leader unit to its owner's hand." (mass bounce, UID-safe)
            // "Return each ... unit" resolves SIMULTANEOUSLY (CR): evaluate each unit's "can't be returned by
            // enemy abilities" protection (LOF_073 Mythosaur / JTL_103 / TWI_220) against the PRE-resolution
            // board — snapshot it BEFORE bouncing any unit, else returning the Mythosaur first would strip the
            // protection off the upgraded units that should have been kept. Skip the protected ones.
            $uids = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) {
                        $uid = intval($o->UniqueID ?? 0);
                        // Protected only vs an ENEMY return (the caster's own units are not "returned by an
                        // enemy ability"), mirroring SWUBounceUnit's guard.
                        $protected = (intval($player) !== intval($o->Controller ?? $player)) && SWUAvoidsBounce($o);
                        if (!$protected) $uids[] = $uid;
                    }
                }
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUBounceUnit(intval($player), $mz);
            }
            return;
};
