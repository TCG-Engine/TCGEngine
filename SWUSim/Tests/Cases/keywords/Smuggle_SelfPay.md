# SmuggledCardPaysTowardItsOwnCost_FromLateIndex
#// The Smuggle twin of bug #925. CR 8.22.e: "the card can exhaust itself toward payment while still in
#//   resources" — and SWUSmuggleResource says so in a comment, then relies on SWUExhaustResources
#//   "naturally including the card". It only does when the card sits among the LOWEST-INDEX ready
#//   resources; the existing Smuggle cases all place it at index 0, so the gap never showed.
#//
#//   SHD_089 Pirate Battle Tank (Smuggle 7) sits LAST among 8 ready resources. Paying 7 with the card
#//   itself as one of them exhausts 6 others, leaving 1 ready; the card then leaves the zone and its
#//   slot is replaced from the top of the deck (exhausted), so the COUNT stays 8.
#//
#//   Without the fix the sweep takes the 7 lowest-index resources — every one of them an OTHER resource
#//   — and the card leaves on top of that: 0 ready, i.e. the player paid 8 for a 7-cost card.

## GIVEN
CommonSetup: gbk/grw
WithP1Resources: 7:SOR_095:1,1:SHD_089:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_089
P1RESCOUNT:8
P1RESAVAILABLE:1

---

# SmuggleWithEmptyDeck_SlotIsLostNotReplaced
#// The Smuggle twin: CR 8.22.g replaces the spent resource from the top of the deck, but an EMPTY deck
#//   leaves nothing to replace it with, so the slot is permanently lost. Self-pay still applies — the
#//   card is exhausted toward its own cost before it leaves.
#//
#//   Same board as the case above (SHD_089, Smuggle 7, last of 8 ready) minus the deck: cost 7 is paid
#//   by the card plus 6 others, leaving 1 ready, and the zone drops 8 → 7 with no replacement.
#//   BASEDMG stays 0 — the replacement is not a draw, so CR 6.1's empty-deck damage must not fire.

## GIVEN
CommonSetup: gbk/grw
WithP1Resources: 7:SOR_095:1,1:SHD_089:1

## WHEN
- P1>SmuggleResource:7

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:7
P1RESAVAILABLE:1
P1BASEDMG:0
