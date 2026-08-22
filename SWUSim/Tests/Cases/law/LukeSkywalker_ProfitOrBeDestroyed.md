# WhenPlayedOpponentChoosesCreditReady
#// LAW_080 Luke Skywalker — the opponent instead picks "create a Credit token; ready this unit". P2
#// gains a Credit; Luke (entered exhausted) becomes ready.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7;theirResources:0}
WithActivePlayer: 1
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:CreditAndReady

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_080
P1GROUNDARENAUNIT:0:READY
P2CREDITCOUNT:1

---

# WhenPlayedOpponentChoosesDeal5
#// LAW_080 Luke Skywalker (9/7) — When Played: an opponent chooses one: [create a Credit token; ready
#// this unit] OR [you may deal 5 to a unit]. The opponent picks Deal5 -> P1 deals 5 to the enemy SOR_046.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Deal5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedOpponentChoosesDeal5ButDeclines
#// LAW_080 Luke Skywalker — the opponent picks the "you may deal 5" option, but the deal is optional
#// ("you may"), so P1 declines. No damage is dealt, no Credit is created, and Luke stays exhausted.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Deal5
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# TheMODECHOICEIsRaisedOnTHEOPPONENTSQueue
#// LAW_080 Luke Skywalker — "An OPPONENT chooses one" puts the mode decision on the opposing seat. All
#// three existing sections ANSWER it as P2 without ever asserting whose decision it was, so a choice
#// wrongly raised on the caster's queue would satisfy them all. Here the decision is left pending after
#// P1's play and read from P2's side, with both modes on offer.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P2OPTIONHAS:CreditAndReady
P2OPTIONHAS:Deal5

---

# TwinSuns_TheCHOSENOpponentMakesTheModeChoice
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "AN opponent chooses one" — the caster picks WHICH opponent
#// decides; OtherPlayer() picked one silently.
#// ⚠ NO $eligible filter: the chosen player needs nothing on their board — both modes are things that
#// happen to the CASTER (or a Credit they simply gain), so nobody can be unable to choose. Taxonomy
#// shape 3, same as LOF_065 Watto and SHD_205.
#// P1 hands the choice to SEAT 3, who must own the OPTIONCHOOSE. Seat 2 — whom the old code always asked —
#// must have no decision at all.
#// ⚠ FIXTURE: keep the existing section's ryw/bgw aspects — LAW_080 is Aggression/Cunning/Heroism and an
#//   off-aspect deck adds a penalty that pushes cost 7 past the pool, so the unit is never played.
#// Mutation check: revert to OtherPlayer() and this reds (the choice lands on seat 2).

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: LAW_080
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
P4NODECISION
