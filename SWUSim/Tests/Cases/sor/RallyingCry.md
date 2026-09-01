# GrantsRaid2
#// SOR_154 Rallying Cry (Event, cost 3) — "Each friendly unit gains Raid 2 this
#// phase." After playing it, P1's Battlefield Marine (power 3) attacks P2's base
#// with Raid 2: 3 + 2 = 5 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# Raid2_ExpiresNextPhase
#// SOR_154 Rallying Cry — "Each friendly unit gains Raid 2 this phase." The grant is a CardID
#// turn-effect token ("SOR_154#2") resolved by the registry to a Raid value of 2 (phase duration).
#// After both players pass (action phase ends → regroup), the centralized duration expiry strips it,
#// so the Battlefield Marine no longer has Raid. (Previously the granted Raid persisted — a latent
#// bug fixed by giving turn effects real durations.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# SimulateRequestBoundary_Raid2SurvivesTurnPass
#// SOR_154 Rallying Cry raises NO interactive decision, but playing it passes the turn — a real
#// production request boundary — so the phase-duration "gains Raid 2" grant is written in one process and
#// read by the attack in a fresh one. Mirrors GrantsRaid2 with the boundary inserted between the play and
#// the attack: the Marine must still hit for 3 + 2 = 5.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# EnemyUnitsDoNotGainRaid
#// SOR_154 Rallying Cry — the scope word "each FRIENDLY unit" is load-bearing: the grant must not reach
#// the opponent's board. P1 plays Rallying Cry, then the turn passes and P2's Imperial Dark Trooper
#// (3/3) attacks P1's base. It deals its printed 3, not 5, and carries no Raid keyword — while P1's own
#// Battlefield Marine, seated at the same moment, does.
#// COVERAGE: offer=N/A (no target choice — "each friendly unit" is a fixed set, nothing is selected) ·
#//           decline=N/A (a played event's effect is mandatory) · boundary=EnemyUnitsDoNotGainRaid
#//           (friendly in / enemy out) + Raid2_ExpiresNextPhase (this phase in / next phase out) ·
#//           control=N/A (a one-shot grant fixed at resolution; see
#//           UnitPlayedAfterTheEventDoesNotGainRaid for the set being closed at resolution time) ·
#//           reqboundary=SimulateRequestBoundary_Raid2SurvivesTurnPass

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154;theirResources:3}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# BothArenasGainRaid
#// SOR_154 Rallying Cry — "EACH friendly unit" is not arena-scoped: a ground unit and a space unit both
#// gain Raid 2. P1's Imperial Dark Trooper (3 power, ground) and TIE/ln Fighter (2 power, space) each
#// attack P2's base after the event: 3+2 = 5 and 2+2 = 4, so the base ends on 9. Quantity
#// discrimination: without the grant the same two attacks total 5.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:9
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1SPACEARENAUNIT:0:HASKEYWORD:Raid

---

# UnitPlayedAfterTheEventDoesNotGainRaid
#// SOR_154 Rallying Cry — "Each friendly unit gains Raid 2 this phase" is a ONE-SHOT grant, not a
#// standing aura: the set of affected units is fixed when the event resolves, so a unit that arrives
#// LATER in the same phase gets nothing. P1 plays Rallying Cry with a Consular Security Force already
#// on the board, then plays an Imperial Dark Trooper. The Consular (present at resolution) has Raid;
#// the Dark Trooper (played afterwards) does not. Asserted on the keyword rather than an attack because
#// a unit played this turn cannot attack.

## GIVEN
CommonSetup: rrk/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: [SOR_154 SEC_080]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
