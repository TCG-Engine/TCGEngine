# SHD_160 Reckless Gunslinger (1-cost 2/1) — "When Played: Deal 1 damage to each base." Both bases
# take 1 (including the controller's own).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_160

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:1
P2BASEDMG:1
