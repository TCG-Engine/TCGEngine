# RegroupExhaustUnlessPay
#// ASH_088 The Conflict Within (Upgrade/Condition) — Attached unit gains "When this unit readies: you may
#// pay 3 resources. If you don't, exhaust this unit." Host SOR_095 starts exhausted; at the regroup ready
#// step P1 declines to pay, so SOR_095 is exhausted again (stays exhausted).
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RegroupPayKeepsReady
#// ASH_088 The Conflict Within — paying 3 resources at the regroup ready step keeps the host ready. P1
#// pays, so SOR_095 stays ready.
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# ReadiedOutsideRegroup_TaxFires
#// ASH_088 The Conflict Within — "When this unit readies" fires for ANY ready, not just the regroup ready
#// step. Host SOR_095 (exhausted) carries The Conflict Within; P1 plays Keep Fighting (SOR_169) to ready it
#// mid-phase — the tax triggers immediately, P1 declines to pay 3, and SOR_095 is exhausted right back.
#// (Regression guard: this path was previously wired only for the regroup ready step — the JTL_192 twin bug.)
## GIVEN
CommonSetup: rrk/bbk/{myBase:SOR_021;theirBase:SOR_021;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
WithP1Hand: SOR_169
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
