# OnAttack_IndirectToUnit
#// JTL_240 Fett's Firespray — On Attack: 1 indirect to a player (no Boba Fett controlled → 1). 1 damage
#// can't split across two targets, but this verifies the assigner may put it on a UNIT instead of the
#// base. With an enemy unit in play P2 assigns the 1 indirect to their 1-HP SOR_128 (defeats it). The
#// Firespray (power 4) attacks P2's base for 4 combat; the indirect goes to the unit, so P2 base = 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION

---

# WhenPlayed_1Indirect
#// JTL_240 Fett's Firespray — When Played: 1 indirect to a player (2 if you control Boba Fett). Without
#// Boba Fett, P1 deals 1 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:1

---

# WhenPlayed_2Indirect_ControlBobaLeader
#// JTL_240 Fett's Firespray — "2 indirect if you control Boba Fett (as a unit, upgrade, or leader)."
#// Here P1's LEADER is Boba Fett (JTL_009, undeployed). _SWUControlsTitle finds him in the leader zone,
#// so the When Played indirect is upgraded 1 → 2 and lands on P2's (unit-less) base.

## GIVEN
CommonSetup: rrk/bbk/{
  myLeader:JTL_009;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:2

---

# WhenPlayed_2Indirect_ControlBobaAsPilotUpgrade
#// JTL_240 Fett's Firespray — the "control Boba Fett" clause explicitly counts Boba as an UPGRADE. P1's
#// leader Boba (JTL_009) is deployed as a Pilot upgrade onto SOR_249 (Frontier AT-RT, 3/5). Boba's own
#// deploy-as-upgrade "up to 4 damage" is dumped onto the 5-HP AT-RT (survives, keeps the upgrade). Playing
#// Fett's Firespray then finds Boba via the host unit's Subcards, so the indirect is upgraded 1 → 2.

## GIVEN
CommonSetup: rrk/bbk/{
  myLeader:JTL_009;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0:4
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:2

---

# WhenPlayed_2Indirect_ControlBobaAsUnit
#// JTL_240 Fett's Firespray — the "control Boba Fett" clause also counts Boba as a UNIT (not just leader
#// or upgrade). P1 controls Boba Fett (SOR_179) in the ground arena and its leader is a non-Boba (JTL_001).
#// Playing Fett's Firespray finds Boba in the arena, so the When Played indirect is upgraded 1 → 2 onto
#// P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_240
WithP1Resources: 12
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:2

---

# OnAttack_2Indirect_ControlBobaLeaderUnit
#// JTL_240 Fett's Firespray — the On Attack indirect is likewise upgraded to 2 when Boba is controlled as
#// a deployed LEADER UNIT (JTL_009). Firespray (power 4) attacks P2's unit-less base for 4 combat, then its
#// On Attack deals 2 indirect (Boba controlled) which auto-lands on the base → P2 base = 6.

## GIVEN
CommonSetup: rrk/bbk/{
  myLeader:JTL_009:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:6
