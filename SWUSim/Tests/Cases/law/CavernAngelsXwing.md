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

---

# Offer_BothBasesAreSelectable
#// LAW_189 Cavern Angels X-Wing — "Deal 2 damage to A BASE" names no controller, so the choice spans both
#// bases and the player picks. Neither existing section asserts the POOL: WhenDefeatedDealBase answers
#// theirBase-0 and the NGOR section answers from the new controller's seat, so an implementation
#// hardcoded to the enemy base would satisfy both. The pick is left pending so the offer is the assertion.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_189:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# WhenDefeated_OwnBaseIsALegalChoice
#// LAW_189 Cavern Angels X-Wing — the other branch of that unqualified "a base": choosing your OWN base is
#// legal and really damages it. Same board as WhenDefeatedDealBase, answering myBase-0 instead: P1's base
#// takes the 2 and P2's base takes nothing from the trigger.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_189:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1SPACEARENACOUNT:0
P1BASEDMG:2
P2BASEDMG:0
