# SharedFinalize_Recruit
#// SSOT #4 (gamelog-updates, 2026-09-11) — a card that is DRAWN has consequences beyond landing in hand, and
#// they lived only in DoDrawCard: the "when you draw" observers (ASH_169 Axe Woves, JTL_111, SHD_184, LOF_148
#// Rey), the per-phase drawn counter SWU_DREW_PHASE (LAW_051 Beilert Valance), telemetry and the undo-consent
#// mark. Every "search … and draw it" / "look at the top card … draw it" path re-implemented the draw with a
#// raw AddHand and dropped some or all of them. They now share _SWUAfterCardsDrawn (and the search paths
#// SWUFinishTopDeckSearch).
#// Each section: ASH_169 Axe Woves ("When you draw 1 or more cards: give an Advantage token to this unit")
#// is in play, so ADVANTAGECOUNT:1 proves the observers ran; P1GLOBALEFFECT:SWU_DREW_PHASE proves the
#// counter did.
#// SOR_123 Recruit uses the SHARED finalize, which fired the observers but never counted the draw.
#// (Fixture from core/GameLog_PeeksAndMoves.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_251 SOR_095 SOR_172 SOR_073 SOR_124 SEC_080]
WithP1GroundArena: ASH_169:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCONTAINS:P1 revealed and drew [[SOR_095|Battlefield Marine]] ([[SOR_123|Recruit]])

---

# InvisibleHand_DrawnDroid
#// JTL_089 The Invisible Hand: "search the top 8 cards of your deck for a Droid unit, reveal it, and draw
#// it. If it costs 2 or less, you may play it for free." Its own finalize drew with a raw AddHand. Hyena
#// Bomber costs 3, so there is no free-play offer. (Fixture from core/GameLog_CardMovesToHand.md.)

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [LOF_158 SOR_095 SOR_237]
WithP1GroundArena: ASH_169:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_158

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
P1NODECISION
LOGCONTAINS:P1 revealed and drew [[LOF_158|Hyena Bomber]] ([[JTL_089|The Invisible Hand]])

---

# SenseThroughTheForce_Drawn
#// ASH_235 Sense Through the Force: "search the top 5 cards of your deck for a card, reveal it, and draw
#// it." Its own finalize drew with a raw AddHand. (Fixture from core/GameLog_CardMovesToHand.md.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1GroundArena: ASH_169:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCONTAINS:P1 revealed and drew [[SOR_046|Consular Security Force]] ([[ASH_235|Sense Through the Force]])

---

# CaptainVaughn_Drawn
#// TS26_39 Captain Vaughn: "When Defeated: Search the top 3 cards of your deck for a card and draw it.
#// Then, put a card from your hand on top of your deck." Its own finalize drew with a raw AddHand. Vaughn
#// dies attacking, so Axe Woves shifts to ground index 0. (Fixture from core/GameLog_CardMovesToHand.md.)

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP1GroundArena: ASH_169:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
P1GROUNDARENAUNIT:0:CARDID:ASH_169
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCONTAINS:P1 drew a card ([[TS26_39|Captain Vaughn]])
P2LOGNOTSEES:[[SOR_095

---

# BountyPosting_Drawn
#// SHD_228 Bounty Posting: "Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your
#// deck.) You may play that upgrade (paying its cost)." Its own finalize drew with a raw AddHand, and its
#// log line said only "revealed" — now the same "revealed and drew" as every other search. P1 declines the
#// may-play; Axe Woves' draw trigger then resolves at the end of the event (CR 7.6.8 — see the next
#// section). (Fixture from shd/BountyPosting.md.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SEC_080 SOR_128 SOR_046]
WithP1GroundArena: ASH_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCONTAINS:P1 revealed and drew [[SHD_173|Guild Target]] ([[SHD_228|Bounty Posting]])
LOGCOUNT:0:P1 revealed [[SHD_173
P2LOGNOTSEES:You saw

---

# BountyPosting_DrawTriggerWaitsForTheEvent
#// CR 7.6.8 — "If an ability triggers during or as the result of a non-attack action, resolve that ability
#// … after that action is fully completed … never interrupts an action or ability that is currently
#// resolving." An EVENT must fully resolve first (user, 2026-09-11). Bounty Posting's "You may play that
#// upgrade" is still open, so Axe Woves' draw trigger has NOT resolved yet (0 Advantage) — while the
#// drawn-this-phase counter, which is not a trigger, is already set.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SEC_080 SOR_128 SOR_046]
WithP1GroundArena: ASH_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173

## EXPECT
P1DECISIONTOOLTIP:Play_Guild_Target?
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GLOBALEFFECT:SWU_DREW_PHASE

---

# IveFoundThem_Drawn
#// IBH_009 I've Found Them: "Reveal the top 3 cards of your deck. Draw a unit revealed this way…" Its own
#// finalize (the rest go to the DISCARD) drew with a raw AddHand. (Fixture from
#// core/GameLog_EngineMoves.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1GroundArena: ASH_169:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCONTAINS:P1 revealed and drew [[SOR_095|Battlefield Marine]] ([[IBH_009|I've Found Them]])

---

# ReinforcementWalker_Drawn
#// SOR_119 Reinforcement Walker: "On Attack: Look at the top card of your deck. Either draw that card or
#// discard it and heal 3 damage from your base." Its draw went through SWUDrawTopCardFront, which moved the
#// card with no draw consequences at all.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_119:1:0
WithP1GroundArena: ASH_169:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
LOGCOUNT:1:P1 drew a card

---

# C3PO_Drawn
#// SOR_238 C-3PO: "Choose a number. Look at the top card of your deck. If its cost is the chosen number, you
#// may reveal and draw it." Also SWUDrawTopCardFront. (Fixture from sor/C3po_ProtocolDroid.md
#// OnAttack_MatchDraw.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_238:1:0
WithP1GroundArena: ASH_169:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE

---

# AdmiralTrench_DeployDraw
#// JTL_014 Admiral Trench (deployed): "When Deployed: Reveal the top 4 cards of your deck. An opponent
#// discards 2 of them. Draw 1 of the remaining cards and discard the other." A reveal-then-draw through
#// the TempZone, not a top-deck search — its draw was a raw AddHand too (found by the scan for raw hand
#// moves in cards whose text says "draw"). Trench deploys to ground index 1 behind Axe Woves. (Fixture from
#// jtl/AdmiralTrench_Chkchkchkchk.md Deploy_RevealOpponentDiscardsDraw.)

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SEC_080
WithP1Deck: SOR_225
WithP1GroundArena: ASH_169:1:0

## WHEN
- P1>DeployLeader
- P2>AnswerDecision:myTempZone-0&myTempZone-1
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_169
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GLOBALEFFECT:SWU_DREW_PHASE
