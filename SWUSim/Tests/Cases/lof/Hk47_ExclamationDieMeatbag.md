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

---

# CapturedHk_CaptorDefeatedByEffect_NoTrigger
#// LOF_130 HK-47 — a CAPTURED HK-47 is not in play, so it observes nothing. P2 plays Take Captive
#// (SHD_131) so their 3/3 captures HK-47; P1 then Vanquishes (SOR_078) the captor. The captor is an enemy
#// unit being defeated, which WOULD fire HK-47 if he were in play → P2's base must stay at 0. (He returns
#// to play when his guard leaves, but only AFTER the defeat he could have observed.)

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SHD_131
WithP1Hand: SOR_078
WithP1Resources: 12
WithP2Resources: 12

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_130

---

# CapturedHk_CaptorDefeatedByDamage_NoTrigger
#// Same as above but the captor dies to COMBAT DAMAGE rather than a defeat effect — the two defeat paths
#// are separate code, so both need the negative. P2's 3/3 captures HK-47 via Take Captive (SHD_131); P1's
#// AT-AT Suppressor (SOR_039, 8/8) then attacks and kills the captor. HK-47 was captured at the moment of
#// that defeat → P2's base stays at 0, and HK-47 returns to play once his guard is gone.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_130:1:0
WithP1GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SHD_131
WithP1Resources: 12
WithP2Resources: 12

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2

---

# HkDefeatedByAMASSDefeatEffect_StillFiresPerEnemy
#// LOF_130 HK-47 — "When an enemy unit is defeated: deal 1 damage to its controller's base." HK-47 was
#// in play when those enemies were defeated, so it must still fire once per enemy even though it died in
#// the same event. SOR_043 Superlaser Blast ("Defeat all units") kills HK-47 and BOTH enemies at once.
#// ⚠ RED. Root cause: SOR_043 loops SWUDefeatUnit PER UNIT, so each defeat is its own
#// SWUCollectLeavePlayReactions call with a single-element $leftCards batch. HK-47 is defeated first, so
#// by the time each enemy's batch is collected HK is neither ACTIVE nor present in that batch — and the
#// "$leftCards re-count" that fixes the combat case only looks inside the CURRENT batch.
#// ⚠ NOTE — this is WIDER than the recorded description ("HK-dead + MULTIPLE enemies"): the companion
#// section below shows it also fires 0x with a SINGLE enemy, so enemy count is irrelevant. What matters
#// is the DEFEAT SOURCE — a per-unit mass-defeat loop rather than one simultaneous batch.
#// Contrast the two sections that already pass: TriggersEvenWhenHkDefeatedSimultaneously (HK dies in
#// COMBAT, one batch) and MultipleSimultaneousDefeats_OnePerUnit (HK SURVIVES a mass effect).

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# HkDefeatedByAMASSDefeatEffect_FiresEvenForASingleEnemy
#// LOF_130 HK-47 — the same defect with ONE enemy, which pins that the enemy COUNT is not the variable.
#// ⚠ RED alongside the section above.

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:1
