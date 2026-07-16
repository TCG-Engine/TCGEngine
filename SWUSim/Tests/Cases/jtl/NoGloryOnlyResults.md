# OnChewbacca103Piloted_FriendlyDefeat
#// JTL_043 No Glory, Only Results vs a ship piloted by JTL_103 Chewbacca — the Chewbacca pilot grants the
#// host "can't be defeated by enemy card abilities." No Glory takes control of the ship FIRST, so the
#// defeat is friendly and the immunity does not apply: the ship is defeated. Both the ship AND the
#// Chewbacca pilot upgrade land in their owner (P2)'s discard (discard +2) — defeated cards always go to
#// their own owner's discard, never the controller's.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2

---

# OnChewbacca103_FriendlyDefeat
#// JTL_043 No Glory, Only Results vs JTL_103 Chewbacca — "This unit can't be defeated by enemy card
#// abilities." No Glory takes control FIRST, so by the time it defeats Chewbacca he's friendly to P1 —
#// the immunity (which only blocks ENEMY defeats) no longer applies, and he is defeated to P2's discard.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# OnRey149_TakeControlBlocked
#// JTL_043 No Glory, Only Results vs LAW_149 Rey — "Opponents can't take control of this unit." No Glory
#// must take control BEFORE it defeats, so when the take-control is blocked there is no friendly unit to
#// defeat — the whole effect fizzles and Rey stays under P2's control, undamaged and in play.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_149
P2DISCARDCOUNT:0

---

# TakeControlDefeat
#// JTL_043 No Glory, Only Results — Take control of a non-leader unit, then defeat it. P1 targets P2's
#// SOR_046: it is taken and defeated, landing in its owner (P2)'s discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1Resources: 13
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# TargetFriendlyUnit_DefeatsAndFiresWhenDefeated
#// JTL_043 No Glory, Only Results — "Take control of a non-leader unit, then defeat it" can target a
#// FRIENDLY unit (taking control of your own is a no-op, then you defeat it). P1 targets its own OOM-Series
#// Officer (TWI_131, "When Defeated: deal 2 to a base"); it is defeated and its When Defeated deals 2 to
#// P2's base.

## GIVEN
CommonSetup: brk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: TWI_131:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:2
P1DISCARDCOUNT:2

---

# PlayedWithNoValidTarget
#// JTL_043 No Glory, Only Results can be played even with no legal target (no non-leader units in play):
#// the opponent controls only a deployed leader and P1 controls nothing, so the event simply resolves with
#// no effect and goes to P1's discard.

## GIVEN
CommonSetup: brk/rrk/{myResources:6;theirLeader:JTL_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_043
