# Stolen AT-Hauler: opponent may play it from owner's discard for free
# JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
# SOR_237 (Alliance X-Wing, 2/3) attacks it — JTL_221 is defeated, gains OTPF.
# P2 has 0 resources — they can still play JTL_221 from P1's discard for free (OTPF).
# P1 then attacks with their 5 power space unit
# P2 claims initiative
# P1 is now allowed to play AT Hauler from their own discard pile for free

## GIVEN
CommonSetup: grw/yrw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Claim
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_221
P1DISCARDCOUNT:0
