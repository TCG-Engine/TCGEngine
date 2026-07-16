# DealsOneToTwoEnemies
#// IBH_005 I'll Cover For You (Event, cost 3, Cunning) — Deal 1 damage to an enemy unit and 1 damage to
#//   another enemy unit. Two enemy 3/3 bodies each take 1 (survive). First pick is chosen; the second
#//   auto-resolves (only one "other" enemy remains).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION

---

# NoEnemies_Fizzles
#// IBH_005 I'll Cover For You — with no enemy units, the event fizzles cleanly (plays to discard, no
#//   decision, no crash).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Reprint039
#// IBH_039 I'll Cover For You (reprint of IBH_005) — same effect: deal 1 to an enemy unit and 1 to
#//   another. Confirms the duplicate CardID is wired.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_039
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION

---

# SingleEnemy_SecondFizzles
#// IBH_005 I'll Cover For You — with only ONE enemy unit, it takes 1 damage and the "another enemy unit"
#//   half fizzles cleanly (single mandatory target auto-resolves, no leftover decision).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
