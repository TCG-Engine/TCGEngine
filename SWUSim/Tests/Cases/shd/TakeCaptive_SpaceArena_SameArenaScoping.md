# SHD_131 Take Captive — same-arena scoping: space capturer captures space enemy only.
# P1 has one space unit (SOR_162 Disabling Fang Fighter, 3/3) as the capturer.
# P2 has one space unit (SOR_237 Alliance X-Wing, 2/3) as the enemy to capture.
# No ground units on either side, confirming space→space arena derivation in SHD131_PICK_CAPTURER.
# Both choices auto-picked (single eligible each step via PASSPARAMETER).
# Assertions: P1 space unit has UPGRADECOUNT 1; captive is SOR_237; P2 space arena empty.
# Resources: 3 ready → 0 remaining after paying SHD_131 cost 3.
# Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1SpaceArena: SOR_162:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_162
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
P1RESAVAILABLE:0
