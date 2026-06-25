# SOR_149 Mace Windu (5/7) — "When this unit attacks and defeats a unit: Ready him." Mace attacks
# a 3/3, defeats it, and is readied (so he ends READY despite having attacked). He takes 3
# counter-damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:3
