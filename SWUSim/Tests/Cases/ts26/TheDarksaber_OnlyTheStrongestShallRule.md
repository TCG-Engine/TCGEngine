# GrantSentinelReadyOn4Keywords
#// TS26_22 The Darksaber (Upgrade +2/+2, cost 4) — Attach to a non-Vehicle unit; it gains Sentinel. When
#// Played: if there are 4+ different keywords among friendly units, ready the attached unit. The friendlies
#// have Sentinel (from Darksaber), Grit + Raid (501st Veteran), and Shielded (Crafty Smuggler) = 4 distinct
#// → the exhausted host SEC_080 is readied and has Sentinel.
## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 SOR_207:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NotReadyUnder4Keywords
#// TS26_22 The Darksaber — with fewer than 4 different keywords among friendlies (only Sentinel from the
#// Darksaber), the host is NOT readied (stays exhausted), but still gains Sentinel.
## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: SEC_080:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# KeywordsOnENEMYUnitsDoNotCountTowardTheFour
#// TS26_22 The Darksaber — "if there are 4 or more different keywords among FRIENDLY units". P2 plays the
#// Darksaber onto their own exhausted SEC_080 while the keyword-rich units all belong to P1: from P2's
#// side the friendly count is short, so the attached unit gains Sentinel but is NOT readied.

## GIVEN
CommonSetup: grk/grk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [TS26_20:1:0 SOR_207:1:0]
WithP2Hand: TS26_22
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
