# IgnoreAspectPenalty_OfficialUnit
#// SEC_009 Mon Mothma — Ignore the aspect penalties on non-Villainy Official units you play.
#// P1's leader is SEC_009 (Command/Heroism), base JTL_019 (Vigilance). SEC_163 (Fringe/Official, Aggression,
#// cost 2) is off-aspect → normally +2 penalty (cost 4). With exactly 2 resources it would be unplayable,
#// but Mon Mothma zeroes the penalty → it plays for 2 (RESAVAILABLE:0). Its "may defeat an upgrade" When
#// Played fizzles (no upgrades in play).

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_009;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_163
P1RESAVAILABLE:0

---

# Deployed_OfficialAllyGetsPlusHp
#// SEC_009 Mon Mothma (deployed unit side) — "Each other friendly Official unit gets +0/+1."
#// This clause exists ONLY on the deployed side (the front leader side has just the aspect-penalty passive).
#// Deployed Mon Mothma (3/7) is seated at idx2. SEC_237 Supreme Council Aide (2/2, Official) → +0/+1 = 2/3.
#// SOR_095 Battlefield Marine (3/3, Rebel/Trooper — NOT Official) → unchanged 3/3. Mon Mothma herself is
#// excluded by "other" → stays 3/7. Power is +0 for everyone.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
WithP1GroundArena: SEC_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_237
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:CARDID:SEC_009
P1GROUNDARENAUNIT:2:HP:7

---

# Undeployed_NoOfficialAura
#// The +0/+1 Official aura must NOT apply while Mon Mothma is the FRONT (undeployed) leader — that side
#// only carries the aspect-penalty passive. SEC_237 (Official) stays 2/2.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_009;myBase:SOR_021;theirBase:SOR_021}
WithP1GroundArena: SEC_237:1:0

## WHEN

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_237
P1GROUNDARENAUNIT:0:HP:2

---

# VillainyOfficialStillPaysTheAspectPenalty
#// SEC_009 Mon Mothma — the waiver is scoped to NON-Villainy Officials, so a Villainy Official is not
#// covered. SEC_237 Supreme Council Aide (Villainy, Official, cost 1) is off-aspect for this board
#// (base JTL_019 Vigilance + leader SEC_009 Command/Heroism), so it pays the full +2 penalty: 3
#// resources, not the 1 the waiver would leave. The negative that proves the waiver's Villainy clause
#// is load-bearing.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_009;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_237
P1RESAVAILABLE:0

---

# Deployed_EnemyOfficialGetsNothing
#// SEC_009 Mon Mothma (deployed) — "each other FRIENDLY Official unit" excludes the opponent's Officials.
#// P2's SEC_237 Supreme Council Aide stays 2/2 while P1's copy is buffed to 2/3 by the same aura. The
#// scope negative the friendly-side section cannot prove on its own.

## GIVEN
CommonSetup: gbk/brk/{myLeader:SEC_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
WithP1GroundArena: SEC_237:1:0
WithP2GroundArena: SEC_237:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SEC_237
P1GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:CARDID:SEC_237
P2GROUNDARENAUNIT:0:HP:2
