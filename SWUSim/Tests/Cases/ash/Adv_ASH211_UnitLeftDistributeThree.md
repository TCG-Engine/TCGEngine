# ASH_211 Fateful Goodbye (Event, cost 2) — If a friendly unit left play this phase, distribute 3 Advantage
# tokens among friendly units. SOR_095 dies attacking SOR_038 (sets the flag), then Fateful Goodbye piles 3
# Advantage onto SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_211}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SOR_038:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
