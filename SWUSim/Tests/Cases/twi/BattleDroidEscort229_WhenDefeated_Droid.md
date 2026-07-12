# TWI_229 Battle Droid Escort — the same ability fires from When Defeated. It (1/1) attacks SOR_046 (3/7)
# and dies to the counter, creating a Battle Droid in its place.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_229:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
