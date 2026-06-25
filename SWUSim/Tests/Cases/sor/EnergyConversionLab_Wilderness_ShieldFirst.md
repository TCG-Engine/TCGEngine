# SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player declines Ambush.
# SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
# Ambush fires first (auto-dispatch). Player says NO → no attack. Shielded fires next → shield given.
# Unit survives with 0 damage and 1 shield.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED
