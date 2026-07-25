# EnemyDefeated_BaseDamage
#// LOF_130 HK-47 (2/4) — "When an enemy unit is defeated: deal 1 damage to its controller's base." HK-47
#// attacks and defeats the enemy 3/1; on its defeat, HK-47 deals 1 to P2's base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# TriggersEvenWhenHkDefeatedSimultaneously
#// LOF_130 HK-47 (2/4) attacks B1 Attack Platform (5/2). HK-47 kills the platform (2 dmg) and dies to its
#// 5 power — SIMULTANEOUS. HK-47's "when an enemy unit is defeated" must STILL fire (it was in play when
#// the enemy was defeated), dealing 1 to P2's base. Regression: HK-47 was already removed at the trigger
#// collection point, so the active-count was 0 → no damage; the fix also counts HK-47s defeated this batch.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: TWI_133:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# DefeatedByCardEffect_BaseDamage
#// LOF_130 HK-47 (2/4) — the trigger also fires when the enemy is defeated by a CARD EFFECT (not just
#// combat). P1 plays Takedown (SOR_077) on the enemy Death Star Stormtrooper (3/1) → defeated → HK deals
#// 1 to P2's base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_077
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# MultipleSimultaneousDefeats_OnePerUnit
#// LOF_130 HK-47 (2/4) fires ONCE PER enemy defeated when multiple die simultaneously. P1 plays Blood Sport
#// (TWI_173, "deal 2 damage to each ground unit"): both enemies (Death Star Stormtrooper 3/1, Freelance
#// Assassin 4/2) are defeated at once, HK (4 HP) survives the 2 → HK deals 1 per enemy = 2 to P2's base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: TWI_212:1:0
WithP1Hand: TWI_173
WithP1Resources: 12

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# FriendlyStolenUnit_NoTrigger
#// LOF_130 HK-47 — the trigger is "when an ENEMY unit is defeated". P1 plays Traitorous (SOR_122) to steal
#// the enemy Death Star Stormtrooper (cost 1). It is now a FRIENDLY unit. P2 then Vanquishes (SOR_078) that
#// stolen unit → it is friendly to HK, so HK does NOT fire → P2's base stays at 0.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_122
WithP2Hand: SOR_078
WithP1Resources: 12
WithP2Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:0

---

# EnemyStolenUnit_Damage
#// LOF_130 HK-47 — an owned unit that has been STOLEN by the opponent is an enemy unit. P2 plays Change of
#// Heart (SOR_224) to take control of P1's Scimitar (LOF_233). P1 then Takedowns (SOR_077) the now-enemy
#// Scimitar → HK fires against its current controller (P2) → P2's base takes 1.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_130:1:0
WithP1SpaceArena: LOF_233:1:0
WithP1Hand: SOR_077
WithP2Hand: SOR_224
WithP1Resources: 12
WithP2Resources: 12

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2BASEDMG:1

---

# CloseShieldGate_PreventsHkDamage
#// LOF_130 HK-47's ping is normal damage, so Close the Shield Gate (JTL_074) prevents it. P2 protects its
#// own base, then P1 Takedowns (SOR_077) the enemy Battlefield Marine (SOR_095) → HK's 1 damage to P2's
#// base is prevented → P2's base stays 0.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_077
WithP2Hand: JTL_074
WithP1Resources: 12
WithP2Resources: 12

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
