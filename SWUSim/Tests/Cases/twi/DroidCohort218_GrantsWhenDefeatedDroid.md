# TWI_218 Droid Cohort (Upgrade +1/+1, cost 1, Supply) — "Attached unit gains: 'When Defeated: Create a
# Battle Droid token.'" SOR_095 (+1/+1 → 4/4, pre-damaged to 1) attacks SOR_046 (3/7): it deals 4 and
# dies to the 3 counter (1 + 3 ≥ 4 HP); the granted When Defeated creates a Battle Droid.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP1GroundArenaUpgrade: 0:TWI_218
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENAUNIT:0:DAMAGE:4
