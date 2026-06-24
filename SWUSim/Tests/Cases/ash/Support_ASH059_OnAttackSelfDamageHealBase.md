# ASH_059 Leia Organa (Ground, 3/4, Support) — On Attack: you may deal 1 damage to this unit; if you do,
# heal 2 damage from your base. P1's base starts at 3 damage; Leia attacks the enemy base, takes 1
# self-damage, and heals 2 from her base (3 → 1). The enemy base takes Leia's 3.
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:3}
WithP1GroundArena: ASH_059:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:ASH_059
P1GROUNDARENAUNIT:0:DAMAGE:1
