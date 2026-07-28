# FromPlay_NotReplayable
#// LAW_200 — the replay only applies if discarded "from your hand or deck this phase." When the Blaster
#// goes to the discard FROM PLAY (its host is defeated), it is NOT stamped TPP, so it can't be played from
#// discard — even with a valid non-Vehicle host available. SEC_080 (5/3 with the Blaster) attacks SOR_046
#// (3/7) and dies to the counter; the Blaster lands in P1's discard (From PLAY). P1's attempt to replay it
#// onto SOR_095 is a no-op: the Blaster stays in the discard and SOR_095 gets no upgrade. (P1's discard
#// holds the defeated SEC_080 AND the Blaster = 2 cards; if the Blaster had been replayable it would be 1.)

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
