# WhenPlayed_BaseMoreDamaged_Readies
#// IBH_031 Millennium Falcon (Space, 5/6, Cunning/Heroism, cost 7) — When Played: if your base has more
#//   damage than an enemy base, ready this unit. P1 base at 3 damage, enemy base 0 → Falcon enters ready.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_031

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:IBH_031
P1SPACEARENAUNIT:0:READY
P1NODECISION

---

# WhenPlayed_BaseNotMoreDamaged_StaysExhausted
#// IBH_031 Millennium Falcon — if your base is NOT more damaged than an enemy base, the unit enters
#//   exhausted as normal. Both bases at 0 damage → condition false → Falcon stays exhausted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_031

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:IBH_031
P1SPACEARENAUNIT:0:EXHAUSTED
P1NODECISION
