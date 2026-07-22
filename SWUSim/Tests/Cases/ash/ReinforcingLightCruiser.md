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
