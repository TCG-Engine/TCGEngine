# OnAttackAggressionToBottomDealBases
#// LAW_174 0-0-0 (4/4) — On Attack: you may put an Aggression card from your discard on the bottom of
#// your deck. If you do, deal 1 to each enemy base. SOR_128 (Aggression) -> bottom; base takes 4 (combat)
#// + 1 (ability) = 5.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_128}
P1OnlyActions: true
WithP1GroundArena: LAW_174:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:5

---

# OnAttackDeclineNoBaseDamage
#// LAW_174 0-0-0 — the recycle-and-deal-1 ability is optional. Attack the base, then decline (Pass): the
#// discard stays intact (SOR_128 remains) and the enemy base only takes 4 (combat), no extra 1.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_128}
P1OnlyActions: true
WithP1GroundArena: LAW_174:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1DISCARDCOUNT:1
P2BASEDMG:4

---

# ControlledUnit_OfferComesFromControllersDiscard
#// COVERAGE: control=ControlledUnit_OfferComesFromControllersDiscard +
#//           ControlledUnit_BottomsControllersDeckAndHitsOwnersBase (0-0-0 sits in P1's ground arena but
#//           is OWNED by P2; "your discard pile"/"your deck"/"each enemy base" must all resolve from the
#//           CONTROLLER's seat, not the owner's) · offer=this section (two Aggression cards per seat, so
#//           nothing auto-resolves and the pool names which discard was read) · decline=OnAttackDecline-
#//           NoBaseDamage · reqboundary=N/A (single On Attack decision, no state re-read after it).
#//
#// LAW_174 0-0-0 — owner ≠ controller. P1 CONTROLS 0-0-0, P2 OWNS it. Both discard piles hold the SAME
#// two Aggression cards (SOR_128, SOR_164), so a pool drawn from the wrong seat would still be non-empty
#// — only the mzID frame distinguishes them. "Your discard pile" is the ability CONTROLLER's, so exactly
#// P1's two cards are selectable and none of P2's. Two candidates also stop it auto-resolving.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_174:2
WithP1Discard: [SOR_128 SOR_164]
WithP2Discard: [SOR_128 SOR_164]
WithP1Deck: SOR_095
WithP2Deck: [SOR_225 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# ControlledUnit_BottomsControllersDeckAndHitsOwnersBase
#// LAW_174 0-0-0 — the resolution half of the owner ≠ controller board. P1 controls the P2-OWNED 0-0-0
#// and puts SOR_128 from P1's discard on the bottom of P1's DECK: P1's discard drops 2 → 1 and P1's deck
#// grows 1 → 2, while P2's identical discard (2) and deck (2) are untouched — the seats are only
#// distinguishable by those counts. "Each enemy base" is enemy-of-the-CONTROLLER, so the extra 1 lands on
#// P2's base (4 combat + 1 = 5) and P1's own base — the base of the unit's OWNER — takes nothing.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_174:2
WithP1Discard: [SOR_128 SOR_164]
WithP2Discard: [SOR_128 SOR_164]
WithP1Deck: SOR_095
WithP2Deck: [SOR_225 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2
P1DECKCOUNT:2
P2DECKCOUNT:2
P2BASEDMG:5
P1BASEDMG:0

---

# TwinSuns_DealsOneToEVERYEnemyBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 3, "deal 1 damage to EACH enemy base" is a fan-out.
#// It took OtherPlayer(), so above two seats every enemy base but seat 2 was spared.
#// 0-0-0 attacks SEAT 4's base (4 combat) and the ability then adds 1 to EVERY enemy base: P4 → 5, P2 → 1.
#// The asymmetry is deliberate — attacking P4 while P2 takes only the ability damage means a fix that
#// merely swapped which single seat got hit cannot pass. P3 is a teammate and takes nothing.
## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_128}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: LAW_174:1:0
## WHEN
- P1>AttackGroundArena:0:P4B
- P1>AnswerDecision:myDiscard-0
## EXPECT
SEATCOUNT:4
P4BASEDMG:5
P2BASEDMG:1
P3BASEDMG:0
