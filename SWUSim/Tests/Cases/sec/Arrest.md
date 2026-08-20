# CaptureTokenDefeatsInstead
#// SEC_195 Arrest — "Your base captures an enemy non-leader unit." Tokens can't be captured: a token that
#// would be captured is defeated and removed from play instead (never stored as a base captive, so it is
#// NOT returned to its owner at regroup). P1's base "captures" P2's SEC_T01 Spy → the Spy is defeated, and
#// P2's arena is empty with no base-captive to rescue.
#// A defeated TOKEN CEASES to exist rather than entering a discard pile, so P2's discard stays EMPTY —
#// only P1's spent Arrest event is discarded.
## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# BaseCaptive_RescuedAtRegroup
#// SEC_195 Arrest — the base captive is rescued by its owner at the start of the regroup phase.
#// P1 captures P2's SOR_095 (it leaves play), then both players pass to reach the regroup phase. At
#// RegroupPhaseStart, SOR_095 returns to P2's control (in its arena). Net: P2 has SOR_095 back.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# BaseCapturesEnemyUnit
#// SEC_195 Arrest (Event, cost 2, Cunning/Villainy)
#//   "Your base captures an enemy non-leader unit. At the start of the regroup phase, its owner rescues it."
#// This test: the capture. P1 plays Arrest and captures P2's SOR_095 — it leaves play (removed; stored on
#// P1's base via a GlobalEffects flag since bases have no Subcards). P2's arena is now empty.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# CaptureWithUpgrade_UpgradeDiscarded_RescuedExhausted
#// SEC_195 Arrest — capturing an upgraded unit drops the upgrade to its owner's discard (upgrades don't
#//   travel into capture). At the start of the regroup phase the owner rescues the unit: it returns to its
#//   arena EXHAUSTED and with no upgrades. P1 captures P2's SOR_095 bearing SOR_120 (Academy Training);
#//   SOR_120 hits P2's discard immediately, then both players pass to regroup and SOR_095 comes back bare.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
P2DISCARDCOUNT:1

---

# NoEnemyNonLeaderUnit_NoEffect
#// SEC_195 Arrest — with no enemy non-leader unit in play there is nothing to capture; the event simply
#//   resolves with no effect and goes to P1's discard. P2 controls no units.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# BaseCaptive_RescuableBeforeRegroupByL337
#// SEC_195 Arrest — a base captive is a captured card like any other, so a generic "rescue a captured
#// card" effect must be able to free it BEFORE the regroup phase. P1's base captures P2's SOR_095; P2
#// then plays SHD_197 L3-37 ("You may rescue a captured card. If you don't, give a Shield token to this
#// unit") and rescues it. SOR_095 returns to P2's arena EXHAUSTED (CR 8.34.3) and L3-37 gets NO Shield,
#// because the rescue branch was taken.
#// Regression guard: base captives live in a GlobalEffects flag rather than a Subcards slot, so a
#// captive scan that only walks units in play cannot see them at all.

## GIVEN
CommonSetup: yyk/yyw
WithP1Resources: 6
WithP2Resources: 6
WithP1Hand: SEC_195
WithP2Hand: SHD_197
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SHD_197
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# BaseCaptive_DeclineRescue_L337ShieldedInstead
#// SEC_195 Arrest — the positive control for the section above: the same board, but P2 DECLINES the
#// rescue. The "if you don't" branch fires, so L3-37 takes a Shield token and SOR_095 stays captured on
#// P1's base (P2's arena holds only L3-37). This proves the rescue above was a real choice, not an
#// unconditional side effect of playing L3-37.

## GIVEN
CommonSetup: yyk/yyw
WithP1Resources: 6
WithP2Resources: 6
WithP1Hand: SEC_195
WithP2Hand: SHD_197
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_197
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# CapturedStolenUnit_RescuedByItsOWNERNotItsController
#// SEC_195 Arrest — "its OWNER rescues it", which matters when the captured unit was stolen. P1 owns
#// SHD_029; P2 plays SOR_224 Change of Heart to take control of it, then P1's Arrest captures it off P2.
#// At regroup the owner (P1) rescues it, so it comes back to P1's arena exhausted — the theft does not
#// survive the capture, and P2 ends with nothing.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 2
WithP1Resources: 6
WithP2Resources: 7
WithP1Hand: SEC_195
WithP2Hand: SOR_224
WithP1GroundArena: SHD_029:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_029
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:0

---

# TwoBasesHoldCaptives_RescueOffersBothAndFreesOnlyThePicked
#// SEC_195 Arrest — with a captive on EACH base, a generic rescue must offer both and free exactly the
#// one chosen. P1's base captures SOR_095, P2's base captures SHD_029, then P1 plays SHD_197 L3-37 and
#// is offered both captives (myTempZone-0 = the captive on P1's base, myTempZone-1 = the one on P2's).
#// P1 rescues its own SHD_029 back out of P2's base; SOR_095 stays captured until the regroup phase,
#// where it is rescued by its owner P2. Guards against a rescue that frees every captive at once, or
#// that consumes the wrong base's store.

## GIVEN
CommonSetup: yyk/yyk
WithP1Resources: 10
WithP2Resources: 6
WithP1Hand: [SEC_195 SHD_197]
WithP2Hand: SEC_195
WithP1GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_197
P1GROUNDARENAUNIT:1:CARDID:SHD_029
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# TwoArrestsInOnePhase_BothHeldUnderTheBase
#// SEC_195 Arrest played TWICE in the same phase (2 resources each). Both captured units are held
#// under P1's base at once, so the base captive count is 2 — the case the ARRESTED (n) tab exists to
#// show, and the reason it is a COUNT rather than a boolean.
#// ⚠ Asserts the two halves of the base's Subcards array separately. Upgrades and captives live in
#// ONE array told apart by IsCaptive, so a change that mixed them up would keep the total right and
#// still be wrong: here the Fortify count must stay 0 while the captive count goes to 2.
#// The captives are rescued at the start of the regroup phase (covered by BaseCaptive_RescuedAtRegroup),
#// which is why the count is per-phase rather than cumulative.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASECAPTIVECOUNT:2
P1BASEUPGRADECOUNT:0
P1DISCARDCOUNT:2
