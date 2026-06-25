# SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player picks Ambush first.
# SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
# P2 Marine has 1 damage. Wilderness attacks (no shield yet) → Marine dies. Takes 3 back → 3 damage.
# Shield token then applied after combat. Survives at 3 damage with a fresh shield.

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
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED
