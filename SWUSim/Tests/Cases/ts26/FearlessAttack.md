# BonusPerDefendingUnit
#// TS26_84 Fearless Attack (Event, cost 4, Heroism) — Attack with a unit; it gets +1/+0 for this attack
#// per unit the defending player controls. The opponent controls 2 space units, so SEC_080 (3 power) gets
#// +2 → 5 and hits the enemy base for 5.
## GIVEN
CommonSetup: bgw/rrk/{myResources:4;handCardIds:TS26_84}
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: [SOR_237:1:0 SOR_225:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:5

---

# ZeroDefendingUnitsMeansNoBonus
#// TS26_84 Fearless Attack — "+1/+0 for each unit controlled by the DEFENDING player". With P2 controlling
#// nothing, the bonus is zero and SEC_080 hits their base for its printed 3.
#// Boundary partner to BonusPerDefendingUnit, where two enemy units make it 5.

## GIVEN
CommonSetup: bgw/rrk/{myResources:4;handCardIds:TS26_84}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3

---

# TwinSuns_CountsOnlyTheDEFENDINGPlayersUnits
#// "Attack with a unit. It gets +1/+0 for this attack for each unit controlled by THE DEFENDING PLAYER."
#// Two defects at once, the same pair ASH_234 Masterstroke had: the count used
#// GetUnitsInPlay(OtherPlayer($player)), and it ran at CARD RESOLUTION — before BeginSWUAttack, i.e.
#// before any target had been declared, so there was no defending player to read even in principle.
#// Fixed by the TS26_84_ATK marker + _SWUApplyDefenderConditionalAttackEffects.
#//
#// Seat 4 (the defender) controls ONE unit; seat 2 controls FOUR. Correct bonus +1 → 3 power + 1 = 4 on
#// seat 4's base. The legacy count gives +4 = 7.
#// Seat 1 has exactly one READY unit so the attacker choice auto-resolves and the only pending decision
#// is the attack target.

## GIVEN
CommonSetup: rrk/bbw/{theirBase:SOR_021; myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: TS26_84
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Base-0

## EXPECT
SEATCOUNT:4
P4BASEDMG:4
P2BASEDMG:0
