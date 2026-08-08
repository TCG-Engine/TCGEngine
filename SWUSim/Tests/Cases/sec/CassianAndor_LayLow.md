# PreventsEnemyAbilityDamage
#// SEC_042 (Ground, 2/2) — If an enemy card ability would deal damage to this unit, prevent 2. SEC_042
#//   is on P2's side; P1 plays SEC_152 (When Played: deal 2 to a ready unit) targeting it → the 2 is
#//   prevented down to 0 damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SEC_042:1:0
WithP1Hand: SEC_152

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WhileDefending_AttackerMinus2
#// SEC_042 (Ground, 2/2) — While defending, the attacker gets -2/-0. P2's SOR_046 (3/3) attacks
#//   SEC_042; attacker power 3-2 = 1 → SEC_042 takes 1 (survives, 2 HP). SEC_042 deals 2 back → SOR_046
#//   takes 2 (survives, 3 HP).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NotDebuff_WhenAttackingOtherFriendly
#// SEC_042 Cassian Andor — the -2/-0 applies only while CASSIAN is the defender. Here P1's SOR_095 Battlefield
#//   Marine (3/3) attacks P2's SHD_218 Resourceful Pursuers (5/6), not Cassian → the attacker keeps full power:
#//   RP takes 3, and RP's 5 counter defeats the Marine.

## GIVEN
CommonSetup: rrk/bbw
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_042:1:0
WithP2GroundArena: SHD_218:1:0

## WHEN
- P1>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# PreventsOnly2_OpenFireStillKills
#// SEC_042 Cassian Andor — the prevention caps at 2, it is not full immunity. P1 plays SOR_172 Open Fire (deal
#//   4) on Cassian → 4-2 = 2 damage on a 2-HP unit → Cassian is defeated.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SEC_042:1:0
WithP1Hand: SOR_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION

---

# NotPreventFriendlyAbilityDamage
#// SEC_042 Cassian Andor — the prevention is only for ENEMY card abilities. Cassian's own controller (P2)
#//   plays SHD_178 Daring Raid (deal 2) on Cassian → not prevented → 2 damage → Cassian (2 HP) is defeated.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2GroundArena: SEC_042:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# NotPreventIndirectDamage
#// SEC_042 Cassian Andor — indirect damage is unpreventable, so the "prevent 2 from enemy card abilities" does
#//   not apply. P1's JTL_234 Torpedo Barrage deals 5 indirect to P2, who assigns 2 to Cassian → Cassian (2 HP)
#//   is defeated.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_234
WithP2GroundArena: SEC_042:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2,myBase-0:3

## EXPECT
P2GROUNDARENACOUNT:0

---

# PreventionSavesAShieldFromBeingDefeated
#// SEC_042 — "if an enemy card ability would deal damage to this unit, prevent 2." Prevention applies
#// BEFORE a Shield would be spent, so a 2-damage ability against a shielded SEC_042 is reduced to 0 and
#// the Shield is NOT consumed. P2's SEC_042 carries a Shield token; P1 plays SEC_152 (When Played: deal 2
#// to a ready unit) at it — 0 damage lands and the Shield is still attached.
## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_042:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: SEC_152
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION
