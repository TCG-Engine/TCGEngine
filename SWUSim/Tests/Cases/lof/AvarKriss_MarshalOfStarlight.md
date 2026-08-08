# CreateForce
#// LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). P1 starts without
#// the Force and gains it.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_007;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASFORCE
P1LEADER:EXHAUSTED

---

# Deployed_Passive_ForceBuff
#// LOF_007 Avar Kriss (deployed, 4/10) — passive: while the Force is with you, this unit gets +4/+0
#// and gains Overwhelm. With the Force → 8 power + Overwhelm.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:LOF_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm


---

# Deployed_NoForce_NoBuff
#// LOF_007 Avar Kriss (deployed, 4/10) — the +4/+0 and Overwhelm are conditioned on a LIVE PlayerHasTheForce
#// read. Without the Force token she is a plain 4-power unit with no Overwhelm (complements the with-Force
#// section; also covers that she does nothing when the player does not have the Force).

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:LOF_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# LeaderAbility_TriggerableWhenAlreadyHasForce
#// LOF_007 Avar Kriss (front) — the "Action [Exhaust]: The Force is with you" ability is triggerable EVEN
#// when P1 already holds the Force token. The token is idempotent (still exactly one), but the action still
#// resolves and exhausts her. Intended: "should be triggerable even if the player already has the Force".

## GIVEN
CommonSetup: ggw/bbk/{myLeader:LOF_007;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASFORCE
P1LEADER:EXHAUSTED

---

# EpicDeploy_NineResources
#// LOF_007 Avar Kriss — Epic Action: "If (resources you control) + (times you used the Force this phase) is
#// 9 or more, deploy this leader." With 9 resources (and 0 Force-uses) the sum reaches her printed cost 9, so
#// she deploys — flips, readies, and moves to the ground arena. Intended: "deploy epic action should work when the
#// player controls 9 resources".

## GIVEN
CommonSetup: ggw/bbk/{myLeader:LOF_007;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_007

---

# EpicDeploy_EightResources_Blocked
#// LOF_007 Avar Kriss — complement to the 9-resource case: with only 8 resources (and 0 Force-uses) the sum
#// 8 is below her deploy threshold 9, so the Epic Action does nothing — she stays in leader form and the
#// ground arena is empty. (Mirrors the intended per-phase Force-use gating; the seedable half is the resource count.)

## GIVEN
CommonSetup: ggw/bbk/{myLeader:LOF_007;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:READY

---

# Deployed_ForceBuff_OverwhelmDamageThrough
#// LOF_007 Avar Kriss (deployed) — with the Force she is 8 power and has Overwhelm. Attacking a 3/3
#// Battlefield Marine (SOR_095) defeats it (3 hp) and the 5 excess power spills onto the enemy base via
#// Overwhelm. Intended: "+4/+0 and Overwhelm when the player has the Force" (asserts the 5 damage to base).

## GIVEN
CommonSetup: ggw/brk/{myLeader:LOF_007:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# Deployed_ForceGainedDynamically_BuffOn
#// LOF_007 Avar Kriss (deployed) — the +4/+0 & Overwhelm are a LIVE read of PlayerHasTheForce. Starting with
#// no Force she is 4 power; using the Mystic Monastery (LOF_022) base Action to create the Force token flips
#// the buff on to 8 power + Overwhelm. Intended: "should work when the Force is gained and lost" (gain half).

## GIVEN
CommonSetup: ggw/brk/{myLeader:LOF_007:1:1:1;myBase:LOF_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Deployed_ForceLostDynamically_BuffOff
#// LOF_007 Avar Kriss (deployed) — losing the Force turns the buff off live. Starting with the Force (8 power
#// + Overwhelm), playing Cure Wounds (LOF_075, "Use the Force") spends the token, dropping her back to 4
#// power with no Overwhelm. Intended: "should work when the Force is gained and lost" (loss half).

## GIVEN
CommonSetup: ggw/brk/{myLeader:LOF_007:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_075
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# Deployed_ForceGrantedByForceBase_ActiveForAttack
#// LOF_007 Avar Kriss (deployed) — the buff must be live in time for attack damage when the Force is granted
#// mid-attack. Starting with no Force (4 power) she attacks the enemy base; the Crystal Caves (LOF_029) base
#// triggers "When a friendly Force unit attacks: create your Force token" — Avar has the Force trait — so she
#// is 8 power by the time damage resolves, dealing 8 to base. Intended: "should activate in time for attack damage
#// when granted the Force by a Force base".

## GIVEN
CommonSetup: ggw/brk/{myLeader:LOF_007:1:1:1;myBase:LOF_029;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:8
