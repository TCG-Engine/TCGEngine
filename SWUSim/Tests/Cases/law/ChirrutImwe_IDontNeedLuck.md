# AttackEndHealIfBase
#// LAW_046 Chirrut Îmwe (8/6, Saboteur) — When Attack Ends: if this unit dealt combat damage to a base,
#// you may heal 4 from another unit. Attacks the base; heal 4 from the damaged friendly SOR_046 (4 -> 0).

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# HealFromEnemyUnit
#// LAW_046 heals 4 from "another unit" — this can be an ENEMY unit. Chirrut attacks the base, then heals
#// 4 from the damaged enemy SOR_046 (4 -> 0).

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP2GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoHealWhenAttackingUnitNoBaseDamage
#// LAW_046 does not trigger if Chirrut deals no combat damage to a base. He attacks an enemy unit (no
#// Overwhelm), so the damaged friendly SOR_046 is not healed.

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SOR_046:1:4
WithP2GroundArena: IBH_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# NoHealWhenAnotherUnitDamagesBase
#// LAW_046 only cares about ITS OWN attack. Another friendly unit (SEC_080) attacks the base; Chirrut's
#// ability does not fire, so the damaged friendly SOR_046 is not healed.

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:DAMAGE:4

---

# BaseDamageViaOVERWHELM_StillCounts
#// LAW_046 Chirrut Îmwe — "if this unit dealt combat damage to a base" does not require him to have
#// ATTACKED the base. SEC_157 One Way Out grants +1/+0 and Overwhelm, so attacking SOR_095 Battlefield
#// Marine (3/3) with 9 power kills it and spills 6 excess onto the enemy base. That excess IS combat
#// damage dealt to a base, so the heal fires: SOR_232 AT-ST goes 5 damage -> 1.
#// The distinction matters because a naive implementation gates on the attack TARGET being a base rather
#// than on damage actually reaching one.

## GIVEN
CommonSetup: brw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [LAW_046:1:2 SOR_232:1:5]
WithP2GroundArena: [SOR_039:1:0 SOR_095:1:0]
WithP1Hand: SEC_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# TriggersEVENIfChirrutIsDefeatedDuringTheAttack
#// "When Attack Ends" fires even when the attacker DIED in that attack (CR 16.c — firing on death is the
#// default; surviving is a per-card opt-in, and LAW_046's text carries no "and survives" rider).
#// One Way Out puts Chirrut (8/6, 2 damage → 4 remaining) at 9 power with Overwhelm into SOR_039 AT-AT
#// Suppressor (8/8): he kills it, spills 1 onto the base, and takes 8 back — 2+8 on 6 HP, so he is
#// defeated. The heal must STILL resolve: AT-ST 5 damage -> 1.
#// P1GROUNDARENACOUNT:1 pins that he really did die, so the heal cannot be passing for the wrong reason.

## GIVEN
CommonSetup: brw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [LAW_046:1:2 SOR_232:1:5]
WithP2GroundArena: [SOR_039:1:0 SOR_095:1:0]
WithP1Hand: SEC_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:1
P2GROUNDARENACOUNT:1

---

# NoTriggerIfBaseDamageWasOnAPREVIOUSAttack
#// The condition is scoped to THIS attack, not "has ever dealt base damage". Chirrut attacks the base and
#// heals AT-ST (5 -> 1). SHD_182 Bravado then readies him IN THE SAME PHASE, and his second attack —
#// into SOR_095 Battlefield Marine, dealing no base damage — must NOT offer the heal again.
#// Both attacks are in the same phase deliberately: a phase boundary would let a per-PHASE implementation
#// pass this too, which is exactly the wrong condition.
#// AT-ST staying at 1 (not 0) is the assertion — a second heal would take it to 0.

## GIVEN
CommonSetup: brw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: [LAW_046:1:2 SOR_232:1:5]
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SHD_182

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# DirectOverwhelm_DefenderAlreadyDeadWhenCombatDamageLands
#// CR step 4.c — "If the defending unit is no longer in-play, no combat damage is dealt UNLESS the
#// attacker has Overwhelm" — with §9.11 / 7.f: ALL of the attacker's combat damage then becomes excess
#// and is dealt to the enemy base.
#// SHD_177 Vambrace Flamethrower's On Attack deals 3 to SOR_095 Battlefield Marine (3/3), killing it
#// BEFORE combat damage; SEC_157 One Way Out has already granted Overwhelm. So Chirrut's whole hit lands
#// on the base, which is combat damage to a base — and his heal fires (AT-ST 5 -> 1).
#// Distinct from BaseDamageViaOVERWHELM_StillCounts, where the defender was ALIVE and absorbed part of
#// the damage: there the excess is power-minus-HP, here there is no defender to subtract at all.
#// Chirrut is a deliberate choice of witness — his heal keys on base damage specifically, so it also
#// checks the attribution, not just that some damage landed somewhere.

## GIVEN
CommonSetup: brw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [LAW_046:1:2 SOR_232:1:5]
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:DAMAGE:1
