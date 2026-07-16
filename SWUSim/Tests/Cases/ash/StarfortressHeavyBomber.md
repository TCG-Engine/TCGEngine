# DealSixNonUnique
#// ASH_174 StarFortress Heavy Bomber (Space, 3/3, cost 5) — When Played: you may deal 6 damage to a
#// non-unique ground unit. P1 targets the non-unique SEC_080 (3/3), dealing 6 and defeating it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:ASH_174}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
