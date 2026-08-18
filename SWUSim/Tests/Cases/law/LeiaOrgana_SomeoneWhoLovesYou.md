# FrontBuffPerAspect
#// LAW_010 Leia Organa (leader front) — "Action [2 resources, Exhaust]: For this phase, give a unit
#// +1/+1 for each different aspect it has." SEC_080 (Command/Villainy = 2 aspects) gets +2/+2 → 5/5.
#// COVERAGE: offer=DeployedGiveExperiencePerAspect (choose pool spans all units in play; the pick lands
#//           on the chosen friendly) · reqboundary=DeployedGiveExperiencePerAspect (the choose pends
#//           across a request; the aspect count is computed at resolution) · control=N/A (both sides
#//           read "you"/"units you control" from Leia's controller; no control-change variant intended)
#//           · boundary pair=FrontBuffPerAspect/FrontBuffSameAspectCountsOnce (distinct vs duplicate
#//           aspects) + FrontBuffExpiresNextPhase (buff on this phase vs gone next phase) · decline=N/A
#//           (both abilities are mandatory once initiated; the deploy-window Plot interplay stays on the
#//           documented deferral list)

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:0

---

# FrontBuffSingleAspect
#// LAW_010 Leia Organa (leader front) — a unit with a single aspect gains +1/+1. SEC_213 A-Wing (Cunning
#// only) goes from 1/2 to 2/3 for this phase.

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: SEC_213:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3
P1RESAVAILABLE:0

---

# FrontBuffSameAspectCountsOnce
#// LAW_010 Leia Organa (front) — only DIFFERENT aspects count. SHD_107 Enterprising Lackeys (Command,
#// Command) has just one distinct aspect, so it gains only +1/+1 → 6/6.

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_107:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1RESAVAILABLE:0

---

# DeployedGiveExperiencePerAspect
#// LAW_010 Leia Organa (deployed) — "When Deployed: Choose a unit. Give an Experience token to that unit
#// for each different aspect among units you control." P1 controls SEC_080 (Command/Villainy) and SOR_128
#// (Aggression/Villainy), and Leia deploys as a Command/Heroism unit → distinct aspects {Command, Villainy,
#// Aggression, Heroism} = 4. SEC_080 is chosen and receives 4 Experience tokens (3/3 → 7/7).

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: [SEC_080:1:0 SOR_128:1:0]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# FrontBuffExpiresNextPhase
#// LAW_010 Leia Organa (front) — the +1/+1-per-aspect buff is "for this phase" only. Intended: after the
#// phase ends, the unit is back to printed stats. SEC_213 A-Wing (Cunning, 1/2) is buffed to 2/3, then
#// both players pass through the regroup phase; next action phase the A-Wing is 1/2 again.

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: SEC_213:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1SPACEARENAUNIT:0:CARDID:SEC_213
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:2

---

# DeployedGiveExperiencePerAspect_SurvivesTheRequestBoundary
#// LAW_010 Leia Organa — request-boundary guard. Identical to DeployedGiveExperiencePerAspect except the
#// game round-trips through serialization (SimulateRequestBoundary) while the "choose a unit" pick is
#// still pending (a real three-option offer: the two seeded units plus deployed Leia herself). In a real
#// game the answer arrives in a fresh process, so the When Deployed continuation — and the aspect count
#// it resolves against ({Command, Villainy, Aggression, Heroism} = 4) — must come from serialized state.
#// SEC_080 must still receive 4 Experience tokens (3/3 -> 7/7).

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_010;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: [SEC_080:1:0 SOR_128:1:0]

## WHEN
- P1>DeployLeader
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# EpicDeployIsBlockedBelowFiveResources
#// LAW_010 Leia Organa — "Epic Action: If you control 5 OR MORE resources, deploy this leader" is a gate no
#// existing section measures: every one of them uses the front Action or a leader already deployed. With
#// exactly 4 resources the Epic is unavailable and the deploy is a no-op — Leia stays undeployed, the
#// ground arena stays empty, the Epic slot is NOT spent and no resources are consumed. Boundary partner of
#// EpicDeployAtExactlyFive below.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4

---

# EpicDeployAtExactlyFive
#// LAW_010 Leia Organa — the other side of the same threshold: at exactly 5 resources the Epic Action is
#// available and she deploys as a ground-arena leader unit. The pair is what pins the comparison at "5 or
#// more" rather than "more than 5" or "any number".

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
