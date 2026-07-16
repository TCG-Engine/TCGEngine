# FriendlyDamagedDealsToBases
#// ASH_032 Rancor Keeper (Ground, 2/4) — When a friendly unit is dealt damage and survives: deal 1 damage
#// to any number of bases (once each round). Rancor attacks SEC_080 (3/3): both survive, and Rancor (a
#// friendly unit) took 3 counter and survived → the player deals 1 to each base.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_032:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0&theirBase-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:ASH_032
P1GROUNDARENAUNIT:0:DAMAGE:3
