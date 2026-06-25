# SOR_226 Admiral Motti WhenDefeated — player declines; Villainy unit stays exhausted.
# P1 attacks with Motti (1/1) into P2's Battlefield Marine (3/3). Motti is defeated.
# P1 says NO; Cell Block Guard (SOR_229) remains exhausted.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_226:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:EXHAUSTED
