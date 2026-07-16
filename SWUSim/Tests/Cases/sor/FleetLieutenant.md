# WhenPlayed_RebelGetsBuff
#// SOR_240 Fleet Lieutenant (3/3, Ground) — When Played: You may attack with a unit. If it's a
#// Rebel unit, it gets +2/+0 for this attack. The chosen attacker (Battlefield Marine, a Rebel
#// 3/3) attacks the undefended enemy base for 3 + 2 = 5. The +2 is for THIS attack only, so the
#// attacker is back to power 3 afterward.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_095:1:0    # Rebel attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
