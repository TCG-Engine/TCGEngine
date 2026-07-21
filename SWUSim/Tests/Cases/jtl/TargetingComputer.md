# ControllerAssignsIndirect
#// JTL_171 Targeting Computer — "Attached unit gains: You assign all indirect damage dealt by this unit."
#// JTL_237 TIE Bomber (On Attack: 3 indirect to the defending player) carries the Targeting Computer.
#// Normally the damaged player (P2) would assign; with JTL_171, P1 (the controller) assigns instead — so
#// P1 answers the split in the "their" frame and dumps all 3 onto P2's SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_171
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0:3

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3

---

# DoesNotAffectIndirectFromAnotherFriendlyUnit
#// JTL_171 Targeting Computer grants "You assign all indirect dealt by THIS unit" — it is PER-SOURCE, not
#// per-controller. Here P1 has a TIE Bomber (JTL_237, the ATTACKER, no upgrade) AND a separate unit carrying
#// Targeting Computer (SOR_046 + JTL_171). The TIE Bomber's 3 indirect is dealt by a DIFFERENT unit than the
#// JTL_171 host, so the DAMAGED player (P2) assigns it — P2 answers the split in its own frame.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_237:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_171
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0:1,myBase:2

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:2
