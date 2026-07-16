# WhenDefeated_6Power_StaysInDiscard
#// SEC_035 Darth Sion — boundary guard: 6 power at defeat (< 7) does NOT return him to hand.
#// One Experience token makes Sion 6/6. He attacks the 8/8 SOR_039 and dies to the counter. His
#// power-at-defeat is 6 (< 7), so the When Defeated does nothing — he stays in P1's discard pile.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_035:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_035
P2GROUNDARENACOUNT:1

---

# WhenDefeated_7Power_ReturnsToHand
#// SEC_035 Darth Sion (Unit, 5/5, cost 5, Vigilance/Villainy, Force/Sith)
#//   "When Defeated: If this unit had 7 or more power, return him to his owner's hand."
#// Sion is base 5/5; two Experience tokens make him 7/7. He attacks an 8/8 enemy (SOR_039) and dies
#// to the counter (7 HP < 8 power). His power-at-defeat (7, via the Experience subcards) is >= 7, so
#// he returns to P1's hand instead of staying in the discard. Because base power is 5 (< 7), a return
#// here PROVES the at-defeat snapshot is read, not the post-strip base power. He dies as the ATTACKER
#// so the When Defeated drains inside P1's own action (cross-player whenDefeated doesn't drain).

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

---

# WhenPlayed_ExpPerEnemyDefeated
#// SEC_035 Darth Sion (Ground, 5/5) — When Played: give an Experience token to him for each enemy unit
#//   defeated this phase. P1's SOR_095 kills SOR_128 first (1 enemy defeated), then Darth Sion enters →
#//   1 Experience → 6/6.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_035

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_035
P1GROUNDARENAUNIT:0:POWER:6
P1NODECISION
