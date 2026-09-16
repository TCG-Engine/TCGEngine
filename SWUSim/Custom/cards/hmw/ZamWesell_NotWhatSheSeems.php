<?php
// HMW_134 Zam Wesell — Unit (Ground) 2/4, cost 2, [Command], Underworld/Bounty Hunter.
// "Hidden. This unit gains each friendly leader's traits except Force, even while she's not in play."
// Hidden is generated ($Hidden_Cards). The trait grant:
//   • "friendly leader" = every leader card of Zam's controller (owner, out of play) — both Twin Suns
//     leaders — plus a Team Suns teammate's leaders. An opponent's leaders never count.
//   • A leader's traits are its CURRENT traits: a leader deployed as a unit reads its arena object through
//     TraitContains (so HMW_004 deployed is The Death Star — Imperial, Vehicle, Capital Ship — and a
//     LOF_073-granted Mandalorian carries over); an undeployed leader, a leader deployed as a PILOT upgrade,
//     or a flipped leader reads its printed leader-side traits.
//   • Force is never gained. A blanked Zam (in play) gains nothing; SEC_046 Galen naming her blanks her out of
//     play too. HMW_108 The First Legion still strips a gained trait (TraitContains checks it first).
// So with The Death Star deployed Zam IS a Vehicle: Pilots and pilot leaders may attach to her, "non-Vehicle"
// upgrades may not. Eligibility is only checked on attach (CR 3.4.a) — a Pilot on her stays if she stops
// being a Vehicle.
// Wiring: TraitContains (in play, by controller) and _SWUCardHasTrait (out of play, by owner) call
// _SWUHmw134GrantsTrait precisely; the bare HasTrait($cardID) reads resolve the seat via _SWUHmw134ContextSeat.

if (!function_exists('_SWUHmw134LeadersHaveTrait')) {
    function _SWUHmw134LeadersHaveTrait(int $seat, string $trait): bool {
        static $depth = 0;
        if ($seat <= 0 || strcasecmp(trim($trait), 'Force') === 0 || $depth > 0) return false;
        $depth++;
        try {
            foreach (array_merge([$seat], SWUTeammatesOf($seat)) as $s) {
                $s = intval($s);
                foreach (GetLeader($s) as $l) {
                    if ($l === null || !empty($l->removed)) continue;
                    $lcid = (string)($l->CardID ?? '');
                    if ($lcid === '') continue;
                    $duid = intval($l->DeployedUniqueID ?? 0);
                    $unit = null;
                    if (!empty($l->Deployed) && $duid > 0) {
                        foreach (GetLiveSeatsArray() as $ps) {
                            foreach (GetUnitsInPlay(intval($ps)) as $u) {
                                if (empty($u->removed) && intval($u->UniqueID ?? -1) === $duid) { $unit = $u; break 2; }
                            }
                        }
                    }
                    if ($unit !== null) {
                        if (TraitContains($unit, $trait)) return true;
                    } elseif (HasTrait($lcid, $trait)) {
                        return true;
                    }
                }
            }
            return false;
        } finally {
            $depth--;
        }
    }

    // In-play grant ($obj = a Zam arena object).
    function _SWUHmw134GrantsTraitInPlay($obj, string $trait): bool {
        if (LostAbilities($obj)) return false;
        return _SWUHmw134LeadersHaveTrait(intval($obj->Controller ?? 0), $trait);
    }

    // Out-of-play grant (a Zam card owned by $owner).
    function _SWUHmw134GrantsTraitOutOfPlay(int $owner, string $trait): bool {
        if ($owner <= 0 || _SWUGalenSuppressesCard($owner, 'HMW_134')) return false;
        return _SWUHmw134LeadersHaveTrait($owner, $trait);
    }

    // Seat context for a bare HasTrait('HMW_134', …) read, which carries no object. The seat that controls a
    // Zam in play or owns one out of play; if both players have one — or none is findable — the acting player.
    // An in-play Zam that is blanked contributes no context.
    function _SWUHmw134ContextSeat(): int {
        global $playerID;
        $seats = [];
        foreach (GetLiveSeatsArray() as $s) {
            $s = intval($s);
            foreach (GetUnitsInPlay($s) as $u) {
                if (empty($u->removed) && ($u->CardID ?? '') === 'HMW_134' && !LostAbilities($u)) $seats[$s] = true;
            }
            foreach ([GetHand($s), GetDeck($s), GetDiscard($s), GetResources($s)] as $zone) {
                foreach ($zone ?? [] as $c) {
                    if (!empty($c->removed) || ($c->CardID ?? '') !== 'HMW_134') continue;
                    $owner = intval($c->Owner ?? $s);
                    $seats[$owner > 0 ? $owner : $s] = true;
                }
            }
        }
        // No copy anywhere = the card is in transit inside the acting player's own ability (a top-deck search
        // array_splices the peeked cards off the deck before filtering them) → that player.
        if (empty($seats)) return intval($playerID);
        if (count($seats) === 1) return intval(array_key_first($seats));
        if (isset($seats[intval($playerID)])) return intval($playerID);
        return 0;
    }
}
