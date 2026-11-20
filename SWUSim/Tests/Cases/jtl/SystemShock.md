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

---

# FortifyUpgradeOnABase_IsNotATarget
#// JTL_175 System Shock — "Defeat a non-leader upgrade ATTACHED TO A UNIT." A Fortify upgrade is attached
#// to a BASE ("Attach this to your base, not a unit."), so it is not in the pool. Bug report P-23 (game
#// 4827): the bot's System Shock defeated P1's HMW_126 Verdant Fortress and dealt 1 damage to the base.
#// With the base upgrade as the ONLY upgrade on the table the event fizzles: no picker, the Fortify stays,
#// the base takes nothing.

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
WithP2BaseUpgrade: HMW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASE:UPGRADECOUNT:1
P2BASEDMG:0

---

# UnitAndBaseUpgrades_OfferIsTheUnitsOnly
#// JTL_175 System Shock — the same rule with a real choice on the table: P2's unit wears two upgrades and
#// P2's base wears a Fortify. Only the two unit upgrades are offered; theirBase-0.u0 is not. The decision
#// is left PENDING so the offer itself is asserted.

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
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2BaseUpgrade: HMW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0.u0&theirGroundArena-0.u1
