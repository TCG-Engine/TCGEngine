# DefeatTwoHealOnEnemyDefeat
#// ASH_052 Chimaera (Space, 6/6, cost 7) — When Played: you may choose a friendly unit and an enemy
#// non-leader unit; if you do, defeat both. Plus: When an enemy unit is defeated, heal 2 from your base.
#// P1's base starts at 3 damage; playing Chimaera defeats friendly SOR_095 and enemy SEC_080, and the
#// enemy defeat heals 2 (3 → 1).
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052;myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:1

---

# WhenPlayed_Decline
#// ASH_052 Chimaera — the When Played defeat is optional. Declining leaves both units alive and heals
#// nothing (base stays at 3).
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052;myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1BASEDMG:3

---

# EnemyDefeatedInCombat_Heal2
#// ASH_052 Chimaera — the reactive "when an enemy unit is defeated: heal 2" fires for ANY enemy defeat, not
#// just the When Played one. A seated Chimaera watches SOR_046 kill the enemy SOR_128 in combat → heal 2
#// (base 3 → 1).
## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:3}
WithP1SpaceArena: ASH_052:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:1

---

# WhenPlayed_EnemyNotDefeatable
#// ASH_052 Chimaera — the When Played defeat still resolves against an enemy that "can't be defeated by enemy
#// card abilities" (JTL_103 Chewbacca): the friendly SOR_095 is chosen and defeated, but Chewbacca's immunity
#// keeps it in play. No enemy was defeated, so the reactive heal does not fire (base stays at 3).
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052;myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: JTL_103:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1BASEDMG:3

---

# WhenPlayed_CannotChooseEnemyLeader
#// ASH_052 Chimaera — an enemy leader unit is not a legal "enemy non-leader unit" target. With P2's only unit
#// being a deployed leader, the When Played pair can't be completed, so nothing is defeated (friendly SOR_095
#// survives) and there is no decision to answer.
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052;theirLeader:SOR_011:1:1:1}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1

---

# WhenPlayed_NoEnemyUnit_NoOp
#// ASH_052 Chimaera — the pair requires BOTH a friendly unit and an enemy non-leader unit. With no enemy units
#// at all, the ability does nothing: the friendly SOR_095 stays and no prompt appears.
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1

---

# Reactive_EnemyDefeatedByEvent_Heal2
#// ASH_052 Chimaera — the reactive "when an enemy unit is defeated: heal 2" fires for a non-combat defeat too.
#// A seated Chimaera watches SOR_078 Vanquish defeat the enemy SOR_095 → heal 2 (base 5 → 3).
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:SOR_078;myBaseDamage:5}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:3

---

# Reactive_EachEnemyDefeat_Heals
#// ASH_052 Chimaera — the reactive heal fires once per enemy unit defeated. Two separate removals (SOR_078
#// Vanquish, then SOR_077 Takedown) defeat two enemy units → heal 2 each (base 10 → 8 → 6).
## GIVEN
CommonSetup: bbk/bbk/{myResources:9;handCardIds:SOR_078,SOR_077;myBaseDamage:10}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: [SOR_095:1:0 SHD_098:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:6

---

# Reactive_FriendlyDefeat_NoHeal
#// ASH_052 Chimaera — the reactive heal only cares about ENEMY defeats. Defeating a friendly unit (SOR_077
#// Takedown on the friendly SOR_164) heals nothing (base stays at 5).
## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077;myBaseDamage:5}
WithP1SpaceArena: ASH_052:1:0
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:5

---

# Reactive_EnemyControlledFriendlyDefeat_Heal2
#// ASH_052 Chimaera — "enemy unit" is by control, not ownership. A P1-owned SOR_164 that P2 controls counts as
#// an enemy unit for P1's Chimaera; defeating it (SOR_077 Takedown) heals 2 (base 5 → 3).
## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077;myBaseDamage:5}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArenaControlled: SOR_164:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:3

---

# Reactive_TradeInCombat_ChimaeraDiesToo_StillHeals
#// ASH_052 Chimaera — ⚠ THE TRADE CELL (live bug report #961). Chimaera attacks and BOTH units die in the
#// same combat: combat damage is simultaneous, so the enemy unit was defeated while Chimaera was still in
#// play and the heal must happen. Contrast EnemyDefeatedInCombat_Heal2 above, where Chimaera watches from
#// safety — that section passes with a "count only the copies STILL in play" implementation, which is
#// exactly what this one catches.
#// Chimaera is 6/6 seeded with 1 damage (5 remaining) and JTL_251 Jedi Light Cruiser is 6/7 seeded with 1
#// damage (6 remaining): 6 power each way kills both. Base 5 -> 3 is the heal.
## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5}
WithP1SpaceArena: ASH_052:1:1
WithP2SpaceArena: JTL_251:1:1
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1BASEDMG:3

---

# Reactive_MassDefeat_ChimaeraInTheSameBatch_StillHeals
#// ASH_052 Chimaera — the other simultaneous-defeat path. SOR_043 Superlaser Blast ("Defeat all units")
#// walks the board one unit at a time inside a simultaneous-defeat window, so Chimaera can be removed
#// BEFORE the enemy unit's defeat is collected. It was in play when the effect started, so it still
#// observes the enemy defeat and heals 2 (base 5 -> 3).
#// SOR_043 is Vigilance/Villainy — on-aspect for this bbk deck, so 8 resources pay it exactly.
## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SOR_043;myBaseDamage:5}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:3
