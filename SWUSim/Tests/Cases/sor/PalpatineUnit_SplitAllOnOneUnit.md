# SOR_135 — only one enemy unit on the board: all 6 must be assigned to it (overkill is legal).
# A single 3/3 takes the full 6 and is defeated. Confirms the full pool can pile onto one target.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0    # 3/3 — takes all 6, defeated

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:6

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_135
