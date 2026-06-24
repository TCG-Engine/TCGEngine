# ASH_147 The Cyborg Mech — the alternative mode: 2 damage to an UNDAMAGED ground unit. P1 targets the
# undamaged SEC_080 (3/3) → 2 damage (survives).
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
