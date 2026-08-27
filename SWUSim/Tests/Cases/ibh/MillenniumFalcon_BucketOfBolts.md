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

---

# TwinSuns_ComparesAgainstTheLEASTDamagedEnemyBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 4, an EXISTENTIAL comparison.
#// "If your base has more damage on it than AN enemy base" — "an" means ANY, so the condition is met
#// when even one enemy base is less damaged than yours. This compared against GetOpponent() alone, i.e.
#// seat 2.
#// The fixture is built so seat 2 says NO and seat 4 says YES: P1 has 3 damage, seat 2 has 5 (3 > 5 is
#// false) and seat 4 has 0 (3 > 0 is true). Under the old code the Falcon does not ready; it must.

## GIVEN
CommonSetup: yyw/rrk/{myBaseDamage:3;theirBaseDamage:5}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 7
WithP1Hand: IBH_031

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SPACEARENAUNIT:0:CARDID:IBH_031
P1SPACEARENAUNIT:0:READY
