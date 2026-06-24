# ASH_170 Desert Sharpshooter (Ground, 3/3, cost 3) — When Played: you may deal 2 damage to an upgraded
# ground unit. P1 targets the only upgraded ground unit, SEC_080 (3/3 + SOR_120), dealing 2.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_170}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
