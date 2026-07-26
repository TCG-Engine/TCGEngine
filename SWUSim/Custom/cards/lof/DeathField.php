<?php
// LOF_141
// Death Field
// Text: Deal 2 damage to each non-Vehicle enemy unit. If you control a Force unit, draw a card.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_141:0"] = function($player, $mzID = '') {
// Death Field — "Deal 2 damage to each non-Vehicle enemy unit. If you control a
                          // Force unit, draw a card."
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (!HasTrait($o->CardID ?? '', 'Vehicle')) $uids[] = intval($o->UniqueID ?? -1);
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 2, intval($player));
            }
            if (PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1)) DoDrawCard(intval($player), 1);
            return;
};
