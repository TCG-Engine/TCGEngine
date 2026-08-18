# WhenPlayedDealFive
#// LAW_045 Zeb Orellios (4/4, Sentinel) — When Played: deal 3 to a ground unit (5 instead if you control
#// a Command or Cunning unit). P1 controls SOR_095 (Command) -> deal 5 to the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedDealThreeNoCommandCunning
#// LAW_045 Zeb Orellios — When Played: deal only 3 (not 5) when you control NO Command/Cunning unit
#// (Zeb himself is Vigilance/Aggression). Only ground units targetable: enemy space SOR_178 is untouched.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:0

---

# WhenPlayedDealFiveCunning
#// LAW_045 Zeb Orellios — When Played: deal 5 when you control a friendly Cunning unit (SEC_213 A-Wing).
#// Target enemy AT-ST (SOR_232 6/7) -> 5 damage.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1SpaceArena: SEC_213:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedDeclineNoDamage
#// LAW_045 Zeb Orellios — When Played ability is optional ("you may"): decline -> no damage dealt.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ForeignOwnedCommandUnit_EnablesFive
#// LAW_045 — control axis. "If you CONTROL a Command or Cunning unit" counts by control, not by
#// ownership. The only Command unit on the board is SEC_080 Imperial Dark Trooper (Command/Villainy)
#// sitting in P1's ground arena but OWNED BY P2 (the end state after a control-take). P1 owns no
#// Command or Cunning unit anywhere, and Zeb himself is Vigilance/Aggression — so the upgraded clause
#// can only turn on if the foreign-owned unit is counted for its CONTROLLER. It is: the enemy SOR_046
#// (3/7) takes 5, not 3.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# OwnedButEnemyControlledCommandUnit_DoesNotEnableFive
#// LAW_045 — the mirror that makes the control read discriminating. SOR_095 Battlefield Marine
#// (Command/Heroism) is OWNED BY P1 but CONTROLLED BY P2, so P1 does NOT control a Command or Cunning
#// unit and Zeb's damage stays at the base 3. Keyed on ownership the clause would read as satisfied
#// and SOR_046 would take 5.
#// The 3/7 Consular Security Force is the target precisely because it survives either amount, so the
#// 3-vs-5 difference is readable as DAMAGE rather than as a defeat. The second arena slot pins the
#// fixture: P2's arena really does hold the P1-owned Marine.
#//
#// COVERAGE: offer=the damage pool is unqualified ("a ground unit") and spans both sides —
#//           WhenPlayedDealThreeNoCommandCunning pins the arena restriction (an enemy SPACE unit is
#//           untouched) while the enabled/disabled sections target enemy ground units ·
#//           decline=WhenPlayedDeclineNoDamage (PASS on the "you may") · control=
#//           ForeignOwnedCommandUnit_EnablesFive + OwnedButEnemyControlledCommandUnit_DoesNotEnableFive
#//           (the "you control" gate counted by controller in both directions) · boundary=
#//           WhenPlayedDealFive (Command) vs WhenPlayedDealFiveCunning (Cunning) vs
#//           WhenPlayedDealThreeNoCommandCunning (neither aspect present) · reqboundary=the damage
#//           target is answered on a request after the play in every section.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaControlled: SOR_095:1
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SOR_095

---

# Errata_NamingZebOrrelios_MustBlockThisCard
#// ✅ FIXED 2026-08-17 (was RED when written). LAW_045 shipped with its name MISPRINTED as "Zeb Orellios" (one r) and was corrected by official
#// errata on 2026-03-27 to "Zeb Orrelios" — the spelling the set's other two Zebs already use
#// (SOR_146 Headstrong Warrior, ASH_161 Fists Work Every Time).
#// Our card data still carries the misprint: $titleData['LAW_045'] === 'Zeb Orellios'.
#// Every name-matching effect keys off that title. SOR_062 Regional Governor stores the named title as
#// SWU_NAMEBLOCK|<uid>|<title> and SWUCardPlayBlocked compares CardTitle($cardID) against it, so naming
#// the CORRECT, errata'd "Zeb Orrelios" fails to stop this card. MEASURED: it plays out of hand normally.
#// The same title feeds the client's name-a-card list, which therefore offers BOTH spellings of one
#// character as if they were different cards.
#// The control below is what makes this a real assertion rather than a fixture that simply never plays —
#// an earlier probe "passed" twice while LAW_045 was silently unplayable for an unrelated reason
#// (initiative had been CLAIMED, so P1 had already passed and could not act at all).
#// FIXED IN THE DATA, not here. The official API deliberately keeps `title` as PRINTED and records the
#// correction in the record's `rules` field as `(ERRATA) Name: "Zeb Orrelios"` — its updatedAt IS the
#// errata date with the title left alone — so there was nothing upstream to re-fetch.
#// zzCardCodeGenerator.php now parses that field (SWUErrataName) and applies NAME errata after Phase 1,
#// on both the live-fetch and rebuild-from-cache paths. Regenerating changed exactly one value in the
#// dictionary. Scope is names only; text/templating errata are NOT applied and are tracked separately.

## GIVEN
CommonSetup: brw/bbw/{myResources:7}
WithActivePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_062
WithP1Hand: LAW_045

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Zeb Orrelios
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# Errata_Control_NamingAnUnrelatedCardLeavesZebPlayable
#// LAW_045 — the discriminating control for the section above. Identical board and flow, except Regional
#// Governor names an unrelated card: Zeb plays out of hand normally (arena 1, hand 0). Without this half a
#// "blocked" assertion is satisfied by any fixture in which the play simply never happened, which is
#// exactly the trap the first probe of this pair fell into.

## GIVEN
CommonSetup: brw/bbw/{myResources:7}
WithActivePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_062
WithP1Hand: LAW_045

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Battlefield Marine
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_045
P1HANDCOUNT:0
