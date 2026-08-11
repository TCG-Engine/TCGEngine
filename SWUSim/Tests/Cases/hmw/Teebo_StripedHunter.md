# TeeboHasHiddenInnately
#// HMW_162 Teebo (3/1, Ewok) — "Hidden (This unit can't be attacked if it was played this phase.) /
#// Other friendly Ewok units gain Hidden."
#// His OWN Hidden is auto-wired: the generator derives $Hidden_Cards from the reminder text. This section
#// is the regression guard for that membership (it is green before the grant is implemented, by design).

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_162:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_162
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# GrantsHiddenToAnotherFriendlyEwok
#// The grant clause: HMW_177 Adamant Ewoks is a friendly Ewok, so it gains Hidden while Teebo is in play.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_162:1:0 HMW_177:1:0]

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_177
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden

---

# DoesNotGrantToAFriendlyNonEwok
#// The trait gate is load-bearing: SOR_095 Battlefield Marine (Rebel/Trooper) is friendly but not an
#// Ewok, so it gains nothing.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_162:1:0 SOR_095:1:0]

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden

---

# DoesNotGrantToAnEnemyEwok
#// "OTHER FRIENDLY Ewok units" — an ENEMY Ewok gains nothing. Pairs with the section above so the trait
#// half and the controller half of the filter are each proven on their own.

## GIVEN
CommonSetup: rrw/rrw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_162:1:0
WithP2GroundArena: HMW_177:1:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_177
P2GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# GrantEndsWhenTeeboLeavesPlay
#// A "while this unit is in play" aura must END with the unit. Teebo (3/1) attacks SOR_046 Consular
#// Security Force (3/7) and dies to the 3 counter-damage; the surviving HMW_177 then no longer has Hidden.
#// (Its partner section above proves it HAD Hidden a moment earlier — that pair is what makes this
#// discriminating rather than trivially green.)

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_162:1:0 HMW_177:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_177
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# GrantedHiddenProtectsAnEwokPlayedEARLIERTheSamePhase
#// The grant is retroactive within the phase: Hidden reads "can't be attacked if it WAS PLAYED this
#// phase", so an Ewok played BEFORE Teebo becomes unattackable the moment Teebo arrives.
#// P1 plays ASH_034 Wicket "Yub Nub!" (1 cost, Ewok — NOT in the Hidden registry, so any Hidden he has
#// can only have come from Teebo), P2 passes, then P1 plays Teebo (1 cost). Both P1 units are now
#// played-this-phase AND Hidden — Teebo innately, Wicket by the grant.
#// P2 then plays LOF_208 Mysterious Hermit (2 cost, Ambush). Ambush has NO legal enemy unit to attack,
#// so it adds no trigger at all: no prompt, nothing damaged on either side.
#// Aspects are exact — P1 rgw covers Command/Aggression/Heroism (1+1 = both resources), P2 ryw covers
#// Cunning (2) — so an unaffordable play can't be mistaken for the fizzle.

## GIVEN
CommonSetup: rgw/ryw/{myResources:2; theirResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: [ASH_034 HMW_162]
WithP2Hand: LOF_208

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_034
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_162
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_208
P2GROUNDARENAUNIT:0:DAMAGE:0
P2NODECISION

---

# WithoutTeeboThatSameEwokIsAValidAmbushTarget
#// The control that makes the fizzle above meaningful. Identical timing and board size, except P1's
#// second play is LAW_180 Inspired Recruit (1 cost, Rebel/Trooper) instead of Teebo — so NOTHING grants
#// Hidden and both P1 units are ordinary played-this-phase targets.
#// The Hermit's Ambush now finds targets and raises its "Ambush attack?" prompt (in the Teebo section it
#// is skipped outright, which is why that one asserts P2NODECISION with the Hermit already on the board).
#// Driving it through: the Hermit (1/4) attacks ASH_034 (3/3) — Wicket takes 1, the Hermit takes 3.
#// ⚠ P1's second action must be a PLAY, not a Pass: two consecutive passes end the action phase, and an
#// earlier draft of this control silently asserted a board where P2 had never played the Hermit at all.

## GIVEN
CommonSetup: rgw/ryw/{myResources:2; theirResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: [ASH_034 LAW_180]
WithP2Hand: LOF_208

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_034
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:LOF_208
P2GROUNDARENAUNIT:0:DAMAGE:3
