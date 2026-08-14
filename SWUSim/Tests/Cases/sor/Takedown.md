# DefeatsLowHpUnit
#// SOR_077 Takedown (Event, cost 4) — "Defeat a unit with 5 or less remaining HP."
#// P2 has Battlefield Marine (SOR_095, 3/3 → 3 remaining HP, targetable) and Consular
#// Security Force (SOR_046, 3/7 → 7 remaining HP, NOT targetable). Only the Marine
#// qualifies, so it's the sole target → auto-defeated; SOR_046 is left untouched.
#// COVERAGE: offer=Offer_LeadersAlliesAndHpFilter (exact pool: both sides, both arenas, deployed
#//           leaders in, >5-remaining bodies out) · decline=N/A (mandatory event effect — the only
#//           decision is the target pick) · control=N/A (no controller-sensitive clause; the filter
#//           reads remaining HP only) · boundary pair=DefeatsLowHpUnit + Offer_LeadersAlliesAndHpFilter
#//           (≤5 in, 6+/8 out) + HpReducingAura_BringsUnitIntoRange (an aura moves a 7-HP body across
#//           the boundary) · reqboundary=N/A (single-step event: the target answer and the defeat
#//           resolve inside one request)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # remaining HP 3 ≤ 5 → targetable, index 0
WithP2GroundArena: SOR_046:1:0    # remaining HP 7 > 5 → not targetable, index 1

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# Offer_LeadersAlliesAndHpFilter
#// SOR_077 Takedown — "a unit with 5 or less remaining HP" spans BOTH players, BOTH arenas, and
#// deployed LEADER units, filtered by REMAINING HP. P1: Pyke Sentinel (2/3, in), Academy Defense
#// Walker wearing Entrenched (5/5 +3/+3 → 8 remaining, out), deployed Boba Fett leader (4/7 with 4
#// damage → 3 remaining, in). P2: AT-ST (7 remaining, out), ISB Agent (3 remaining, in), Cartel
#// Spacer in space (3 remaining, in), deployed Sabine leader (2/5 → 5 remaining, in). The target
#// choice is left PENDING so the exact legal set can be inspected.

## GIVEN
CommonSetup: bbk/bbk/{
  myResources:4;
  handCardIds:SOR_077;
  myLeader:SHD_008:1:1:0:4;
  theirLeader:SOR_014:1:1
}
P1OnlyActions: true
WithP1GroundArena: SHD_029:1:0
WithP1GroundArena: SOR_037:1:0
WithP1GroundArenaUpgrade: 1:SOR_072
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_176:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirGroundArena-1&theirGroundArena-2&theirSpaceArena-0

---

# DefeatsAlly
#// SOR_077 Takedown — the defeat may be aimed at your OWN unit. Both a friendly Pyke Sentinel and an
#// enemy ISB Agent qualify, so the pick is a real prompt; P1 chooses their own Pyke → it is defeated
#// into P1's discard (joining Takedown itself) while the enemy agent survives.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077}
P1OnlyActions: true
WithP1GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_176:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:2
P1NODECISION

---

# DefeatsDeployedLeader
#// SOR_077 Takedown — a deployed leader unit is a legal target and its defeat sends it back to the
#// leader zone undeployed (not to the discard). P2's deployed Sabine (2/5 → 5 remaining) is the only
#// body within range (the AT-ST has 7 remaining), so the mandatory pick auto-resolves onto her.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077;theirLeader:SOR_014:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2LEADER:NOTDEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2DISCARDCOUNT:0
P1NODECISION

---

# HpReducingAura_BringsUnitIntoRange
#// SOR_077 Takedown — "remaining HP" is read AFTER continuous effects. A bare AT-ST (6/7, 7
#// remaining) is out of range, but with P1's Supreme Leader Snoke (each enemy non-leader unit gets
#// -2/-2) it stands at 4/5 → 5 remaining and becomes the only legal target (Snoke himself remains at
#// 6) → auto-defeated into P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077}
P1OnlyActions: true
WithP1GroundArena: SHD_037:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_232
P1GROUNDARENACOUNT:1
P1NODECISION
