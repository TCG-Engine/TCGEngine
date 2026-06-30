# SOR_182 Bossk — "When you play an event: you may deal 2 damage to a unit."
# Bossk in play; P1 plays a neutral event (Confiscate, fizzles with no upgrades).
# The reactive trigger fires → deal 2 to the enemy unit.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
