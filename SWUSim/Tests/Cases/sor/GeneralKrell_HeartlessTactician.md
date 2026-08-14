# GrantedWhenDefeated_Draw
#// COVERAGE: offer=N/A (the granted ability is a YES/NO draw, never a target pool) ·
#//           decline=GrantedWhenDefeated_Draw's YES vs the implicit NO path (the YESNO refused leaves
#//           state unchanged; the mandatory-absence branches SelfDefeated_NoDraw /
#//           FriendlyDiesAfterKrellGone_NoDraw prove the prompt itself is absent) ·
#//           control=NGOR_ControlGained_Draws + NGOR_OpponentTakes_NoDraw (the aura follows the
#//           defeated unit's CONTROLLER at defeat time, in both directions) ·
#//           boundary=PlayedAfterOtherUnits_GrantApplies + ReturnedReplayed_DrawsOnce (aura attaches to
#//           units regardless of entry order and never double-registers) ·
#//           reqboundary=ReturnedReplayed_DrawsOnce (grant re-evaluated across multiple actions and a
#//           leave-play/replay round trip)
#// SOR_105 General Krell (5/4) — "Each other friendly unit gains: 'When Defeated: You may draw a
#// card.'" P1's 3/3 (granted by Krell) attacks into a 3/7 and dies; its granted When-Defeated lets
#// P1 draw a card. Krell itself survives.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1

---

# SelfDefeated_NoDraw
#// SOR_105 General Krell — the grant is "each OTHER friendly unit", so Krell's OWN defeat draws
#// nothing. Krell is the only friendly; he attacks into lethal and dies → no card is drawn.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP2GroundArena: SOR_213:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# PlayedAfterOtherUnits_GrantApplies
#// SOR_105 General Krell — the grant is a live aura, not a play-time stamp: a unit ALREADY in play
#// when Krell arrives gains the ability too. Marine is on the board first; Krell is then played from
#// hand; the marine trades into the 3/7 and its granted When-Defeated draws P1 a card.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  myResources:5;
  myhandCardIds:SOR_105
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
P1DECKCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# LeaderUnitDefeated_Draws
#// SOR_105 General Krell — "each other friendly unit" includes a deployed LEADER unit. Leia
#// (deployed, 5 damage → 1 HP left) attacks the 3/7 for 4 (Raid 1) and dies to the counter damage;
#// she returns to the leader zone AND her granted When-Defeated still resolves: P1 draws a card.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  myLeader:SOR_009:1:1:1:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# FriendlyDiesAfterKrellGone_NoDraw
#// SOR_105 General Krell — the aura dies with Krell: first Krell trades with the enemy 5/4 (his own
#// defeat draws nothing — "each OTHER friendly unit"), then the marine trades into the 3/7 and its
#// defeat no longer has any grant behind it. No prompt, no draw.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_213:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:1
P1NODECISION

---

# ReturnedReplayed_DrawsOnce
#// SOR_105 General Krell — leave-play/replay never double-registers the grant. P1 Waylays his OWN
#// Krell back to hand (3+2 off-aspect Cunning = 5), replays him (5, on-aspect), then the marine
#// trades into the 3/7: its granted When-Defeated draws exactly ONE card (deck seeded with two —
#// one must remain).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  myResources:10;
  myhandCardIds:SOR_222
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
P1DECKCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# NGOR_ControlGained_Draws
#// SOR_105 General Krell × JTL_043 No Glory, Only Results — control at DEFEAT time decides the
#// grant: P1 takes control of P2's Wampa and defeats it. Under P1's control the Wampa is a friendly
#// unit alongside Krell, so it carries the granted When-Defeated and P1 may draw. JTL_043 costs
#// 5+4 off-aspect (Vigilance and Villainy both uncovered under ggw) = 9. Wampa still goes to its
#// owner's (P2) discard.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  myResources:9;
  myhandCardIds:JTL_043
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# NGOR_OpponentTakes_NoDraw
#// SOR_105 General Krell × JTL_043 — the other direction: P2 takes control of P1's marine and
#// defeats it. At the defeat the marine is under P2's control and P2 controls no Krell, so the aura
#// does not apply: P2 gets no draw prompt and no card. The marine still goes to its owner's (P1)
#// discard. JTL_043 costs 5+2 off-aspect (Villainy uncovered under the rw leader; Vigilance covered
#// by the b base) = 7.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 7
WithP2Hand: JTL_043
WithP2Deck: SOR_128
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1DISCARDCOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:1
P2NODECISION
P1NODECISION
