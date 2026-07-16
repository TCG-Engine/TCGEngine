# DebuffsEnemyNonLeaderUnits
#// SHD_037 Supreme Leader Snoke — passive field-presence debuff:
#//   "Each enemy non-leader unit gets –2/–2."
#// P1 plays Snoke. P2's AT-AT (9/9) is an enemy unit → 7/7. P1's own Imperial
#// Dark Trooper (3/3) is friendly to Snoke's controller → unaffected, stays 3/3.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:SHD_037
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7

---

# DefeatsLowHpEnemyUnit
#// SHD_037 Supreme Leader Snoke — the passive –2/–2 lowers HP directly (not damage),
#// so an enemy non-leader unit whose HP drops to 0 is defeated as a state-based effect.
#// P1 plays Snoke while P2 controls Leia (SOR_189, 2/2) → 2/2 becomes 0/0 → defeated.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP2GroundArena: SOR_189:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_037
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# EnemyDebuff
#// SHD_037 Supreme Leader Snoke (8-cost ground) — "Each enemy non-leader unit gets -2/-2." Guard: the enemy
#// SOR_046 (3/7) is reduced to 1/5 while Snoke is in play.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_037:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
