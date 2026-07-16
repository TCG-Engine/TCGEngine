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
