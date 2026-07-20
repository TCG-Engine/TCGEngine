# RegroupDeclineExhaust
#// JTL_192 In Debt to Crimson Dawn — When attached unit readies: exhaust it unless its controller pays 2.
#// The host SOR_095 (exhausted) readies at the regroup ready step; P1 declines to pay and it is exhausted.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
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
#// JTL_192 In Debt to Crimson Dawn — paying 2 resources keeps the host ready. P1 pays the tax at the
#// regroup ready step, so SOR_095 stays ready.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
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
#// JTL_192 In Debt to Crimson Dawn — "When attached unit readies" fires for ANY ready, not just the regroup
#// ready step. P1's exhausted SOR_095 carries In Debt; P1 plays Keep Fighting (SOR_169) to ready it
#// mid-phase — the tax triggers immediately, P1 declines to pay, and SOR_095 is exhausted right back.
#// (Regression guard: this path was previously wired only for the regroup ready step.)

## GIVEN
CommonSetup: rrk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
WithP1Hand: SOR_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
