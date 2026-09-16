# SixResources_PlusTwo
#// COVERAGE: offer=N/A (STRUCTURAL: a constant ability) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=this section (6) paired with FiveResources_NoBonus (5)
#//           quantity=CreditsAreNotResources · control=N/A ("you control" reads the controller)
#//           reqboundary=N/A (STRUCTURAL: live read) · modes=2P only ("you control" is self-only)
#//
#// HMW_256 Jedi Interceptor — Unit (Space) 2/2, cost 2, [Heroism], Jedi/Republic/Vehicle/Fighter.
#// "While you control 6 or more resources, this unit gets +2/+0."

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_256:1:0
WithP1Resources: 6:SOR_046:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:2

---

# FiveResources_NoBonus

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_256:1:0
WithP1Resources: 5:SOR_046:1

## EXPECT
P1SPACEARENAUNIT:0:POWER:2

---

# CreditsAreNotResources

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_256:1:0
WithP1Resources: 5:SOR_046:1
WithP1Credits: 2

## EXPECT
P1SPACEARENAUNIT:0:POWER:2

---

# TheBonusDealsDamage

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_256:1:0
WithP1Resources: 6:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
