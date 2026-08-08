# DefeatBaseDamager
#// SEC_077 Retaliation (Event, Vigilance, cost 5) — "Defeat a unit that dealt damage to a base this phase."
#//   SOR_095 attacks P2's base (marked), then SEC_077 defeats it (the only base-damager this phase).

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:0
P1NODECISION

---

# DefeatEnemyBaseDamager
#// SEC_077 Retaliation — "Defeat a unit that dealt damage to a base this phase." Works on an ENEMY
#//   attacker: P2's SOR_046 (3/7) attacks P1's base, then P1 plays Retaliation and defeats it (the sole
#//   base-damager, auto-resolves).

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_077

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
TURNPLAYER:2
P1NODECISION

---

# NoDefeat_AttackedUnitNotBase
#// SEC_077 Retaliation — a unit that attacked another UNIT (not a base) is not a legal target. P2's
#//   SOR_046 attacks P1's SOR_046 (both 3/7, both survive); no base was damaged this phase, so playing
#//   Retaliation has no legal target and fizzles — nothing is defeated.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_077

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1BASEDMG:0
P1DISCARDCOUNT:1
TURNPLAYER:2
P1NODECISION

---

# OverwhelmSpillToBase_CountsAsDamagingABase
#// SEC_077 Retaliation — "dealt damage to a base" must count OVERWHELM spillover, not just a direct base
#// attack. P1's Wampa (SOR_164, 4 power, Overwhelm) attacks P2's 2/2 (SHD_110): 2 excess spills onto P2's
#// base, so the Wampa IS a legal Retaliation target and is defeated. Same damage-path enumeration family
#// as the JTL_177 indirect bug — a "dealt damage to X" condition must cover every route to X.
## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_110:1:0
WithP1Hand: SEC_077
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P1NODECISION

---

# UndeployedLeaderDamagedBase_NotATarget
#// SEC_077 Retaliation — "defeat a UNIT that dealt damage to a base". A leader that damaged a base while
#// UNDEPLOYED is not a unit in play, so it can never be the target, and with no other base-damager the
#// event fizzles with nothing defeated. Guards the unit-scope half of the condition.
## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1NODECISION
