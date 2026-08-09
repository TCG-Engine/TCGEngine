# HealsFiveFromAUnit
#// IBH_013 Recovery (Event, cost 3, Heroism) — Heal 5 damage from a unit. A friendly 3/7 with 6 damage
#//   heals exactly 5 → 1 damage left (a heal-all bug would show 0; proves the amount is 5).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_013
WithP1GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# LessThanFiveDamage_AllDamageRemoved
#// IBH_013 Recovery — the heal CLAMPS at 0; it never overshoots into negative damage. A friendly 3/7
#// carrying only 2 damage (less than the 5 healed) ends at exactly 0, not below.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_013
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# CanHealAnENEMYUnit_OfferSpansBothSides
#// IBH_013 Recovery — "heal 5 damage from a unit" has no "friendly" qualifier, so an ENEMY unit is a
#// legal target. The offer is asserted directly (both a friendly and an enemy damaged unit are
#// selectable), then the ENEMY 3/7 at 6 damage is healed to 1 while the friendly is left untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_013
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
