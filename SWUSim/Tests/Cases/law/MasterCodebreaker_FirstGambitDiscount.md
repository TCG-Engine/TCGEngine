# LAW_229 The Master Codebreaker — "the first Gambit card you play each round costs 1 resource less."
# With LAW_229 in play, SEC_211 (Gambit, Cunning/Heroism, cost 2) plays for 1 (off only by the discount):
# with just 1 ready resource it leaves hand for discard and ends at 0 ready (empty deck -> search fizzles).

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
WithP1GroundArena: LAW_229:1:0
WithP1Hand: SEC_211

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
