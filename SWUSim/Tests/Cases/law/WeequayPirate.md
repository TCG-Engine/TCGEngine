# NoResourcesPaid_GetsExperience
#// LAW_231 Weequay Pirate (Ground Unit 3/2, cost 2, Cunning/Underworld) —
#// "When Played: If no resources were paid to play this unit, give an Experience token to it."
#// 0-resources-paid mechanism: SOR_235 Galactic Ambition calls ActivateCard($player, $mzID, true)
#// (ignoreCost=true), which stamps SWU_PAID_0 on the entering unit (Task 3.1 logic).
#// SWUUnitResourcesPaid returns 0 == 0, so LAW_231 gets 1 Experience token (+1/+1 → 4/3).
#// P1's base also takes 2 damage (Galactic Ambition: deal cost of played unit to your base).
#// LAW_231 is the only non-Heroism unit in hand → auto-selected by SWUQueueChooseTarget.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:2
P1RESAVAILABLE:0

---

# PaidResources_NoExperience
#// LAW_231 Weequay Pirate (Ground Unit 3/2, cost 2, Cunning/Underworld) —
#// "When Played: If no resources were paid to play this unit, give an Experience token to it."
#// Guard: P1 plays LAW_231 from hand paying its full cost of 2 resources → SWU_PAID_2 is stamped.
#// SWUUnitResourcesPaid returns 2 ≠ 0, so NO Experience token is granted.
#// Weequay Pirate enters as a bare 3/2 unit with no subcards.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:LAW_231}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0

---

# CreditPartialPay_NoExperience
#// LAW_231 Weequay Pirate — "If no resources were paid, give an Experience token." P1 has 1 real
#// resource + 1 Credit token. Playing Weequay (cost 2) defeats the Credit to pay 1 less and pays the
#// remaining 1 from a real resource → 1 resource paid ≠ 0 → NO Experience. Bare 3/2 enters.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_231
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# CreditFullPay_GetsExperience
#// LAW_231 Weequay Pirate — if Credit tokens pay the FULL cost, no real resources are paid → Experience
#// is granted. P1 has 0 real resources + 2 Credit tokens. Playing Weequay (cost 2) defeats both Credits
#// to cover the whole cost → 0 resources paid → +1 Experience (4/3, one upgrade).

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1Hand: LAW_231
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1CREDITCOUNT:0

---

# SneakAttackDiscount_GetsExperience
#// LAW_231 Weequay Pirate — played for free via a resource discount still triggers Experience. P1 plays
#// SOR_219 Sneak Attack (Cunning event, cost 2), which plays Weequay (cost 2) for 3 less → free (0 paid).
#// Weequay is the only unit in hand, auto-selected. 0 resources paid → +1 Experience (4/3, one upgrade).

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:SOR_219,LAW_231}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# FreeReplayFromDiscard_GetsExperience
#// COVERAGE: offer=N/A (no target choice — the Experience attach is automatic and self-directed) ·
#//           decline=N/A (no "you may" clause; the token grant is mandatory when 0 resources were paid) ·
#//           boundary=CreditPartialPay_NoExperience + CreditFullPay_GetsExperience (1-paid vs 0-paid
#//           boundary pair) · control=N/A (the token always goes to this unit; no control-change text) ·
#//           reqboundary=CreditFullPay_GetsExperience + FreeReplayFromDiscard_GetsExperience (the
#//           paid-amount check resolves across the payment/replay decision boundaries)
#// LAW_231 Weequay Pirate — played for FREE from the discard pile still counts as 0 resources paid.
#// Weequay (2/3) wears SHD_053 Second Chance; P2's SOR_095 Battlefield Marine (3/3) defeats it in
#// combat, sending both cards to P1's discard (Weequay carries the free-replay marker). P1 replays
#// Weequay from discard for free → 0 resources paid → +1 Experience: it re-enters as a 3/4 with one
#// upgrade, and P1 still has 0 resources.
#// The discard-replay path queues the When Played on the EffectStack; the trailing Drain gives the
#// queued trigger its resolution request (it needs no answers — omitting the Drain leaves it pending).

## GIVEN
CommonSetup: yyk/grw
WithP1GroundArena: LAW_231:1:0
WithP1GroundArenaUpgrade: 0:SHD_053
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>PlayFromDiscard:1
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_053
P1RESAVAILABLE:0

---

# DroidPartialPay_ResourcesStillPaid_NoExperience
#// LAW_231 Weequay Pirate — the boundary partner for the Droid alt-payment path. SEC_122 Vuutun Palaa
#// lets each friendly Droid be exhausted "to pay costs as if it were a resource"; when Droids cover the
#// WHOLE cost, resources-paid is 0 and Weequay gets the Experience token — that full-cover case is
#// asserted in Tests/Cases/sec/VuutunPalaa_DroidControlShip.md::DroidPaymentLowersResourcesPaid, which is
#// why it does not appear again here. This section pins the other side of the line: ONE Droid covers 1 of
#// the cost-2 and a real resource covers the rest, so resources WERE paid and NO Experience is given.
#// Without this half, an implementation that stamped "no resources paid" on any Droid-assisted play would
#// look correct.
#// P1 has exactly 1 ready resource, so the single remaining pip must come from it (P1RESAVAILABLE:0).

## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 1
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LAW_231
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0
