# ConditionNotMet_Fizzle
#// TWI_040 A Fine Addition — the condition ("If an enemy unit was defeated this phase") is NOT met, so the
#// event fizzles: no upgrade is played, the upgrade stays in hand, the friendly unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# Decline
#// TWI_040 A Fine Addition — the play is a "may": P1 declines (answers "-"), so nothing is played, the
#// upgrade stays in hand, and the unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1

---

# IgnoresAspectPenalty
#// TWI_040 A Fine Addition — "ignoring its aspect penalty": SOR_120 (Command, base cost 2) is fully
#// off-aspect under an Aggression/Villainy board. With only 2 ready resources it is affordable ONLY because
#// the aspect penalty is waived (unignored it would cost 4 and could not be offered → the event would
#// fizzle and POWER would stay 3). It attaches, spending exactly its base cost (2 → 0 resources).
## GIVEN
CommonSetup: brk/bbw/{myResources:2;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:0

---

# PilotFromHand
#// TWI_040 A Fine Addition — a PILOT can be played this way (user-confirmed ruling: A Fine Addition plays
#// from a known zone, no "search for an upgrade" clause, so pilots qualify — unlike Reforge). JTL_046
#// (Piloting [2], +2/+2) attaches as a Pilot to the only friendly Vehicle (SOR_237 Alliance X-Wing 2/3 →
#// 4/5). Vigilance/Heroism is fully off-aspect here, so it is affordable at cost 2 (from 3) ONLY because
#// the aspect penalty is ignored (unignored it would be 6).
## GIVEN
CommonSetup: brk/bbw/{myResources:3;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: JTL_046
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
P1RESAVAILABLE:1

---

# UpgradeFromHand
#// TWI_040 A Fine Addition — core: after defeating an enemy this phase (P1's Marine kills the 3/1 Trooper),
#// play a regular Upgrade from hand. SOR_120 Academy Training (+2/+2, cost 2, Command) attaches to the only
#// friendly unit (auto-resolved host). Command is off-aspect under an Aggression/Villainy board, but the
#// aspect penalty is IGNORED, so it costs 2 (6→4 resources), not 4.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:4

---

# UpgradeFromOpponentDiscard
#// TWI_040 A Fine Addition — "from any player's discard pile" includes the OPPONENT's discard. SOR_120 is
#// in P2's discard; P1 plays it from there onto its own unit. (The upgrade is still owned by P2 for later
#// discard routing, but the play + attach are what matter here.)
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;theirDiscardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4

---

# UpgradeFromOwnDiscard
#// TWI_040 A Fine Addition — "from any player's discard pile": play an Upgrade out of your OWN discard.
#// SOR_120 sits in P1's discard; after the kill, P1 plays it from discard onto the Marine.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4

---

# EnemyDefeatedItsOwnUnit_ConditionMet
#// TWI_040 — "If AN ENEMY UNIT was defeated this phase" is EXISTENTIAL, not "if you defeated one".
#// P2 defeats their own Battle Droid token as TWI_182's Exploit 1 cost; P1 never attacks. The condition
#// is met, so P1's A Fine Addition plays SOR_120 from hand (aspect penalty ignored → cost 2).
#//
#// ⚠ Every other section here defeats the enemy via P1's own attack — the one path that stamps
#// SWU_ENEMY_DEFEATED (it is guarded by `$owner !== $player`, CombatLogic 811/848). A self-defeat by
#// the opponent stamps it on nobody, so none of them could observe this. Two seats suffice.

## GIVEN
CommonSetup: brk/yyk/{myResources:6;handCardIds:TWI_040}
WithActivePlayer: 2
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2Hand: TWI_182
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4

---

# MixedPool_PickTheZoneFirst
#// TWI_040 A Fine Addition — when the candidates span MORE THAN ONE zone, the player first picks WHICH zone
#// ("your hand or any player's discard pile"), then the card from that zone alone. A mixed pool could not be
#// answered in the browser: a discard pile draws only its latest card, so a buried candidate had nothing to
#// click (Discord 2026-10-03: "doesn't work with other players' discard pile"). One zone at a time is always
#// pickable. Here SOR_120 is in P1's hand AND P2's discard → the zone menu offers both, nothing else.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_040 SOR_120]
WithP2Discard: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1OPTIONHAS:Your_Hand
P1OPTIONHAS:Opponent's_Discard
P1OPTIONNOT:Your_Discard

---

# MixedPool_OpponentDiscardChosen_OnlyThatPileOffered
#// Choosing the opponent's discard narrows the card pick to that pile alone — the hand copy is NOT offered.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_040 SOR_120]
WithP2Discard: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:Opponent's_Discard
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirDiscard-0

---

# MixedPool_OpponentDiscardChosen_Plays
#// …and picking it plays the upgrade from P2's discard: attached to P1's only unit, P1's hand copy untouched.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_040 SOR_120]
WithP2Discard: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:Opponent's_Discard
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P2DISCARDCOUNT:1
P1RESAVAILABLE:4

---

# MixedPool_HandChosen_OnlyHandOffered
#// Choosing the hand narrows the pick to the hand — the discard copy is NOT offered.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_040 SOR_120]
WithP2Discard: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:Your_Hand
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0

---

# MixedPool_DeclineAfterPickingAZone
#// The play stays a "may": after choosing a zone the player can still decline the card pick.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_040 SOR_120]
WithP2Discard: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:Your_Hand
- P1>AnswerDecision:-
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# TwinSuns3P_PickWhichOpponentsDiscard
#// Twin Suns: each opponent's discard is its own zone, named by seat. SOR_120 is in P2's AND P3's discard;
#// P1 picks P3's pile and plays from it. (A P3 pile off the current view had no UI at all before.)
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: TWI_040
WithP3Base: SOR_021:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Discard: SOR_120
WithP3Discard: SOR_120
## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:P3's_Discard
## EXPECT
P1SELECTABLEEXACT:p3Discard-0

---

# TwinSuns4P_PickWhichOpponentsDiscard_Plays
#// The same on four seats (Twin Suns tests need 3P AND 4P): the menu names every opponent pile that holds a
#// candidate, and the pick plays from P4's discard — P2's and P3's piles are untouched.
## GIVEN
CommonSetup: brk/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: TWI_040
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP3Discard: SOR_120
WithP4Discard: SOR_120
## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:P4's_Discard
- P1>AnswerDecision:p4Discard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P3DISCARDCOUNT:1
P4DISCARDCOUNT:0
