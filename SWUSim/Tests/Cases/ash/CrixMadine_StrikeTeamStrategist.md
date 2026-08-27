# PlayHeroismDiscounted
#// ASH_108 Crix Madine (Ground, 3/2, cost 3) — When Played: you may play a Heroism unit from your hand. It
#// costs 2 less for each arena in which you control the most units. P1 already controls SOR_046 in the
#// ground arena; after Crix enters, P1 has the most units in the ground arena only (1 arena = -2). The
#// Heroism SOR_095 (cost 2) is played for free (-2): 8 - 3 (Crix) - 0 = 5 resources left. (Without the
#// discount SOR_095 would cost 2, leaving 3.)
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,SOR_095}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENAUNIT:2:CARDID:SOR_095

---

# Decline_NoFreePlay
#// ASH_108 Crix Madine — the discounted play is optional. Declining leaves SOR_095 in hand; only Crix's own
#// cost (3, from 8) is spent, leaving 5.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,SOR_095}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENACOUNT:2

---

# Discount4_MostInBothArenas
#// ASH_108 Crix Madine — "costs 2 less for each arena in which you control the most units." After Crix
#// enters the ground arena, P1 has the most units in BOTH arenas: ground 2 (SOR_128 + Crix) vs 1, space
#// 2 (SOR_237 x2) vs 0. That is -4. The Heroism JTL_103 Chewbacca (cost 5, Command/Heroism) is played for
#// 5 - 4 = 1: 8 - 3 (Crix) - 1 = 4 resources left.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,JTL_103}
WithP1GroundArena: SOR_128:1:0
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0]
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:2:CARDID:JTL_103

---

# Discount2_SpaceArenaOnly
#// ASH_108 Crix Madine — controlling the most units in ONLY the space arena is -2. After Crix enters
#// ground, ground is tied (P1 1 = Crix, P2 1 = SOR_046) so no ground bonus; space is 2 (SOR_237 x2) vs 0.
#// JTL_103 (cost 5) is played for 5 - 2 = 3: 8 - 3 - 3 = 2 resources left.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,JTL_103}
WithP1SpaceArena: [SOR_237:1:0 SOR_237:1:0]
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:1:CARDID:JTL_103

---

# Discount0_NotMostInAnyArena
#// ASH_108 Crix Madine — no cost reduction when P1 has the most units in no arena. After Crix enters
#// ground, ground is tied (1 vs 1) and space is 0 vs 1 (P2 controls space). JTL_103 (cost 5) costs full 5:
#// 8 - 3 - 5 = 0 resources left.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,JTL_103}
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:1:CARDID:JTL_103

---

# Discount0_BothArenasTied
#// ASH_108 Crix Madine — a tie is not "the most," so a tie in both arenas gives no reduction. After Crix
#// enters ground, ground is 1 vs 1 and space is 1 (SOR_237) vs 1 (SOR_237). JTL_103 costs full 5:
#// 8 - 3 - 5 = 0 resources left.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,JTL_103}
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:1:CARDID:JTL_103

---

# Discount0_AnArenaEmpty
#// ASH_108 Crix Madine — an arena where neither player has a unit is not "the most" for anyone. After Crix
#// enters ground, ground is tied 1 vs 1 and the space arena is empty for both. No reduction; JTL_103 costs
#// full 5: 8 - 3 - 5 = 0 resources left.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,JTL_103}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:1:CARDID:JTL_103

---

# Decline_NoFreePlay_ByConfirmingEmpty
#// ⚠ PASS-TWIN of Decline_NoFreePlay — byte-for-byte identical except the decline.
#// `-` and "PASS" are two DIFFERENT declines, and the client only ever submits "PASS" (all three decline
#// paths in Core/UILibraries*.js). Historically every decline test here answered `-`, so the path players
#// actually take was untested. This continuation (DISCOUNT_PLAY_FROM_HAND) is one that does more than apply the pick, and
#// it now runs on a decline because SWUQueueMayChooseTarget defaults dontSkipOnPass to 1 — this twin is
#// what covers that. If the two declines ever diverge, one of the pair goes red.
## GIVEN
CommonSetup: ggw/ggk/{myResources:8;handCardIds:ASH_108,SOR_095}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENACOUNT:2

---

# TwinSuns_MostUnitsIsVsEVERYPlayer
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "each arena in which you control THE MOST units".
#// "The most" is a comparison against EVERY other player, not against one opponent. It compared only
#// against OtherPlayer($player), so a seat-4 board bigger than yours was invisible and the discount was
#// OVER-granted.
#// Fixture: P1 ends with 2 ground units and SEAT 4 also has 2 — so P1 does NOT have the most and gets no
#// discount. Seat 2 is empty, which is exactly what the old code looked at and why it would grant one.
#// Mutation-verified: restricting the comparison back to OtherPlayer() reddens this.
## GIVEN
CommonSetup: ggw/ggk
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 8
WithP1Hand: ASH_108
WithP1Hand: SOR_095
WithP1GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_059:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:3
