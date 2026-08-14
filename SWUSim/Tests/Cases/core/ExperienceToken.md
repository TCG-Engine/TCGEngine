# BuffPersists_ThroughCombat
#// CORE TOKEN RULE — an Experience token (SOR_T01) grants the attached unit +1/+1 as a PERSISTENT
#// upgrade effect, not a this-phase buff. Battlefield Marine (3/3) + Experience = 4/4 attacks a
#// 3/7: it deals 4 (buffed power) and SURVIVES the 3 back-damage only because of the +1 HP — at
#// printed 3/3 the trade would have killed it. After combat the stats still read 4/4 and the
#// token is still attached: the effect did not expire with the attack.
#//
#// COVERAGE: offer=GivenToEnemyUnit_BuffsUnderTheirControl (Experience attach pool includes enemy
#//           units; the give-a-token picker is exercised there) · reqboundary=this section (attack
#//           resolves across a request boundary and the buff is re-read afterwards) ·
#//           control=GivenToEnemyUnit_BuffsUnderTheirControl (token created under the OPPONENT's
#//           control functions for them — the observable half of the token owner/controller rule;
#//           the owner/controller FIELDS themselves are not exposed to the harness) ·
#//           boundary pair=survives-at-4HP here vs DirectDefeat_TokenCeasesNotDiscarded stripping
#//           the buff back to printed stats · decline=N/A (a static upgrade grant has no choice)
#// Token-cease-on-host-defeat is already covered by
#// core/TokensCeaseOnLeavingPlay.md::DefeatedUnitsTokenUpgrades_CeaseWhileRealUpgradesDiscard.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# DirectDefeat_TokenCeasesNotDiscarded
#// CORE TOKEN RULE — a token UPGRADE defeated directly (SOR_251 Confiscate: "defeat an upgrade")
#// ceases to exist: it goes to no discard pile, and the host drops back to printed stats. The
#// only card in any discard is P1's spent Confiscate.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1;handCardIds:SOR_251}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# GivenToEnemyUnit_BuffsUnderTheirControl
#// CORE TOKEN RULE (CR errata: the owner and controller of a token upgrade is the controller of
#// the attached unit) — the observable consequence: P1's SHD_040 Clan Wren Rescuer gives an
#// Experience token to an ENEMY unit, and the token sits in P2's arena buffing P2's unit +1/+1.
#// Both units are offered (the give is unit-unrestricted); P1 picks the enemy.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_040
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:8
