# DefeatFriendly_DamageThenIndirect
#// JTL_127 Lightspeed Assault — "Defeat a friendly space unit and deal damage equal to its power to an
#// enemy space unit. If you do, deal indirect damage equal to the enemy unit's power to its controller."
#// P1 defeats JTL_069 (power 4), dealing 4 to the enemy SOR_225 (2/1) which dies; then 2 indirect (its
#// power) goes to P2, who now controls no units, so it auto-resolves onto P2's base. SOR_237 (the other
#// friendly space unit) is the non-chosen option and survives.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
P2BASEDMG:2

---

# NoEnemySpace_FriendlyStillDefeated
#// JTL_127 Lightspeed Assault — "Defeat a friendly space unit AND deal damage equal to its power to an
#// enemy space unit." The defeat is the first half of the sentence and is NOT conditional on an enemy
#// being available: an ability resolves as much of itself as it can. With P2 holding only a GROUND unit
#// there is no enemy space unit, so the chosen friendly is still defeated and simply nothing else
#// happens — no damage is dealt, and therefore the "if you do" indirect never triggers either.
#// (A single friendly space unit auto-resolves the choose, so no target answer is needed.)
#//
#// ⚠ BEHAVIOR CHANGE (approved by the user, 2026-08-02): this section previously asserted the OPPOSITE
#// — that the whole event fizzled and the friendly SURVIVED. The engine gated the friendly's defeat on
#// an enemy space unit existing, in both the entry condition and JTL_127#0. Both gates were removed.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION

---

# IndirectAssignedToEnemyUnit
#// JTL_127 Lightspeed Assault — the follow-up indirect is assigned by the DAMAGED player among their base
#// and units. P1 defeats JTL_069 (power 4) → 4 to the enemy SOR_225 (2/1) which dies → 2 indirect (its
#// power) to P2, who still controls SOR_044 and dumps all 2 onto it (base stays clean).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
WithActivePlayer: 1
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:mySpaceArena-0:2

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:0

---

# ShieldedEnemyTarget_ShieldAbsorbs_IndirectStillDealt
#// JTL_127 Lightspeed Assault — "…deal damage equal to its power to an enemy space unit. If you do,
#// deal indirect damage equal to the enemy unit's power to its controller." The "if you do" is keyed on
#// the damage EVENT happening, not on damage sticking, so a Shield on the target changes only the first
#// half: the Shield absorbs the hit (target ends at 0 damage, Shield consumed) and the indirect is STILL
#// dealt for the enemy unit's power. P1 defeats JTL_069 (power 4) → 4 at the shielded SOR_044 → the
#// Shield eats it (0 damage, upgrade gone) → 2 indirect (SOR_044's power) which P2 assigns to its base.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
WithActivePlayer: 1
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myBase-0:2

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:2

---

# NoFriendlySpaceUnit_PlayedWithNoEffect
#// JTL_127 Lightspeed Assault — with NO friendly space unit there is nothing to defeat, so the event is
#// still playable but does nothing at all: it goes to the discard, the enemy space unit is untouched and
#// no indirect is dealt. Proves the event is not blocked from being played (a player may cycle it) and
#// that the enemy half never runs without the friendly half.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Offer_DefeatPoolIsFriendlySpaceUnitsOnly
#// JTL_127 Lightspeed Assault — "Defeat a FRIENDLY SPACE unit…". The first choice enforces both halves:
#// arena and controller. Board: friendly JTL_069 and friendly SOR_237 are both in the space arena and
#// belong in the pool; the friendly SOR_095 sits in the GROUND arena (wrong arena) and the enemy
#// SOR_225 sits in the space arena (wrong controller) — neither may be offered, even though the enemy
#// space unit IS the legal target of the ability's second half.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_friendly_space_unit
P1SELECTABLEEXACT:mySpaceArena-0&mySpaceArena-1
