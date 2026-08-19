# SEC040_CreditPaysEventCost_ButGrantsNoExperience
#// RULING: a Credit token may pay a card's COST (CR 3.13 "while paying resources … pay 1 less"), but it
#// is never itself "a resource paid this way", because a Credit is created in the resource zone and is
#// explicitly NOT a resource. So for SEC_040 Emergency Powers — "Choose a non-leader unit and pay any
#// number of resources. For each resource paid this way, give an Experience token" — Credits pay for the
#// EVENT but can never buy Experience.
#//
#// P1 has ZERO ready resources and 2 Credits. One Credit pays the event's cost 1 (that offer is correct
#// and is answered below). The effect then has no resources behind it at all, so no "pay N" prompt is
#// raised and the chosen unit gets NOTHING — it stays 3/3 with no upgrades, and the second Credit is
#// still there. Failing this test would mean Credits are buying Experience.

## GIVEN
CommonSetup: bbk/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: SEC_040
WithP1Credits: 2
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1CREDITCOUNT:1
P1NODECISION

---

# SEC040_ResourcesGrantExperience_CreditsUntouched
#// The positive half: real resources DO scale the effect. P1 has 3 ready resources and 2 Credits and
#// declines the Credit offer on the event (resources are available, so the token is worth keeping).
#// The event costs 1, leaving 2 ready; paying both grants exactly 2 Experience (3/3 → 5/5).
#// Both Credits survive untouched — they were neither spent nor counted.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_040
WithP1Credits: 2
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1CREDITCOUNT:2
P1RESAVAILABLE:0

---

# SEC040_CreditsDoNotExtendThePayableAmount
#// BOUNDARY: the amount payable to the effect is capped by READY RESOURCES, not by total payment
#// capacity. P1 keeps 1 ready resource after the event and holds 3 Credits — capacity 4, resources 1.
#// Asking to pay 3 must not succeed by burning 2 Credits: the payment fails outright, so no Experience
#// is given, the resource stays ready and all 3 Credits remain. (Under the buggy reading this granted
#// 3 Experience for 1 resource + 2 Credits.)

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_040
WithP1Credits: 3
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:3

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1CREDITCOUNT:3
P1RESAVAILABLE:1

---

# LOF255_CreditPaysUnitCost_ButGrantsNoExperience
#// The same rule on a UNIT's When Played. LOF_255 Curious Flock (cost 1, 1/1, no aspect) — "Pay up to 6
#// resources. For each resource paid this way, give an Experience token to this unit."
#// P1 has 0 ready resources and 2 Credits: one Credit pays the unit's cost, and because no resources
#// remain the pay-for-Experience loop is never even offered. The Flock enters as a plain 1/1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: LOF_255
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_255
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1CREDITCOUNT:1
P1NODECISION

---

# LOF255_LoopStopsWhenResourcesRunOut_CreditsIgnored
#// LOF_255's payment is an iterative "pay 1 → 1 Experience" loop, so the rule has to hold on the
#// LOOP'S EXIT as well as its entry. P1 declines the Credit offer, pays the unit's cost 1 from
#// resources (1 ready left), then pays that last resource for 1 Experience (1/1 → 2/2). With 0 ready
#// resources and 2 Credits still in the zone the loop must STOP — not keep offering against Credits.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LOF_255
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1CREDITCOUNT:2
P1RESAVAILABLE:0
P1NODECISION

---

# TWI125_CreditsDoNotCreateTokens
#// Same family, different payoff: TWI_125 The Clone Wars — "Pay any number of resources. Create that
#// many Clone Trooper tokens. Each opponent creates that many Battle Droid tokens." "That many" counts
#// resources, so Credits must not create tokens for either player. P1 declines the Credit offer and
#// spends both resources on the event's cost 2, leaving 0 ready and 2 Credits — so the pay prompt is
#// never raised and NOBODY gets a token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: TWI_125
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:2
P1RESAVAILABLE:0
P1NODECISION

---

# SOR138_CreditsDoNotScaleTheDamage
#// SOR_138 Force Lightning — "Choose a unit. It loses all abilities for this phase. Then, if you control
#// a Force unit, pay any number of resources and deal 2 damage to the chosen unit for each resource paid
#// this way." The ability-loss half is free and still happens; only the DAMAGE scales with resources.
#// P1 controls Obi-Wan (a Force unit), declines the Credit offer, and spends its only resource on the
#// event's cost 1 — leaving 0 ready and 2 Credits, so no damage prompt is raised and the enemy is
#// untouched. Guards the whole "pay any number of resources" family, not just the Experience ones.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1Credits: 2
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:2
P1RESAVAILABLE:0
P1NODECISION
