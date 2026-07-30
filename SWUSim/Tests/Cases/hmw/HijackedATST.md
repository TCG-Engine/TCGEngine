# WhenPlayed_DoesntReadyDuringNextRegroup
#// HMW_121 Hijacked AT-ST (5/7, Command/Heroism, cost 5) — "Overwhelm. When Played: This unit doesn't
#// ready during the next regroup phase." Overwhelm is auto-wired; only the self-targeted skip-regroup
#// marker is new (same SWU_SKIP_REGROUP_READY_ marker HMW_095 uses on a chosen unit). A played unit enters
#// EXHAUSTED, so a control unit that WAS exhausted and DOES ready is what proves the ready step actually ran.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_121
WithP1GroundArena: SOR_095:0:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:HMW_121
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# WhenPlayed_ReadiesNormallyTheFollowingRound
#// Scope guard: the marker is ONE-SHOT (consumed at the next regroup). After that regroup the AT-ST readies
#// like any other unit the round after. Here it is placed already exhausted WITHOUT the marker (via the
#// arena fixture, not PlayHand) — so it must ready at the very next regroup, proving the block is not permanent.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: HMW_121:0:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_121
P1GROUNDARENAUNIT:0:READY
