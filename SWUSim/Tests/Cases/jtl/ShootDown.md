# DefeatsSpaceUnit_DealsBase
#// JTL_176 Shoot Down (event) — Deal 3 to a space unit; if it is defeated this way, you may deal 2 to a
#// base. The TIE (SOR_225, 2/1) is defeated by the 3, so P1 then deals 2 to P2's base.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2

---

# NotDefeated_NoBase
#// JTL_176 Shoot Down (event) — the base damage only follows if the space unit is DEFEATED. JTL_069
#// (4/7) survives the 3 damage, so no base option is offered.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1NODECISION

---

# DefeatedButDeclineBase
#// JTL_176 Shoot Down — the follow-up base damage is a MAY. The TIE (SOR_225, 2/1) is defeated by the 3,
#// but P1 declines the "deal 2 to a base" (Pass): the unit is gone and no base damage is dealt.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:0

---

# ShieldedTarget_NoBase
#// JTL_176 Shoot Down — the follow-up base damage only fires if the target is DEFEATED this way. A shielded
#// TIE (SOR_225, 2/1 + Shield token SOR_T02) has its Shield prevent the entire 3-damage instance, so it
#// survives (0 damage, 0 shields) and is NOT defeated → no "deal 2 to a base" option is offered.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0
P1NODECISION

---

# CannotBeDefeatedByNoRemainingHP_NoBaseDamage
#// JTL_176 Shoot Down — "If that unit is defeated THIS WAY, you may deal 2 damage to a base." The gate
#// measures the OUTCOME, not the attempt: a target that survives lethal damage because it can't be
#// defeated by having no remaining HP (LOF_043 The Tragedy of Plagueis, for this phase) takes the full
#// 3 damage and stays in play, so no base-damage option is offered at all.
#// Sibling of ShieldedTarget_NoBase, which blocks the DAMAGE; this one lets the damage land and blocks
#// the DEFEAT — the harder half, and the one that distinguishes "took no damage" from "took lethal
#// damage and lived".
#// (P2 casts Plagueis on their own X-Wing; its second clause then makes P1 defeat one of P1's own
#// units, which is why a throwaway SOR_108 is seated on P1's ground.)

## GIVEN
CommonSetup: rrw/bbk/{
  myBase:JTL_022;
  theirBase:SOR_021;
  theirResources:5
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP1GroundArena: SOR_108:1:0
WithP2Hand: LOF_043
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
P2NODECISION
