# ReplayingTheUnit_DoesNOTGrantAnExtraAction
#// Bug report #997 (game 3608): "when P2 plays One Must Destroy to Create they replay their Malak as
#// expected, but when I respond with my Trap Field reactive trigger, P2 gets an extra action."
#//
#// ROOT CAUSE, and it is NOT the Trap Field — measured with no Trap Field on the board at all.
#// ASH_247's second step replays the defeated unit with a nested ActivateCard, and ActivateCard runs its
#// OWN after-action. The event's normal FINISH_PLAY_CARD already owns one, so the turn swapped TWICE and
#// came straight back to the caster: a free extra action off a single event.
#// Fixed with the JTL_089#1 save/restore that HMW_204 and HMW_016 use for the same shape.
#//
#// ⚠ WHY NOBODY SAW IT SOONER: a double SWUAfterAction is INVISIBLE under P1OnlyActions, which claims
#// initiative and auto-passes the opponent, so the turn comes back either way. Only a TURNPLAYER
#// assertion on a genuinely alternating turn can see it — the same blind spot that hid this bug on
#// HMW_204 behind twelve green sections earlier.
#//
#// P2 plays ASH_247 (Villainy, cost 3 — on-aspect for an Aggression base with an Aggression/Villainy
#// leader). Their only non-leader unit auto-resolves as the defeat target, so YES is the whole answer.
#// One P2 action ⇒ the turn must be P1's.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: [SEC_080:1:0]
WithP2Hand: [ASH_247]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2DISCARDCOUNT:1
TURNPLAYER:1

---

# DecliningTheReplay_AlsoPassesTheTurn
#// The control. Declining never reaches the nested ActivateCard, so this passed even before the fix —
#// which is exactly why it is worth keeping: a "fix" that only corrected the accept path would look
#// complete while the decline path was never in question, and a LATER change that moved the
#// after-action into the shared handler would break this one first.
#// The unit stays defeated (the defeat is not conditional on the replay) and the discard holds it plus
#// the event itself.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: [SEC_080:1:0]
WithP2Hand: [ASH_247]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
TURNPLAYER:1

---

# TheREPORTEDLine_TrapFieldRespondsAndTheTurnSTILLPasses
#// The scenario exactly as filed: P2 replays their unit off ASH_247, the entering unit trips P1's
#// HMW_171 Trap Field (a base-hosted "when a non-leader ground unit enters play" reaction owned by the
#// NON-ACTIVE player), P1 accepts, and the turn must still pass to P1 — one action, one swap.
#//
#// ⚠ The Trap Field turned out to be a BYSTANDER: the extra action reproduces with no Trap Field on the
#// board at all (first section). It is kept because it is the reported line and because it is the only
#// section here where a CROSS-PLAYER reaction resolves inside the caster's action — a shape that has
#// produced its own turn-ordering bugs elsewhere, and one a fix to the nested play could plausibly break.
#//
#// Trap Field defeats itself to deal 3, so P1's base ends with no upgrade and the replayed SOR_046
#// (a 3/7) survives on 3 damage — a 3/3 would die to it and could not carry the assertion.
#//
#// ⚠ The `P1>Drain` before the answer is REQUIRED, not filler. P2's action leaves P1 holding an
#// UNDISPATCHED RESOLVE_TRIGGER — the Trap Field offer does not exist yet — and answering at that
#// point lands on the trigger entry and CANCELS it, which presents exactly as "the reaction did
#// nothing" (measured: DAMAGE:0). Drain executes the trigger, then the YESNO is there to answer.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: [SOR_046:1:0]
WithP2Hand: [ASH_247]
WithP1BaseUpgrade: HMW_171

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
TURNPLAYER:1

---

# TrapFieldDECLINED_TurnStillPasses_AndTheUpgradeSurvives
#// The "you may" half: declining leaves Trap Field attached and the entering unit undamaged, and must
#// not disturb the turn either. Pairs with the section above so neither the accept nor the decline path
#// can regress alone.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: [SOR_046:1:0]
WithP2Hand: [ASH_247]
WithP1BaseUpgrade: HMW_171

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>Drain
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
TURNPLAYER:1
