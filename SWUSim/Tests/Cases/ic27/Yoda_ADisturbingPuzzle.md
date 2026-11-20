# ChooseTheDefender_NoCombatDamageEitherWay
#// IC27_035 Yoda (Ground 3/4, cost 3, [Vigilance][Heroism]) — "On Attack: You may choose an enemy unit. Prevent all
#// combat damage that would be dealt to and dealt by that unit for this phase." Yoda attacks P2's Battlefield Marine
#// (3/3) and chooses it: Yoda's 3 to it is prevented, and its 3 counter to Yoda is prevented. Both undamaged.
#// Fixture: CommonSetup bbw = Vigilance base + Vigilance/Heroism leader.
#// COVERAGE: offer=Offer_EnemyUnitsOnly_BothArenas · decline=Decline_NormalCombat ·
#//           boundary=N/A (no number; "all" combat damage — every amount is prevented, pinned by the 3- and 4-power
#//           hits) · control=N/A (Yoda's choice is made by his controller; the marker sits on the chosen unit, whoever
#//           controls it — MarkedUnit_AttacksOnItsOwnTurn_DealsAndTakesNothing has it act for its own controller) ·
#//           reqboundary=RequestBoundary_ChoiceInAFreshRequest (+ every later-attack section crosses actions)
#//           modes=2P,TwinSuns ("an enemy unit" spans every opponent seat — TwinSuns_AFarSeatUnitIsOffered),
#//                 TeamSuns ("enemy" = the opposing team — TeamSuns_YourTeammatesUnitIsNotEnemy)

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Decline_NormalCombat
#// Declining prevents nothing: Yoda deals 3 (the Marine is defeated) and takes 3.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_EnemyUnitsOnly_BothArenas
#// The choice is left PENDING. Offered: P2's two ground units and P2's space unit. Not offered: Yoda and P1's Marine
#// (friendly). Yoda attacks P2's base.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# ChooseAnotherEnemy_LaterThisPhaseItTakesAndDealsNothing
#// Yoda attacks P2's base and chooses P2's Marine. Later in the phase P1's Consular Security Force (3/7) attacks that
#// Marine: neither damages the other.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# OnlyTheChosenUnit_OtherEnemiesFightNormally
#// The prevention is on the chosen unit only: P2's OTHER unit (the Security Force, 3/7) takes P1's Security Force's 3
#// and counters for 3 as usual.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# MarkedUnit_AttacksOnItsOwnTurn_DealsAndTakesNothing
#// Alternating turns: Yoda marks P2's Marine; on P2's turn the Marine attacks P1's Security Force — it deals nothing
#// and takes nothing (the prevention covers the unit as an ATTACKER too).

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: IC27_035:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# MarkedUnit_AttacksABase_DealsNothing
#// "dealt by that unit" includes combat damage to a base: the marked Marine attacks P1's base for 0.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:0

---

# PreventionExpires_AtTheEndOfThePhase
#// "for this phase": in the next round the Security Force's attack on the Marine deals its 3 damage as usual.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# AbilityDamage_IsNotCombatDamage_NotPrevented
#// Only COMBAT damage is prevented: after Yoda marks the Marine, SOR_172 Open Fire's 4 ability damage defeats it.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:SOR_172}
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# Overwhelm_IntoAMarkedDefender_NoExcess
#// Prevented damage is not excess: General Veers (3/3, Overwhelm) attacks a marked Death Star Stormtrooper (3/1) —
#// it takes nothing, is not defeated, and nothing spills to P2's base. (The marker is seeded: the state after Yoda's
#// choice earlier in the phase.)

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_058:1:0
WithP2GroundArena: SOR_128:1:0:IC27_035

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# UnpreventableDamage_StillLands
#// ASH_196 Gorian Shard's Corsair makes damage dealt by friendly Underworld cards unpreventable: P1's Industrious Team
#// (Underworld, 4/7) defeats the marked Marine anyway. The Marine's own counter is NOT unpreventable, so it is still
#// prevented — the Team is undamaged.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: SOR_095:1:0:IC27_035

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# MaulTwoDefenders_TheMarkedOneTakesAndDealsNothing
#// TWI_135 Darth Maul's two-defender path: Maul (5/6) hits a marked Battlefield Marine and an unmarked Imperial Dark
#// Trooper. The marked one takes 0 and deals 0; the other is defeated and counters for 3.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0:IC27_035 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# MaulMarked_DealsAndTakesNothingFromBothDefenders
#// Maul himself marked (P2's Yoda chose him earlier): he deals nothing to either defender and takes neither counter.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0:IC27_035
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# BlankedYoda_NoChoice
#// Yoda under SOR_138 Force Lightning has no On Attack: nothing is offered and combat is normal.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0:SOR_138
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# RequestBoundary_ChoiceInAFreshRequest
#// ChooseTheDefender_NoCombatDamageEitherWay with a fresh request before the answer.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# TwinSuns_AFarSeatUnitIsOffered
#// Three seats, no teams: both opponents' units are enemy units — seat 2's and seat 3's are offered.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:3
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-0

---

# TeamSuns_YourTeammatesUnitIsNotEnemy
#// Team game: seat 3 is P1's teammate. Only the opposing team's units (seats 2 and 4) are offered.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1GroundArena: IC27_035:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0
