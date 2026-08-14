# SearchPlayDiscounted
#// LOF_100 Kelleran Beq — When Played: search the top 7 for a unit, reveal it, and play it costing 3 less.
#// The deck is all SOR_095 (cost 3 → 0 after −3), so P1 plays one for free; Kelleran + the searched unit
#// are both in play, and 6 cards remain in the deck.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DECKCOUNT:6

---

# SearchPool_ExcludesUnaffordable
#// LOF_100 Kelleran Beq — "search the top 7 for a unit, reveal it, and play it. It costs 3 resources
#// less." BUG (live game): the search offered units the player couldn't afford even after the −3 discount,
#// so picking one just fizzled (the resolve handler silently returns it to the deck). The offered/playable
#// pool must exclude units the player can't pay for.
#//
#// P1 has 7 resources; Kelleran costs 7 (Command/Heroism, fully covered by Leia + green base → no penalty),
#// so after playing him 0 ready resources remain. The top of the deck holds:
#//   - SOR_095 Battlefield Marine — cost 2 → max(0, 2−3) = 0 net → affordable (0 ≤ 0), MUST be offered.
#//   - SOR_119 Reinforcement Walker — cost 8 (Command, covered) → max(0, 8−3) = 5 net → UNaffordable
#//     (5 > 0), must NOT be offered.
#//
#// The TOPDECKSEARCH decision is left pending so its playable set (matchIDs) can be asserted directly —
#// the harness's answer path doesn't enforce that set, so only inspecting the offer catches the bug.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_095
P1SEARCHPLAYABLENOT:SOR_119

---

# FetchedUnitFiresItsOwnWhenPlayed
#// "…and PLAY it" — a real play, not a put-into-play, so the fetched unit's own When Played must resolve.
#// This handler used to place the unit with a bare AddGroundArena, which fired nothing.
#// SHD_080 Salacious Crumb: cost 1, Command/Villainy — Villainy is uncovered by the ggw/Command setup, so
#// he is 1 + 2 = 3, exactly cancelled by the −3, and lands for free. His "When Played: Heal 1 damage from
#// your base" is MANDATORY, so it needs no answer and cannot be confused with a stray prompt.
#// The base starts at 5 damage and must end at 4. Under the old placement it stayed at 5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myBaseDamage:5;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: [SHD_080 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_080

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_080
P1BASEDMG:4

---

# FetchedPILOTingUnitIsPlayedAsAUNIT_NoPilotChoice
#// The card-vs-unit distinction, and it does NOT go the way the wording first suggests.
#// A Piloting card IS a unit card, so it is a legal FIND for "search … for a unit". The naive read is
#// that "play it" then lets you pick the pilot-upgrade mode. It does not: the ability named what it was
#// looking for and plays THAT, so no Unit-vs-Pilot choice is offered — the whole search-and-play family
#// behaves this way. JTL_093 Nien Nunb lands in the ground arena as a UNIT and the turn simply ends.
#// ⚠ THE VEHICLE AND THE RESOURCES ARE BOTH LOAD-BEARING. SEC_214 is a friendly Vehicle with no Pilot,
#// i.e. a legal pilot host, and 10 resources leave 3 after Kelleran — enough to pay Nien Nunb's
#// 1-resource Piloting cost. So the pilot mode is affordable AND has a target: if it were on offer, it
#// would appear. Drop either and this section passes for the wrong reason.
#// (Contrast ASH_090 Reforge, which searches for "an UPGRADE": there this same card must not be FOUND at
#// all, because that filter is a card-TYPE test — Reforge.md::SearchExcludesPILOTUnitCards.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:LOF_100}
P1OnlyActions: true
WithP1GroundArena: SEC_214:1:0
WithP1Deck: [JTL_093 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_093

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:JTL_093
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
