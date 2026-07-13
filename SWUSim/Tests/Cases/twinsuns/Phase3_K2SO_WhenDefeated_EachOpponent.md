# Twin Suns Phase 3: SOR_145 K-2SO "When Defeated: FOR EACH OPPONENT, choose one: deal 3 to that player's
# base OR that player discards." In a 3-player game K-2SO's controller gets a SEPARATE choice per opponent.
# K-2SO attacks P2's 4/7 wall and dies to the 4 counter; P1 then chooses Base for P2 AND Base for P3 →
# 3 damage to EACH of their bases (2-player fires one choice; here it fires twice, once per opponent).

## GIVEN
CommonSetup: ggw/brw
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:Base
- P1>AnswerDecision:Base

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P3BASEDMG:3
