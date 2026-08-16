# FromPlay_NotReplayable
#// LAW_200 — the replay only applies if discarded "from your hand or deck this phase." When the Blaster
#// goes to the discard FROM PLAY (its host is defeated), it is NOT stamped TPP, so it can't be played from
#// discard — even with a valid non-Vehicle host available. SEC_080 (5/3 with the Blaster) attacks SOR_046
#// (3/7) and dies to the counter; the Blaster lands in P1's discard (From PLAY). P1's attempt to replay it
#// onto SOR_095 is a no-op: the Blaster stays in the discard and SOR_095 gets no upgrade. (P1's discard
#// holds the defeated SEC_080 AND the Blaster = 2 cards; if the Blaster had been replayable it would be 1.)
#// COVERAGE: offer=OnlyAttachesToNonVehicle (Vehicle excluded from the host pool; single legal host
#//           auto-attaches, the Vehicle staying bare is the proof) · reqboundary=PlayFromDiscardAfterHandDiscard
#//           + PlayFromDiscardAfterDeckMill (the stamp survives across requests/turn swaps before the replay)
#//           · control=N/A (the replay permission belongs to the discard's owner; no control-change variant
#//           intended) · boundary pair=PlayFromDiscardAfterHandDiscard (this phase → replayable) vs
#//           NotReplayableNextPhase_StampExpires (next phase → not), plus FromPlay_NotReplayable (wrong
#//           source zone) · decline=N/A (the replay is an optional action; not attempting it is the
#//           default and needs no prompt)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_200
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDCOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PlayFromDiscardAfterHandDiscard
#// LAW_200 Salvaged Blaster (+2/+0 upgrade, Aggression) — "Action: If this upgrade was discarded from your
#// hand or deck this phase, play it from your discard pile (paying its cost). Attach to a non-Vehicle unit."
#// P1's Pillage (SHD_181) forces P2 to discard 2 from hand; P2 discards LAW_200 (→ TPP this phase) and a
#// filler. P2 then plays LAW_200 from its discard (cost 2) onto SEC_080 (3/3, non-Vehicle) → 5/3, attached.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:LAW_200,SOR_095;theirResources:2}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:5
P2DISCARDCOUNT:1
P2RESAVAILABLE:0

---

# OnlyAttachesToNonVehicle
#// LAW_200 Salvaged Blaster (+2/+0) — attaches only to a NON-Vehicle unit. With a Creature (Wampa SOR_164)
#// and a Vehicle (AT-ST SOR_232) in play, the Vehicle is not a legal host, so the Blaster auto-attaches to
#// Wampa (4 -> 6 power); AT-ST gets no upgrade.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_200
WithP1GroundArena: [SOR_164 SOR_232]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# PlayFromDiscardAfterDeckMill
#// LAW_200 Salvaged Blaster — "discarded from your hand OR DECK this phase" also covers a DECK mill. P2's
#// Kanan Jarrus (SOR_047) mills the top of P1's deck (the Blaster → P1's discard, stamped playable this
#// phase); P1 then plays it from discard (cost 2, on-aspect Aggression) onto SOR_046, non-Vehicle host.

## GIVEN
CommonSetup: rrw/grw/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Resources: 2
WithP1Deck: LAW_200
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P1>PlayFromDiscard:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0

---

# NotReplayableNextPhase_StampExpires
#// LAW_200 Salvaged Blaster — "If this upgrade was discarded from your hand or deck THIS PHASE" is a
#// per-phase stamp: a Blaster discarded from hand this phase is NOT replayable in the NEXT action phase.
#// Same forced-discard setup as PlayFromDiscardAfterHandDiscard (P1's Pillage makes P2 discard LAW_200 +
#// a filler), but both players pass through the regroup phase first; P2's replay attempt next phase is a
#// no-op — the Blaster stays in the discard and SEC_080 gets no upgrade.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:LAW_200,SOR_095;theirResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
PHASE:MAIN
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:LAW_200
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2RESAVAILABLE:2

---

# PlayFromDiscardAfterDeckMill_SurvivesTheRequestBoundary
#// LAW_200 Salvaged Blaster — the replay-from-discard is a two-request sequence in production: the action
#// starts the play (paying the cost, pulling the Blaster out of the discard) and the host pick is answered
#// in a FRESH process, so the in-flight upgrade play must ride the serialized gamestate, not an in-memory
#// continuation — the shape that makes a card silently vanish in a real game.
#// Mirrors PlayFromDiscardAfterDeckMill with a request boundary inserted between PlayFromDiscard and the
#// host answer. The host pick is a genuine two-candidate MZCHOOSE (myGroundArena-0 & theirGroundArena-0),
#// so the boundary is not a no-op.
#// NOTE the sibling PlayFromDiscardAfterHandDiscard is NOT usable for this: with a 2-card hand and a
#// discard-2, Pillage has no choice to offer and its two answer lines are auto-resolve artifacts — a
#// boundary inserted there would pass vacuously.

## GIVEN
CommonSetup: rrw/grw/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Resources: 2
WithP1Deck: LAW_200
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P1>PlayFromDiscard:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0

---

# AttachOffer_NonVehicleEitherSide
#// LAW_200 Salvaged Blaster — OFFER assertion for "Attach to a non-Vehicle unit." The restriction names no
#// controller, so per CR 2.e it spans BOTH sides; the only exclusion is the Vehicle trait. Discriminating
#// board: P1's Wampa (Creature, non-Vehicle) is IN and P1's AT-ST (Vehicle) is OUT; P2's Battlefield
#// Marine (non-Vehicle) is IN — an ENEMY host is legal — and P2's AT-ST plus P2's space X-Wing (both
#// Vehicles) are OUT. Pool must be exactly the two non-Vehicles, one per side.
#// COVERAGE-UPDATE (offer axis): strengthens the FromPlay_NotReplayable ledger's
#// "offer=OnlyAttachesToNonVehicle (single legal host auto-attaches)" — that fixture is friendly-only and
#// auto-resolving, so it could not see a missing enemy host; this section pins the full pool.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_200
WithP1GroundArena: [SOR_164:1:0 SOR_232:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
