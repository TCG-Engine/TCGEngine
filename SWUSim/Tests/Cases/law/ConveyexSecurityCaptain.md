# DisablesEnemyCreditPayment
#// LAW_117 Conveyex Security Captain (Unit, cost 3, Vigilance, 2/4) — "Enemy Credit tokens lose all
#//   abilities." P2 controls LAW_117, so P1's Credit token loses its "defeat to pay 1 less" ability:
#//   no credit-payment offer appears when P1 plays a card, and P1 must pay the full cost in resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP2GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# DoesNotAffectFriendlyCredits
#// LAW_117 Conveyex Security Captain — its constant ability only blanks ENEMY Credit tokens. When P1
#// controls Conveyex, P1's OWN Credit token keeps its "defeat to pay 1 less" ability. P1 plays SOR_095
#// (cost 2, Command/Heroism) with 2 resources + 1 friendly Credit, defeats the Credit to pay 1 less →
#// only 1 resource is exhausted and the Credit is gone.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP1GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:2
P1CREDITCOUNT:0
P1RESAVAILABLE:1
P1NODECISION

---

# BlankedEnemyCreditsCannotCoverACost_TheCardBecomesUnplayable
#// Blanking the Credit does not just remove a prompt — it removes PAYMENT CAPACITY. P1 holds SOR_046
#// (cost 4, on-aspect) with only 2 ready resources and 2 Credit tokens; with P2's Conveyex in play the
#// Credits can't be defeated to pay, so the card is simply not playable. Nothing happens: it stays in
#// hand, no resource is exhausted, and both Credits survive.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Credits: 2
WithP2GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:2
P1RESAVAILABLE:2

---

# CreditsCreatedAfterConveyexIsAlreadyInPlayAreBlankedToo
#// The ability is a live board check, not a snapshot taken when Conveyex entered: a Credit created LATER
#// is blanked just the same. P1 plays LAW_244 Unmarked Credits (cost 1) to create a Credit, leaving 2
#// ready resources, then tries to play JTL_221 Stolen AT-Hauler (cost 3, on-aspect). The brand-new Credit
#// cannot help, so the play is blocked and the Credit is still sitting there.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: [LAW_244 JTL_221]
WithP2GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1CREDITCOUNT:1
P1RESAVAILABLE:2
P1SPACEARENACOUNT:0

---

# WithoutConveyexThatSameCreditPaysNormally
#// The control for the section above — identical fixture with no Conveyex on the board. The Credit
#// created by LAW_244 is defeated to pay 1 less for JTL_221, so the unit enters play, the Credit is gone
#// and all 3 resources are spent (1 + 2). This is what makes the blocked case above a real result rather
#// than an unaffordable fixture.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: [LAW_244 JTL_221]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1HANDCOUNT:0
P1CREDITCOUNT:0
P1RESAVAILABLE:0
P1SPACEARENACOUNT:1

---

# CreditsWorkAgainOnceConveyexLeavesPlay
#// The blanking is a field-presence effect, so it ends with the unit. P1 controls Conveyex and defeats
#// its OWN copy with SOR_078 Vanquish; P2's two Credits immediately become usable again and pay for
#// SOR_046 (cost 4) alongside P2's 2 resources.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: LAW_117:1:0
WithP1Hand: SOR_078
WithP2Hand: SOR_046
WithP2Credits: 2
WithP2Resources: 2

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2CREDITCOUNT:0
P2RESAVAILABLE:0

---

# CreditsWorkAgainWhileConveyexHasLostItsAbilities
#// Conveyex does not have to LEAVE play — it only has to stop having abilities. P1's deployed JTL_018
#// Kazuda Xiono attacks ("On Attack: choose any number of friendly units; they lose all abilities for
#// this round") and blanks P1's own Conveyex; P2's Credits pay for SOR_046 normally while Conveyex is
#// still standing on the board.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:JTL_018:1:1:1}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: LAW_117:1:0
WithP2Hand: SOR_046
WithP2Credits: 2
WithP2Resources: 2

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_117
P2GROUNDARENACOUNT:1
P2CREDITCOUNT:0
P2RESAVAILABLE:0
