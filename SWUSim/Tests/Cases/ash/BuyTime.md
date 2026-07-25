# Event_TokenWithSentinel
#// ASH_091 Buy Time (Event) — create a Mandalorian token and give it Sentinel for this phase.

## GIVEN
CommonSetup: yrw/grw/{myResources:6;handCardIds:ASH_091}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TokenSentinelExpiresNextPhase
#// ASH_091 Buy Time — the Mandalorian token's Sentinel is "for this phase" only. After playing Buy Time and
#// passing into regroup and back to a new action phase, the token no longer has Sentinel (the SWU_DUR_PHASE
#// grant expired). (Decks are seeded so the regroup empty-deck base damage doesn't interfere.)
## GIVEN
CommonSetup: bbw/bbk/{myResources:3;handCardIds:ASH_091}
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
