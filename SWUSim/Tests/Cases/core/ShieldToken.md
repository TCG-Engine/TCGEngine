# GiveShieldOffer_IncludesEnemyUnits
#// CORE TOKEN RULE — "Give a Shield token to a unit" (SOR_073 Moment of Peace) is unit-
#// unrestricted: the pick pool contains BOTH P1's and P2's units. The decision is left pending
#// and the offer asserted exactly (one unit per side keeps the pool at two).
#//
#// COVERAGE: offer=this section · reqboundary=GivenToEnemyUnit_ShieldWorksForThem (attach then a
#//           later attack in the same flow re-reads the token across request boundaries) ·
#//           control=GivenToEnemyUnit_ShieldWorksForThem (a shield P1 creates on P2's unit is P2's
#//           token — the observable half of the token owner/controller rule; the owner/controller
#//           FIELDS are not exposed to the harness) · boundary pair=shield absorbs the hit vs the
#//           shieldless follow-up state (UPGRADECOUNT:0 after one absorb) · decline=N/A ("give a
#//           Shield token" is mandatory)
#// Shield absorb mechanics, two-shield stacking, and cease-on-host-defeat are covered by
#// keywords/Shielded_AbsorbsDamage.md, keywords/Shielded_TwoShields.md, and
#// core/TokensCeaseOnLeavingPlay.md::DefeatedUnitsTokenUpgrades_CeaseWhileRealUpgradesDiscard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# GivenToEnemyUnit_ShieldWorksForThem
#// CORE TOKEN RULE — P1 shields the ENEMY's unit: the token is created under P2's control and
#// functions for P2. P1's SOR_046 (3/7) then attacks the shielded Marine (3/3): the shield
#// absorbs all 3 damage and is consumed (ceases — P2's discard stays empty), while the attacker
#// still takes 3 back-damage. The enemy unit ends undamaged and shieldless.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
