<?php
// HMW_141 Rex — Unit (Ground) 5/6, cost 5, [Command], Fringe/Clone.
// "Friendly units with no abilities get +1/+1."
// USER RULING (2026-09-16): "no abilities" = no printed text AND no gained abilities. A unit that LOST all
// abilities (JTL_018 Kazuda, SHD_072 Imprisoned, SOR_138 Force Lightning, …) has none, so it qualifies —
// unless the blanking itself leaves one behind (SEC_054 Exiled from the Force keeps Grit).
// Gained abilities checked: every keyword (live, so conditional/aura/upgrade grants count), quoted abilities
// granted by an attached upgrade or Pilot ("Attached unit gains: '…'"), phase/attack grants carried as a
// TurnEffects marker, field-presence grants (SOR_105 Krell, ASH_063 Bo-Katan's Gauntlet, TWI_047 Satine),
// and abilities lent by a Support unit for the attack. A blanked unit "can't gain abilities", so for it only
// the keyword read (which already honours SEC_054's Grit) is consulted.
// "Friendly" is team-wide (Team Suns). Each Rex copy adds +1/+1. A blanked Rex grants nothing.

if (!function_exists('_SWUHmw141HasNoAbilities')) {
    // TurnEffects bases that grant a quoted (non-keyword) ability to the unit carrying them.
    function _SWUHmw141GrantMarkers(): array {
        return ['SOR_150', 'SHD_006', 'SHD_031', 'TWI_103', 'JTL_156', 'JTL_177', 'LOF_205', 'LAW_169', 'ASH_162', 'ASH_186'];
    }

    function _SWUHmw141TextGrantsAbility(string $cardID): bool {
        return preg_match('/gains?:?\s*["“]/u', (string)CardText($cardID)) === 1;
    }

    function _SWUHmw141HasNoAbilities($obj): bool {
        static $inProgress = [];
        if ($obj === null || SWUObjGone($obj)) return false;
        $key = intval($obj->UniqueID ?? 0);
        // Re-entry (a keyword condition reading this unit's stats) answers "has abilities" — the Rex bonus is
        // left out of the nested read rather than looping.
        if (isset($inProgress[$key])) return false;
        $inProgress[$key] = true;
        try {
            $lost = LostAbilities($obj);
            if (!$lost && trim((string)CardText($obj->CardID ?? '')) !== '') return false;
            if (_SWUCountDistinctKeywords($obj) > 0) return false;
            if (function_exists('HasKeyword_Coordinate') && HasKeyword_Coordinate($obj)) return false;
            if ($lost) return true;
            foreach (GetUpgradesOnUnit($obj) as $up) {
                if (!empty($up->removed)) continue;
                $ucid = (string)($up->CardID ?? '');
                // A Pilot LEADER attached as an upgrade grants its deployed-side abilities.
                if (stripos((string)CardType($ucid), 'Leader') !== false) return false;
                if (_SWUHmw141TextGrantsAbility($ucid)) return false;
            }
            $markers = _SWUHmw141GrantMarkers();
            foreach (($obj->TurnEffects ?? []) as $te) {
                $p = SWUParseTurnEffect((string)$te);
                if (in_array($p['base'], $markers, true)) return false;
                if ($p['base'] === 'SUPPORT_GRANT') {
                    $lender = (string)($p['params'][0] ?? '');
                    foreach (preg_split('/\n/', (string)CardText($lender)) as $line) {
                        $line = trim($line);
                        if ($line !== '' && !preg_match('/^(Support|Sentinel|Ambush|Overwhelm|Grit|Saboteur|Shielded|Hidden|Raid|Restore|Exploit|Coordinate|Smuggle|Bounty|Plot|Piloting)\b/i', $line)) return false;
                    }
                }
            }
            $ctrl = intval($obj->Controller ?? 0);
            if (!empty(_SWUFieldPresenceGrantedWDTypes($ctrl, (string)($obj->CardID ?? '')))) return false;
            if (_SWUSatineInPlay()) return false;
            return true;
        } finally {
            unset($inProgress[$key]);
        }
    }

    // +N/+N for $obj, N = active Rex copies controlled by $obj's controller or a teammate.
    function _SWUHmw141Bonus($obj): int {
        if ($obj === null) return 0;
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return 0;
        $n = 0;
        foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
            foreach (GetUnitsInPlay(intval($seat)) as $u) {
                if (!empty($u->removed) || ($u->CardID ?? '') !== 'HMW_141') continue;
                if (LostAbilities($u)) continue;
                $n++;
            }
        }
        if ($n === 0) return 0;
        return _SWUHmw141HasNoAbilities($obj) ? $n : 0;
    }
}
