# Overwhelm: excess damage spills to base
# Wampa (4/5, Overwhelm) attacks Battlefield Marine (3/3).
# Wampa's 4 power kills Marine (3 HP) and 4-3=1 excess goes to P2 base.
# Wampa takes 3 damage and survives (5 HP).

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0   # Wampa 4/5
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
P2BASEDMG:1

---

# Overwhelm_DefenderSURVIVES_NoBaseDamage
#// CR 7.c / attack-step 4.d — "If the defending unit would not be defeated by the attacker's combat
#// damage … there is no excess damage and no damage is dealt to the defending player's base."
#// The load-bearing NEGATIVE of the section above: Wampa (4 power, Overwhelm) attacks SOR_046 Consular
#// Security Force (3/7). 4 damage does not defeat it, so there is no excess and the base takes NOTHING —
#// not "4 minus 7 clamped to 0" by accident, but nothing because the defender lived.
#// Without this, an implementation computing max(0, power - remainingHP) passes the positive test and is
#// still right only by coincidence.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Overwhelm_ShieldPrevents_NoBaseDamage
#// CR 7.e — "If a Shield token on a defending unit prevents combat damage from an attacker with
#// Overwhelm, no damage is dealt to the enemy base."
#// Wampa (4 power, Overwhelm) attacks a SHIELDED SOR_207 Crafty Smuggler (2/2). The shield absorbs the
#// whole hit, so the Smuggler is not defeated — and crucially the base takes 0, NOT the 2 excess that a
#// naive "power minus printed HP" calculation would spill. The shield is consumed and Wampa still takes
#// the Smuggler's 2 counter-damage.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Overwhelm_SpillIsCombatDamageToABase_ButNOTAnAttackOnIt
#// CR 7.d / 7.f — Overwhelm excess "is considered to have dealt combat damage to the base, but it is NOT
#// considered to have attacked that base." Two different conditions that are easy to conflate in code.
#// ASH_054 Pointless to Resist gives the attached unit -3/-0 "while attacking a base". Wampa attacks a
#// UNIT (SOR_095 Battlefield Marine 3/3) and spills onto the base — the debuff must NOT apply, so Wampa
#// swings at its full 4: the Marine dies and 1 excess reaches the base. If the spill were treated as
#// attacking the base, Wampa would be at 1 power and the Marine would survive on 1 damage.
#// The other half of this distinction — an ability keyed on "dealt combat damage to a base" DOES fire off
#// Overwhelm spill — is covered by law/ChirrutImwe_IDontNeedLuck.md::BaseDamageViaOVERWHELM_StillCounts.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_054
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Control_ASH054DoesApplyWhenGenuinelyAttackingTheBase
#// The control that stops the section above from passing vacuously. Same Wampa + ASH_054, but attacking
#// the BASE directly: now "while attacking a base" genuinely holds, so Wampa is 4-3 = 1 power and the
#// base takes 1 rather than 4. Without this, an UNIMPLEMENTED or broken ASH_054 would make the
#// not-a-base-attack section pass for entirely the wrong reason.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_054

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1

---

# Overwhelm_DefenderLeavesPlayBeforeDamage_ALLDamageToBase
#// CR step 4.c — "If the defending unit is no longer in-play, no combat damage is dealt UNLESS the
#// attacker has Overwhelm." CR §9.11 / 7.f — in that case ALL of the attacker's combat damage is treated
#// as excess and dealt to the enemy base.
#// Wampa (4/5, native Overwhelm) carries SHD_177 Vambrace Flamethrower (+1/+1 -> 5 power, and an On
#// Attack that deals 3 divided among enemy ground units). It attacks SOR_095 Battlefield Marine (3/3);
#// the Flamethrower kills the Marine during the On Attack step, so by the damage step there is no
#// defender at all — and the full 5 goes to the base, NOT zero.
#// ⚠ This is the exact case that used to fizzle to 0 damage: the whole attack silently evaporated.
#// The counterpart is Overwhelm_DefenderSURVIVES_NoBaseDamage — defender alive but not defeated is the
#// one case that yields NO base damage; defender GONE yields ALL of it.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Overwhelm_MultipleInstancesDoNOTStack
#// CR 7.b — "Multiple instances of Overwhelm do not stack."
#// Wampa already has Overwhelm natively; SEC_157 One Way Out grants it a SECOND instance (plus +1/+0).
#// Attacking SOR_095 Battlefield Marine (3/3) with 5 power, the excess is computed ONCE: 5 - 3 = 2 to the
#// base. A stacking implementation — spilling per instance — would send 4.
#// Wampa takes no counter-damage here because One Way Out also makes it deal damage first, defeating the
#// Marine before it can swing back.
#// (Wampa is P1's only unit, so One Way Out's attacker choice auto-resolves — only the TARGET is answered.)

## GIVEN
CommonSetup: grw/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
