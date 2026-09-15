# Defending_CountersForThree_TheBonusEndsWithTheAttack
#// HMW_083 Batcher, Loyal Hound — Unit (Ground) 2/3, cost 2, [Vigilance], Creature, unique.
#// "Restore 1 (When this unit attacks, heal 1 damage from your base.)
#//  This unit gets +1/+0 while defending."
#//
#// COVERAGE: offer=N/A (structural — nothing on the card selects anything: a keyword and a self-passive)
#//           decline=N/A (structural — no optional clause; Restore and the defending bonus are both mandatory)
#//           boundary=this section (+1 exactly: a 4-HP attacker takes 3, where +0 leaves 2 and +2 kills it)
#//                    + Defending_PowerOnly_BothTrade (+1/+0, not +1/+1: 3 damage still kills her)
#//           control=N/A (structural — "this unit" names no owner- or controller-scoped zone; the bonus is
#//                   read off the defending object, whoever controls it)
#//           reqboundary=N/A (structural — both clauses are computed inside ONE combat resolution from the
#//                   unit's identity; nothing is written before a decision and read after it)
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// Restore 1 is registry-wired (generic coverage under keywords/); the rider is the work. It follows the
#// three released "+N/+0 while defending" cards (LOF_049, SHD_042, ASH_073): counter-damage only.
#// HMW is a preview set, so there is no ruling on file — that reading is the precedent, flagged here.
#//
#// P2's SOR_063 Cloud City Wing Guard (2/4) attacks her. She counters for 2 + 1 = 3 and survives on 1.
#// Afterwards she reads her printed 2 again — the bonus lasts only while she is defending. Restore does
#// not fire either: it is "when this unit ATTACKS", and she did not.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
WithActivePlayer: 2
WithP1GroundArena: HMW_083:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_083
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:2
P1BASEDMG:5

---

# Defending_PowerOnly_BothTrade
#// The bonus is +1/+0 — POWER only. SEC_080 Imperial Dark Trooper (3/3) attacks: she deals 3 back and
#// kills it, and its 3 kills her (3 HP). A +1/+1 misreading would leave her alive on 4 HP; no bonus at
#// all would leave the Trooper alive on 1.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_083:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:HMW_083

---

# Attacking_DealsItsPrintedTwo_AndRestoreHealsOne
#// THE NEGATIVE for the rider: while ATTACKING she is a plain 2. SOR_046 Consular Security Force (3/7)
#// takes 2, not 3. Restore 1 fires on a unit attack as well as a base attack: base 5 -> 4.
#// SOR_046's 3 kills her.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: HMW_083:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0
P1BASEDMG:4

---

# AttackingTheBase_TwoToTheBase_RestoreHealsOne
#// The base-attack path: 2 to the enemy base (no bonus while attacking), 1 healed from her own.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: HMW_083:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AnotherFriendlyUnitDefending_GetsNoBonus
#// "THIS unit" — the bonus is hers alone. With Batcher in play, SOR_063 attacks her friend SOR_095
#// Battlefield Marine (3/3) instead: it counters for its own 3, so SOR_063 (4 HP) survives on 3. Were the
#// bonus an aura it would take 4 and die.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_083:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# LostAllAbilities_NoDefendingBonus
#// The rider is an ABILITY. Under SOR_138 Force Lightning ("it loses all abilities for this phase") she
#// counters for her printed 2, so SOR_063 ends on 2 damage rather than 3.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 2
WithP1GroundArena: HMW_083:1:0:SOR_138
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# DarthMaulAttacksTwoUnits_SheStillCountersForThree
#// The OTHER combat resolver. TWI_135 Darth Maul ("this unit can attack 2 units instead of 1") resolves
#// through a separate two-defender path, which read each defender's printed power and so skipped every
#// "while defending" bonus. Maul (5/6) attacks Batcher and a TWI_T01 Battle Droid token (1/1): the counter
#// is (2 + 1) + 1 = 4, not 3. Maul survives on 4 so the number stays readable; both defenders die.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [HMW_083:1:0 TWI_T01:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0
