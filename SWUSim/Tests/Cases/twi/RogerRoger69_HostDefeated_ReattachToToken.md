# TWI_069 Roger Roger (Upgrade +1/+1, attach to a Battle Droid token) — "When Defeated: Attach this
# upgrade to a friendly Battle Droid token." Roger Roger's host token (2/2 with it) attacks a 3/3 and
# dies; instead of going to discard, Roger Roger re-attaches to the other friendly Battle Droid token,
# which becomes a 2/2 (1/1 token + Roger Roger's +1/+1).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
