# FirstLight_GrantsGrit
#// SHD_036 First Light — "Grit. Each other friendly non-leader unit gains Grit." With First Light in the
#// space arena, the friendly SOR_046 (3/7, 2 damage) gains Grit → +2 power (5), and shows the keyword.
#// COVERAGE: offer=N/A for the constant ability (a static aura has no target pick); the Smuggle
#//           additional cost's friendly-unit offer is NOT asserted — see the deferral note below ·
#//           boundary=GritGrant_ZeroDamage_NoBonus + GritGrant_OneDamage_PlusOne (the 0/1 pair) and
#//           GritScalesUp_MidCombat / GritScalesDown_WhenHealed (the dynamic pair) ·
#//           control=GritGrant_ExcludesLeaderUnitAndEnemyUnits (an enemy-controlled copy of the same
#//           card gets nothing) + GritGrant_EndsWhenFirstLightLeavesPlay (the aura dies with its source) ·
#//           reqboundary=N/A (no stored per-unit marker — Grit is recomputed from live damage every read,
#//           so there is no state to survive a serialization round-trip) ·
#//           decline=N/A (nothing on this card is a "you may").
#// DEFERRED: the Smuggle half of this card (Smuggle [7 resources, Vigilance Villainy, deal 4 damage to a
#//           friendly unit]) has no scenarios here — SWUSim charges 4 resources instead of 7 and never
#//           asks for / applies the 4-damage additional cost, so every Smuggle scenario would encode the
#//           wrong numbers. Re-add once the additional cost exists.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5

---

# GritGrant_ExcludesLeaderUnitAndEnemyUnits
#// SHD_036 — the grant is "each OTHER friendly NON-LEADER unit", so it has three exclusions and this
#// section asserts all of them at once. P1's deployed leader (SOR_002 Iden Versio, 4/4, 2 damage) is a
#// LEADER unit → no Grit, power stays 4. P2's SOR_046 (3/7, 2 damage) is an ENEMY unit → no Grit, power
#// stays 3. P1's own SOR_046 (same card, same damage, friendly) DOES get it → power 5. The two SOR_046s
#// being 2 apart in power on identical damage is the proof the aura is controller-scoped.
#// A deployed leader seats AFTER the regular ground-arena lines, so it lands at P1 ground index 1.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_002:1:1:0:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P2GROUNDARENAUNIT:0:POWER:3

---

# GritGrant_ReachesBothArenas_AndFirstLightsOwnGrit
#// SHD_036 is a SPACE unit but the grant is arena-agnostic — "each other friendly non-leader unit".
#// A friendly SPACE unit (SOR_237 Alliance X-Wing 2/3, 1 damage) gains it → power 3, and the friendly
#// GROUND unit (SOR_046 3/7, 3 damage) gains it → power 6. First Light's OWN Grit is printed, not
#// granted (the grant says "each OTHER"), so with 3 damage on it its power is 4 + 3 = 7.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:3
WithP1SpaceArena: SOR_237:1:1
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_036
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1SPACEARENAUNIT:0:POWER:7
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:HASKEYWORD:Grit
P1SPACEARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# GritGrant_ZeroDamage_NoBonus
#// SHD_036 boundary pair, low side. Grit is "+1/+0 for each damage on this unit", so an UNDAMAGED
#// granted unit gets nothing: SOR_046 stays at its printed 3 power while still showing the keyword.
#// (Paired with GritGrant_OneDamage_PlusOne.)

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# GritGrant_OneDamage_PlusOne
#// SHD_036 boundary pair, high side — one single point of damage is enough to move the power by exactly
#// one (3 → 4). HP is untouched: Grit is +1/+0, never +1/+1.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7

---

# GritScalesUp_MidCombat
#// SHD_036 — the granted Grit is recomputed from live damage, not snapshotted at grant time. The
#// friendly SOR_046 (3/7) starts on 2 damage → power 5 with the grant, and attacks the enemy SOR_046
#// (3/7, undamaged, no Grit → power 3). The defender takes 5 (the Grit-boosted number), the attacker
#// takes 3 back → 5 damage total, so AFTER combat the attacker's power has climbed to 3 + 5 = 8.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:8
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:POWER:3

---

# GritScalesDown_WhenHealed
#// SHD_036 — the other half of the dynamic pair: healing the damage takes the Grit bonus back off.
#// The friendly SOR_046 sits on 3 damage → power 6. P1 plays SOR_074 Repair (1 cost, Vigilance —
#// covered by the Vigilance base) targeting it and heals 3 → 0 damage, and the power drops back to the
#// printed 3 while the keyword is still granted.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2;myhandCardIds:SOR_074}
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3

---

# GritGrant_EndsWhenFirstLightLeavesPlay
#// SHD_036 — the grant is a constant ability that only works while First Light is IN PLAY. P2 bounces
#// it back to P1's hand with SOR_222 Waylay ("Return a non-leader unit to its owner's hand", 3 cost,
#// Cunning — covered by P2's Cunning base/leader), and the friendly SOR_046 (3/7, 2 damage) immediately
#// loses both the keyword and the +2, dropping from 5 back to its printed 3.
#// Intended: an aura from a card that has left play stops applying at once, with no lingering buff.

## GIVEN
CommonSetup: bbk/yyk/{theirResources:3;theirhandCardIds:SOR_222}
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3

---

# Smuggle_DealsFourDamageToAFriendlyUnitAsACost
#// SHD_036 — Smuggle [7 resources, Vigilance Villainy, deal 4 damage to a friendly unit]. Both halves of
#// the cost are charged: all 7 ready resources go (the smuggled card pays for itself as one of them), and
#// a friendly unit takes 4. The damage is a COST, so it is chosen and paid as part of playing the card —
#// First Light itself is NOT a legal target, since it is not yet a friendly unit when the cost is paid.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 6:SOR_095:1,1:SHD_036:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_036
P1RESCOUNT:7
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Smuggle_NoFriendlyUnits_CannotBePlayed
#// SHD_036 — the "deal 4 damage to a friendly unit" clause is a COST, not an effect, so it must be
#// PAYABLE for the play to be legal. With no friendly unit anywhere, the Smuggle play is refused
#// outright: First Light stays in the resource zone and not a single resource is spent.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0
WithP1Resources: 6:SOR_095:1,1:SHD_036:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1SPACEARENACOUNT:0
P1RESAVAILABLE:7

---

# Smuggle_ShieldPreventsTheCostDamage_CostStillCountsAsPaid
#// SHD_036 — paying a cost is not the same as the damage landing. The chosen friendly unit carries a
#// Shield token, which absorbs the whole instance (CR 8.31), so it ends on 0 damage and loses the shield
#// — and the Smuggle play still goes through in full: First Light is in space and all 7 resources are
#// spent. Boundary partner of Smuggle_DealsFourDamageToAFriendlyUnitAsACost (no shield -> 4 damage).

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 6:SOR_095:1,1:SHD_036:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:6
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_036
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
