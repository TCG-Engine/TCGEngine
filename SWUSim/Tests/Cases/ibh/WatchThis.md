# NoUnits_Fizzles
#// IBH_052 Watch This — with no units in play, there is nothing to return and the event fizzles cleanly.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1NODECISION

---

# ReturnAndExhaustSameArena
#// IBH_052 Watch This (Event, cost 6, Cunning) — Return a non-leader unit (cost ≤6) to its owner's hand,
#//   then exhaust each other enemy unit in the SAME arena. P1 returns an enemy ground unit; the other
#//   enemy ground unit is exhausted, while a friendly ground unit and an enemy SPACE unit are untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY
P2HANDCOUNT:1
P1NODECISION

---

# ReturnsAFRIENDLYUnit_StillExhaustsEveryEnemyInThatArena
#// IBH_052 Watch This — "a non-leader unit" is unqualified, so a FRIENDLY unit is a legal target. P1
#// returns its own SEC_080 to its own hand; the exhaust half is separately scoped to "each other ENEMY
#// unit in the same arena", so BOTH enemy ground units exhaust even though the returned unit was
#// friendly. The enemy SPACE unit is in a different arena and stays ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2SPACEARENAUNIT:0:READY
P1NODECISION

---

# ReturnsASPACEUnit_ExhaustsTheSPACEArenaNotTheGround
#// IBH_052 Watch This — "the same arena" follows the RETURNED unit. Returning an enemy SPACE unit
#// exhausts the other enemy SPACE unit and leaves the enemy GROUND unit ready — the mirror of the
#// ground-side section above, and what proves the exhaust is arena-scoped rather than board-wide.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P2HANDCOUNT:1
P1NODECISION

---

# ReturnsAUnitThatMOVEDArenas_ExhaustsItsLIVEArena
#// IBH_052 Watch This — arena membership is LIVE, not printed. JTL_096 Blue Leader is a printed SPACE
#// unit that can move itself to the ground arena; seated in the ground arena here to model that
#// post-move state directly. It is a legal target, and returning it exhausts the other enemy GROUND
#// unit while the enemy SPACE unit stays ready — i.e. "the same arena" reads the unit's current arena,
#// not the arena printed on the card.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP2GroundArena: JTL_096:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY
P2HANDCOUNT:1
P1NODECISION

---

# ChosenUnitCANNOTBeReturned_TheExhaustHalfStillApplies
#// IBH_052 Watch This — JTL_103 Chewbacca "can't be defeated or returned to hand by enemy card
#// abilities", so choosing him returns nothing. The two halves are independent: the exhaust still
#// resolves against each OTHER enemy unit in his arena, so SOR_095 exhausts while Chewbacca stays in
#// play and READY. Nothing reaches either hand.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_103
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:0
P1NODECISION
