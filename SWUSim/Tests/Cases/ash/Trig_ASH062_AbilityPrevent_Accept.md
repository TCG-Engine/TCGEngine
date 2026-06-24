# ASH_062 The Mandalorian — the rider also fires against NON-combat (ability) damage. P1 plays Open Fire
# (SOR_172, "Deal 4 damage to a unit") targeting its OWN damaged SOR_095; P1 defeats The Mandalorian's
# Shield to prevent it, so SOR_095 takes 0 and the Shield is gone.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 1
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
