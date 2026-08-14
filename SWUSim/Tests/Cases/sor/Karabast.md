# FriendlyDamagePlus1
#// SOR_151 Karabast (Event, cost 2) — a friendly unit deals damage to an enemy unit
#// equal to (damage on the friendly unit + 1). P1's Battlefield Marine has 2 damage on
#// it, so it deals 2 + 1 = 3 to P2's Consular Security Force (7 HP → 3 damage, survives).
#// Both selections auto-resolve (one option each).
#// COVERAGE: offer=Offer_FriendlyPool_BothArenas (pending SELECTABLEEXACT: friendly dealer pool
#//           spans both arenas, damaged or not); the enemy pool is exercised interactively in
#//           UndamagedDealer_Deals1 (both enemy units candidates, ground one picked) ·
#//           decline=N/A (both picks mandatory) · control=N/A (damage snapshot of the chosen
#//           friendly unit; no persistent effect to change controller) · boundary=damage 2 → deals
#//           3 (FriendlyDamagePlus1) vs damage 0 → deals 1 (UndamagedDealer_Deals1) ·
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
