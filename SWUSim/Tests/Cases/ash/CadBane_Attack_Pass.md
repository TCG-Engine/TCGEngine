## GIVEN
SkipPreGame: true
CommonSetup: grk/rrk/{
  myLeader: ASH_011:1:1:3
}
WithP1GroundArena: SEC_080

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:PASS
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0