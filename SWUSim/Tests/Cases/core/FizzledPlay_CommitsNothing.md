# FizzledUpgradePlay_NoLogNoCountersNoFlags
#// ⚠ USER RULING (2026-09-17): "a reverted play should also revert its counters."
#// An upgrade play with NO LEGAL HOST fizzles — the card stays in hand and nothing attaches (by design:
#// "The upgrade stays in hand until ATTACH_UPGRADE succeeds, giving natural rollback on payment
#// failure"). But SWUCommitPlay ran FIRST, at the top of ActivateCard, so the fizzle still:
#//   • wrote "P1 played <card>" to the game log,
#//   • bumped the cards-played-this-phase counter (SOR_190 Lothal Insurgent reads it as "another card"),
#//   • armed the "played a <X> card this phase" flags (the TWI_017 / JTL_010 / LOF_243 family),
#//   • and bumped per-card telemetry.
#// Found while building the Force-upgrade section of lof/CaretakerMatron.md: that fixture's host was
#// FRINGE, so the play fizzled — and the Matron's Action still drew a card, which is how the premature
#// commit surfaced. A fixture passing for the wrong reason.
#//
#// THIS BOARD: LOF_074 Bolstered Endurance says "Attach to a Force unit". The only unit in play is the
#// Matron (LOF_243), who is FRINGE — so there is no legal host and the play must do NOTHING at all.
#// The Matron's own Action is still usable (its condition is simply unmet), so she exhausts and draws no
#// card: that draw is the sharpest probe that the Force flag was not armed.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:LOF_074}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
#// Hand 1 = the fizzled card is STILL THERE. Deck 1 = the Matron's Action drew NOTHING, which is the
#// sharpest probe that the Force flag was never armed.
P1HANDCOUNT:1
P1DECKCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
LOGCOUNT:0:played [[LOF_074

---

# FizzledUpgradePlay_DoesNotCountAsAnotherCard
#// The cards-played counter half, read through a real consumer: SOR_190 Lothal Insurgent's When Played
#// is "If you played ANOTHER card this phase, each opponent draws then discards a random card". A
#// fizzled upgrade must not be that "another card", so Lothal Insurgent played immediately afterwards
#// must NOT fire: P2's hand and deck are untouched.
#// (P2 holds a card so a real trigger would be visible as a discard.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:LOF_074,SOR_190}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP2Hand: SOR_128
WithP2Deck: SOR_129

## WHEN
#// index 0 fizzles and STAYS in hand, so Lothal Insurgent is still at index 1.
- P1>PlayHand:0
- P1>PlayHand:1

## EXPECT
P2HANDCOUNT:1
P2DECKCOUNT:1
P1GROUNDARENACOUNT:2
LOGCOUNT:0:played [[LOF_074
