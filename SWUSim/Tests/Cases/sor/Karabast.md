# FriendlyDamagePlus1
#// SOR_151 Karabast (Event, cost 2) — a friendly unit deals damage to an enemy unit
#// equal to (damage on the friendly unit + 1). P1's Battlefield Marine has 2 damage on
#// it, so it deals 2 + 1 = 3 to P2's Consular Security Force (7 HP → 3 damage, survives).
#// Both selections auto-resolve (one option each).
#// COVERAGE: offer=Offer_FriendlyPool_BothArenas (pending SELECTABLEEXACT: friendly dealer pool
#//           spans both arenas, damaged or not) + Offer_EnemyPool_IncludesADeployedLeaderUnit
#//           (the second pick left pending: an enemy deployed leader unit is a legal target) ·
#//           decline=N/A (both picks mandatory) · control=N/A (damage snapshot of the chosen
#//           friendly unit; no persistent effect to change controller) · boundary=damage 2 → deals
#//           3 (FriendlyDamagePlus1) vs damage 0 → deals 1 (UndamagedDealer_Deals1), with the
#//           zero-legal-target edge in NoEnemyUnits_EventFizzlesButIsStillPlayed ·
#//           reqboundary=UndamagedDealer_Deals1 (play → dealer pick → target pick span separate
#//           requests)

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;handCardIds:SOR_151}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # friendly dealer with 2 damage
WithP2GroundArena: SOR_046:1:0    # enemy target (3/7)

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_FriendlyPool_BothArenas
#// SOR_151 Karabast — the FIRST pick (the friendly dealer) offers every friendly unit in
#// either arena, damaged or not; with two candidates the pick stays pending and is asserted
#// here without answering.

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;handCardIds:SOR_151}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # damaged friendly
WithP1SpaceArena: SOR_060:1:0     # undamaged friendly
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# UndamagedDealer_Deals1
#// SOR_151 Karabast — an UNDAMAGED friendly dealer deals 0 + 1 = 1. P1 picks the undamaged
#// space unit as the dealer, then picks P2's ground unit from the enemy pool (both enemy
#// units are candidates); the enemy unit takes exactly 1.

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;handCardIds:SOR_151}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # damaged friendly (NOT chosen)
WithP1SpaceArena: SOR_060:1:0     # undamaged friendly dealer
WithP2GroundArena: SOR_046:1:0    # enemy target
WithP2SpaceArena: SOR_060:1:0     # other enemy candidate

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0

---

# Offer_EnemyPool_IncludesADeployedLeaderUnit
#// SOR_151 Karabast — the SECOND pick's pool had never been asserted: Offer_FriendlyPool_BothArenas
#// reads only the dealer pool, and UndamagedDealer_Deals1 answers the enemy pick, which proves the
#// branch and not the pool. "An ENEMY UNIT" is unqualified — it carries no non-leader exclusion, so a
#// deployed leader unit is a legal target just like any other unit. Here P1's only friendly unit
#// auto-resolves as the dealer, and the enemy pick is left PENDING with P2's ordinary unit AND P2's
#// deployed leader unit both in the pool.
#// (A deployed leader seats at the END of its arena, so it is theirGroundArena-1.)

## GIVEN
CommonSetup: rrw/rrw/{
  myResources:2;
  handCardIds:SOR_151;
  theirLeaderDeployed:true
}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # sole friendly — auto-resolves as the dealer
WithP2GroundArena: SOR_046:1:0    # ordinary enemy unit

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_an_enemy_unit
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoEnemyUnits_EventFizzlesButIsStillPlayed
#// SOR_151 Karabast — the no-valid-target edge. P1 has a friendly unit to deal the damage but P2 has
#// no units at all, so there is nothing for it to be dealt to. Per the standing ruling that SWUSim
#// raises no "use it anyway?" confirmation, the event is still played and paid for and simply does
#// nothing: it goes to the discard, no pick is raised (not even the dealer pick, which would be a
#// dangling decision with no second step to consume it), the friendly unit keeps its 2 damage and is
#// not made to hit anything, and P2's base is untouched — "an enemy UNIT" is not a base.

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;handCardIds:SOR_151}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_151
P1GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
P1NODECISION
