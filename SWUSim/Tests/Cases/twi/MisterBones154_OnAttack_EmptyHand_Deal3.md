# TWI_154 Mister Bones (Unit 3/1, Ground, cost 1, Aggression/Aggression, Fringe/Droid) — "On Attack: If
# you have no cards in your hand, you may deal 3 damage to a ground unit." With an empty hand, attacking
# the enemy base offers the deal; targeting SOR_046 (3/7) deals it 3, then combat deals power 3 to the base.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_154:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:3
