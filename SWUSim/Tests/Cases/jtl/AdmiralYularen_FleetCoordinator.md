# GritGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Grit, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Grit keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Grit

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Grit

---

# RestoreGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; friendly Vehicles gain it. Choosing Restore 1,
#// the friendly Vehicle SOR_237 attacks the base (for 2) and heals P1's base by 1 (3 → 2).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Restore_1
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:2

---

# SentinelGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Sentinel, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Sentinel keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Sentinel

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# ShieldedGrant
#// JTL_047 Admiral Yularen — When Played: choose a keyword; while in play, friendly Vehicles gain it.
#// Choosing Shielded, the friendly Vehicle SOR_237 (Alliance X-Wing) gains the Shielded keyword.
#// SOR_237 was already in play BEFORE the grant, so it gains the keyword but receives no Shield token
#// (Shielded only shields a unit as it enters play, not retroactively) — SHIELDCOUNT stays 0.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Resources: 7
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# ShieldedGrant_JTL130TokensEnterShielded
#// JTL_047 Admiral Yularen grants Shielded to friendly Vehicles, THEN JTL_130 Timely Reinforcements
#// creates X-Wing tokens (JTL_T02, Vehicles). The opponent controls 8 resources → 4 X-Wings. Because
#// Yularen grants Shielded to Vehicles, each token gains Shielded and — since it's entering play — must
#// enter WITH a Shield token (Shielded applies on creation, not just when "played").
#// gbw aspects cover JTL_047 (Vigilance/Heroism, cost 3) and JTL_130 (Command, cost 5) with no penalty.

## GIVEN
CommonSetup: gbw/grw/{myResources:8;theirResources:8}
P1OnlyActions: true
WithP1Hand: JTL_047
WithP1Hand: JTL_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:2:SHIELDCOUNT:1
P1SPACEARENAUNIT:3:SHIELDCOUNT:1
