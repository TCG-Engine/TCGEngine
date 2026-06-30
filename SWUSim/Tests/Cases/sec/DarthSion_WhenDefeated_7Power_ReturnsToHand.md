# SEC_035 Darth Sion (Unit, 5/5, cost 5, Vigilance/Villainy, Force/Sith)
#   "When Defeated: If this unit had 7 or more power, return him to his owner's hand."
# Sion is base 5/5; two Experience tokens make him 7/7. He attacks an 8/8 enemy (SOR_039) and dies
# to the counter (7 HP < 8 power). His power-at-defeat (7, via the Experience subcards) is >= 7, so
# he returns to P1's hand instead of staying in the discard. Because base power is 5 (< 7), a return
# here PROVES the at-defeat snapshot is read, not the post-strip base power. He dies as the ATTACKER
# so the When Defeated drains inside P1's own action (cross-player whenDefeated doesn't drain).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_035:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_039
