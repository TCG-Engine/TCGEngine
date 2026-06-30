# ASH_259 LEP Ratcatcher (Ground, 1/1, cost 1) — When Played: you may deal 1 damage to a ground unit. P1
# deals 1 to the enemy SEC_080.
## GIVEN
CommonSetup: bbw/bbk/{myResources:1;handCardIds:ASH_259}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
