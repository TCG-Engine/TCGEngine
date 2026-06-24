# ASH_162 Rash Action (Event, cost 2) — the discard rider only fires on a BASE hit. Here SOR_095 (3/3,
# +1/+0 → 4/3) attacks the enemy unit SOR_046 (3/7) instead of the base, so no combat damage reaches P2's
# base and P2 discards nothing (hand stays at 1). Confirms the dealt-to-base condition gates the discard.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_162;theirHandCardIds:SOR_095}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1
