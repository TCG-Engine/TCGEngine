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
