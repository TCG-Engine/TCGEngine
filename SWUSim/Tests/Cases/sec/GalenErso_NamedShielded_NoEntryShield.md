# SEC_046 Galen Erso — naming a Shielded card denies the keyword, so it gets no Shield token on entry.
# P1 names "Crafty Smuggler" (SOR_207, Shielded — normally shields itself when played). P2 then plays
# SOR_207; with Shielded denied it enters with no shield.

## GIVEN
CommonSetup: bbw/yyk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 8
WithP2Hand: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Crafty Smuggler
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_207
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
