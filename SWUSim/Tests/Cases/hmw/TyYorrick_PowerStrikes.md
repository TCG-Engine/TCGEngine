# StrikeTrue_GorianDealer_TyPlusOne_StillUnpreventable
#// HMW_185 Ty Yorrick: "If a friendly ability would deal damage, you may have that ability deal that much damage plus 1
#// instead." With ASH_196 Gorian Shard's Corsair as SOR_127 Strike True's dealer, the +1 does not change who dealt it:
#// 6+1 = 7, still unpreventable, so it goes through the Shield onto JTL_204 Home One (8 HP) and the Shield stays.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_127}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:7
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# Breach_GorianDealer_TyPlusOne_StillUnpreventable
#// HMW_114 Breach with Gorian dealing into its arena and Ty accepted: 7 through the Shield. Gorian is the only friendly
#// unit with an enemy in its arena (Ty is ground, P2 has none), so Breach picks him itself and Ty's question comes first.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:7
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# FocusFire_NoGorian_OneTyQuestion_PlusOneOnTheTotal
#// JTL_129 Focus Fire, owner rulings 2026-10-07: one simultaneous event; Ty's +1 applies ONCE, to the event total. Two
#// X-Wings (2 + 2) and Ty accepted: 5 onto an unshielded Home One, from a single question.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0]
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_204:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:5
P1NODECISION

---

# FocusFire_GorianAndXWing_TyPlusOneRidesTheUnpreventableShare
#// With Gorian (6, Underworld) and an X-Wing (2) on a Shielded Home One and Ty accepted: ONE question, and the +1 rides
#// the unpreventable share (owner ruling 2026-10-07) — 6+1 = 7 lands, the X-Wing's 2 is stopped by the Shield (used up).
#// Before the fix this event went through the divided-damage funnel, which never offers Ty: it dealt 6.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: [ASH_196:1:0 SOR_237:1:0]
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:7
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# FocusFire_GorianAndXWing_TyDeclined_Unchanged
#// The decline: no +1 — Gorian's 6 lands, the X-Wing's 2 is stopped.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:JTL_129}
P1OnlyActions: true
WithP1SpaceArena: [ASH_196:1:0 SOR_237:1:0]
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: JTL_204:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:6
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# CaughtInTheCrossfire_TyPlusOneOnEachHit_OneQuestion
#// TWI_176 Caught in the Crossfire, owner ruling 2026-10-07: ONE Ty question; accepted, EACH hit is +1. P1 (the caster)
#// controls Ty. P2's units: SOR_046 (3/7) and LAW_124 (4/7). LAW_124 deals 4+1 = 5 to SOR_046; SOR_046 deals 3+1 = 4 to
#// LAW_124. Before the fix the hits went through the divided-damage funnel, which never offers Ty. (LAW_124 is the only
#// other enemy unit once SOR_046 is picked, so the second pick is automatic and Ty's question follows the first.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:TWI_176}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:DAMAGE:4
P1NODECISION

---

# CaughtInTheCrossfire_TyPlusOne_WithTheirGorian_UnderworldHitStillUnpreventable
#// Ty and Gorian together: P1's Ty boosts both hits; P2's Gorian makes LAW_124's (Underworld) hit unpreventable. Its
#// 4+1 = 5 goes through SOR_046's Shield; SOR_046's 3+1 = 4 lands on LAW_124.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:TWI_176}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: ASH_196:1:0
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:1:DAMAGE:4
P1NODECISION

---

# TurbolaserSalvo_OneTyQuestion_PlusOneToEachUnit_GorianUnpreventable
#// JTL_131 Turbolaser Salvo, owner ruling 2026-10-07: ONE Ty question for the whole ability; accepted, each enemy unit in
#// the arena takes power+1. Gorian (6) is the dealer, so every hit is also unpreventable: the Shielded Home One (8 HP)
#// takes 7 with its Shield intact, and the other enemy Home One takes 7 too — from ONE question (P1NODECISION).

## GIVEN
CommonSetup: ggw/rrk/{myResources:12;handCardIds:JTL_131}
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: HMW_185:1:0
WithP2SpaceArena: [JTL_204:1:0 JTL_204:1:0]
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:7
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P2SPACEARENAUNIT:1:DAMAGE:7
P1NODECISION
