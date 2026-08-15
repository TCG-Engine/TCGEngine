# UpgradedPassive_OnAttackPoe
#// JTL_147 Black One — While upgraded, +1/+0; On Attack: if you control Poe Dameron, may deal 1 to a unit.
#// Upgraded (SOR_069) Black One has power 3 and, with Poe as leader, deals 1 to SOR_046 on attack, then
#// hits the enemy base for 3.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1SpaceArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# NotUpgraded_NoBuff
#// JTL_147 Black One — the "+1/+0 while upgraded" is conditional. With no upgrade attached, Black One is at
#// its printed 2 power.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_147
P1SPACEARENAUNIT:0:POWER:2

---

# NoPoe_OnAttack_NoDamage
#// JTL_147 Black One — the On-Attack "deal 1 to a unit" requires you to control Poe Dameron. With a non-Poe
#// leader (JTL_001) and no Poe in play, the ability does not trigger: attacking the base deals 3 (upgraded)
#// and the enemy SOR_046 is not damaged (no decision is offered).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1SpaceArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttack_PoeAsUnit
#// JTL_147 Black One — the On-Attack "deal 1 to a unit" counts controlling Poe Dameron AS A UNIT. With a
#// non-Poe leader (JTL_001) but SHD_153 Poe Dameron on the ground, un-upgraded Black One (power 2) attacks
#// P2's base and the ability triggers: it deals 1 to the enemy SOR_128 (Death Star Stormtrooper, 1 HP),
#// defeating it.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1GroundArena: SHD_153:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:0

---

# OnAttack_PoeAsUpgrade
#// JTL_147 Black One — the On-Attack "deal 1 to a unit" counts controlling Poe Dameron AS AN UPGRADE. Poe
#// JTL_100 is attached to Black One as a Pilot upgrade (so Black One is now upgraded). On attacking P2's
#// base, the ability triggers and deals 1 to the enemy SOR_128 (Death Star Stormtrooper, 1 HP), defeating
#// it.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1SpaceArenaUpgrade: 0:JTL_100
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_147
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0

---

# Offer_AnyUnitEitherArenaIncludingSelf
#// JTL_147 Black One — "you may deal 1 damage to A UNIT". The printed text puts NO restriction on side
#// or arena, so the pool must be EVERY unit in play — friendly and enemy, ground and space — including
#// the attacking Black One itself. Board: Black One + an enemy space unit (space), a friendly and an
#// enemy ground unit (ground). Poe (JTL_013) is P1's leader, so the "if you control Poe Dameron" gate
#// is met. The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
