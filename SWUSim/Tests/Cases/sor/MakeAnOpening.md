# CanTargetFriendly
#// SOR_076 Make an Opening — "a unit" means ANY unit, friendly included (unlike Disarm).
#// Only a friendly unit in play (AT-AT, 9/9) → auto-target it: power 9−2=7, HP 9−2=7.
#// Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# DebuffsAndHeals
#// SOR_076 Make an Opening — Give a unit −2/−2 for this phase. Heal 2 damage from your base.
#// Single unit in play (enemy AT-AT, 9/9) → auto-target: power 9−2=7, HP 9−2=7.
#// P1 base starts at 3 damage → healed by 2 → 1.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7

---

# ShrinkDefeatsShieldedUnit
#// SOR_076 Make an Opening — the shrink is NOT damage: it lowers HP directly.
#// A 2/2 unit dropped to 0 HP is defeated as a state-based effect — and a Shield
#// token does NOT save it, because shields only prevent damage, not HP reduction.
#// Target Leia (SOR_189, 2/2) carrying a Shield → −2/−2 → 0 HP → defeated.
#// The shield token is set aside (not discarded); only the unit hits the discard.
#// Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_189:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
