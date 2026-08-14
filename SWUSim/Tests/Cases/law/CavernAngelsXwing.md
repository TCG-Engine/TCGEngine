# WhenDefeatedDealBase
#// LAW_189 Cavern Angels X-Wing (2/1, space) — When Defeated: deal 2 damage to a base. Attacks SOR_237
#// (2/3) and dies to the counter; deal 2 to P2's base.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_189:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2

---

# NGORWhenDefeatedNewControllerBase
#// LAW_189 Cavern Angels X-Wing — the When Defeated "deal 2 to a base" resolves for whoever controls the
#// unit at defeat. P2 plays No Glory, Only Results (JTL_043) to take control of P1's X-Wing and defeat it,
#// so P2 makes the base choice and hits P1's base (P2's "enemy base") for 2.

## GIVEN
CommonSetup: bbw/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1SpaceArena: LAW_189:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1SPACEARENACOUNT:0
P1BASEDMG:2
