# BaseDamaged_Readies
#// JTL_218 Guerilla Soldier — When Played: 3 indirect to a player; if a base is damaged this way, ready
#// this unit. P1 plays JTL_218 (enters exhausted) and aims the indirect at P2, who controls no units, so
#// all 3 land on P2's base → a base is damaged this way → JTL_218 readies itself.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_218

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_218
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:3

---

# NoBaseDamaged_NotReady
#// JTL_218 Guerilla Soldier — the self-ready only happens "if a base is damaged this way". Aiming the 3
#// indirect at the OPPONENT when P2 controls units lets P2 assign it all to their units (avoiding the base),
#// so no base is damaged → Guerilla stays exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_218
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:mySpaceArena-0:1,mySpaceArena-1:1,mySpaceArena-2:1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_218
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
