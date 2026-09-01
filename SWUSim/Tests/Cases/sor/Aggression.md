# Modal_DefeatUpgradesCrossUnit
#// SOR_155 Aggression — "Defeat up to 2 upgrades" now spans DIFFERENT units (two chained "may defeat 1"
#// flows, each re-reading the board). SEC_080 holds SOR_120 and SOR_095 holds SOR_069; DefeatUpgrades
#// removes one upgrade from EACH (impossible with the old host-scoped single flow). The second mode is
#// Draw. Aggression,Aggression is fully off-aspect for SOR_009 → cost 8.
#// COVERAGE: offer=ModeOffer_FirstPickOffersAllFourModes + ModeOffer_SecondPickDropsTheModeAlready
#//           Taken (the mode menu, asserted pending on both picks) +
#//           Ready_PowerBoundary_ThreeIsInFourIsOut and Deal4_PoolNamesNoController (each mode's own
#//           target pool, pending SELECTABLEEXACT, with an excluded body present in each) ·
#//           boundary=Ready_PowerBoundary_ThreeIsInFourIsOut (power 3 in / 4 out) +
#//           DefeatUpgrades_UpToTwoMayStopAtOne (1 of an available 3 when "up to 2" stops early) +
#//           ModeOffer_SecondPickDropsTheModeAlreadyTaken (two modes, never the same one twice) ·
#//           decline=DefeatUpgrades_UpToTwoMayStopAtOne (the only optional sub-choice on the card —
#//           "up to 2"; the two MODE picks are mandatory, "choose two" carries no "you may") ·
#//           control=Deal4_KillsAStolenUnit_CardGoesToItsOwnersDiscard ("a unit" names no controller,
#//           and the killed card returns to its OWNER's discard) ·
#//           reqboundary=ModeChainSurvivesRequestBoundary

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_237
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatUpgrades
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-1.u0
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:3

---

# Modal_DrawAndDeal4
#// SOR_155 Aggression (event, cost 4) — Draw a card + Deal 4 to a unit. P1 draws (hand 0→1) and deals 4
#// to the only unit (LAW_124, a 4/7, survives at 4). Aggression is off-aspect for SOR_009 → cost 6.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>AnswerDecision:Deal4

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1

---

# Modal_ReadyAndDeal4
#// SOR_155 Aggression — Ready a unit with ≤3 power + Deal 4 to a unit. SEC_080 (exhausted, 3 power) is
#// readied (only it qualifies, ≤3 power); then 4 damage is dealt to LAW_124 (4 power, so not a Ready
#// target). (The DefeatUpgrades mode is smoke-verified separately — its TempZone picker is covered by
#// SOR_251/SOR_170; the in-process regression harness can't drive a TempZone MZMULTICHOOSE nested in the
#// modal, though it resolves correctly through the live engine.)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready
- P1>AnswerDecision:Deal4
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1

---

# ModeOffer_FirstPickOffersAllFourModes
#// SOR_155 Aggression (event, cost 4, [Aggression][Aggression]) — "CHOOSE TWO, in any order". The mode
#// picker is the card's first decision and all four printed modes must be on it, regardless of whether
#// any of them could currently do anything (the board here is empty, so Ready/Deal4/DefeatUpgrades
#// would all fizzle — a mode is not filtered out for being ineffective). Left pending so the menu
#// itself is the assertion.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:Draw
P1OPTIONHAS:DefeatUpgrades
P1OPTIONHAS:Ready
P1OPTIONHAS:Deal4

---

# ModeOffer_SecondPickDropsTheModeAlreadyTaken
#// SOR_155 Aggression — "choose TWO" means two DIFFERENT modes: once Draw is taken and has resolved
#// (hand 0 → 1), the second menu must still offer the other three and must NOT offer Draw again. This
#// is the boundary that separates "choose two" from "choose one twice".

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw

## EXPECT
P1HASDECISION
P1HANDCOUNT:1
P1OPTIONNOT:Draw
P1OPTIONHAS:DefeatUpgrades
P1OPTIONHAS:Ready
P1OPTIONHAS:Deal4

---

# Ready_PowerBoundary_ThreeIsInFourIsOut
#// SOR_155 Aggression — OFFER + boundary for the Ready mode: "Ready a unit with 3 OR LESS power."
#// Three exhausted friendly bodies — two at power 3 (SEC_080 and SOR_095) and one at power 4 (SOR_164
#// Wampa) — plus an exhausted 3-power ENEMY unit, since the printed text says "a unit" and names no
#// controller. Two eligible targets keep the pick pending, so the pool is the assertion.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_164:0:0
WithP2GroundArena: SOR_128:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# Deal4_PoolNamesNoController
#// SOR_155 Aggression — OFFER for the Deal4 mode: "Deal 4 damage to A UNIT" carries no controller
#// word, so every unit on the board is a legal recipient — the caster's own included, and across both
#// arenas. Two friendly units (one ground, one space) and one enemy unit are seeded; the pool must be
#// all three and the decision is left pending.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Deal4

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# DefeatUpgrades_UpToTwoMayStopAtOne
#// SOR_155 Aggression — "Defeat UP TO 2 upgrades" includes stopping at one. Three upgrades are on the
#// board (two on SEC_080, one on SOR_095); the first defeat is taken and the second is DECLINED, so
#// exactly one upgrade leaves play and the other two stay attached. The second mode is Draw, proving
#// the declined sub-choice does not abort the rest of the card.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatUpgrades
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:-
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1HANDCOUNT:1
P1NODECISION

---

# Deal4_KillsAStolenUnit_CardGoesToItsOwnersDiscard
#// SOR_155 Aggression — CONTROL CHANGE. "Deal 4 damage to a unit" names no controller, so a 3/3 that
#// P1 CONTROLS but P2 OWNS (the end state after a take-control effect) is a legal recipient; the 4
#// destroys it and, per CR, the card returns to its OWNER's discard pile — P2's, not the caster's.
#// P1's discard holds only the spent event.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1GroundArenaControlled: SOR_095:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Deal4
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1HANDCOUNT:1

---

# ModeChainSurvivesRequestBoundary
#// SOR_155 Aggression — REQUEST BOUNDARY. "Choose two" spans several requests in production: the first
#// mode, its own sub-target, then the second mode. The already-taken mode and the remaining pick count
#// must live in the serialized gamestate, so a round-trip is inserted before every answer. Deal4
#// finishes off the enemy 3/1 and Draw then fills the hand — the same end state as the boundary-free
#// run, with no mode offered twice and no decision left hanging.

## GIVEN
CommonSetup: rrw/brw/{myResources:8;myhandCardIds:SOR_155}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_095
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Deal4
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Draw

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION
