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

---

# PlayThreeOfFour_IneligiblesStay_EventCosts6
#// ASH_104 Dathomiri Magicks (cost 6) — "up to 3 non-Vehicle units that each cost 2 or less." The discard
#// holds four eligibles (SOR_095 cost2, SOR_108 cost1, SOR_128 cost1, SOR_140 cost1) and three ineligibles
#// (SOR_164 cost4, SOR_225 a Vehicle, SHD_178 a non-unit event). P1 revives three of the four eligibles;
#// the unpicked eligible and all three ineligibles remain in the discard (plus the spent event itself = 5),
#// and only the event's own 6 is paid.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_104;discardCardIds:SOR_095,SOR_108,SOR_128,SOR_140,SOR_164,SOR_225,SHD_178}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1&myDiscard-2
## EXPECT
P1GROUNDARENACOUNT:3
P1DISCARDCOUNT:5
P1RESAVAILABLE:0

---

# NoForceUnit_FullPrice6
#// ASH_104 Dathomiri Magicks — the "costs 1 less" reduction requires controlling a Force unit. With no Force
#// unit in play the event costs the full 6: P1 has exactly 6 resources, revives the one discard unit
#// (SEC_080), and is left with 0 available resources (all 6 exhausted).
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_104;discardCardIds:SEC_080}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0
