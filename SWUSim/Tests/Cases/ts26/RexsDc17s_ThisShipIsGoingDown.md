# ReadyHostOnEnemyReady
#// TS26_63 Rex's DC-17s (Upgrade +3/+2) — attached unit gains "When an enemy unit readies during the
#// action phase: ready this unit (once each round)." Chaotic Diversion readies the exhausted enemy SOR_128,
#// which readies the host SEC_080 (wearing the DC-17s).
## GIVEN
CommonSetup: ryk/rrk/{myResources:1;handCardIds:TS26_31}
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:TS26_63
WithP2GroundArena: SOR_128:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:READY

---

# AttachesOnlyToNonVehicleUnits
#// TS26_63 Rex's DC-17s — "Attach to a non-Vehicle unit", with no friendly-only clause: the friendly
#// SOR_095 and the enemy Wampa are both offered while the friendly Vehicle ASH_261 is not.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_63
WithP1GroundArena: [SOR_095:1:0 ASH_261:1:0]
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# UseOnlyOnceEachRound
#// TS26_63 Rex's DC-17s — "Use this ability only once each round." The first Chaotic Diversion readies an
#// enemy and readies the host, who then attacks P2's base for 6 (3 printed + 3 from the upgrade) and is
#// exhausted. A second Chaotic Diversion readies the other enemy, but the DC-17s is spent for the round,
#// so the host stays exhausted and the base takes nothing more.

## GIVEN
CommonSetup: ryk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_31 TS26_31]
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:TS26_63
WithP2GroundArena: [SOR_128:0:0 SOR_046:0:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:6

---

# AnAlreadyREADYHostDoesNotSpendTheUse
#// TS26_63 Rex's DC-17s — the once-per-round use is spent by an actual ready, not by the window opening.
#// Here the host starts READY, so the first enemy ready does nothing; the host attacks (exhausting), and
#// the SECOND enemy ready still finds the ability available and readies him.
#// Discriminating against UseOnlyOnceEachRound, which is the identical script with an exhausted host and
#// ends the other way round.

## GIVEN
CommonSetup: ryk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_31 TS26_31]
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_63
WithP2GroundArena: [SOR_128:0:0 SOR_046:0:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:6

---

# DoesNotTriggerOnTheREGROUPReadyStep
#// TS26_63 Rex's DC-17s — the window is "during the ACTION phase". Passing out the phase readies every
#// unit at regroup, including the enemy SOR_128, but that mass ready is not the trigger: both units come
#// back ready by the regroup step itself and the upgrade raises no decision of its own.

## GIVEN
CommonSetup: ryk/rrk/{myResources:2}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:TS26_63
WithP2GroundArena: SOR_128:0:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# HostUnderACANNOTREADYEffectStaysExhausted
#// TS26_63 Rex's DC-17s — the grant readies the host, and readying is still subject to whatever says it
#// can't. The host also wears Frozen in Carbonite (SHD_193, "attached unit can't ready"), so when Chaotic
#// Diversion readies the enemy SOR_128 the host stays exhausted. Its third upgrade is the Shield that
#// Chaotic Diversion's second half hands out.
#// Discriminating: the grant readied by writing Status directly, which skipped every can't-ready check.

## GIVEN
CommonSetup: ryk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_31
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:TS26_63
WithP1GroundArenaUpgrade: 0:SHD_193
WithP2GroundArena: SOR_128:0:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
