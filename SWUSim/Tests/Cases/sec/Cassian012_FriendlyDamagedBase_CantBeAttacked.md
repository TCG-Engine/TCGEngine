# SEC_012 Cassian Andor (leader front passive) — Friendly units that have damaged an opponent's base this
# phase can't be attacked (unless they have Sentinel). P1's SOR_095 attacks P2's base (flagging it as
# having damaged the base). When P2 then attacks, SOR_095 is no longer a legal target, so P2's SOR_128
# auto-resolves onto P1's base instead (proving the exclusion — with 2 legal targets it would not
# auto-resolve). SOR_095 ends undamaged.

## GIVEN
P1LeaderBase: SEC_012/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
