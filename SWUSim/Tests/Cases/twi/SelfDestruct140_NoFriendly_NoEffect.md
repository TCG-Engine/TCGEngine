# TWI_140 Self-Destruct — condition guard: with no friendly unit to defeat, nothing happens (the "if you
# do" damage never fires); the enemy unit is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_140}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
