# SHD_016 Fennec Shand (front Action [1 resource, Exhaust]) — "Play a unit that costs 4 or less from
# your hand (paying its cost). Give it Ambush for this phase." SOR_229 (cost 2) is played and gains
# Ambush; with no enemy units the Ambush attack is skipped, so it sits in play with the keyword.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 6

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
