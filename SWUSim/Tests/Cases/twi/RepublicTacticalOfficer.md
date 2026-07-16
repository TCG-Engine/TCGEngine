# WhenPlayed_AttackRepublic
#// TWI_091 Republic Tactical Officer (Unit 1/4, Ground, cost 2) — "When Played: You may attack with a
#// Republic unit. It gets +2/+0 for this attack." A friendly Clone Trooper (Republic, 2/2) attacks P2's
#// base with +2/+0 → deals 4.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_091}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
