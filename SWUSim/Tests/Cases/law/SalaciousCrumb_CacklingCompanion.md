# EntersExhausted_NoJabba
#// LAW_210 Salacious Crumb — fizzle/guard: with NO Jabba the Hutt controlled, Crumb enters play
#// EXHAUSTED (CR 8.22.f default). Played at index 0.
#// COVERAGE: offer=N/A (static enters-play replacement — playing Crumb raises no target decision) ·
#//           decline=N/A (no optional choice) · reqboundary=ForeignFreePlay_* (the enters-ready check
#//           resolves deep inside Vermillion's When Attack Ends flow, after several answered decisions)
#//           · boundary=EntersReady_WithJabba vs EntersExhausted_NoJabba (condition on/off) ·
#//           control=ForeignFreePlay_PlayerLacksJabba_EntersExhausted + ForeignFreePlay_PlayerControlsJabba_EntersReady
#//           (owner ≠ entering controller: the "you control Jabba" check follows the player the unit
#//           enters play under, not the card's owner)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_WithJabba
#// LAW_210 Salacious Crumb (0/2 ground, Underworld/Creature, Raid 2) — "If you control Jabba the Hutt
#// (as a leader or unit), this unit enters play ready." P1 controls SOR_181 Jabba the Hutt (a unit) →
#// Crumb (played at index 1) enters READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersReady_WithSECJabbaLeaderFront
#// LAW_210 Salacious Crumb — "If you control Jabba the Hutt (as a leader or unit), this unit enters
#// play ready." P1's leader is SEC_002 Jabba the Hutt (undeployed front). Controlling the Jabba
#// LEADER counts, so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SEC_002}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:READY

---

# EntersReady_WithSECJabbaLeaderUnit
#// LAW_210 Salacious Crumb — controlling a DEPLOYED SEC_002 Jabba the Hutt leader unit also satisfies
#// "control Jabba the Hutt", so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SEC_002:1:1:1}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersReady_WithSHDJabbaLeaderFront
#// LAW_210 Salacious Crumb — P1's leader is SHD_006 Jabba the Hutt (undeployed front). Controlling the
#// Jabba leader counts, so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_006}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:READY

---

# EntersReady_WithSHDJabbaLeaderUnit
#// LAW_210 Salacious Crumb — a DEPLOYED SHD_006 Jabba the Hutt leader unit satisfies "control Jabba the
#// Hutt", so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_006:1:1:1}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersExhausted_OpponentControlsJabbaLeader
#// LAW_210 Salacious Crumb — only YOUR control of Jabba the Hutt matters. P1's own leader is SHD_018
#// The Mandalorian and the OPPONENT controls SHD_006 Jabba the Hutt (front). P1 does not control Jabba,
#// so Crumb enters play EXHAUSTED (CR 8.22.f default).

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_018; theirLeader:SHD_006}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersExhausted_OpponentJabbaLeaderUnit
#// LAW_210 Salacious Crumb — an ENEMY Jabba the Hutt on the field doesn't help either: the OPPONENT's
#// SHD_006 Jabba is DEPLOYED as a leader unit, but "if YOU control Jabba the Hutt" checks Crumb's
#// controller, so Crumb still enters play EXHAUSTED (CR 8.22.f default).

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_018; theirLeader:SHD_006:1:1:1}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ForeignFreePlay_PlayerLacksJabba_EntersExhausted
#// LAW_210 Salacious Crumb played from the OPPONENT'S deck via Vermillion (LAW_215): "you control Jabba
#// the Hutt" must check the player the unit ENTERS PLAY UNDER (P1), not the card's owner. P2 — Crumb's
#// owner — controls a Jabba leader (SHD_006), but P1 (who plays it for free) does not, so Crumb enters
#// P1's arena EXHAUSTED. (P1's deck is empty, so Vermillion auto-reveals the opponent's deck-top.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  theirLeader:SHD_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: LAW_210
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ForeignFreePlay_PlayerControlsJabba_EntersReady
#// LAW_210 Salacious Crumb played from the OPPONENT'S deck via Vermillion (LAW_215): P1 — the player
#// playing it for free — controls a Jabba leader (SHD_006), so even though the card's owner (P2) has no
#// Jabba, Crumb enters P1's arena READY.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SHD_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: LAW_210
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:READY
