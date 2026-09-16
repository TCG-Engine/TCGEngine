# Offer_NonVehicleThreePowerOrLess_EitherSide
#// COVERAGE: offer=this section (the host pool) · decline=N/A (STRUCTURAL: an attach restriction, no "may")
#//           boundary=this section (SOR_095 3 in / LAW_124 4 out) · negative=this section (SOR_237 Vehicle out)
#//           current-power=BuffedUnitDropsOut · no-host=NoLegalHost_StaysInHand
#//           control=N/A (attach to "a unit" — enemy hosts legal, CR 2.e) · reqboundary=N/A (STRUCTURAL: no state)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_235 Gaderffii Stick — Upgrade +2/+1, cost 1, [Cunning], Item/Weapon.
#// "Attach to a non-Vehicle unit with 3 or less power."

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_235
WithP1GroundArena: [SOR_095:1:0 LAW_124:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# AttachesAndBuffs

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_235
WithP1GroundArena: [SOR_095:1:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# BuffedUnitDropsOut
#// SOR_095 + SOR_120 Academy Training is 5 power — out; the enemy SEC_080 is the only legal host.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_235
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoLegalHost_StaysInHand

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_235
WithP1GroundArena: LAW_124:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
