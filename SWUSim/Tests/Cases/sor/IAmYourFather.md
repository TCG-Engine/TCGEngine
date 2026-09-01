# ControllerAllows_DealSeven
#// SOR_233 I Am Your Father — when the controller does NOT say "no" (answers NO to the refuse prompt),
#// the 7 damage is dealt. The target is a 4/7 wall, so 7 damage defeats it; the caster draws nothing.
#// COVERAGE: offer=Offer_EnemyUnitsOnlyAcrossBothArenas (pending SELECTABLEEXACT: three enemy
#//           bodies across both arenas, one friendly body excluded) +
#//           Offer_EnemyIsWhoCONTROLSTheUnitNotWhoOwnsIt ·
#//           control=Offer_EnemyIsWhoCONTROLSTheUnitNotWhoOwnsIt (both directions on one board: a
#//           P2-owned unit under P1's control is friendly and out of the pool; a P1-owned unit under
#//           P2's control is an enemy unit and in it) ·
#//           boundary=DamageBoundary_EightHpSurvivesTheSeven vs ControllerAllows_DealSeven (7 HP
#//           destroyed / 8 HP survives at 7) ·
#//           decline=ControllerRefuses_DrawThree — the refusal is the card's decline branch, and it
#//           belongs to the TARGET'S CONTROLLER, not the caster; ControllerAllows_DealSeven is the
#//           take side and NoEnemyUnits_Fizzle the no-valid-target side ·
#//           reqboundary=RefusalChainSurvivesRequestBoundary

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ControllerRefuses_DrawThree
#// SOR_233 I Am Your Father (event, cost 3) — "Deal 7 damage to an enemy unit unless its controller
#// says 'no.' If they do, draw 3 cards." The single enemy unit auto-resolves as the target; its
#// controller (P2) says "no" (refuses the damage), so no damage is dealt and the CASTER draws 3.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:3
P1DISCARDCOUNT:1

---

# NoEnemyUnits_Fizzle
#// SOR_233 I Am Your Father — with no enemy unit to target, the event fizzles cleanly (no decision,
#// no draw) and goes to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# Offer_EnemyUnitsOnlyAcrossBothArenas
#// SOR_233 I Am Your Father — OFFER axis. "Deal 7 damage to an ENEMY unit": the pool is every unit the
#// opponent controls, in BOTH arenas, and nothing the caster controls. Three enemy bodies (two ground,
#// one space) keep the pick genuinely pending, and a friendly ground unit sits on the board as the
#// excluded body. Left unanswered so the pool itself is the assertion.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# Offer_EnemyIsWhoCONTROLSTheUnitNotWhoOwnsIt
#// SOR_233 I Am Your Father — CONTROL CHANGE, both directions in one board. "Enemy" is a CONTROLLER
#// relationship: a unit sitting in P1's own arena but OWNED by P2 is friendly and must stay OUT of the
#// pool, while a unit sitting in P2's arena but OWNED by P1 is an enemy unit and must stay IN it. Two
#// legal targets keep the offer pending. (Controlled units seat after the plain ones, so P2's arena is
#// [LAW_124, the P1-owned SEC_080].)

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaControlled: SEC_080:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# DamageBoundary_EightHpSurvivesTheSeven
#// SOR_233 I Am Your Father — boundary pair, high side. The controller does NOT say "no", so the full
#// 7 is dealt to a 4/8: one more HP than the damage, so it survives at 7 damage. The low side is
#// ControllerAllows_DealSeven above, where a 4/7 is destroyed by the same 7. No cards are drawn,
#// because the draw rides on the refusal.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LOF_049:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:7
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ShieldedTarget_AbsorbsTheWholeSeven
#// SOR_233 I Am Your Father — the interaction that separates "deal 7" from "defeat". The chosen 4/7
#// carries a Shield token; the controller still declines to say "no", the damage is dealt to a
#// shielded unit and the shield is defeated INSTEAD of any damage landing. The unit walks away at 0
#// damage with its shield spent, and the caster still drew nothing.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# RefusalChainSurvivesRequestBoundary
#// SOR_233 I Am Your Father — REQUEST BOUNDARY. The card spans two seats' requests in production: the
#// caster picks the target, then the TARGET'S CONTROLLER answers the refusal, then the caster's draw
#// resolves. A serialization round-trip is inserted before each answer. Two enemy units keep the
#// target pick genuinely pending; P2 then says "no", so no damage is dealt and P1 draws its 3.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_046
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:3
P1DISCARDCOUNT:1
