# FrontSearchAfterRebelDefeat
#// LAW_005 Jyn Erso (leader front) — "Action [1 resource, Exhaust]: If a friendly Rebel unit was defeated
#// this phase, search the top 3 of your deck for a card and draw it." P1's Rebel SOR_095 attacks the 8/8
#// SOR_039 and dies (Rebel defeated this phase); then Jyn's action searches and draws SOR_046.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1
