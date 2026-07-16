# CanAttachFriendly
#// SHD_228 Bounty Posting — Bounty upgrades attach to ANY unit (friendly OR enemy; the rules allow either).
#// With a friendly (SOR_095) and an enemy (SEC_080) both present, playing the drawn Guild Target offers a
#// host CHOICE; here P1 picks the FRIENDLY unit, proving friendly is a valid host under the "any unit" ruling.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SEC_080 SOR_128 SOR_046]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_173
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DeclinePlay_KeepsInHand
#// SHD_228 Bounty Posting — the play is a "may": declining keeps the drawn upgrade in hand, unattached.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SOR_095 SEC_080 SOR_128]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# FindDrawPlayOnEnemy
#// SHD_228 Bounty Posting (Event, cost 1, Cunning)
#//   "Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your deck.) You may play that
#//    upgrade (paying its cost)."
#// P1's deck holds SHD_173 Guild Target (a Bounty upgrade). Playing SHD_228 searches the deck, draws it,
#// and P1 chooses to play it. With exactly one enemy unit (SEC_080) as a valid host, it auto-attaches to
#// the ENEMY unit (Bounty upgrades attach to enemy units) — proving the search+draw+play chain.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SOR_095 SEC_080 SOR_128]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_173
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# NoBountyUpgrade_JustSearch
#// SHD_228 Bounty Posting — deck has no Bounty upgrade → the search finds nothing (blank pick), nothing is
#// drawn or played, and no play offer appears. Only the SHD_228 event sits in discard.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
