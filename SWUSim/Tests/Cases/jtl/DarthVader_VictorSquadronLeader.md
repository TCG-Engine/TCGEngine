# AttackedVehicle_CreatesTIE
#// JTL_006 Darth Vader (leader) — Action [Exhaust]: If you attacked with a non-token Vehicle unit this
#// phase, create a TIE Fighter token. P1's X-Wing (SOR_237, a non-token Vehicle) attacks P2's base, then
#// Vader's action creates a TIE Fighter (JTL_T01) in the space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_006;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# DeployAsPilot_CreatesTwoTies
#// JTL_006 Darth Vader (leader) — "When deployed as an upgrade: Create 2 TIE Fighter tokens." Vader
#// deploys as a Pilot onto the lone friendly Vehicle (SOR_225 TIE/ln Fighter), then makes 2 TIE tokens.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1SPACEARENAUNIT:2:CARDID:JTL_T01

---

# NonVehicleAttack_NoTIE
#// JTL_006 Darth Vader (leader) — the TIE is only created if you attacked with a VEHICLE this phase.
#// Here P1 attacks with a non-Vehicle ground unit (SEC_080), so the condition is not met and no token
#// is created (the leader still exhausts). Proves the "non-token Vehicle" requirement.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_006;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1NODECISION
