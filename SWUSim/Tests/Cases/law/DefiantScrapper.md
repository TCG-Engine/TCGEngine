# DeclineDefeat
#// LAW_106 Defiant Scrapper — the defeat is optional ("You may"). P1 declines; the enemy Credit survives.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106
WithP2Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2CREDITCOUNT:1
P1NODECISION

---

# NoEnemyCredit_Fizzles
#// LAW_106 Defiant Scrapper — with no enemy Credit token in play, the When Played effect has no valid
#//   target and fizzles cleanly (no decision presented).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_106
P1NODECISION

---

# WhenPlayed_DefeatsEnemyCredit
#// LAW_106 Defiant Scrapper (Unit, cost 3, Vigilance/Heroism, 3/4) — When Played: You may defeat an
#//   enemy Credit token. P2 has one Credit token (at theirResources-0 from P1's frame). P1 defeats it.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106
WithP2Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_106
P2CREDITCOUNT:0
P1NODECISION

---

# OfferPool_EnemyCreditsOnlyNotOwnCreditsNorRealResources
#// LAW_106 Defiant Scrapper — offer assertion for "defeat an ENEMY CREDIT TOKEN". Two independent
#// exclusions each get their own violator: P1's own two Credits (myResources-3 / -4) are out on "enemy",
#// and P2's two ordinary resources (theirResources-0 / -1) are out on "Credit token" even though they sit
#// in the very same zone the Credits are addressed through — so a pool that had confused "an entry in the
#// opponent's resource zone" for "an enemy Credit" would show up here and nowhere else. Only P2's two
#// Credits, at theirResources-2 and theirResources-3, remain.
#// Holding Credits also puts the Credit ALT-PAYMENT prompt (CR 3.13) in front of the play; it is declined
#// with '-' so the When Played offer is the pending decision at end state. P1CREDITCOUNT:2 pins that the
#// decline really left P1's own Credits on the board to be (wrongly) offered.
#// COVERAGE: offer=OfferPool_EnemyCreditsOnlyNotOwnCreditsNorRealResources (pending SELECTABLEEXACT: own
#//           Credits and the opponent's real resources both excluded) · decline=DeclineDefeat ('-' on the
#//           "you may") · boundary pair=WhenPlayed_DefeatsEnemyCredit (a Credit exists -> defeated) vs
#//           NoEnemyCredit_Fizzles (none in play -> no decision at all) · control=N/A (Credit tokens are
#//           owned by the seat whose resource zone holds them; there is no per-unit marker to survive a
#//           control change) · reqboundary=not encoded (the payment answer and the defeat answer are
#//           separate requests in production; no serialize round-trip section exists yet)

## GIVEN
CommonSetup: bbw/rrk/{myResources:3; theirResources:2}
P1OnlyActions: true
WithP1Hand: LAW_106
WithP1Credits: 2
WithP2Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_106
P1CREDITCOUNT:2
P2CREDITCOUNT:2
P1SELECTABLEEXACT:theirResources-2&theirResources-3
