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

---

# PrintedBountyCountsTowardTheFour
#// TS26_22 The Darksaber — Bounty is a keyword (CR 7.5.13). Friendlies: Sentinel (Darksaber on SEC_080,
#// and TS26_20 while undamaged), Grit + Raid (TS26_20 501st Veteran), Bounty (SHD_195 Cartel Turncoat) =
#// 4 different keywords → the exhausted host is readied. Player report 2026-10-09.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 SHD_195:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ThreeKeywordsWithoutTheBountyUnit_NotReady
#// TS26_22 The Darksaber — boundary partner of PrintedBountyCountsTowardTheFour: the same board minus the
#// Bounty unit has only Sentinel, Grit, Raid = 3 → the host stays exhausted.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# UpgradeGrantedBountyCountsTowardTheFour
#// TS26_22 The Darksaber — a Bounty GRANTED by an upgrade counts too: SOR_095 (vanilla) wears SHD_176
#// Death Mark ("Bounty — Draw 2 cards"). Sentinel + Grit + Raid + Bounty = 4 → host readied.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 2:SHD_176
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# SmuggleCountsTowardTheFour
#// TS26_22 The Darksaber — "different keywords" means EVERY keyword (CR 7.5.x), not a hand-kept list.
#// Sentinel + Grit + Raid (TS26_20) + Smuggle (SHD_032 Lom Pyke, its only keyword) = 4 → host readied.
#// Same gap Maul had (Front_SmuggleIsAKeyword): the card keeps its own ten-keyword list.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 SHD_032:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# SupportCountsTowardTheFour
#// TS26_22 The Darksaber — as SmuggleCountsTowardTheFour, with Support (ASH_036 Rukh, its only keyword):
#// Sentinel + Grit + Raid + Support = 4 → host readied.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 ASH_036:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# EffectGrantedBountyCountsTowardTheFour
#// TS26_22 The Darksaber — a Bounty granted by a phase effect counts. SHD_031 The Client (Shielded) uses
#// its Action to give SOR_164 Wampa (Overwhelm) "Bounty — Heal 5 damage from a base". Sentinel (Darksaber)
#// + Shielded + Overwhelm + Bounty = 4 → the exhausted host SEC_080 is readied.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 SHD_031:1:0 SOR_164:1:0]
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-2
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:2:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:0:READY

---

# EffectGrantedBounty_NotGranted_ThreeKeywords_NotReady
#// TS26_22 The Darksaber — boundary partner: same board, but The Client's Action is not used, so there
#// is no Bounty: Sentinel + Shielded + Overwhelm = 3 → the host stays exhausted.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 SHD_031:1:0 SOR_164:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ExploitCountsTowardTheFour
#// TS26_22 The Darksaber — Sentinel + Grit + Raid (TS26_20) + Exploit (TWI_233 Hailfire Tank, its only
#// keyword) = 4 → host readied. Missing from the card's private ten-keyword list.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 TWI_233:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# PilotingCountsTowardTheFour
#// TS26_22 The Darksaber — Sentinel + Grit + Raid (TS26_20) + Piloting (JTL_046 Paige Tico as a unit, its
#// only keyword) = 4 → host readied. Missing from the card's private ten-keyword list.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 JTL_046:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# PlotCountsTowardTheFour
#// TS26_22 The Darksaber — Sentinel + Grit + Raid (TS26_20) + Plot (SEC_034 Cad Bane, its only keyword)
#// = 4 → host readied. Missing from the card's private ten-keyword list.

## GIVEN
CommonSetup: grk/rrk/{myResources:4;handCardIds:TS26_22}
WithP1GroundArena: [SEC_080:0:0 TS26_20:1:0 SEC_034:1:0]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
