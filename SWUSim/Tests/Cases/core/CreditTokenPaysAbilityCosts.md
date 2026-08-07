# LeaderAction_CreditPaysWholeCost
#// CR 3.13 — "While paying resources, you may defeat this token. If you do, pay 1 less." That is
#// unconditional on WHAT is being paid for, so it covers an activated ability's resource cost, not
#// just playing a card. SOR_007 Grand Moff Tarkin is Action [1 resource, Exhaust]: give an Experience
#// token to an Imperial unit. P1 holds ZERO ready resources and one Credit token, so the Credit is
#// the entire cost. The Action must be available, the Credit defeated, and the Experience given.

## GIVEN
CommonSetup: rrk/rrk/{myResources:0;myLeader:SOR_007}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# LeaderAction_CreditPaysPartOfTwoCost
#// A Credit pays exactly 1 of a multi-resource cost (CR 3.13 "pay 1 less"), so 1 ready resource +
#// 1 Credit covers LAW_010 Leia's Action [2 resources, Exhaust]. SEC_080 has two different aspects
#// (Command, Villainy) so the buff is +2/+2 → 5/5. Proves the split payment, not just the all-Credit
#// case: the resource IS exhausted and the Credit IS defeated.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:LAW_010}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# LeaderAction_DeclineCredit_PaysWithResources
#// Defeating the Credit is OPTIONAL ("you MAY defeat this token"). With 1 ready resource AND 1
#// Credit, declining the offer must pay the full cost from resources and KEEP the Credit — the
#// player's real tempo choice (spend a one-shot Credit vs. an exhausting resource). Two Imperial
#// units make Tarkin's own target pick a genuine MZCHOOSE, so the decline answer cannot be
#// swallowed by an auto-resolving target choice.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:SOR_007}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: [SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3
P1CREDITCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_InsufficientTotalCapacity_NoOp
#// The load-bearing NEGATIVE: a Credit pays only 1, so 0 ready resources + 1 Credit is capacity 1,
#// below LAW_010's 2-resource cost. The Action must stay unavailable and be a complete no-op — the
#// leader stays READY (the player keeps their action), the Credit is not consumed, no buff lands.
#// This is what stops the fix from becoming "offer it anyway and fail after exhausting the leader".

## GIVEN
CommonSetup: rrk/rrk/{myResources:0;myLeader:LAW_010}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1CREDITCOUNT:1

---

# UnitAction_CreditPaysPartOfCost
#// A UNIT's activated ability has the same kind of resource cost as a leader's, so CR 3.13 applies
#// identically. TWI_206 Independent Senator is Action [2 resources, Exhaust]: exhaust a unit with 4 or
#// less power. P1 holds 1 ready resource + 1 Credit — enough. Before the fix the Action was silently
#// UNAVAILABLE: clicking it did nothing at all (not a mispriced cost — a dead button).

## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: TWI_206:1:0
WithP2GroundArena: [SOR_095:1:0 TWI_149:1:0]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myResources-1

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:TWI_149
P2GROUNDARENAUNIT:1:READY
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# UnitAction_InsufficientTotalCapacity_NoOp
#// NEGATIVE for the unit-action gate: 0 ready resources + 1 Credit is capacity 1, below TWI_206's
#// 2-resource cost. The Action must stay unavailable and change nothing — the Senator stays READY
#// (its Exhaust cost unpaid), no enemy is exhausted, and the Credit survives.

## GIVEN
CommonSetup: rrk/bbw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: TWI_206:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1CREDITCOUNT:1

---

# BaseEpicAction_CreditPaysCost
#// A base's Epic Action cost is a resource cost too. LAW_029 Citadel Research Center is
#// Epic Action [1 resource]: return a friendly resource to its owner's hand, then resource the top
#// card of your deck. P1's only real resource is EXHAUSTED (0 ready), so today the Epic is "not
#// selectable"; with a Credit the cost IS payable, so it must be offered. The exhausted resource is
#// still a legal thing to RETURN (returning has no ready requirement), and it is the only legal
#// choice once the Credit is spent — so that pick auto-resolves.

## GIVEN
CommonSetup: ybw/grw/{myBase:LAW_029}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_128:0
WithP1Credits: 1
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1BASE:EPICUSED
P1HANDCOUNT:1
P1DECKCOUNT:0
P1RESCOUNT:1
P1CREDITCOUNT:0

---

# BaseEpicAction_CreditCannotSubstituteTheReturnedResource
#// SCOPE EXCLUSION — the two resource-shaped costs are NOT interchangeable. A Credit token may PAY
#// the [1 resource] (CR 3.13) but is never itself "a friendly resource" to return: Credits are
#// created in the resource zone and are explicitly NOT resources. With ONLY a Credit and no real
#// resource the Epic must be a complete no-op and stay AVAILABLE — it must not consume the
#// once-per-game Epic, and must not return the Credit to hand.

## GIVEN
CommonSetup: ybw/grw/{myBase:LAW_029}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1Credits: 1
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICAVAILABLE
P1HANDCOUNT:0
P1DECKCOUNT:1
P1CREDITCOUNT:1

---

# Smuggle_CreditPaysPartOfCost
#// Smuggle is a cost paid to PLAY a card, so CR 3.13 covers it. SHD_111 Pirate Battle Tank has
#// Smuggle [3 resources, Command]. It self-pays 1 by exhausting itself while still a resource
#// (CR 8.22.e), leaving 2 — covered by 1 ready resource + 1 Credit. Before the fix the payment ran a
#// bare resource sweep, so the 2 could not be met and the play silently rolled back.
#// The spent slot is replaced from the top of the deck, so the resource COUNT stays 2.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:SHD_111:1
WithP1Credits: 1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:1
- P1>AnswerDecision:myResources-2

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1CREDITCOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:0

---

# Smuggle_DeclineCredit_PaysWithResources
#// The Credit is optional here too. With TWO other ready resources the player can cover Smuggle's
#// remaining 2 outright, so declining must pay from resources and keep the Credit. Proves the new
#// offer does not force the token away, and that declining still completes the play.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1,1:SHD_111:1
WithP1Credits: 1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:2
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1CREDITCOUNT:1
P1RESAVAILABLE:0

---

# Plot_CreditPaysCost
#// A Plot play pays the card's cost from the resource zone, so CR 3.13 applies. P1 deploys Luke
#// (SOR_005, threshold 6 — Credits are NOT resources so they never count toward it) opening the Plot
#// window. SEC_226 (cost 2, Cunning) is the only READY resource: it self-pays 1, and the remaining 1
#// has no ready resource behind it — only the Credit. Before the fix _SWUEligiblePlotResources gated
#// on ready resources alone, so the Plot was never even OFFERED.
#// SEC_226 is an Upgrade, so its cost is paid host-side by ATTACH_UPGRADE's own alt-pay offer (the
#// Plot path deliberately does NOT offer as well — that double-ask is a known failure mode). Two
#// friendly units are in play once Luke deploys, so the host pick is a real choice.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0,1:SEC_226:1
WithP1Credits: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-5
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myResources-6

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SEC_226
P1CREDITCOUNT:0

---

# DiscountPlay_CreditPaysPartOfCost
#// The "play a card from your hand" EFFECT family (here LAW_020's Epic Action). Two things had to
#// change for it: the offer filter, which listed only cards affordable from ready resources, and the
#// payment terminal, which swept resources only. P1 has 1 ready resource + 1 Credit and a 2-cost
#// on-aspect unit in hand — affordable only if the Credit counts, so before the fix the Epic found
#// zero targets and did nothing while still burning the once-per-game Epic.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1;myBase:LAW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1BASE:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# CardAbilityPayCost_CreditPaysIt
#// A card's own inline "you may pay N" cost. LAW_214 Boba Fett (For a Price) — "On Attack: You may pay
#// 1 resource. If you do, deal 3 damage to a ground unit." With 0 ready resources the offer was
#// suppressed entirely, so a player holding a Credit could never use the ability. Boba attacks the
#// base; the rider is paid with the Credit and deals 3 to the enemy ground unit.

## GIVEN
CommonSetup: yyk/bbw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: LAW_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1CREDITCOUNT:0

---

# CardAbilityPayCost_NoTokenAndNoResource_NotOffered
#// NEGATIVE for the same gate: with neither a ready resource nor any Credit, capacity is 0 and the
#// "you may pay 1" rider must not be offered at all — the attack resolves with no prompt left
#// pending and no rider damage. Guards against the gate being loosened to "always offer, fail later".

## GIVEN
CommonSetup: yyk/bbw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# LeaderAction_DroidPaysCost
#// The other alt-payment source: SEC_122 Droid Control Ship — "Each friendly Droid unit may be
#// exhausted to pay costs as if it were a resource." Like CR 3.13 this says "pay COSTS", not "play
#// cards", so it must cover an activated ability. P1 has 0 ready resources; SEC_080 (an Imperial
#// DROID) is exhausted to pay Tarkin's [1 resource], and still receives the Experience token.

## GIVEN
CommonSetup: rrk/rrk/{myResources:0;myLeader:SOR_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SEC_122:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1RESAVAILABLE:0
