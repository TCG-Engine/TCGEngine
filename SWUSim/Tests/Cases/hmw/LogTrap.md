# AttacksBase_ThenSameUnitAttacksAUnit
#// COVERAGE: offer=FirstAttackerOffer_ReadyUnitsWithTargetsOnly + SecondAttack_TargetPool_UnitsOnly_NoBase
#//           decline=N/A (no "may"; both attacks are mandatory) · boundary=N/A (no threshold)
#//           negative=OtherUnitStaysReady_OnlyTheChosenUnitAttacksTwice, AttackerDefeated_NoSecondAttack,
#//           FirstAttackKilledTheOnlyUnit_NoSecondAttack_StaysExhausted, NoUnitCanAttack_EventDoesNothing
#//           reqboundary=SurvivesRequestBoundary · ordering=NestedLeiaChain_ResolvesBeforeTheSecondAttack
#//           arena=SpaceUnit_AttacksTwiceInSpace · turn=TurnPassesOnceAfterBothAttacks
#//           control=N/A for the pick (own arenas only, as every "attack with a unit" event); the second attack
#//           re-checks control — StolenMidway is not reachable from a fixture (no mid-attack control change card
#//           cheaply available), covered by the controller guard in _SWUHmw149SecondAttack.
#//           modes=2P,TwinSuns — TwinSuns_SecondAttack_PoolSpansEveryOpponent ("friendly" is read as units
#//           you control, the same as every other attack-with event; attacking with a teammate's unit is not
#//           a Team Suns action)
#//
#// HMW_149 Log Trap — Event, cost 2, [Command], Trick.
#// "Attack with a friendly unit. Then attack with it again, even if it's exhausted. It can't attack bases for
#//  the second attack."

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1DISCARDCOUNT:1
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# FirstAttackerOffer_ReadyUnitsWithTargetsOnly
#// Pool: ready units that can attack. Excluded: an exhausted unit (SOR_063) — the "even if exhausted" wording
#// belongs to the SECOND attack only.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_063:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# SecondAttack_TargetPool_UnitsOnly_NoBase

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# OtherUnitStaysReady_OnlyTheChosenUnitAttacksTwice

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# AttackerDefeated_NoSecondAttack
#// SOR_095 (3/3) attacks SOR_046 (3/7) and dies to the counter-damage: "it" is gone, nothing attacks again.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# FirstAttackKilledTheOnlyUnit_NoSecondAttack_StaysExhausted
#// The first attack defeats the only enemy unit; the second attack has no legal (non-base) target, so it
#// never begins — and the attacker is NOT readied by the engine's no-target rollback.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# NoUnitCanAttack_EventDoesNothing

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SurvivesRequestBoundary

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SpaceUnit_AttacksTwiceInSpace

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P2SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# TurnPassesOnceAfterBothAttacks

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
TURNPLAYER:2
NOEXTRAACTION

---

# FizzledSecondAttack_TurnStillPasses

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
TURNPLAYER:2
NOEXTRAACTION

---

# NestedLeiaChain_ResolvesBeforeTheSecondAttack
#// SOR_009 Leia (deployed, Raid 1) is the Log Trap attacker. Her "When this unit completes an attack: you may
#// attack with another Rebel unit" belongs to the FIRST attack, so it resolves before Log Trap's second attack:
#// Leia → base (4), Marine → base (3), then Leia again → the enemy Marine (4, defeated). The enemy has two units
#// so Log Trap's second attack is a real target choice: run in the wrong order, the answers land on the wrong
#// prompts.
#// STRUCTURAL (mutation-checked 2026-09-16): the resume branch's position relative to SWU_CHAINED_ATTACK is
#// not load-bearing — every "when this unit completes an attack: attack with another unit" (SOR_009, SEC_006)
#// queues its attack straight from the trigger via SWUQueueAnotherAttack, so it always resolves inside the
#// first attack's trigger resolution. The var is armed only by events/actions that START their own attack,
#// which cannot be mid-resolution alongside Log Trap. What this section does pin: the second attack waits for
#// the first attack's triggered attack to finish.

## GIVEN
CommonSetup: ggw/brw/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: HMW_149
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2BASEDMG:7
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P1NODECISION

---

# TwinSuns_SecondAttack_PoolSpansEveryOpponent

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: HMW_149
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Base-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-0
