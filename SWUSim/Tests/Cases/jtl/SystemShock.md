# DefeatUpgrade_Deal1ToHost
#// JTL_175 System Shock (event) — Defeat a non-leader upgrade attached to a unit. If you do, deal 1 to
#// that unit. P1 defeats SOR_120 on the enemy SOR_046 and then deals 1 to SOR_046.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_175
WithP1Resources: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1


---

# Offer_AnyPlayersUpgradedUnit_NotLeaderUpgrades
#// JTL_175 System Shock — "Defeat a NON-LEADER upgrade ATTACHED TO A UNIT." The pool is keyed on the
#// upgraded HOST unit and is deliberately NOT controller-scoped: P1's own upgraded marine belongs in it
#// alongside the enemy's upgraded security force. Excluded are the enemy unit carrying NO upgrade at all,
#// and the enemy Vehicle whose only upgrade is a DEPLOYED LEADER PILOT (a leader upgrade). The decision
#// is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirLeader:JTL_001;
  theirLeaderDeployedPilot:true;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_175
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P2GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-1.u0
