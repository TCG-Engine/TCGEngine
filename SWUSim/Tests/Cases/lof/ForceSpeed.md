# ReturnNonUniqueUpgrades
#// LOF_205 Force Speed — Attack with a unit; for this attack it gains "On Attack: return any number of
#// non-unique upgrades attached to the defender to their owners' hands." Plo Koon attacks SOR_046, which
#// carries SOR_054 (non-unique) and SOR_053 (unique); only SOR_054 is offered, and P1 picks it.
#// (Second answer added 2026-08-14: "return any number" is a real 0..N pick now, so even a single
#// eligible upgrade is offered rather than returned automatically.)

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
WithP2GroundArenaUpgrade: 0:SOR_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:1

---

# NoReadyUnit_NoEffect
#// LOF_205 Force Speed — with no ready unit available to attack, the event has no effect: it is still played
#// (leaves hand), but no attack occurs and the sole exhausted unit stays exhausted with no damage dealt. Intended: #// "has no effect if the player does not have ready units to attack with."

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---
# ReturnZeroUpgrades_AnyNumberIncludesNone
#// LOF_205 Force Speed — "return ANY NUMBER of non-unique upgrades" includes NONE. Plo Koon attacks a
#// defender carrying TWO non-unique upgrades, so the pick is genuinely interactive; P1 chooses none.
#// Both upgrades stay attached, P2's hand stays empty — and the ATTACK still resolves, which is the
#// assertion an abandoned attack could not produce (6 damage from Plo Koon's 6 power).

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
WithP2GroundArenaUpgrade: 0:SOR_069
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:6
P1NODECISION

---

# ReturnSubsetOfUpgrades_AnyNumberIsAChoice
#// LOF_205 — "any number" is a real 0..N pick, not "all". With two non-unique upgrades on the defender
#// P1 returns exactly ONE (SOR_069); the other (SOR_054) stays attached and P2's hand gains 1.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
WithP2GroundArenaUpgrade: 0:SOR_069
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-1
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_054
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:6
P1NODECISION

---

# ReturnZeroUpgrades_ByConfirmingEmpty
#// ⚠ REGRESSION GUARD, live bug 2026-08-27 — the PASS twin of ReturnZeroUpgrades_AnyNumberIncludesNone.
#// There are TWO ways to choose nothing on a multi-select. `-` declines; CONFIRMING THE PICKER WITH
#// NOTHING SELECTED submits the literal "PASS", which goes sticky and makes ExecuteStaticMethods skip
#// every following CUSTOM that is not flagged DontSkipOnPass. Every decline test in this repo answered
#// `-`, so the whole class was invisible. This section is the byte-for-byte twin of ReturnZeroUpgrades_AnyNumberIncludesNone: the pair is
#// the point, and if the two declines ever diverge again one of them goes red.
#//
#// LOF_205#1 also DRAINS the TempZone staging, so a skipped continuation leaks the staged upgrade copies
#// into the player's TempZone. P1TEMPZONECOUNT is the assertion that catches it — the visible board state
#// is identical either way, which is why this one needs the staging count and not just the upgrades.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
WithP2GroundArenaUpgrade: 0:SOR_069
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:6
P1TEMPZONECOUNT:0
P1NODECISION
