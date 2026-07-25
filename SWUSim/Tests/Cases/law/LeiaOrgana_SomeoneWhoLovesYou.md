# FrontBuffPerAspect
#// LAW_010 Leia Organa (leader front) — "Action [2 resources, Exhaust]: For this phase, give a unit
#// +1/+1 for each different aspect it has." SEC_080 (Command/Villainy = 2 aspects) gets +2/+2 → 5/5.

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
