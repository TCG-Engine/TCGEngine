# OnAttack_DealOneToBase
#// IBH_006 Rebellion Y-Wing (Space, 2/3, Cunning/Heroism) — On Attack: deal 1 damage to a base. The
#//   Y-Wing attacks an enemy 2/1 space unit (combat → the unit, which dies); the On Attack separately
#//   deals 1 to the enemy base. Isolates the On Attack base damage from combat.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2BASEDMG:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Reprints024_032
#// IBH_024 / IBH_032 Rebellion Y-Wing (reprints of IBH_006) — On Attack: deal 1 to a base. Two reprints,
#//   each attacks the enemy base directly; base takes combat (2) + On Attack (1) = 3.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_024:1:0

## WHEN
- P1>AttackSpaceArena:0:theirBase-0

## EXPECT
P2BASEDMG:3
P1NODECISION
