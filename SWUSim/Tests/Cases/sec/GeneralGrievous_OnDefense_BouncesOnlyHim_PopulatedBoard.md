# SEC_187 General Grievous — clean fizzle on a populated board. P2 controls Grievous AND a vanilla
#   SEC_080 (3/3). P1 attacks Grievous specifically; he bounces to hand before damage, the attack
#   fizzles, and the other P2 unit + the attacker are untouched. Proves the right unit bounces and no
#   stray damage lands.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_187:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:2
