# Action_DealFour_ToAnEnemyUnit
#// IC27_107 Aurra Sing — Getting Even (1/7 Ground, Aggression/Villainy, Underworld·Bounty Hunter, unique)
#// "Hidden. Action [Exhaust]: Deal 4 damage to a unit."
#// Hidden is printed (auto-wired, generic coverage in keywords/Hidden.md). The work is the unit Action:
#// $unitAbilities["IC27_107"] (default 'exhaust' cost) -> a mandatory pick over EVERY unit in play (the text
#// has no "another", "friendly" or "enemy", so Aurra herself, leader units and both arenas are legal) ->
#// DEAL_UNIT_DAMAGE|4 with Aurra as the damage SOURCE (she is the unit dealing it).
#//
#// COVERAGE: offer=Offer_EveryUnitOnTheTable_HerselfIncluded ·
#//           decline=N/A (no "you may"; the target pick is mandatory) ·
#//           boundary=Kill_ThreeHpUnitDies paired with Action_DealFour_ToAnEnemyUnit (4 damage kills a 3-HP unit,
#//           a 7-HP unit survives on 4) ·
#//           control=N/A (no owner-scoped zone: "a unit" is the whole table and the damage has no "your") ·
#//           reqboundary=Action_TargetAnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// Two legal enemy-side candidates (plus Aurra herself) keep the pick interactive.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0    # idx 0 — Industrious Team 4/7
WithP2GroundArena: SOR_095:1:0    # idx 1 — Battlefield Marine 3/3

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_EveryUnitOnTheTable_HerselfIncluded
#// "a unit" — the whole table: Aurra (ground 0), P1's Imperial Dark Trooper (ground 1), P1's deployed
#// leader unit (ground 2), P1's TIE/ln Fighter (space), P2's Battlefield Marine and Alliance X-Wing. No base.

## GIVEN
CommonSetup: rrk/bbw/{myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Kill_ThreeHpUnitDies
#// Boundary partner: 4 damage defeats a 3-HP Battlefield Marine.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:0
P2DISCARDCOUNT:1

---

# SelfTarget_AurraTakesFour
#// She is a legal target of her own ability: 4 damage on her 7 HP, and she survives.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# LoneAurra_OnlyTargetIsHerself_AutoResolves
#// With no other unit in play the mandatory pick has exactly one legal target — Aurra — so it resolves
#// without a prompt and she deals 4 damage to herself.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Exhausted_CannotPayTheExhaustCost
#// An exhausted Aurra cannot pay [Exhaust]: the action does nothing.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Shield_PreventsTheDamage
#// Ordinary ability damage: a Shield token on the target prevents all 4 and is defeated.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# DamageSource_IsAurra_CorsairMakesItUnpreventable
#// Aurra is the SOURCE of the damage, and she is an Underworld card. With ASH_196 Gorian Shard's Corsair
#// ("Damage dealt by friendly Underworld cards is unpreventable") in play, the Shield no longer prevents it:
#// the target takes 4 and the Shield stays. Partner: Shield_PreventsTheDamage (same board, no Corsair).

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP1SpaceArena: ASH_196:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Action_ClosesOnce_TurnPasses
#// Without P1OnlyActions: using the Action is one action, closed exactly once — the turn passes to P2.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
TURNPLAYER:2
NOEXTRAACTION

---

# Action_TargetAnsweredAcrossRequestBoundary
#// The happy path with a request boundary between the offer and the answer.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_107:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
