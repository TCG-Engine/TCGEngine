# Twin Suns Phase 3 / Group B: JTL_130 Timely Reinforcements "Choose an opponent. For every 2 resources
# they control, create an X-Wing (Sentinel)." The caster PICKS which opponent's resources to count. P2 has
# 2 resources, P3 has 6; choosing P3 makes floor(6/2)=3 X-Wings (choosing P2 would make 1) — proving the
# count reads the CHOSEN opponent, not the lone/first one.

## GIVEN
CommonSetup: ggw/bbk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 2
WithP3Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P1SPACEARENACOUNT:3
