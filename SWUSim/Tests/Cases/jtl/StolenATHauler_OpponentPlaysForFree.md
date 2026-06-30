# Stolen AT-Hauler: opponent may play it from owner's discard for free
# JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
# SOR_237 (Alliance X-Wing, 3/2) attacks it — JTL_221 is defeated, gains OTPF.
# P2 has 0 resources — they can still play JTL_221 from P1's discard for free (OTPF).
# After playing, P2's space arena has JTL_221 and P1's discard is empty.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_221
P1DISCARDCOUNT:0
P2RESAVAILABLE:0
