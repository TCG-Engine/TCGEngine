# DefeatCheapUnit
#// LOF_077 Crushing Blow — Defeat a non-leader unit that costs 2 or less. The enemy SOR_059 (cost 1)
#// is defeated.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_077}
P1OnlyActions: true
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# Offer_CostTwoOrLessNonLeader_SpansBOTHSides_AndPinsTheCostBoundary
#// THE OFFER CELL, doubling as the cost BOUNDARY - the pool is where a "2 or less" threshold is
#// actually decided, so asserting it pins the number and the exclusions in one section.
#//   · cost 2 is IN  (P1's Battlefield Marine and P2's Dark Trooper, both cost 2);
#//   · cost 3 is OUT (P2's Cloud City Wing Guard) - one over the line, so the pair fixes the threshold
#//     at 2 rather than at any larger number;
#//   · a deployed LEADER is out regardless of cost ("non-leader");
#//   · no controller word, so P1's own unit is a legal target.
#// ⚠ A deployed leader is appended LAST, so P2 reads [SEC_080, SOR_063, leader].

## GIVEN
CommonSetup: bbw/rrk/{theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: [LOF_077]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
