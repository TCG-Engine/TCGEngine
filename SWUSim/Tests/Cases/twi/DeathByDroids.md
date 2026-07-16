# DefeatEnemy_Create2Droids
#// TWI_076 Death by Droids (Event, cost 5, Vigilance) — "Defeat a unit that costs 3 or less.
#// Create 2 Battle Droid tokens." P2 has a single SEC_080 (cost 3) — the only ≤3-cost unit on the
#// board. Single target → auto-resolves, SEC_080 is defeated, then 2 Battle Droid (TWI_T01) tokens
#// enter P1's ground arena (indices 0,1). Base b = Vigilance covers the single Vigilance pip → no penalty.

## GIVEN
CommonSetup: brk/grw/{myResources:5;handCardIds:TWI_076}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1NODECISION

---

# NoTarget_StillCreatesDroids
#// TWI_076 Death by Droids — no unit costs 3 or less (P2's only unit is JTL_069, cost 4). The defeat
#// clause fizzles cleanly (nothing to defeat), but the second sentence still creates 2 Battle Droid
#// tokens. Guard for the empty-target branch.

## GIVEN
CommonSetup: brk/grw/{myResources:5;handCardIds:TWI_076}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1NODECISION
