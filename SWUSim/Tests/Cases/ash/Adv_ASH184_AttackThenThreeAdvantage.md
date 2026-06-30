# ASH_184 Follow Me (Event, cost 1) — Attack with a unit. After completing the attack, give 3 Advantage
# tokens to a unit. P1 has two ready units (SOR_095 ground, SOR_237 space); plays the event, attacks the
# base with SOR_095 (P2 has no units → auto-targets base), then chooses to give 3 Advantage tokens to
# SOR_095. The post-attack grant fires regardless of which unit attacked.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
