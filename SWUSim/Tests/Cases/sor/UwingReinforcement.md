# SearchPlayTwoUnits
#// SOR_104 U-Wing Reinforcement (Event, cost 7) — Search the top 10 of your deck for up to 3
#// units with combined cost 7 or less and play each for free. The top 10 hold two Battlefield
#// Marines (cost 2 each, combined 4 ≤ 7) among event fillers; both are played free into the
#// ground arena. The U-Wing event goes to discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1

---

# PlayOnlyOneCard
#// "up to 3 units" — one pick is legal with two legal candidates present. The unpicked Marine is not
#// played and goes back to the bottom with the rest.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_095 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DECKCOUNT:9

---

# TakeNothing_DeckIsReturnedNotMilled
#// "up to 3" includes ZERO: nothing is played and all 10 peeked cards return to the deck rather than
#// being milled. The event itself still goes to the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_095 SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:10
P1DISCARDCOUNT:1

---

# CountCapOfTHREE_EvenWhenTheBudgetAllowsMore
#// Two independent limits — "up to 3 units" AND "combined cost 7 or less" — and this pins the COUNT one
#// on its own. Four SOR_108 Vanguard Infantry cost 1 each, so all four together are 4, comfortably inside
#// the 7 budget; only three may still be played. A cap implemented purely as a cost budget passes every
#// other section and fails here.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SOR_108 SOR_108 SOR_108 SOR_108 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_108,SOR_108,SOR_108,SOR_108

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:7

---

# PILOTingUnitIsPlayedAsAUNIT_NotAsAPilot
#// A Piloting card IS a unit card, so it is a legal find for "up to 3 units" — but it is played AS A
#// UNIT, with no Unit-vs-Pilot choice, even though a legal Vehicle host is on the board.
#// JTL_093 Nien Nunb (cost 1, Command/Heroism, "Piloting [1 resource Command Heroism]") must land in the
#// ground arena as its own unit and SEC_214 Skyhopper Canyon Runner must end with no upgrade.
#// The Vehicle is load-bearing: without a legal pilot host the choice could not arise anyway.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1GroundArena: SEC_214:1:0
WithP1Deck: [JTL_093 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_093

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# FetchedUnitsWhenPlayedFiresImmediately
#// "play each of them for free" is a REAL play, so a fetched unit's own When Played resolves — nested,
#// before the game moves on. SHD_080 Salacious Crumb (cost 1) carries a MANDATORY "When Played: heal 1
#// damage from your base": the pre-damaged base must end at 4. Under the old put-into-play placement
#// this stayed at 5 (no trigger, no ceremony) — the probe that first proved the bug.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1BASEDMG:4

---

# TwoFetchedUnits_BothWhenPlayedsResolveInOrder
#// The multi-pick case: each play's triggers drain BEFORE the next unit plays (the queue orders them),
#// so two Crumbs heal 2 total. A batch implementation that placed both and fired triggers once — or
#// jumped the second play over the first's pending trigger — lands on 5 or 4 instead of 3.

## GIVEN
CommonSetup: ggw/ggw/{myResources:7;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_104
WithP1Deck: [SHD_080 SHD_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080,SHD_080

## EXPECT
P1GROUNDARENACOUNT:2
P1BASEDMG:3
