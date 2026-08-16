# DealExhaustedGround
#// LAW_213 Cutthroat Podracer (Cunning,Villainy, cost 4) — When Played: you may deal 2 damage to an
#// exhausted ground unit. Hit the exhausted enemy SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
WithP2GroundArena: SEC_080:0:0
WithP1Hand: LAW_213

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# OfferPool_ExhaustedGroundUnitsEitherSide
#// LAW_213 Cutthroat Podracer — offer assertion for "you may deal 2 damage to an EXHAUSTED GROUND unit".
#// The text names no controller, so the pool spans BOTH sides; the two real filters are "exhausted" and
#// "ground". Discriminating board — one violator per filter, on each side:
#//   exhausted friendly ground SEC_080  → IN   (no "enemy" word; a friendly exhausted unit is legal)
#//   READY    friendly ground SOR_095   → OUT  (ready)
#//   exhausted enemy    ground SOR_046  → IN
#//   READY    enemy    ground SOR_128   → OUT  (ready)
#//   exhausted friendly SPACE  SOR_178  → OUT  (wrong arena)
#//   exhausted enemy    SPACE  SEC_213  → OUT  (wrong arena)
#// The Podracer ITSELF is in the pool (myGroundArena-2): per CR a card enters play EXHAUSTED unless its
#// text says otherwise, so at When-Played time the just-played Podracer is an exhausted ground unit and
#// the text has no "another". That self-inclusion is deliberate, not an artifact. The pick is left
#// UNANSWERED so the pending pool can be read.
#// COVERAGE: offer=OfferPool_ExhaustedGroundUnitsEitherSide (pending SELECTABLEEXACT; a ready violator
#//           and a wrong-arena violator on EACH side, plus the self-inclusion) ·
#//           reqboundary=NOT COVERED (no state is written before the pick and re-read after it — the
#//           pick runs the shared DEAL_UNIT_DAMAGE continuation with no per-card payload) ·
#//           control=N/A (one-shot When-Played damage; no persistent per-unit marker) ·
#//           boundary pair=DealExhaustedGround (an exhausted target exists) vs this section's READY
#//           units, which are excluded from the same board · decline=NOT COVERED (the clause is a
#//           "you may" MZMAYCHOOSE; no decline section exists yet)

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_178:0:0
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SEC_213:0:0
WithP1Hand: LAW_213

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirGroundArena-0
P1GROUNDARENAUNIT:2:CARDID:LAW_213
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:CARDID:SOR_128
P2SPACEARENAUNIT:0:CARDID:SEC_213
