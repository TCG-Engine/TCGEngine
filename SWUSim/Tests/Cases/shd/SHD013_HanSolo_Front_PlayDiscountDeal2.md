# SHD_013 Han Solo (front Action [Exhaust]) — "Play a unit from your hand. It costs 1 resource less.
# Deal 2 damage to it." SOR_229 (cost 3 → 2) is played and takes 2 damage; its discounted cost of 4 (penalized 5, -1) is paid.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:0
