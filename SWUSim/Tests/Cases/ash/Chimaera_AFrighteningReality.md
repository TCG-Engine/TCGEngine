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

---

# Reactive_MassDefeat_SingleReactorIgnition_ChimaeraStillHeals
#// ASH_052 Chimaera — LAW_044 Single Reactor Ignition ("Defeat all units") is SOR_043 Superlaser Blast's
#// sibling above: the same snapshot-the-UIDs-then-defeat-one-at-a-time loop. It never opened the
#// simultaneous-defeat window, so SWUAllUnits() (own side first) removed Chimaera BEFORE either enemy
#// defeat was collected, every later defeat arrived as its own single-element batch, and the heal was
#// silently dropped. Live report 2026-09-28.
#// TWO enemy units, so the heal is 2 apiece: base 6 -> 2. That also separates "healed once" from
#// "healed per enemy unit defeated".
#// LAW_044 is Vigilance/Aggression/Villainy — Aggression is off-aspect for this bbk deck, so its printed
#// cost 8 is 10 here.
#// P2BASEDMG:2 is LAW_044's own "1 damage per enemy unit defeated this way", which proves the wipe ran.
## GIVEN
CommonSetup: bbk/bbk/{myResources:10;handCardIds:LAW_044;myBaseDamage:6}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:2
P2BASEDMG:2

---

# Reactive_MassDefeat_SuperlaserBlast_IdenPlusChimaera_Heals3PerEnemyUnit
#// ASH_052 Chimaera — TWO stacked "when an enemy unit is defeated" observers, both caught in the blast,
#// against EIGHT enemy units (owner's scenario 2026-09-28). SOR_002 Iden Versio's DEPLOYED side heals 1
#// and Chimaera heals 2, so every Battle Droid defeated is worth 3: 8 x 3 = 24, base 25 -> 1.
#// SOR_043 Superlaser Blast walks teamGround -> teamSpace -> theirGround, so BOTH observers are removed
#// before the first droid's defeat is collected. Only the pre-effect snapshot can answer this; a live
#// count heals 0 and a "+ this batch" supplement heals at most once.
#// ⚠ Chimaera is EXHAUSTED here on purpose: exhaustion gates ACTION abilities, not triggered ones, so
#// the heal must be unaffected.
#// P1GROUNDARENACOUNT:0 is the deployed Iden leaving play with the rest of the board.
## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SOR_043;myBaseDamage:25;myLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_052:0:0
WithP2GroundArena: [TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0 TWI_T01:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:1

---

# Reactive_MassDefeat_HyperspaceDisaster_ChimaeraStillHeals
#// ASH_052 Chimaera — SEC_078 Hyperspace Disaster ("Defeat all space units"), the same
#// snapshot-then-defeat-one-at-a-time loop. Chimaera IS a space unit, so it is always among the
#// casualties and can never observe from safety. Two enemy space units -> heal 4, base 6 -> 2.
#// SEC_078 is Vigilance — on-aspect for this bbk deck, so 7 resources pay it exactly.
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SEC_078;myBaseDamage:6}
WithP1SpaceArena: ASH_052:1:0
WithP2SpaceArena: [JTL_251:1:0 JTL_251:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1BASEDMG:2

---

# Reactive_MassDefeat_NebulaIgnition_ChimaeraStillHeals
#// ASH_052 Chimaera — JTL_080 Nebula Ignition ("Defeat each unit that isn't upgraded"). Nothing on this
#// board carries an upgrade, so it is a full wipe and Chimaera dies with it.
#// Two enemy units -> heal 4, base 6 -> 2. JTL_080 is Vigilance, on-aspect: 9 resources.
## GIVEN
CommonSetup: bbk/bbk/{myResources:9;handCardIds:JTL_080;myBaseDamage:6}
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:2

---

# Reactive_MassDefeat_SummaVerminoth_ChimaeraStillHeals
#// ASH_052 Chimaera — ASH_083 Summa-verminoth's "On Attack: Defeat all other space units" is a wipe that
#// the ATTACKER survives ("all OTHER"), so the observer dying while the wiper lives is the shape here.
#// Summa is space index 0 and attacks the enemy base; the On Attack resolves first, defeating Chimaera
#// (space index 1) and both enemy space units. Two enemy units -> heal 4, base 6 -> 2.
#// P1SPACEARENACOUNT:1 is Summa itself; P2BASEDMG:15 is its swing landing afterwards.
## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:6}
WithP1SpaceArena: [ASH_083:1:0 ASH_052:1:0]
WithP2SpaceArena: [JTL_251:1:0 JTL_251:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1BASEDMG:2

---

# Reactive_MassDefeat_RhydoniumDetonation_ChimaeraStillHeals
#// ASH_052 Chimaera — LAW_096 Rhydonium Detonation ("Each player may return a non-leader unit to its
#// owner's hand. Then, defeat all non-leader units."). BOTH players decline the save, so the mass defeat
#// takes Chimaera and both enemy ground units. Two enemy units -> heal 4, base 6 -> 2.
#// LAW_096 is Cunning/Vigilance — Cunning is off-aspect for this bbk deck, so its cost 7 is 9 here.
## GIVEN
CommonSetup: bbk/bbk/{myResources:9;myBaseDamage:6}
WithActivePlayer: 1
WithP1Hand: LAW_096
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P2>AnswerDecision:-
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:2

---

# Reactive_MassDefeat_GovernorsShuttle_ChimaeraStillHeals
#// ASH_052 Chimaera — LAW_099 Governor's Shuttle's "Each player chooses a unit they control. Defeat those
#// units." is the smallest simultaneous defeat that CROSSES SEATS: one of mine and one of theirs, defeated
#// together. P1 gives up Chimaera, P2 gives up its Battlefield Marine, so Chimaera dies alongside the one
#// enemy unit it is meant to observe -> heal 2, base 6 -> 4.
#// P1SPACEARENACOUNT:1 is the Shuttle itself, which P1 chose not to feed to its own ability.
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:6}
WithActivePlayer: 1
WithP1Hand: LAW_099
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0
P1BASEDMG:4

---

# Reactive_MassDefeat_DarthSidiousTPM_ChimaeraStillHeals
#// ASH_052 Chimaera — LOF_039 Darth Sidious's "defeat each non-Sith unit with 3 or less remaining HP" is a
#// CONDITIONAL wipe, and the observer only joins the batch when it is damaged enough to qualify. Chimaera
#// is 6/6 with 3 damage (3 remaining) and non-Sith, so it goes with the two enemy units it is watching:
#// heal 4, base 6 -> 2. Sidious himself is Sith and 8 HP, so he survives and does the wiping.
## GIVEN
CommonSetup: bbk/bbk/{myResources:12;handCardIds:LOF_039;myBaseDamage:6}
P1OnlyActions: true
WithP1Force: true
WithP1SpaceArena: ASH_052:1:3
WithP2GroundArena: [SOR_128:1:0 SEC_080:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1BASEDMG:2

---

# Reactive_OneSidedWipe_InvasionOfChristophsis_ChimaeraWatchesFromSafety
#// ASH_052 Chimaera — TWI_078 The Invasion of Christophsis ("Choose an opponent. Defeat each unit that
#// player controls") is a ONE-SIDED wipe: only the chosen opponent's units are defeated, so Chimaera is
#// never among the casualties and the live observer count is correct WITHOUT a simultaneous-defeat window.
#// This section pins that as a measured fact rather than an argument, and it is the control for the
#// windowed wipes above: the observer watching from safety is the case that passes either way.
#// Two enemy units -> heal 4, base 6 -> 2. TWI_078 is Vigilance, on-aspect for bbk, cost 15.
#// ⚠ Exploit 4 must be DECLINED explicitly here: with Chimaera on the board it is an exploitable unit, so
#// the prompt is pending and the wipe never resolves without the decline.
## GIVEN
CommonSetup: bbk/bbk/{myResources:15;handCardIds:TWI_078;myBaseDamage:6}
P1OnlyActions: true
WithP1SpaceArena: ASH_052:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:0
P1BASEDMG:2
