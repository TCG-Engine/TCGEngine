# Support (ASH) — triggered-ability lending (On Attack). ASH_168 Migs Mayfeld (Ground, 2/3, Support +
# "On Attack: deal 1 to the defending unit") is played. The Marine is chosen and attacks an enemy 1/5
# wall (ASH_036). The lent On Attack deals 1 to the wall, then combat deals 3 → 4 damage total; the wall
# (5 HP) survives. ("this unit" in the lent ability = the Marine, which isn't upgraded → deal 1, not 2.)

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_168}
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine (3/3, attacker)
WithP2GroundArena: ASH_036:2:0   # 1/5 wall (defender)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
