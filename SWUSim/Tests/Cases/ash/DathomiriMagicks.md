# PlayCheapUnitsFromDiscard
#// ASH_104 Dathomiri Magicks (Event, cost 6) — Play up to 3 non-Vehicle units that each cost 2 or less
#// from your discard pile for free. P1's discard has SEC_080 (cost 2) and SOR_128 (cost 1); both are played
#// for free into the ground arena.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_104;discardCardIds:SEC_080,SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
## EXPECT
P1GROUNDARENACOUNT:2

---

# PlayNone_NothingHappens
#// ASH_104 Dathomiri Magicks — "up to 3" may be zero. P1 plays it but selects nothing; no units enter play.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_104;discardCardIds:SEC_080,SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0

---

# ForceUnit_Discounted
#// ASH_104 Dathomiri Magicks — "If you control a Force unit, this event costs 1 less." With the Force unit
#// SOR_049 in play, the event costs 5 (not 6), so 5 resources are enough to play it and revive both discard
#// units (ground goes to 3: SOR_049 + the two revived).
## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:ASH_104;discardCardIds:SEC_080,SOR_128}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
## EXPECT
P1GROUNDARENACOUNT:3
