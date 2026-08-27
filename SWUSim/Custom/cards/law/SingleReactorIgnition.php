<?php
// LAW_044
// Single Reactor Ignition
// Text: Defeat all units. For each enemy unit defeated this way, deal 1 damage to its controller's base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_044:0"] = function($player, $mzID = '') {
// Single Reactor Ignition — "Defeat all units. For each enemy unit defeated
                          // this way, deal 1 damage to its controller's base." Snapshot every unit's
                          // UID + controller, defeat by UID (index-shift safe), then count the enemy
                          // (opponent-controlled) units that actually left play and deal that much to
                          // the opponent's base. Re-checking by UID respects defeat immunity (LAW_149).
            // ⚠ THREE defects at 3+ seats, all fixed 2026-08-27 (Twin Suns sweep Pass 2, §1b family):
            //   • "enemy" was OtherPlayer($player) — literally seat 2 — so a unit controlled by seat 3 or
            //     4 was not counted as an enemy at all.
            //   • the pool was my+their. In a TEAM game 'their' excludes a teammate, so "defeat ALL units"
            //     left the teammate's board standing. SWUAllUnits() is the whole-table fan-out (team+their).
            //   • "deal 1 damage to ITS CONTROLLER's base" is a DETERMINED PER-UNIT seat, but every point
            //     was dealt to the single $opp base. With enemies on two seats the wrong base took it all.
            global $playerID; $playerID = intval($player);
            $enemies = array_flip(OpponentsOf(intval($player)));   // enemy = an opponent of the CASTER
            $ctrlOf  = [];   // uid => controller, captured before any defeat mutates the board
            $allUids = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $uid = intval($o->UniqueID);
                $allUids[] = $uid;
                $c = intval($o->Controller ?? 0);
                if (isset($enemies[$c])) $ctrlOf[$uid] = $c;
            }
            foreach ($allUids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            // Tally per controller — a unit that AVOIDED defeat (LAW_149 immunity) is still in play and
            // must not score, which is why this re-checks by UID rather than trusting the snapshot.
            $perSeat = [];
            foreach ($ctrlOf as $uid => $c) {
                if (SWUFindMzByUID($uid) === null) $perSeat[$c] = ($perSeat[$c] ?? 0) + 1;
            }
            foreach ($perSeat as $seat => $n) if ($n > 0) SWUDealDamageToBase($n, intval($seat));
            return;
};
