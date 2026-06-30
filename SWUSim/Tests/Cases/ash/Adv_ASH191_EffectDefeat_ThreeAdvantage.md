# ASH_191 Shin Hati's Fiend Fighter (Space, 3/1) — When Defeated: you may give 2 Advantage tokens to a
# unit; if NOT defeated by combat damage, give 3 instead. P1 plays Vanquish (SOR_078) on its OWN ASH_191
# (an effect defeat, not combat) → may give 3 Advantage tokens. The bystander SOR_095 receives them.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:SOR_078}
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
