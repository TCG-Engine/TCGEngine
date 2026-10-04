#// ACTION CLOSE WAITS FOR THE ACTOR'S OWN PICKS (2026-10-04).
#// An action closed — and passed the turn — as soon as the code path that started it returned, while picks were
#// still queued on the ACTING player's own queue: a bounty's "Collect bounty?", DJ's resource pick, Tobias
#// Beckett's unit pick (queued by his opponent-pick continuation, behind his queued close). Other seats could not
#// act (AllQueuesEmpty), but the turn indicator showed the next seat, whose clicks bounced off "decisions are
#// pending" (the Plan-counter report, 2026-09-22), and every action-scoped reset (log source, damage source,
#// CR 7.6.11 layers) ran before the picks resolved. Found by a full-suite probe: 164 closes with a pick pending.
#// SWUAfterAction now re-queues the close behind the actor's pending picks.
#// ⚠ No P1OnlyActions anywhere here: it makes TURNPLAYER unobservable.

# Bounty_TurnStaysWhileCollectBountyIsPending
#// JTL_069 attacks SHD_195 (a bounty unit) and defeats it: P1 is asked "Collect bounty?".

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_195:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1HASDECISION
TURNPLAYER:1

---

# Bounty_TurnPassesOnceAfterTheBountyResolves

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_195:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# DJ_TurnStaysWhileTheResourcePickIsPending
#// SHD_213 DJ smuggled: "take control of an enemy resource" — P2 has two, so it is a real pick.

## GIVEN
CommonSetup: yyw/yyw
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7

## EXPECT
P1GROUNDARENACOUNT:1
P1HASDECISION
TURNPLAYER:1

---

# DJ_TurnPassesOnceAfterTheResourcePick

## GIVEN
CommonSetup: yyw/yyw
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# Tobias_TurnStaysWhileTheUnitPickIsPending
#// LAW_002 front: the opponent pick is a PASSPARAMETER at two seats; its continuation then queues the unit pick
#// (two friendly units, so a real one) BEHIND the close the leader ability had already queued.

## GIVEN
CommonSetup: yyw/grw/{myLeader:LAW_002;myBase:SOR_028}
SkipPreGame: true
WithGamePhase: ActionPhase
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# Tobias_TurnPassesOnceAfterTheUnitIsGiven

## GIVEN
CommonSetup: yyw/grw/{myLeader:LAW_002;myBase:SOR_028}
SkipPreGame: true
WithGamePhase: ActionPhase
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# Control_AnActionWithNoPicksPassesTheTurnAtOnce
#// A plain base attack queues nothing: the close is not deferred, and the turn passes exactly once.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION
