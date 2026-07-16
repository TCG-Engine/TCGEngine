# ArenaOnAttackSelfDamage
#// ASH_186 Treacherous Minefield (Event, cost 2) — Choose an arena. For this phase, each unit in that
#// arena gains "On Attack: deal 2 damage to this unit." P1 plays it choosing Ground; then SOR_046 attacks
#// the enemy base and takes 2 self-damage from the granted On Attack.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
