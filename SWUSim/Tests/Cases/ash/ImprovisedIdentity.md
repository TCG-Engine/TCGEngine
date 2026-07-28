# TransplantAbilities
#// ASH_230 Improvised Identity (Upgrade) — grants the host: "Action: search the top 3 for a ground unit and
#// discard it; then you may attack with this unit, gaining the discarded unit's abilities for this attack."
#// SOR_046 (wearing the upgrade) discards SOR_059 (On Attack: may heal 2 from another unit) and attacks P2's
#// base; the transplanted On Attack heals 2 from the damaged SOR_095 (2 → 0 damage).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# DeclineSearch_TakeNothing
#// ASH_230 Improvised Identity — the search may take nothing. P1 uses the action but declines to take a
#// ground unit; nothing is discarded and no attack follows.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:READY

---

# DiscardThenDeclineAttack
#// ASH_230 Improvised Identity — the attack after discarding is optional ("you may attack"). P1 discards
#// SOR_059 but declines to attack, so the host stays ready and deals no damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# AttachToGroundUnit
#// ASH_230 Improvised Identity lands as an upgrade on the chosen ground unit.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
WithP1Hand: [ASH_230]
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_230

---

# SearchDiscardsGroundFromMixedDeck
#// ASH_230 search — the top 3 hold a ground unit (SOR_095), a space unit (SOR_178) and an event (SOR_077);
#// only the ground unit can be discarded. Discarding it leaves 2 cards in the deck.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:1
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:READY

---

# NoGroundInTop3_NothingDiscarded
#// ASH_230 search — when the top 3 contain no ground units (space unit SOR_178, upgrade SOR_069, event
#// SOR_077), nothing can be discarded. Taking nothing leaves the host ready and deals no damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_178 SOR_069 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0

---

# GrantOnAttackDealToBase
#// ASH_230 grants the discarded unit's On Attack ability for this attack. Wampa (SOR_164) discards
#// Cloud-Rider Veteran (LAW_181, "On Attack: Deal 2 damage to a base") and attacks P2's base: the gained
#// On Attack deals 2, plus Wampa's 4 combat damage = 6.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LAW_181 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LAW_181
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:6

---

# StillSearchesWhenExhausted
#// ASH_230's action has no exhaust cost, so an exhausted host can still search and discard. No attack is
#// offered because the host is exhausted.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:0:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_095
## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# OncePerRound_NoSecondUse
#// ASH_230's action is usable once each round. After using it once (take nothing, no attack), using it
#// again the same round is a no-op: no new search decision, nothing discarded, deck unchanged.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:NO
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1DISCARDCOUNT:0
P1DECKCOUNT:4

---

# AttachGroundOnly_SpaceNotSelectable
#// ASH_230 — attach condition is a GROUND unit; a friendly SPACE unit is not a legal host. With only a ground
#// (SOR_046) and a space (SOR_237) friendly present, ASH_230 auto-attaches to the ground unit (the sole legal
#// host) — the space unit is excluded (it gets no upgrade, and no cross-arena prompt appears).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_230}
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# GrantRaid_ExtraBaseDamage
#// ASH_230 — the host gains the discarded unit's KEYWORDS for the attack. Discarding SOR_157 Cantina Braggart
#// (Raid 2) lets SOR_046 (3 power) deal 3 + 2 = 5 to P2's base.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_157 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_157
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5

---

# AttackOfferedWhenNoGroundDiscarded
#// ASH_230 — "Then, you may attack" is unconditional: even with no ground unit in the top 3 (nothing discarded),
#// the host may still attack (with no ability grant). Deck top 3 are all space units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_237 SOR_237 SOR_237]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
