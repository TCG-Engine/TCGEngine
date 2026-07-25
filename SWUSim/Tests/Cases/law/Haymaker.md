# ExpThenDealPower
#// LAW_168 Haymaker (Command event, cost 4) — "Give an Experience token to a friendly unit. That unit
#// deals damage equal to its power to an enemy unit in the same arena." SOR_095 (3/3) gets Exp -> 4/4,
#// then deals 4 to the lone enemy ground unit SOR_046 (3/7, survives at DAMAGE:4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_168

## WHEN
#// One friendly unit -> Exp target auto-resolves; one enemy ground unit -> deal target auto-resolves.
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# ExpThenDealPower_Space
#// LAW_168 Haymaker — the Experience-and-damage works in the space arena too. A lone friendly space unit
#// SOR_237 (2/3) gets Exp -> 3 power, then deals 3 to the lone enemy space unit JTL_040 (6/6, survives at
#// DAMAGE:3). Both the Exp target and the damage target auto-resolve (one each).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_040:1:0
WithP1Hand: LAW_168

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_040
P2SPACEARENAUNIT:0:DAMAGE:3

---

# NoFriendlyUnit_NoEffect
#// LAW_168 Haymaker — with NO friendly unit to receive the Experience token, the event has no legal effect.
#// It is still played (the enemy AT-AT is untouched) and goes to the discard pile.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_168

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ExpGiven_NoDamageTarget
#// LAW_168 Haymaker — the Experience is still granted even when there is no enemy unit in the same arena to
#// receive damage. A lone friendly space unit SOR_237 gets Exp (-> 3 power); the only enemy unit is on the
#// ground, so the damage clause finds no legal target and is skipped (enemy takes 0).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_168

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
