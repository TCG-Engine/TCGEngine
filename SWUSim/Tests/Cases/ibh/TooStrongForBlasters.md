# HealsTwoFromAUnit
#// IBH_066 Too Strong for Blasters (Event, cost 1, Vigilance) — Heal 2 damage from a unit. A friendly
#//   3/7 with 3 damage heals 2 → 1 left (proves the amount is 2, not heal-all).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# Reprint091
#// IBH_091 Too Strong for Blasters (reprint of IBH_066) — heal 2 from a unit. Confirms the duplicate.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_091
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# OneDamage_AllDamageRemoved
#// IBH_066 Too Strong for Blasters — the 2-point heal CLAMPS at 0. A unit carrying only 1 damage ends at
#// exactly 0, never below.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: SOR_046:1:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# UndamagedUnitIsInTheOfferPool
#// IBH_066 Too Strong for Blasters — the target is "a unit", NOT "a damaged unit". With one undamaged
#// and one damaged friendly on board, BOTH are offered. Asserted as the offer with the decision left
#// pending, because there is no pre-condition EXPECT block — answering first would resolve the decision
#// and leave nothing to assert.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# UndamagedUnitChosen_HealsNothing
#// IBH_066 Too Strong for Blasters — choosing the UNDAMAGED unit is legal and simply does nothing.
#// The damaged unit keeping its full 3 is what discriminates: if the undamaged unit had NOT been a legal
#// target, the choice would have auto-resolved onto the damaged one and healed it to 1.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P1NODECISION
