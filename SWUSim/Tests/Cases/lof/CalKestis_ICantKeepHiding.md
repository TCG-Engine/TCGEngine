# DeployedOnAttack
#// LOF_015 Cal Kestis (deployed) — On Attack: an opponent chooses a ready unit they control; exhaust it. He
#// attacks the base; P2 picks SOR_046 from its two ready units to be exhausted.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# OpponentExhaustsUnit
#// LOF_015 Cal Kestis — Action [Exhaust, use the Force]: An opponent chooses a ready unit they control;
#// exhaust that unit. P1 uses the Force; P2 chooses SOR_046 (from its two ready units) to be exhausted.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NOFORCE

---

# LeaderAbility_NoReadyUnits_StillUsable
#// LOF_015 Cal Kestis (front) — the Action can still be used when the opponent has NO ready units: the cost
#// (exhaust + Force) is paid but there is simply no legal exhaust target, so no selection appears. P2's lone
#// unit is seeded exhausted. Intended: "can be used if opponent has no ready units".

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# LeaderAbility_NoForce_Unavailable
#// LOF_015 Cal Kestis (front) — the Action requires the Force. Without a Force token it is unavailable and
#// UseLeaderAbility is a no-op: Cal stays READY and no Force appears. (Low resources so the Epic deploy path
#// can't interfere.) Intended: "cannot be used if the player does not have the force".

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NOFORCE
P1NODECISION

---

# Deployed_NoReadyUnits_NoTrigger
#// LOF_015 Cal Kestis (deployed) — On Attack: an opponent chooses a ready unit to exhaust. If the opponent
#// has NO ready units the reaction does nothing (no selection). P2's lone unit is seeded exhausted; Cal
#// attacks the base for 3. Intended: "does not trigger if opponent has no ready units".

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
P1NODECISION

---

# TwinSuns_PickerOffersOnlyOpponentsWithAREADYUnit
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-24. Asserts the MENU (an outcome-only section cannot pin
#// eligibility; the harness does not validate OPTIONCHOOSE candidates).
#// OFFICIAL RULING (07/14/2025): "If there are multiple opponents, the controlling player chooses which
#// one will be 'an opponent.'"
#// ⚠ FILTER, and it is READY-ONLY — not "has any unit". The chosen player must "choose a READY unit they
#// control" (taxonomy shape 1 — they act on their own board), so an opponent whose entire board is
#// EXHAUSTED is just as unable to act as one with no units at all, and must not be offered.
#// ⚠ THE GATE AND SWUAfterAction STAY OUTSIDE THE PICKER: SWUQueueChooseOpponent queues NOTHING at zero
#//   eligible, so an after-action in the continuation would never fire and the leader Action would hang.
#//   That is the LeaderAbility_NoReadyUnits_StillUsable path, which must keep working.
#// Seats 2 and 3 each have a READY unit — TWO eligible opponents, which is what makes a menu exist at all
#// (at one eligible the picker correctly auto-resolves to an invisible PASSPARAMETER and there is nothing
#// to assert; the first attempt at this section failed for exactly that reason).
#// SEAT 4's only unit is EXHAUSTED, so seat 4 must NOT be offered — that is the ready-only discriminator.
#// ⚠ FIXTURE: `WithP1Force: true` is REQUIRED — Cal's front Action costs "use the Force (lose your Force
#//   token)", so without it UseLeaderAbility is a silent no-op and every assertion fails for an unrelated
#//   reason (see LeaderAbility_NoForce_Unavailable, which pins that path deliberately).
#// Mutation check: widen the filter to "any unit" and P1OPTIONNOT:P4 reds.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021
}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:0:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1
