# RegroupDefeat
#// JTL_216 Contracted Hunter — When the regroup phase starts: Defeat this unit. P1 passes to end the
#// action phase; at regroup start the Hunter is defeated and goes to the discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_216:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# Ambush_OnPlay
#// JTL_216 Contracted Hunter has Ambush — played from hand (no Unit/Pilot prompt) it may immediately attack
#// an enemy unit. It hits P2's SOR_046 (3/7) for 4 and takes the 3 counter, ending exhausted.

## GIVEN
CommonSetup: yyk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: JTL_216
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_216
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NotDefeated_AbilityNullifiedForRound
#// JTL_216 Contracted Hunter — his "When the regroup phase starts: defeat this unit" is an ABILITY. When
#// it is nullified for the whole ROUND (a blank whose duration lasts through the regroup phase), the
#// self-defeat does NOT fire. P1's leader JTL_018 Kazuda Xiono's Action [Exhaust] makes a friendly unit
#// lose all abilities for THIS ROUND; P1 targets the friendly Contracted Hunter. Both players pass to end
#// the action phase, and at regroup start the (blanked) self-defeat never triggers, so the Hunter survives
#// into the next round (ground count stays 1). Intended behavior: not defeated when nullified for the round.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_216:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_216

---

# Defeated_AbilityNullifiedForPhase
#// JTL_216 Contracted Hunter — when his self-defeat ability is nullified only for the PHASE (a blank that
#// expires at the end of the action phase, BEFORE regroup), the ability is active again by the time the
#// regroup phase starts, so the Hunter IS defeated. P1 plays SOR_138 Force Lightning on the lone unit
#// (his own Contracted Hunter): "It loses all abilities for this phase." P1 controls no Force unit, so the
#// pay-and-damage half no-ops. P1 passes; the phase-blank expires at end of action phase, and at regroup
#// start the self-defeat fires — the Hunter goes to the discard (alongside the spent Force Lightning event,
#// so discard = 2). Intended behavior: defeated when nullified only for the phase.

## GIVEN
CommonSetup: rrk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: JTL_216:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
