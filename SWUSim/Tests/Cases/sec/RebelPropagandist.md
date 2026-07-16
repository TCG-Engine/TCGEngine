# WhenPlayed_BuffAndSaboteur
#// SEC_202 Rebel Propagandist (Ground, 2/4, Cunning/Heroism) — When Played/When Defeated: give another
#//   friendly unit +1/+0 and Saboteur for this phase. Buffs SOR_095 → 4/3 with Saboteur.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_202

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1NODECISION
