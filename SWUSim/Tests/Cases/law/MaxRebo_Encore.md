# AdditionalRegroupPhase
#// LAW_072 Max Rebo — "There is an additional regroup phase after the first regroup phase each round."
#// While Max Rebo is in play there are TWO regroup phases: both players draw 2 cards in EACH, so each
#// player draws 4 total this round (a normal single regroup draws only 2). The round still advances just
#// once (the round counter increments on the final regroup, not per regroup phase).
#// Both players start with empty hands; after both regroups (declining each resource step) each has 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:4
P2HANDCOUNT:4
PHASE:MAIN

---

# BothPlayers_Stacks
#// LAW_072 Max Rebo — the "additional regroup phase" effect STACKS: each Max Rebo in play adds one
#// additional regroup phase. With BOTH players controlling a Max Rebo there are 2 additional regroups
#// (3 total), so each player draws 2 per regroup → 6 total. The round still advances only once (on the
#// final regroup). The 6-ResourcePass sequence reaches the next action phase (MAIN).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:1:0
WithP2GroundArena: LAW_072:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:6
P2HANDCOUNT:6
PHASE:MAIN

---

# NoMaxRebo_SingleRegroup_Control
#// LAW_072 control — WITHOUT Max Rebo in play, there is exactly ONE regroup phase: each player draws 2.
#// Same setup as MaxRebo072_AdditionalRegroupPhase but with a vanilla unit instead of Max Rebo, so only
#// one regroup runs (one resource step per player) and each player ends with 2 cards.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
PHASE:MAIN

---

# StartOfRegroupTriggersFireInAdditionalRegroup
#// LAW_072 Max Rebo — abilities that trigger "at the start of the regroup phase" fire in the ADDITIONAL
#// regroup phase too, not just the first. Fireball (JTL_198) deals 1 damage to itself at the start of
#// each regroup phase. With Max Rebo in play there are two regroup phases this round, so Fireball takes
#// 1 damage in the first and 1 more in the additional = 2 damage total (it would take only 1 without the
#// extra regroup).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:1:0
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:2
PHASE:MAIN

---

# LeavesPlayDuringTheFirstRegroup_NoAdditionalRegroup
#// LAW_072 Max Rebo — the additional regroup is granted by a Max Rebo that is still in play when the
#// first regroup ENDS, so one that leaves play during it grants nothing. Sneak Attack (SOR_219) plays Max
#// Rebo cheaply and carries a delayed "at the start of the regroup phase, defeat it": he is discarded as
#// regroup 1 opens, so there is exactly ONE regroup and each player draws 2, not 4.
#// Discriminating against AdditionalRegroupPhase, which is the same board with a surviving Max Rebo and
#// ends on 4 cards each. Both Sneak Attack and Max Rebo end in P1's discard.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021;myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_219 LAW_072]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
PHASE:MAIN

---

# ENTERSPlayDuringTheFirstRegroup_StillGrantsTheAdditionalRegroup
#// LAW_072 Max Rebo — the mirror of the above, and the reason the check is at the END of the first
#// regroup rather than its start. P2 plays Arrest (SEC_195) so their base captures Max Rebo; Arrest's
#// delayed "at the start of the regroup phase, its owner rescues it" puts him back on P1's board as
#// regroup 1 opens. He is therefore in play when it ends, the additional regroup happens, and each player
#// draws 4.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021;myResources:10;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: LAW_072
WithP2Hand: SEC_195
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:4
P2HANDCOUNT:4
P1GROUNDARENACOUNT:1
PHASE:MAIN

---

# NextRegroupLastingEffect_CoversOnlyTheFIRSTRegroup
#// LAW_072 Max Rebo + SEC_137 Dryden Vos — "this unit doesn't ready during the NEXT regroup phase" must
#// bind to regroup 1 alone, not to every regroup this round. Dryden attacks P2's base with his power
#// doubled (2 -> 4 damage) and takes the no-ready rider.
#// This section is the MIDPOINT: it stops inside regroup 2's resource step (PHASE:RES, and hand 4 shows
#// both draw steps have run). Dryden is still EXHAUSTED — his rider ate regroup 1's ready — while the
#// control SOR_095, seated exhausted and carrying no rider, HAS readied.
#// The control is what makes this discriminating: without it, "exhausted" is also what you would see
#// before any ready step at all.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [SEC_137:1:0 SOR_095:0:0]
WithP2GroundArena: LAW_072:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:RES
P1HANDCOUNT:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P2BASEDMG:4

---

# NextRegroupLastingEffect_ExpiresSoDrydenReadiesInTheADDITIONALRegroup
#// LAW_072 Max Rebo + SEC_137 Dryden Vos — the other half of the pair. Running on to the end of the
#// ADDITIONAL regroup, Dryden is READY: the "next regroup phase" rider was spent on regroup 1 and did not
#// carry into regroup 2. Same board and same script as the midpoint section, two resource steps further on.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [SEC_137:1:0 SOR_095:0:0]
WithP2GroundArena: LAW_072:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1HANDCOUNT:4
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P2BASEDMG:4

---

# ThisRegroupPhaseEffect_BindsToTheFIRSTRegroup_Midpoint
#// LAW_072 Max Rebo + LAW_073 Patient Hunter — "give an Experience token to a non-leader unit. If you do,
#// that unit can't ready during THIS regroup phase." Both units start exhausted. In regroup 1 P2 aims the
#// rider at P1's Max Rebo.
#// MIDPOINT, stopped as regroup 2 opens (PHASE:RGS, hand 2 = only regroup 1's draw has run): Max Rebo is
#// still EXHAUSTED, holding the Experience that carried the restriction, while Patient Hunter — which
#// carried no rider — has readied normally.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:0:0
WithP2GroundArena: LAW_073:0:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P2>AnswerDecision:theirGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:RGS
P1HANDCOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ThisRegroupPhaseEffect_DoesNotCarryIntoTheADDITIONALRegroup
#// LAW_072 Max Rebo + LAW_073 Patient Hunter — the payoff. Patient Hunter triggers AGAIN in the additional
#// regroup (it is a "when the regroup phase starts" ability, so the extra phase gets its own trigger) and
#// this time P2 aims it at Patient Hunter itself. At the end: Max Rebo has READIED, because the regroup-1
#// restriction bound to that phase only and did not follow him into regroup 2.
#// Patient Hunter is also ready, and that is correct rather than a miss: it had ALREADY readied in
#// regroup 1, and "can't ready" prevents a readying — it does not exhaust a unit that is already ready.
#// It still holds the Experience it gave itself, so the rider did resolve.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:0:0
WithP2GroundArena: LAW_073:0:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P2>AnswerDecision:theirGroundArena-0
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1HANDCOUNT:4
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# StackingCountsOnlyTheMaxRebosSTILLInPlayWhenTheFirstRegroupEnds
#// LAW_072 Max Rebo — the stacking rule is "one additional regroup per Max Rebo in play at the END of the
#// first regroup", so a Max Rebo that dies on the way there contributes nothing. Both players have one:
#// P1's arrives via Sneak Attack (SOR_219) and is defeated as regroup 1 opens, P2's survives. Exactly ONE
#// additional regroup is granted, so each player draws 4 — not the 6 that two live Max Rebos produce.
#// Discriminating in both directions: BothPlayers_Stacks is this board with both surviving and ends on 6,
#// and LeavesPlayDuringTheFirstRegroup_NoAdditionalRegroup is this board with neither and ends on 2.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021;myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_219 LAW_072]
WithP2GroundArena: LAW_072:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:4
P2HANDCOUNT:4
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
PHASE:MAIN

---

# RoundDurationEffectSurvivesTheADDITIONALRegroup
#// LAW_072 Max Rebo + JTL_018 Kazuda Xiono + JTL_216 Contracted Hunter — a round is "action phase →
#// regroup phase", and Max Rebo makes it "action phase → regroup → regroup", still ONE round. So a
#// "for this round" effect must cover EVERY regroup in it.
#// Kazuda's Action blanks Contracted Hunter ("When the regroup phase starts: defeat this unit") for the
#// round. The Hunter must therefore survive BOTH regroups and still be in play in the next action phase.
#// BUG THIS PINS: the round-duration expiry lived inside RegroupPhaseStart with no guard, so it fired at
#// the start of regroup 1 and the blank was already gone by regroup 2 — the Hunter self-defeated in the
#// additional regroup. Fixed by deferring the expiry to the round's FINAL regroup, the same
#// _SWUNeedsExtraRegroup() guard the deferred round increment in ReadyPhase already used.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;theirLeader:JTL_018;myBase:SOR_021;theirBase:SOR_021;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_072:1:0
WithP2GroundArena: JTL_216:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P2>UseLeaderAbility
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P2GROUNDARENACOUNT:1
P1HANDCOUNT:4

---

# WithoutTheRoundBlank_TheHunterStillSelfDefeats
#// LAW_072 Max Rebo + JTL_216 Contracted Hunter — the control for the section above. Identical board, but
#// Kazuda's Action is never used, so nothing blanks the Hunter and its "when the regroup phase starts:
#// defeat this unit" resolves on the first regroup as normal.
#// This is what makes the pair discriminating: it proves the survival above comes from the round-duration
#// blank and not from the additional regroup somehow skipping the trigger.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;theirLeader:JTL_018;myBase:SOR_021;theirBase:SOR_021;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LAW_072:1:0
WithP2GroundArena: JTL_216:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P2GROUNDARENACOUNT:0

---

# DelayedRegroupEffectFiresOnlyInTheFIRSTRegroup
#// LAW_072 Max Rebo + SHD_203 Zorii Bliss — a DELAYED effect scheduled for "the regroup phase" is a
#// one-shot: it resolves in the next regroup and is spent, unlike a "when the regroup phase starts"
#// ABILITY (Patient Hunter above), which re-triggers in every regroup.
#// Zorii attacks P2's base for 4 and draws a card, scheduling "at the start of the regroup phase, discard
#// a card from your hand". Regroup 1 opens with that discard prompt; regroup 2 must NOT raise it again.
#// The arithmetic is the discriminator: +1 from Zorii's draw, -1 discarded, then +2 and +2 from the two
#// regroup draws = hand 4 with exactly 1 card in the discard. A delayed effect that re-armed per regroup
#// would land on hand 3 / discard 2.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SHD_203:1:0
WithP2GroundArena: LAW_072:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P2>Pass
- P1>AnswerDecision:myHand-0
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1HANDCOUNT:4
P1DISCARDCOUNT:1
P2BASEDMG:4
