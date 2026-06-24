# ASH_196 Gorian Shard's Corsair (Space, 6/5, cost 6) — When Played: you may deal 2 damage to a unit.
# (The "friendly Underworld damage is unpreventable" passive is deferred.) P1 deals 2 to the enemy
# SEC_080.
## GIVEN
CommonSetup: yyk/yyk/{myResources:6;handCardIds:ASH_196}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
