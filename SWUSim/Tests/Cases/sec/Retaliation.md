# DefeatBaseDamager
#// SEC_077 Retaliation (Event, Vigilance, cost 5) — "Defeat a unit that dealt damage to a base this phase."
#//   SOR_095 attacks P2's base (marked), then SEC_077 defeats it (the only base-damager this phase).

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:0
P1NODECISION

---

# DefeatEnemyBaseDamager
#// SEC_077 Retaliation — "Defeat a unit that dealt damage to a base this phase." Works on an ENEMY
#//   attacker: P2's SOR_046 (3/7) attacks P1's base, then P1 plays Retaliation and defeats it (the sole
#//   base-damager, auto-resolves).

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_077

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
TURNPLAYER:2
P1NODECISION

---

# NoDefeat_AttackedUnitNotBase
#// SEC_077 Retaliation — a unit that attacked another UNIT (not a base) is not a legal target. P2's
#//   SOR_046 attacks P1's SOR_046 (both 3/7, both survive); no base was damaged this phase, so playing
#//   Retaliation has no legal target and fizzles — nothing is defeated.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_077

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1BASEDMG:0
P1DISCARDCOUNT:1
TURNPLAYER:2
P1NODECISION

---

# OverwhelmSpillToBase_CountsAsDamagingABase
#// SEC_077 Retaliation — "dealt damage to a base" must count OVERWHELM spillover, not just a direct base
#// attack. P1's Wampa (SOR_164, 4 power, Overwhelm) attacks P2's 2/2 (SHD_110): 2 excess spills onto P2's
#// base, so the Wampa IS a legal Retaliation target and is defeated. Same damage-path enumeration family
#// as the JTL_177 indirect bug — a "dealt damage to X" condition must cover every route to X.
## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_110:1:0
WithP1Hand: SEC_077
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P1NODECISION

---

# UndeployedLeaderDamagedBase_NotATarget
#// SEC_077 Retaliation — "defeat a UNIT that dealt damage to a base". A leader that damaged a base while
#// UNDEPLOYED is not a unit in play, so it can never be the target, and with no other base-damager the
#// event fizzles with nothing defeated. Guards the unit-scope half of the condition.
## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1NODECISION

---

# UnitThatDamagedABaseThenCHANGEDCONTROL_IsStillAValidTarget
#// SEC_077 Retaliation — "a unit that dealt damage to a base this phase" tracks the UNIT, not who
#// controlled it at the time. P1's SOR_095 attacks P2's base, P2 then steals it with SOR_122 Traitorous,
#// and P1's Retaliation can still defeat it in its new home.

## GIVEN
CommonSetup: bbk/ggk
P1OnlyActions: false
WithActivePlayer: 1
WithP1Resources: 6
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077
WithP2Hand: SOR_122

## WHEN
- P1>AttackGroundArena:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# UnitDefeatedAndRETURNEDToPlayIsANewUnit_NotATarget
#// SEC_077 Retaliation — the mark rides a specific in-play unit, so a unit that damaged a base, LEFT
#// play and came back is a new object with no mark. P1's SOR_095 attacks P2's base, P2 bounces it to
#// P1's hand with SOR_222 Waylay, P1 replays it, and Retaliation then has no legal target: the fresh
#// copy survives.

## GIVEN
CommonSetup: bbk/ggk
WithActivePlayer: 1
WithP1Resources: 12
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077
WithP2Hand: SOR_222

## WHEN
- P1>AttackGroundArena:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:1
- P2>Pass
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# UnitThatDamagedABaseByAnUPGRADEsAbilityWhileAttackingAUnit_IsATarget
#// SEC_077 Retaliation — "dealt DAMAGE to a base", not "attacked a base". Here P1's SOR_095 attacks an
#// enemy UNIT (so it never attacked a base at all) while SEC_264 Clandestine Connections, attached to
#// it, pays 2 to deal 2 to P2's base. That ability damage alone makes it a legal Retaliation target,
#// and P1 defeats its own unit with the event.
#// Complements OverwhelmSpillToBase_CountsAsDamagingABase, which covers the combat-overflow route.

## GIVEN
CommonSetup: bbk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264
WithP1Hand: SEC_077
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# UnitThatDamagedItsOWNBaseByAnAbility_IsATarget
#// SEC_077 Retaliation — "Defeat a unit that dealt damage to A BASE this phase." Any base, including the
#// unit's own. P2's SOR_142 Sabine Wren attacks P1's Battle Droid token and uses her On Attack to ping
#// P2's OWN base; she is then a legal Retaliation target for P1 and is defeated.
#// This was a documented gap: the two older markers are both ENEMY-BASE-ONLY (they exist for SEC_012
#// Cassian, whose condition is "damaged an OPPONENT'S base"), so a self-inflicted base ping left no trace.
#// It is closed by the any-base marker added for SEC_012's control-change cases.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:SEC_077}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_142:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:myBase-0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# UnitThatOnlyDamagedAUNIT_IsNotATargetEvenAfterAnOwnBasePing_Control
#// SEC_077 Retaliation — the control for the section above, so "she was targetable" can't be a false
#// positive from Retaliation simply hitting anything. Same board, but Sabine declines her On Attack ping
#// (no base is damaged at all), so no unit in play has damaged a base this phase and Retaliation has no
#// legal target: the event is spent for nothing and Sabine survives.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:SEC_077}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_142:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_142
