# Deployed_Action_PlayEventCheaper
#// LOF_013 Barriss Offee (deployed) — Action [use the Force]: play an event from your hand, costs 1
#// resource less. Barriss spends the Force and plays Confiscate (SOR_251, cost 1 -> 0); it fizzles with
#// no upgrades and goes to discard. NO self-exhaust on the deployed side (Force is the only cost).

## GIVEN
CommonSetup: byk/brk/{
  myLeader:LOF_013;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_013:1:0
WithP1Hand: SOR_251
WithP1Resources: 2

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:READY

---

# PlayEventDiscount
#// LOF_013 Barriss Offee — Action [Exhaust, use the Force]: Play an event from your hand. It costs 1 less.
#// P1 plays SOR_073 (Moment of Peace) which gives Plo Koon a Shield; the Force is spent.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_013;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_073
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NOFORCE

---

# EventCostingExactlyOneMoreThanReady
#// LOF_013 Barriss Offee — "Play an event, it costs 1 less." An event costing exactly (ready resources + 1)
#// is playable because the −1 discount is applied at the affordability gate. P1 has 2 ready; No Bargain
#// (SHD_244, Villainy cost 3) plays at cost 2 → 0 resources left. Force spent, Barriss exhausted.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:LOF_013;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SHD_244
WithP1Resources: 2
WithP2Hand: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Front_NoForce_CannotPlayEvent
#// LOF_013 Barriss Offee (front) — the Action is "[Exhaust, use the Force]: Play an event...". Without the
#// Force token the ability is unavailable: using it is a no-op — the event stays in hand, Barriss is not
#// exhausted, and no Force appears. Intended: "should not allow ... if he does not have the force" (undeployed).

## GIVEN
CommonSetup: yyk/bbk/{myLeader:LOF_013;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1LEADER:READY
P1NOFORCE

---

# Deployed_NoForce_CannotPlayEvent
#// LOF_013 Barriss Offee (deployed) — the deployed Action is "[use the Force]: Play an event...". Without the
#// Force token the unit ability is unavailable and is a no-op: the event stays in hand and Barriss stays
#// ready. Intended: "should not allow ... if he does not have the force" (deployed).

## GIVEN
CommonSetup: yyk/bbk/{myLeader:LOF_013;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_013:1:0
WithP1Hand: SOR_251
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:READY
P1NOFORCE

---

# Deployed_PlayEvent_WhileExhausted
#// LOF_013 Barriss Offee (deployed) — the deployed Action's only cost is the Force (no self-exhaust), so she
#// can play an event even while EXHAUSTED. Seated exhausted with the Force, she plays Confiscate (SOR_251,
#// cost 1 -> 0 at the discount) which fizzles to discard; the Force is spent, she remains exhausted. Intended: #// "should allow the controller to play an event ... even if Barriss Offee is exhausted".

## GIVEN
CommonSetup: yyk/bbk/{myLeader:LOF_013;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_013:0:0
WithP1Hand: SOR_251
WithP1Resources: 2

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_UnitInHand_UsesForceNoPlay
#// LOF_013 Barriss Offee (deployed) — CR 6.4.587.c: "use the Force" is the COST (a game-state change), so
#// the Action is usable with the Force even when only a unit (SOR_095, not an event) is in hand. It SPENDS
#// the Force and plays nothing (the effect plays an event only); the unit stays in hand, Barriss stays ready.
#// (Intended per CR: paying the cost is the state change, so the Action stays available and "chooses nothing".)

## GIVEN
CommonSetup: yyk/bbk/{myLeader:LOF_013;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_013:1:0
WithP1Hand: SOR_095
WithP1Resources: 5

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# Deployed_UsedTwiceInSamePhase
#// LOF_013 Barriss Offee (deployed) — the deployed Action costs ONLY "use the Force" (no exhaust), so it is
#// not once-per-phase: with a second Force token it fires again in the SAME phase. The base is Mystic
#// Monastery (LOF_022, "Action: create your Force token", up to 3× per game), which refills the Force
#// between uses. Two Confiscates (SOR_251, cost 1 → 0) are played, hand empties, both land in the discard,
#// and Barriss is still READY at the end — she never exhausts on this side.
## GIVEN
CommonSetup: byk/brk/{
  myLeader:LOF_013;
  myBase:LOF_022
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_013:1:0
WithP1Hand: SOR_251
WithP1Hand: SOR_251
WithP1Resources: 2
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>UseBaseAbility
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:2
