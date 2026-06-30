# SOR_091 The Emperor's Legion — "Return each unit in your discard pile that was defeated this
# phase to your hand." P1's SOR_128 (3/1) attacks P2's SEC_080 (3/3): both die (SOR_128 deals 3 =
# lethal, takes 3 back). SOR_128 went to P1's discard as DEFEATED-this-phase. P1 then plays SOR_091
# → SOR_128 returns to P1's hand. SEC_080 is in P2's discard (different pile) → untouched.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_091
