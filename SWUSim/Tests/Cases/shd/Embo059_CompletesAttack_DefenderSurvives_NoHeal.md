# SHD_059 Embo — the heal is gated on the defender being defeated. Embo (3 power) attacks SOR_046 (3/7),
# which survives → SWU_LAST_DEFENDER_DEFEATED is not set → no heal offer. The damaged friendly SEC_080
# stays at 2 damage, and there is no pending decision.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SEC_080:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION
