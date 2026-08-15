# Action_HPAsDamage
#// LOF_206 — Action [Exhaust]: Attack with a friendly Droid; for this attack it deals damage equal to
#// its remaining HP instead of its power. The Droid SOR_188 (1/3) deals 3 (its HP) to the enemy base
#// instead of 1 (its power). LOF_206 exhausts to pay the action; SOR_188 exhausts from attacking.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_206:1:0
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# Action_HPAsDamage_IncorporatesHPModifiers
#// LOF_206 — the "deals damage equal to its remaining HP" reads the LIVE remaining HP including modifiers.
#// The Droid Chopper (SOR_188, 1/3) carries Resilient (SOR_069, +0/+3) → 6 HP. Babu's action makes it attack
#// the enemy base dealing 6 (its boosted remaining HP) instead of its 1 power. Intended: "incorporates HP
#// modifiers into the damage calculation".

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_206:1:0
WithP1GroundArena: SOR_188:1:0
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# Action_HPAsDamage_OnAttackSelfDamageReducesDealt
#// LOF_206 — because the damage equals REMAINING HP at the moment it is dealt, an on-attack self-damage
#// lowers it. IG-11 (SHD_170, 6/5) already has 1 damage (4 remaining). Babu makes it attack the enemy base;
#// its On Attack "deal 3 to a damaged ground unit" hits itself (now 4 damage, 1 remaining), so the base takes
#// only 1. Intended: "deals the right amount of damage if the Droid unit takes damage in the on-attack step".
#// (Two answers since 2026-08-14: the first picks the attacking Droid — LOF_206 reads "you MAY attack",
#// so the offer now always prompts instead of auto-resolving a lone Droid — and the second is IG-11's
#// On Attack self-target.)

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_206:1:0
WithP1GroundArena: SHD_170:1:1

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# Action_NoFriendlyDroid_NoEffect
#// LOF_206 — the ability is "you may attack with a friendly Droid unit"; Babu Frik himself is Underworld, not
#// a Droid. With no other friendly Droid unit there is no legal attacker, so the action resolves with no
#// effect but still exhausts Babu (paying the Exhaust cost) and deals no base damage. Intended: "has no effect if
#// there are no friendly Droid units".

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_206:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
