# WhenPlayed_HealPerOfficial
#// SEC_102 Renowned Dignitaries (Ground, 5/6, cost 6) — Overwhelm + When Played: heal 2 damage from your
#//   base for each friendly Official unit. With SEC_041 (Official) already out, playing SEC_102 (also an
#//   Official) = 2 Officials → heal 4 (base 5 → 1).

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:1
