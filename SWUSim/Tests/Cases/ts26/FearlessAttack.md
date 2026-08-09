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
