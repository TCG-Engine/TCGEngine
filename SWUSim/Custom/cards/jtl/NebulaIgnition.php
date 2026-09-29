<?php
// JTL_080
// Nebula Ignition
// Text: Defeat each unit that isn't upgraded.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_080:0"] = function($player, $mzID = '') {
// Nebula Ignition — defeat each unit that isn't upgraded (no attached upgrades,
                          // including token upgrades). Snapshot UIDs first (mass defeat is index-unstable).
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (empty(GetUpgradesOnUnit($o))) $uids[] = intval($o->UniqueID ?? 0);
            }
            // One simultaneous defeat, walked a unit at a time — so "when an enemy unit is defeated"
            // observers caught in the wipe (ASH_052 Chimaera, SOR_002 Iden Versio, TS26_13 Darth Sidious)
            // must be judged against the pre-effect board. Without the window each defeat is its own
            // single-element batch and an observer removed early stops counting. An UPGRADED observer is
            // exempt from this card and so watches from safety — the unupgraded one is the case that
            // needs it. See SWUSimulDefeatBegin (GameLogic.php).
            SWUSimulDefeatBegin();
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') SWUDefeatUnit(intval($player), $mz);
            }
            SWUSimulDefeatEnd();
            return;
};
