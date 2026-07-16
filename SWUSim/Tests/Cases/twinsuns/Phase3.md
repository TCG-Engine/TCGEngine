# AttackTargets_PerOpponentSentinel
#// Twin Suns Phase 3: attack targeting UNIONS all opponents, and Sentinel is PER-OPPONENT (CR §11.4.4 —
#// a Sentinel on opponent A only forces attacks against A's units, it does NOT restrict attacks against
#// opponent B). P1's ground unit sees: P2's lone Sentinel (SOR_229) — P2's base is dropped because of it —
#// PLUS P3's non-Sentinel unit AND P3's base (unaffected by P2's Sentinel) = 3 targets. A broken (global)
#// Sentinel model would return only 1 (the Sentinel); a lone-opponent model would return only P2's 1.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: LAW_124:1:0
WithP3Base: SOR_019

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
ATTACKTARGETS:1:G:0:3

---

# Attack_BaseDamage_ToChosenOpponent
#// Twin Suns Phase 3: combat damage to a SPECIFIC opponent's base. P1's 3-power unit attacks P3's base
#// (both P2 and P3 bases are valid targets → the picker resolves to p3Base-0). The 3 damage must land on
#// P3's base, NOT P2's — proving (a) a "p{n}Base" target is not silently skipped (the old code only matched
#// the literal "theirBase"), and (b) it routes to the mzID's real owner, not the 2-player OtherPlayer.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATCOUNT:3
P3BASEDMG:3
P2BASEDMG:0
P1NODECISION

---

# Attack_UnitCombat_SpecificOpponent
#// Twin Suns Phase 3: unit-vs-unit combat against a SPECIFIC opponent's unit (a p{n}GroundArena defender).
#// P1's 3/3 attacks P3's 3/3 → both take 3 and are defeated simultaneously, and each goes to its OWN
#// owner's discard. This exercises the full p{n} defender path: the union offers P3's unit, the picker
#// resolves p3GroundArena-0, GetZoneObject fetches that defender (a Phase-1 gap this fixes), combat damage
#// applies, and defeat routes to the correct owner (P3's card → P3's discard, P1's → P1's).

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3G0

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P3DISCARDCOUNT:1

---

# JTL130_ChooseOpponent_ResourceCount
#// Twin Suns Phase 3 / Group B: JTL_130 Timely Reinforcements "Choose an opponent. For every 2 resources
#// they control, create an X-Wing (Sentinel)." The caster PICKS which opponent's resources to count. P2 has
#// 2 resources, P3 has 6; choosing P3 makes floor(6/2)=3 X-Wings (choosing P2 would make 1) — proving the
#// count reads the CHOSEN opponent, not the lone/first one.

## GIVEN
CommonSetup: ggw/bbk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 2
WithP3Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P1SPACEARENACOUNT:3

---

# K2SO_WhenDefeated_EachOpponent
#// Twin Suns Phase 3: SOR_145 K-2SO "When Defeated: FOR EACH OPPONENT, choose one: deal 3 to that player's
#// base OR that player discards." In a 3-player game K-2SO's controller gets a SEPARATE choice per opponent.
#// K-2SO attacks P2's 4/7 wall and dies to the 4 counter; P1 then chooses Base for P2 AND Base for P3 →
#// 3 damage to EACH of their bases (2-player fires one choice; here it fires twice, once per opponent).

## GIVEN
CommonSetup: ggw/brw
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:Base
- P1>AnswerDecision:Base

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P3BASEDMG:3

---

# Maul_Overwhelm_SplitAcrossOpponents
#// Twin Suns Phase 3: Darth Maul (TWI_135) "attacks 2 units" can pick units from DIFFERENT opponents in an
#// N-player game. With Overwhelm (TWI_119 → 7/8), each defeated 3/3 leaves 4 excess. The 2-player ruling
#// "COMBINED excess to the defending player's base" generalizes PER defending player: P2's unit's 4 excess
#// spills to P2's base and P3's unit's 4 to P3's base — NOT 8 combined onto one base. Maul takes 3+3=6
#// counter (survives at 6 on 8 HP).

## GIVEN
CommonSetup: rrk/bbw
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:TWI_119
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0

## EXPECT
SEATCOUNT:3
P2BASEDMG:4
P3BASEDMG:4
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:6

---

# OpponentsOf
#// Twin Suns Phase 3: OpponentsOf returns all LIVE opponents in seat order. Seat 2 eliminated (LiveSeats
#// = 1,3), so P1's opponents are just [3] and P3's are just [1]; a dead seat never appears.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 13

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
OPPONENTSOF:1:3
OPPONENTSOF:3:1
OPPONENTSOF:2:1,3

---

# Ruthlessness_DefendingPlayerBase
#// Twin Suns Phase 3: SHD_143 Ruthlessness ("When this unit attacks and defeats a unit: Deal 2 damage to
#// the defending player's base") must hit the DEFENDING player's base — the owner of the defeated unit —
#// not merely OtherPlayer. P1's Ruthlessness-equipped 4/7 attacks and defeats P3's 3/3, so P3's base takes
#// 2 (P2's base is untouched). Derived from the defender's mzID via SWUMzOwner.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: LAW_124:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3G0

## EXPECT
SEATCOUNT:3
P3BASEDMG:2
P2BASEDMG:0
P3GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SOR171_ChoosePlayer_Draws
#// Twin Suns Phase 3 / Group B: SOR_171 Mission Briefing "Choose a player. They draw 2 cards." In a
#// 3-player game the "You&Opponent" binary picker expands to You / P2 / P3. Choosing P3 makes P3 draw 2
#// (proving the picker reaches a specific opponent, not just the lone/first one). P3 starts with an empty
#// hand and a seeded deck.

## GIVEN
CommonSetup: rrw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_171
WithP1Resources: 3
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P3HANDCOUNT:2
P3DECKCOUNT:1

---

# TWI078_ChooseOpponent_DefeatUnits
#// Twin Suns Phase 3 / Group B: TWI_078 "Choose an opponent. Defeat each unit that player controls." In a
#// 3-player game the caster PICKS which opponent (via the SWUQueueChooseOpponent OPTIONCHOOSE). Choosing P3
#// defeats only P3's unit; P2's unit is untouched. (A bare "theirGroundArena" union would wrongly defeat
#// ALL opponents' units — this proves the choose-one-opponent semantics.)

## GIVEN
CommonSetup: bbk/grw/{myResources:15;handCardIds:TWI_078}
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:0

---

# TheirZone_UnionsOpponents
#// Twin Suns Phase 3: in an N-player game, a "choose an enemy ground unit" search (theirGroundArena)
#// spans ALL opponents. P1's search finds P2's 1 unit + P3's 2 units = 3 (the picked target's owner is
#// implied by its seat-specific mzID). In 2-player this is unchanged (single opponent).

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
ZONESEARCH:1:theirGroundArena:3
ZONESEARCH:2:theirGroundArena:2

---

# Ziro_WhenPlayed_EachOpponent
#// Twin Suns Phase 3: TWI_185 Ziro the Hutt "When Played: FOR EACH OPPONENT, you may exhaust a unit THAT
#// player controls." In a 3-player game this is a separate optional prompt per opponent, each scoped to that
#// opponent's own units. P1 plays Ziro, then exhausts P2's unit AND P3's unit (2-player fires one prompt).

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:TWI_185}
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P3GROUNDARENAUNIT:0:EXHAUSTED
