# Decline_NoDamage
#// SOR_097 Admiral Ackbar — the When Played damage is optional ("You may"). Declining (AnswerDecision:-)
#// deals nothing.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# GroundCount_DealsDamage
#// SOR_097 Admiral Ackbar (Command/Heroism unit, cost 3, 1/4, Rebel/Official) — "Restore 1. When
#// Played: You may deal damage to a unit equal to the number of units you control in its arena."
#// P1 plays Ackbar into a ground arena that already has 2 friendly units → 3 friendly ground units
#// (incl. Ackbar). Targeting an enemy GROUND unit deals 3 (the friendly ground count). LAW_124 (4/7)
#// survives at DAMAGE:3.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SpaceArenaCount
#// SOR_097 Admiral Ackbar — the damage equals the number of units you control in the CHOSEN unit's
#// arena, NOT your total units. P1 has 3 ground units (incl. Ackbar) but only 1 space unit. Targeting
#// an enemy SPACE unit deals 1 (the friendly SPACE count), proving it counts the target's arena.
#// JTL_069 (4/7) survives at DAMAGE:1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:1
