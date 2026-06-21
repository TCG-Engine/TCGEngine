# LAW_096 Rhydonium Detonation (Cunning,Vigilance event, cost 7) — "Each player may return a non-leader
# unit to its owner's hand. Then, defeat all non-leader units." P1 saves SEC_080, P2 saves SOR_095;
# the remaining non-leader (P2's SOR_237) is defeated.

## GIVEN
CommonSetup: byk/brk/{myResources:7}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_096

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
