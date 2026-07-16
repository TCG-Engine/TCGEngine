# OnAttack_3Indirect
#// JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. Power 1, attacking the
#// base: 1 combat + 3 indirect = 4 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# OnAttack_IndirectSplitUnitAndBase
#// JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. With an enemy unit in
#// play, P2 (the defending/damaged player) ASSIGNS the 3 indirect across a unit AND the base: 1 to their
#// 1-HP SOR_128 (defeats it) + 2 to their base. The Y-Wing (power 1) attacks the base for 1 combat, so
#// P2 base = 1 combat + 2 indirect = 3; SOR_128 is defeated. No You/Opponent choice (it hits the defender).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1NODECISION

---

# TakeControlImmune
#// LAW_149 Rey — "Opponents can't take control of this unit." P1 plays Change of Heart (SOR_224: take
#// control of a non-leader unit) at Rey; it fizzles and Rey stays under P2's control (never enters P1's
#// arena).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10;handCardIds:SOR_224}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_149

---

# 242_WhenDefeated_MoveTokenUpgrade
#// JTL_242 Shuttle ST-149 — Shielded + "When Played/When Defeated: You may take control of a token
#// upgrade on a unit and attach it to a different eligible unit." JTL_242 attacks JTL_069 (4/7) into a
#// lethal counter and dies; its When Defeated takes the Shield token off SOR_095 and moves it to SOR_046.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_242:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
