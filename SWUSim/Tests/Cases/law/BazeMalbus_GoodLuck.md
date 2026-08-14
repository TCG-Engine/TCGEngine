# HealedDealsThatMuch
#// LAW_047 Baze Malbus (6/8, Sentinel) — When 1+ damage is healed from this unit: you may deal that much
#// to a unit. Ezra (LAW_035) heals 2 from the damaged Baze; Baze then deals 2 to the enemy SOR_046.
#// COVERAGE: offer=HealedOfferIsAllUnitsBothArenas (pending SELECTABLEEXACT: all units, both arenas and
#//           sides incl. Baze himself) · decline=HealedDeclineDealsNothing · boundary pair=heal-source
#//           sweep — unit ability (HealedDealsThatMuch), event (HealedByEventThenDeals), leader ability
#//           (HealedByLeaderAbilityThenDeals), with per-section amounts 2/3/1 proving "that much" tracks
#//           the healed amount · control=N/A (the reaction is hosted on Baze and reads its live
#//           controller; no persistent marker) · reqboundary=HealedByLeaderAbilityThenDeals (heal target
#//           and damage target answered on separate requests; the healed amount survives the round-trip)

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_047
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# HealedByEventThenDeals
#// LAW_047 Baze Malbus — the "when 1+ damage is healed from this unit, deal that much" reaction fires no
#// matter the heal SOURCE. Here an EVENT (Repair, heals 3) heals Baze from 5 -> 2 damage, then Baze deals
#// 3 to the enemy SOR_046.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:5
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_047
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# HealedByLeaderAbilityThenDeals
#// LAW_047 Baze Malbus — the heal reaction also fires when the heal comes from a LEADER ability. Obi-Wan
#// Kenobi TWI_003 (undeployed, "Action [Exhaust]: Heal 1 damage from a unit") heals 1 from the damaged
#// Baze (2 -> 1), then Baze deals that 1 to the enemy SOR_046.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4;myLeader:TWI_003}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:LAW_047
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# HealedOfferIsAllUnitsBothArenas
#// LAW_047 Baze Malbus — the deal-that-much offer spans ALL units: Baze himself, the friendly SPACE
#// unit, and the enemy ground unit are all candidates. Repair heals 3 from Baze (5 -> 2), then the
#// damage decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:5
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# HealedDeclineDealsNothing
#// LAW_047 Baze Malbus — the deal-that-much is a "you may": declining it leaves every other unit
#// untouched. Repair heals 3 from Baze (5 -> 2); the damage offer is declined and the enemy SOR_046
#// takes 0.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:5
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_047
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
