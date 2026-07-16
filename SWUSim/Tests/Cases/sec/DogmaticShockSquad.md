# MultiplePlots_OneAtATime_ReplacementNotReplayable
#// PLOT (CR §19.b/§19.d) — multiple Plot cards in one leader deploy
#// Proves:
#//   (b) "You may play ANY NUMBER of Plot cards ... play them one at a time ... abilities
#//       triggered by playing a card with Plot must be resolved before playing the next."
#//   (d) "If the replacement card from the top of the deck has Plot, it cannot be played as
#//       part of the same leader deploy action."
#//
#// P1 controls SEC_036 (Plot, cost 6) at myResources-0 and SEC_034 (Plot, cost 5) at
#// myResources-1, plus 9 vanilla = 11 ready (affords both: 6 + 5). The deck is TOPPED with
#// SEC_070 — itself a Plot card — so the first Plot play replaces its slot with another Plot
#// card; per 19.d that fresh resource must NOT be offered this deploy.
#//
#// Flow: deploy → Plot offers {SEC_036, SEC_034} → play SEC_036 (no When Played; slot replaced
#// by SEC_070 from deck) → Plot re-offers ONLY SEC_034 (SEC_070 excluded by 19.d) → play
#// SEC_034 → its When Played defeats the 2-HP enemy → no Plots remain in the snapshot → done.
#// End state: leader unit + SEC_036 + SEC_034 in play; SEC_070 sits unplayed in resources;
#// all 11 resources spent; deck drew 2 replacements.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_036:1,1:SEC_034:1,9:SOR_095:1
WithP1Deck: [SEC_070 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SEC_036
P1GROUNDARENAUNIT:2:CARDID:SEC_034
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1RESCOUNT:11
P1RESAVAILABLE:0
P1DECKCOUNT:2
P1NODECISION

---

# NotAffordable_NotOffered
#// PLOT (CR §19) — affordability gate (PlayerHasPlotsToPlay)
#// A Plot card the player cannot pay for is NOT offered when a leader deploys. Plot cost must
#// be checked against READY resources (incl. aspect penalties).
#//
#// P1 controls SEC_036 (Plot, cost 6) at myResources-0, plus 2 ready and 3 exhausted vanilla
#// resources = 6 controlled (meets Iden's deploy threshold) but only 3 READY. 3 < 6 → SEC_036
#// is unaffordable → no Plot window appears. The leader deploys normally; SEC_036 stays a
#// resource and the deck is untouched.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_036:1,2:SOR_095:1,3:SOR_095:0
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1RESCOUNT:6
P1RESAVAILABLE:3
P1DECKCOUNT:1
P1NODECISION

---

# UnitFromResources_Sentinel_EntersExhausted
#// PLOT (CR §19) — SEC_036 Dogmatic Shock Squad (Unit, Sentinel + Plot, cost 6, Vigilance/Villainy)
#// Proves the UNIT branch of Plot: a Plot unit played from resources deploys to the arena,
#// enters exhausted (CR 19.c), keeps its keywords (Sentinel), and its resource slot is
#// replaced by the top card of the deck.
#//
#// P1 controls SEC_036 as myResources-0 + 5 vanilla resources (6 ready — also meets Iden's
#// deploy threshold). bk leader (Iden, Vigilance+Villainy) covers BOTH of SEC_036's aspects
#// → no penalty, cost stays 6 (paid by exhausting all 6 ready resources, incl. the Plot card
#// itself). After paying, SEC_036 leaves resources → replaced by top of deck (exhausted).
#//
#// SEC_036 has no When Played, so after it resolves the Plot window finds no more affordable
#// snapshot Plots → no further decision.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_036:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_036
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DECKCOUNT:1
P1NODECISION
