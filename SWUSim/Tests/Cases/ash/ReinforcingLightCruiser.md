# WhenPlayedExhaust
#// ASH_051 Reinforcing Light Cruiser (Space, 5/5, cost 6) — When Played: you may exhaust a unit. Played,
#// it exhausts the enemy SEC_080.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_051}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_Decline_NoExhaust
#// ASH_051 Reinforcing Light Cruiser — the exhaust is optional. Declining leaves the enemy SEC_080 ready.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_051}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayed_ExhaustFriendly
#// ASH_051 Reinforcing Light Cruiser — the exhaust may target ANY unit, including a friendly one. Played
#// with a friendly SOR_046 in play, P1 exhausts its own SOR_046.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:ASH_051}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED
