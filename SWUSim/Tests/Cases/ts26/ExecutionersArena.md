# EpicDeal2PerLeaderUnit
#// TS26_11 Executioner's Arena (Base, Aggression) — Epic Action: for each friendly leader unit, you may
#// deal 2 damage to a unit. With one deployed leader unit, deal 2 to the enemy LAW_124.
## GIVEN
CommonSetup: rrk/rrk/{myBase:TS26_11;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASE:EPICUSED

---

# NoFriendlyLeaderUnitsMeansNoDamage
#// TS26_11 Executioner's Arena — "FOR EACH friendly LEADER UNIT, you may deal 2 damage to a unit". With
#// the leader undeployed there is nothing to iterate, so the Epic Action raises no offer and LAW_124 is
#// untouched.

## GIVEN
CommonSetup: rrk/rrk/{myBase:TS26_11}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# BothInstancesIntoOneShieldedUnit_OneInstance_ShieldStopsAll
#// CR 8.34.1 / 8.34.1.a: "For each friendly leader unit, you may deal 2 damage to a unit" — choose every instance
#// first, then deal all damage to a target as ONE instance. P1 has two friendly leader units (the deployed leader and
#// SOR_095 made a leader by ASH_135 The Darksaber) and aims both at P2's Shielded SOR_046: one instance of 4, which the
#// Shield prevents entirely. (Dealt as two 2s, the Shield stopped the first and the second got through.)
## GIVEN
CommonSetup: rrk/rrk/{myBase:TS26_11;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASE:EPICUSED
