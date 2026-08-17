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
