# SOR_226 Admiral Motti WhenDefeated — readies the only Villainy unit (auto-pick).
# P1 attacks with Motti (1/1) into P2's Battlefield Marine (3/3). Motti is defeated.
# P1 says YES; Cell Block Guard (SOR_229, Villainy, exhausted) is the only eligible target.

## GIVEN
P1LeaderBase: SOR_001/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_226:1:0
WithP1GroundArena: SOR_229:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:READY
