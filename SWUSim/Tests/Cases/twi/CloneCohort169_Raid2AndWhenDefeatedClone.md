# TWI_169 Clone Cohort (Upgrade, cost 2, Supply) — "Attached unit gains Raid 2 and: 'When Defeated: Create
# a Clone Trooper token.'" SOR_095 (with the cohort) attacks SOR_046 (3/7): Raid 2 makes it deal 3+2=5,
# and dying to the 3 counter creates a Clone Trooper (TWI_T02).

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_169
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:5
