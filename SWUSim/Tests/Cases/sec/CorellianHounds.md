# DamagedUnit_Deal2
#// SEC_254 Heroic ARC-170 (Ground, 4/3, Heroism, cost 4) — When Played: if you control a damaged unit,
#//   you may deal 2 to an enemy unit. A damaged friendly is in play → deal 2 to the enemy.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_254

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# NoDamagedUnit_NoDeal
#// SEC_254 Heroic ARC-170 — no damaged friendly unit → no damage offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_254

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# EntersExhausted_OppHasGround
#// SEC_170 — when the opponent DOES control a ground unit, SEC_170 enters play exhausted (the default).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_NoOppGround
#// SEC_170 Corellian Hounds (Ground, 5/5) — "If an opponent controls no ground units, this unit enters
#//   play ready." P2 has no ground units → SEC_170 enters ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
