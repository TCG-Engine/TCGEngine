# ExhaustTwoCheap
#// ASH_233 Keep Them Talking (Event, cost 2) — Exhaust up to 2 units that each cost 3 or less. P1 exhausts
#// SEC_080 (cost 2) and SOR_225 (cost 2); the cost-4 SOR_046 is not eligible and stays ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:READY
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:EXHAUSTED

---

# ExhaustOnlyOne
#// ASH_233 Keep Them Talking — "up to 2" may be just one. P1 exhausts only SEC_080; SOR_225 stays ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY

---

# ExhaustNone_Decline
#// ASH_233 Keep Them Talking — "up to 2" may be zero. P1 declines; both units stay ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY

---

# ExhaustTwoFriendly
#// ASH_233 Keep Them Talking — the targets may be FRIENDLY units. P1 exhausts both its own cheap units:
#// SOR_095 (cost 2, ground) and SOR_141 (cost 2, space).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:EXHAUSTED

---

# ExhaustOneFriendly
#// ASH_233 Keep Them Talking — "up to 2" may be a single FRIENDLY unit. P1 exhausts only its SOR_095;
#// its SOR_141 stays ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY

---

# ExhaustOneFriendlyOneEnemy
#// ASH_233 Keep Them Talking — the two targets may mix sides: one friendly (SOR_095, cost 2) and one
#// enemy (SEC_080, cost 2) are both exhausted.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_233}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
