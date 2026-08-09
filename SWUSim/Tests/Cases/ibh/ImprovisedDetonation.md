# AttackPlusTwo
#// IBH_021 Improvised Detonation (Event, cost 2, Cunning) — Attack with a unit; it gets +2/+0 for this
#//   attack. P1's only ready unit (3 power) attacks the enemy base (no enemy units → auto-targets base)
#//   for 3+2 = 5. (A missing +2 would show 3.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_021
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# NoReadyUnit_Fizzles
#// IBH_021 Improvised Detonation — with no READY friendly unit (only an exhausted one), there is no unit
#//   to attack with and the event fizzles cleanly.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_021
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Reprint030
#// IBH_030 Improvised Detonation (reprint of IBH_021) — attack with a unit, +2/+0. Confirms the duplicate.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_030
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1NODECISION

---

# PowerBoostIsForTHATATTACKOnly_ExpiresImmediately
#// IBH_021 Improvised Detonation — "+2/+0 for this attack" is attack-scoped, not phase-scoped. SOR_046
#// (3 power) attacks the enemy base for 3+2 = 5, and the moment the attack ends its power is back to the
#// printed 3. Asserting the base damage alone would pass even if the buff were permanent; reading POWER
#// after the attack is what proves the duration ENDS.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_021
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION
