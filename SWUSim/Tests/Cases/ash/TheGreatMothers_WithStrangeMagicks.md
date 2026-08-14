# ShieldBlocksCombatDamage_NoDefeat
#// ASH_101 The Great Mothers (Ground, 6/7) — When Attack Ends: defeat each non-leader unit it dealt COMBAT
#// damage to. Here the defender SEC_080 (3/3) carries a Shield (SOR_T02): the shield absorbs all 6 combat
#// damage (removed, unit takes 0), so no combat damage was dealt — the unit is NOT defeated. Great Mothers
#// takes the 3 counter.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_101:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttacksBase_DefeatsNothing
#// The ability keys on combat damage dealt to non-leader UNITS. Attacking the enemy base deals no combat
#// damage to any unit, so nothing is defeated — a bystander enemy unit (SOR_128) is untouched.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_101:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:6
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128

---

# LeaderUnitNotDefeated
#// The ability defeats only NON-leader units. Great Mothers attacks an enemy deployed leader unit (SOR_016,
#// 3/9): it takes the 6 combat damage but, being a leader unit, is NOT defeated. Great Mothers takes the 3
#// counter.
## GIVEN
CommonSetup: grk/grk/{theirLeader:SOR_016:1:1}
WithP1GroundArena: ASH_101:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# BasicDefeat_DamagedNonLeaderUnit
#// ASH_101 The Great Mothers (Ground, 6/7) — When Attack Ends: defeat each non-leader unit it dealt COMBAT
#// damage to. Here it attacks a high-HP non-leader unit (SOR_046 Consular Security Force, 3/7): the 6 combat
#// damage leaves it at 6 (survives combat, 7 HP) and GreatMothers takes the 3 counter — then the rider
#// defeats the unit it dealt combat damage to. P2's arena is emptied; GreatMothers ends with 3 damage.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_101:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_101
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Support_LendsAttackEndDefeatDamagedUnit
#// ASH_101 The Great Mothers (Support) — the lent "When Attack Ends: defeat the non-leader units this unit
#// dealt combat damage to" now fires on the BORROWING attacker. GreatMothers is played; SOR_046 supports and
#// attacks SOR_063 (2/4, survives combat), then the lent rider defeats SOR_063.
## GIVEN
CommonSetup: grk/grk/{myResources:7;handCardIds:ASH_101}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
