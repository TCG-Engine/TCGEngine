# IBH_088 General Veers (reprint of IBH_068) — if Vigilance unit: deal 2 enemy base + heal 2 own base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_088
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1BASEDMG:1
P1NODECISION
