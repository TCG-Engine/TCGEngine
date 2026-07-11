# SHD_129 Timely Intervention (1-cost event) — "Play a unit from your hand (paying its cost). Give
# it Ambush for this phase." The marine (sole affordable hand unit → auto-pick) enters with granted
# Ambush and ambush-attacks the enemy Dark Trooper: mutual 3-damage kill. Resources prove the cost
# was paid (1 event + 2 marine = all 3 spent).

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_129
WithP1Hand: SOR_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1HANDCOUNT:0
