# Tarfful_WookieeCombatDamage_DealsBack
#// SHD_250 Tarfful — "When a friendly Wookiee unit is dealt combat damage and isn't defeated: That unit
#// deals that much damage to an enemy ground unit." SHD_249 (Wookiee, 2/5) attacks SOR_046 (3/7): it deals
#// 2 (SOR_046 survives) and takes 3 counter-damage, surviving. Tarfful's observer then has SHD_249 deal 3
#// to the enemy SHD_095 (2/3), defeating it.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_250:1:0
WithP1GroundArena: SHD_249:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
