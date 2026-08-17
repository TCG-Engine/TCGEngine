# DefeatedAtRegroup
#// LAW_245 Salvaged Materials — "At the start of the next regroup phase, defeat it." After attaching
#// SOR_071, passing to regroup defeats the upgrade (UPGRADECOUNT back to 0, host power back to 3/3).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;discardCardIds:SOR_071}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_245

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# PlayItemFromDiscard
#// LAW_245 Salvaged Materials (Cunning event, cost 1) — "Play an Item upgrade from your discard pile. It
#// costs 3 resources less." SOR_071 Electrostaff (Item, Vigilance) is off-aspect vs the Cunning/Villainy
#// leader: printed 2 + 2 penalty = 4, minus the -3 discount = 1 paid. The attach SUCCEEDS with only 1
#// ready resource left after the event — proving the discount (without it, 4 is unaffordable). Net: 0 ready.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;discardCardIds:SOR_071}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_245

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:0

---

# ForeignOwnedEvent_PlaysFromItsControllersDiscard
#// LAW_245 — control axis. "Play an Item upgrade from YOUR discard pile" resolves from the ability's
#// CONTROLLER, not the card's owner. LAW_215 Vermillion reveals the top card of P2's deck and lets P1
#// play the P2-OWNED Salvaged Materials for FREE, so the discard it reaches into must be P1's.
#// The two discards are made distinguishable — each holds a DIFFERENT Item upgrade, and both would be
#// legal and affordable for their side:
#//   · P1's discard: SOR_071 Electrostaff (Item, Vigilance — on-aspect for P1's Vigilance base/leader,
#//     printed 2, minus the −3 → free)
#//   · P2's discard: SHD_174 Hotshot DL-44 Blaster (Item, Aggression)
#// so the attached upgrade's CARDID says which pile was read. SOR_071 attaches to P1's SEC_080 and
#// P1's discard empties, while P2's discard is left holding its own Blaster PLUS the spent event
#// (P2DISCARDCOUNT:2) — the effect follows the controller, the card follows the owner.
#// ⚠ FIXTURE NOTE: P1's deck must be NON-EMPTY. Vermillion only raises the "which deck?" prompt when
#// BOTH decks have a top card; with P1's deck empty the reveal auto-targets P2's deck, the prompt
#// disappears, and every subsequent answer shifts by one — which silently turns this section into a
#// no-op that looks like an engine bug. SOR_237 is seeded into P1's deck purely to keep that prompt.
#//
#// COVERAGE: offer=not asserted as a pool (each fixture leaves exactly one legal Item upgrade, so the
#//           discard pick auto-resolves; this section proves the pool is built from the CONTROLLER's
#//           discard by reading the attached CARDID) · decline=N/A (no "you may") · control=this
#//           section (foreign-owned event plays out of its controller's discard; the spent event
#//           returns to its owner) · reqboundary=the host pick is answered on a later request ·
#//           boundary=PlayItemFromDiscard (the −3 makes an otherwise unaffordable attach succeed) vs
#//           DefeatedAtRegroup (the delayed defeat at the next regroup).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:3;
  discardCardIds:SOR_071;
  theirDiscardCardIds:SHD_174
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_237
WithP2Deck: LAW_245
WithP2Deck: SOR_164

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_071
P1DISCARDCOUNT:0
P2DISCARDCOUNT:2
P2DECKCOUNT:1
