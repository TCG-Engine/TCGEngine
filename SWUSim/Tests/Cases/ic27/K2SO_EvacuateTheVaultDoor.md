# CombatTrade_FiveToEachEnemyUnitAndTheEnemyBase
#// IC27_124 K-2SO - Evacuate The Vault Door (Unit, Ground 10/5, cost 9, [Aggression,Heroism]) — "When Defeated: Deal 5
#// damage to each enemy unit and each enemy base."
#//
#// COVERAGE: positive=CombatTrade_FiveToEachEnemyUnitAndTheEnemyBase (ground + space + a deployed LEADER unit, the friendly
#//   unit and base untouched) · negative=NotDefeated_NoBlast · lethal=EnemyBaseAt25_TheBlastWins ·
#//   shields=AShieldedEnemyUnit_TheShieldTakesIt · no-target=NoEnemyUnits_TheBaseIsStillHit ·
#//   defeat-path=EnemyTurnDefeat_TheBlastStillResolves (the OPPONENT defeats it, on THEIR turn) ·
#//   control=Stolen_EnemyMeansTheController'sEnemies (owner ≠ controller) · Ty Yorrick=TyYorrick_OneQuestion_* ·
#//   reqboundary=TyYorrick_RequestBoundary_BeforeTheAnswer (the only decision the card can raise) ·
#//   modes=2P,TwinSuns,TeamSuns ("each enemy base"/"each enemy unit" — every opponent at four seats, never a teammate:
#//   TwinSuns_EveryOpponentIsHit / TeamSuns_TheTeammateIsSpared)
#//
#// K-2SO (4 damage, 1 HP left) attacks P2's 3/7 Consular Security Force: both die. The blast then hits P2's 4/6 Guerilla
#// Attack Pod (survives on 5), P2's deployed 5/8 Darth Vader leader unit (5), P2's 2/3 X-Wing (defeated) and P2's base.
#// P1's own Battlefield Marine and base are untouched. P2's ground arena compacts to Guerilla, then Vader.
## GIVEN
CommonSetup: ggw/rrk/{theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [IC27_124:1:4 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_148:1:0]
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_148
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:CARDID:SOR_010
P2GROUNDARENAUNIT:1:DAMAGE:5
P2SPACEARENACOUNT:0
P2BASEDMG:5
P1NODECISION

---

# NotDefeated_NoBlast
#// The negative: the ability is When Defeated only. K-2SO attacks the base and survives — 10 combat damage to the base,
#// nothing to P2's units.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_124:1:0
WithP2GroundArena: SOR_148:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:10

---

# EnemyBaseAt25_TheBlastWins
#// The enemy base (30 HP) sits on 25: the blast's 5 is lethal.
## GIVEN
CommonSetup: ggw/rrk/{theirBaseDamage:25}
P1OnlyActions: true
WithP1GroundArena: IC27_124:1:4
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1WIN

---

# AShieldedEnemyUnit_TheShieldTakesIt
#// Card-ability damage, so a Shield on the Guerilla Attack Pod prevents all 5 and is defeated. The base still takes 5.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_124:1:4
WithP2GroundArena: [SOR_046:1:0 SOR_148:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_148
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:5

---

# NoEnemyUnits_TheBaseIsStillHit
#// The only enemy unit dies in the trade, so "each enemy unit" is empty — the base half still resolves.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_124:1:4
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1BASEDMG:0
P1DISCARDCOUNT:1

---

# EnemyTurnDefeat_TheBlastStillResolves
#// The cross-seat path: P2 attacks K-2SO on P2's own turn. P1 resolves the When Defeated, and "enemy" is still P2's side —
#// the 3/7 Consular takes 5 (survives), P2's base takes 5, P1's Battlefield Marine is untouched.
## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: [IC27_124:1:4 SOR_095:1:0]
WithP2GroundArena: [SOR_148:1:0 SOR_046:1:0]
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Drain
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:5

---

# Stolen_EnemyMeansTheController'sEnemies
#// P1 controls a K-2SO that P2 OWNS. It attacks P2's deployed 5/8 Vader and both die. "Enemy" is judged from the
#// CONTROLLER: P2's Guerilla Attack Pod and P2's base take 5; P1's Battlefield Marine and base take nothing. K-2SO goes to
#// its OWNER's discard pile. (A controlled-unit seed lands AFTER the plain seeds, so K-2SO is P1's ground index 1.)
## GIVEN
CommonSetup: ggw/rrk/{theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArenaControlled: IC27_124:2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_148:1:0
## WHEN
- P1>AttackGroundArena:1:1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_148
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:5
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# TyYorrick_OneQuestion_AcceptedSixToEach
#// HMW_185 Ty Yorrick (friendly) — "If a friendly ability would deal damage, you may have that ability deal that much
#// damage plus 1 instead." The blast is ONE ability, so ONE question (owner ruling 2026-10-07, as for Turbolaser Salvo):
#// accepted, the Guerilla Attack Pod takes 6 (6 HP — defeated) and P2's base takes 6. No second question follows for the
#// base. Ty himself is friendly and untouched.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: [IC27_124:1:4 HMW_185:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_148:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:HMW_185
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# TyYorrick_OneQuestion_Declined
#// The same board, Ty's offer refused: 5 each, as printed.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: [IC27_124:1:4 HMW_185:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_148:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:5
P1NODECISION

---

# TyYorrick_RequestBoundary_BeforeTheAnswer
#// Ty's question ends the request; the blast resolves in the next one.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: [IC27_124:1:4 HMW_185:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_148:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6

---

# TwinSuns_EveryOpponentIsHit
#// Four seats, no teams: seats 2, 3 and 4 are all enemies. Each enemy base takes 5 and each far-seat Guerilla Attack Pod
#// takes 5. P1's base takes nothing.
## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_124:1:4
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_148:1:0
WithP4GroundArena: SOR_148:1:0
## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
## EXPECT
SEATCOUNT:4
P1BASEDMG:0
P2BASEDMG:5
P3BASEDMG:5
P4BASEDMG:5
P3GROUNDARENAUNIT:0:DAMAGE:5
P4GROUNDARENAUNIT:0:DAMAGE:5

---

# TeamSuns_TheTeammateIsSpared
#// Team Suns: seat 3 is P1's teammate, so its base and unit are not enemies. Seats 2 and 4 take 5 each.
## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_124:1:4
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_148:1:0
WithP4GroundArena: SOR_148:1:0
## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
## EXPECT
SEATCOUNT:4
P1BASEDMG:0
P2BASEDMG:5
P3BASEDMG:0
P4BASEDMG:5
P3GROUNDARENAUNIT:0:DAMAGE:0
P4GROUNDARENAUNIT:0:DAMAGE:5
