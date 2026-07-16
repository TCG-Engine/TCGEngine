# Deployed_OnAttack_DamageReadyAnother
#// SOR_011 Grand Inquisitor — Deployed: On Attack you MAY deal 1 damage to another friendly
#// unit with 3 or less power and ready it. GI (idx 1) attacks the base; the only other friendly
#// (a 3/3 at idx 0, exhausted) is chosen → takes 1 damage and is readied. Base takes GI's power 3.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# Deployed_OnAttack_Decline
#// SOR_011 Grand Inquisitor — Deployed: the On Attack damage-and-ready is optional ("you may").
#// Declining the MZMAYCHOOSE leaves the other friendly unit untouched (no damage, still exhausted);
#// the attack still deals its base damage.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# LeaderAction_DamageReady
#// SOR_011 Grand Inquisitor — Leader Action [Exhaust]: Deal 2 damage to a friendly unit with
#// 3 or less power and ready it. The one eligible 3/3 friendly (exhausted) takes 2 damage and
#// is readied.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED

---

# LeaderAction_KillsTarget_NoReady
#// SOR_011 Grand Inquisitor — "Deal 2 damage to a friendly unit with 3 or less power and ready it."
#// If the 2 damage DEFEATS the chosen unit (a 3/1), there's nothing left to ready — the unit is gone,
#// no crash, leader still pays its exhaust.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_180:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEligibleTarget_Fizzle
#// SOR_011 Grand Inquisitor — Leader Action targets "a friendly unit with 3 or less power".
#// The only friendly is a 4-power unit (ineligible), so the action fizzles: the leader still pays
#// its [Exhaust] cost but no unit is damaged and no decision is queued.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
