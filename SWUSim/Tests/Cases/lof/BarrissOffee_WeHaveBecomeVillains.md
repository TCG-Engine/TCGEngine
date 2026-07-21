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
#// exhausted, and no Force appears. Ref: "should not allow ... if he does not have the force" (undeployed).

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
#// ready. Ref: "should not allow ... if he does not have the force" (deployed).

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
#// cost 1 -> 0 at the discount) which fizzles to discard; the Force is spent, she remains exhausted. Ref:
#// "should allow the controller to play an event ... even if Barriss Offee is exhausted".

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

# Deployed_UnitInHand_ActionUnavailable
#// LOF_013 Barriss Offee (deployed) — the Action plays an EVENT. With the Force but only a unit in hand
#// (SOR_095 Battlefield Marine, not an event) there is no playable target, so the ability is unavailable and
#// using it is a no-op: the Force is retained and the unit stays in hand. (Guards the Event-only target gate.)

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
P1HASFORCE
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:READY
