# SHD_224 Boba Fett's Armor — the prevention is gated on the host being Boba Fett. On a non-Boba host
# (SOR_046), SHD_180's 3 damage is NOT reduced → full 3 sticks.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP1Hand: SHD_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
