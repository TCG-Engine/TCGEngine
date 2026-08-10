# AttackBaseSelfDefeat
#// LAW_205 Flash the Vents (Aggression event, cost 1) — "Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, if that unit damaged a base, defeat that
#// unit." SEC_080 (power 3) attacks the base for 3+2 = 5, then self-defeats.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# OverwhelmSpillDefeatsAttacker
#// LAW_205 attacks an enemy unit; the granted Overwhelm spills the excess onto the base, so the attacker
#// self-defeats. SEC_080 (3/3, +2 = power 5) attacks IBH_063 (1/3): 3 defeats it, 2 overwhelm to the base;
#// SEC_080 survives combat but is defeated for having damaged a base.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: IBH_063:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# SurvivesNoBaseDamageNotDefeated
#// LAW_205 attacks a high-HP unit with no overwhelm spill; no base damage means the attacker is NOT defeated.
#// SEC_080 (+2 = power 5) attacks LOF_112 (2/6): 5 damage, no spill; LOF_112 deals 2 back, SEC_080 survives.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_112:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:LOF_112
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# NoReadyUnitToAttack
#// LAW_205 played with no unit that can attack (only an exhausted unit) does nothing but is still spent.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2BASEDMG:0
P1DISCARDCOUNT:1

---

# AbilityDamageToEnemyBaseDefeatsAttacker
#// LAW_205 "if that unit damaged a base" is not limited to COMBAT damage — the attacker's own ability
#// damage counts too. SOR_014 Sabine Wren (deployed leader, 2/5) attacks SEC_081 Major Partagaz (0/6);
#// with +2/+0 she deals 4, which Partagaz survives, so there is NO overwhelm spill and no combat damage
#// to a base. Her On Attack ("deal 1 damage to each enemy base") is what damages P2's base — and that is
#// enough: Sabine is defeated and returns to the leader zone (ground arena empties).

## GIVEN
CommonSetup: rrw/bgw/{myLeader:SOR_014:1:1:1; myResources:3}
P1OnlyActions: true
WithP2GroundArena: SEC_081:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# AbilityDamageToYourOWNBaseDefeatsAttacker
#// LAW_205 says "damaged a base", unqualified — EITHER base. SOR_142 Sabine Wren, Explosives Artist
#// (2/3, "On Attack: You may deal 1 damage to the defender or to a base") attacks SEC_081 Major Partagaz
#// (0/6) and aims her 1 damage at HER OWN base. She deals 4 combat damage, which Partagaz survives with
#// no spill, so the only base damaged all attack is P1's — and she is still defeated for it.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SEC_081:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# AbilityDamageAimedAtTheDefenderInsteadLeavesTheAttackerAlive
#// The negative that makes the section above load-bearing: identical fixture, but SOR_142 aims her On
#// Attack damage at the DEFENDER rather than a base. Partagaz takes 4 combat + 1 ability = 5 (and still
#// survives on 6 HP), no base is damaged by anyone, and Sabine is NOT defeated.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SEC_081:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# AttackEndAbilityDamageDefeatsAttacker
#// The base damage may arrive as late as the attack-end window and still count. LAW_056 Cassian Andor
#// (4/4, "When a friendly unit's attack ends: if the defending unit was defeated, deal 2 damage to a
#// base") attacks SEC_081 Major Partagaz (0/6) for 4+2 = 6 — exactly lethal, so no overwhelm spill and no
#// combat damage to a base. His OWN attack-end ability then deals 2 to P2's base, and because Cassian is
#// the source of that damage he is defeated by his own trigger.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_056:1:0
WithP2GroundArena: SEC_081:1:0
WithP2SpaceArena: LOF_144:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:0
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# AnotherUnitDamagingABaseDoesNotDefeatTheAttacker
#// The condition is scoped to THAT unit, not to "a base was damaged this attack". SEC_006 Colonel Yularen
#// (deployed leader, 4/6) is the Flash the Vents attacker: 4+2 = 6 exactly defeats SEC_081 Major Partagaz
#// (0/6) with no spill, so Yularen himself damages no base. His deployed attack-end ability then attacks
#// with SOR_095 Battlefield Marine, which hits P2's base for 3 — a DIFFERENT unit damaging a base.
#// Yularen is not defeated, and neither is the Marine (it never had the marker).

## GIVEN
CommonSetup: rrk/bgw/{myLeader:SEC_006:1:1:1; myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_081:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:SEC_006
P2GROUNDARENACOUNT:0
P2BASEDMG:3

---

# NoAttackerAfterABaseWasAlreadyDamagedThisPhase
#// Regression guard for the no-legal-attacker path when the "a unit damaged a base this phase" state is
#// already populated: P2's SOR_095 attacks P1's base for 3, then P1 plays Flash the Vents holding only an
#// EXHAUSTED unit. The event finds no attacker, so nothing is attacked and nothing is defeated — the
#// stale base-damage state must not make the self-defeat clause fire on a unit that never attacked.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_205

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1BASEDMG:3
P2BASEDMG:0
P1DISCARDCOUNT:1
