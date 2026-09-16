# ThreeUnits_GainsSentinel
#// COVERAGE: offer=N/A (STRUCTURAL: a constant keyword grant) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=this section (3) paired with TwoUnits_NoSentinel (2)
#//           control=N/A ("you control" reads the controller) · reqboundary=N/A (STRUCTURAL: live read)
#//           behaviour=Sentinel_RedirectsAnEnemyAttack · modes=2P only ("you control" is self-only) —
#//           TeamSuns_TeammatesUnitsDoNotCount pins it
#//
#// HMW_137 V-19 Skirmisher — Unit (Space) 3/3, cost 3, [Command], Republic/Vehicle/Fighter.
#// "While you control 3 or more units, this unit gains Sentinel."

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_137:1:0
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwoUnits_NoSentinel

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_137:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# TokenUnitsCount

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_137:1:0
WithP1GroundArena: [TWI_T01:1:0 TWI_T01:1:0]

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# Sentinel_RedirectsAnEnemyAttack
#// P2's SOR_225 TIE Fighter (2/1) attacks the base; the V-19 is the only legal target, so it takes the hit.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_137:1:0
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# TeamSuns_TeammatesUnitsDoNotCount

## GIVEN
CommonSetup: ggw/bbk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: HMW_137:1:0
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: [SOR_095:1:0 SOR_095:1:0]

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
