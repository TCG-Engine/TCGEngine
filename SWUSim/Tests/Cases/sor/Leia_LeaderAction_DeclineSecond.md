# SOR_009 Leia Organa — the second attack is optional ("you may"). Declining it leaves only the
# first Rebel's attack: base takes 3, the second Rebel is untouched and stays ready.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED
