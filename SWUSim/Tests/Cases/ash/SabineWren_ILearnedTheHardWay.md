# ShieldAttach_ExhaustGround
#// ASH_208 Sabine Wren (Ground, 4/5, Shielded, cost 5) — "When 1 or more upgrades attach to this unit
#// (including from Shielded): you may exhaust a ground unit." Playing Sabine gives her a Shield (Shielded),
#// which counts as an upgrade attaching, so P1 may exhaust a ground unit — here the enemy SOR_046.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_208}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ShieldAttach_Decline_NoExhaust
#// ASH_208 Sabine Wren — the exhaust is optional. When her Shield attaches on play, P1 declines, so the
#// enemy SOR_046 stays ready (and Sabine still gets her Shield).
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_208}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# MultipleUpgradesSameTurn_TriggersEach
#// ASH_208 Sabine Wren — the ability is not once-per-turn. Two separate upgrades attached to her in the same
#// phase each trigger it. P1 plays Pointless to Resist (ASH_054) onto Sabine → exhaust one enemy; then plays
#// a second Pointless to Resist onto Sabine → exhaust the other enemy. Both enemies end EXHAUSTED.
## GIVEN
CommonSetup: yyw/yyk/{myResources:10}
WithP1Hand: [ASH_054 ASH_054]
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_208
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# UpgradeOnOtherFriendlyUnit_NoTrigger
#// ASH_208 Sabine Wren — her ability only cares about upgrades attaching to HER. Playing Pointless to Resist
#// (ASH_054) onto a different friendly unit (Yoda SOR_045) does NOT trigger Sabine, so no exhaust prompt
#// appears and the enemy SOR_046 stays READY.
## GIVEN
CommonSetup: yyw/yyk/{myResources:10}
WithP1Hand: ASH_054
WithP1GroundArena: ASH_208:1:0
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_045
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# OpponentAttachesUpgradeToHer_Triggers
#// ASH_208 Sabine Wren — the ability triggers on ANY upgrade attaching to her, including one the opponent
#// attaches. P2 plays Condemn (SEC_038, an enemy-attached upgrade) onto Sabine; because Sabine's controller
#// is P1, P1 gets the "may exhaust a ground unit" choice and exhausts the enemy SOR_046.
## GIVEN
CommonSetup: yyw/yyk/{theirResources:10;theirhandCardIds:SEC_038}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_208
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ExperienceTokensFromEffect_TriggersOnce
#// ASH_208 Sabine — Experience tokens granted by an effect count as upgrades attaching. LOF_239 gives Sabine
#// 2 Experience at once → her "when 1 or more upgrades attach" fires exactly ONCE (not per-token); P1 exhausts
#// the enemy SOR_046, and no second prompt remains.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:LOF_239}
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# UpgradeOnEnemyUnit_NoTrigger
#// ASH_208 Sabine Wren — her ability only fires for upgrades attaching to HER, not to enemy units. P2 plays
#// Pointless to Resist (ASH_054) onto their OWN unit (SOR_046, auto-targeted as P2's lone unit). Sabine does
#// not trigger: no exhaust prompt for P1, Sabine stays READY, and the upgrade lands on the enemy unit.
## GIVEN
CommonSetup: yyw/yyk/{theirResources:10;theirhandCardIds:ASH_054}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:ASH_208
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# UpgradeAttach_ClosesTheAction_TurnPasses
#// PLAYER REPORT (Twin Suns, no game id): "played Mastery on Sabine - I could exhaust someone like
#// intended but it automatically gave me another action. After having played Mastery, I could attack."
#//
#// EVERY SECTION ABOVE USES `P1OnlyActions: true`, WHICH IS WHY THIS SHIPPED. That directive claims
#// initiative and makes the other seats auto-pass, so TURNPLAYER is unobservable - a turn that never
#// moved looks identical to one that moved and came back. This section drops it.
#//
#// ROOT CAUSE: _SWUFinalizeUpgradeAttach counts Sabine's attach trigger in $triggered and therefore
#// SKIPS its own SWUAfterAction(), handing the close to whatever resolves the trigger. Sabine queues
#// the generic EXHAUST_UNIT continuation - a UNIVERSAL handler that exhausts and returns, closing
#// nothing. The action stays open and the player keeps the turn.
#//
#// THE CARD CHOICE IS LOAD-BEARING. LAW_129 Mastery has NO ability of its own (its only text is a cost
#// reduction), so Sabine's trigger is the sole thing that "triggered" and the missing close is exposed.
#// My first attempt used LAW_127 Kill Switch and PASSED - Kill Switch has its own "When Played: Exhaust
#// attached unit", whose trigger closes the action and masks this completely.
#// Mastery does not prompt for a host here (one legal target), so the first pending decision after the
#// play is already Sabine's exhaust offer.

## GIVEN
CommonSetup: yyw/yyk/{myResources:5}
SkipPreGame: true
WithP1Hand: LAW_129
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2
NOEXTRAACTION

---

# UpgradeAttach_DECLINED_StillClosesTheAction
#// The other half: the exhaust is a "you may", and declining must close the action just the same.
#// This is the branch a naive fix breaks - a close queued behind the offer is skipped when the player
#// answers PASS unless it is flagged dontSkipOnPass (the sticky-PASS trap), leaving the decline path
#// still handing out a free action.

## GIVEN
CommonSetup: yyw/yyk/{myResources:5}
SkipPreGame: true
WithP1Hand: LAW_129
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:READY
TURNPLAYER:2
NOEXTRAACTION

---

# UpgradeWithItsOwnTrigger_ClosesExactlyOnce
#// CONTROL, and the section that guards the FIX rather than the bug. LAW_127 Kill Switch brings its own
#// "When Played: Exhaust attached unit", so two things want to end this action: Kill Switch's trigger
#// and Sabine's. Exactly one close may happen - NOEXTRAACTION reads the close ledger directly, so a fix
#// that queues an unconditional second close reds here even though the turn still looks right.

## GIVEN
CommonSetup: yyw/yyk/{myResources:5}
SkipPreGame: true
WithP1Hand: LAW_127
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
TURNPLAYER:2
NOEXTRAACTION

---

# ShieldOnPlay_ClosesExactlyOnce
#// The OTHER path into Sabine's trigger, and the one the fix must not disturb: her own Shielded gives
#// her a Shield as she enters play, which is an upgrade attaching. That route goes through
#// DoGiveShieldToken, not _SWUFinalizeUpgradeAttach, and the PLAY already owns the close - so adding a
#// close to the attach path must not produce a second one here.

## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_208}
SkipPreGame: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2
NOEXTRAACTION

---

# TwinSuns_UpgradeAttach_ClosesTheAction_TurnPasses
#// The reported FORMAT. The defect is seat-count independent, but Twin Suns is where it actually hurts:
#// "in premier this would be fine since I could just pass but in ts you can't".

## GIVEN
CommonSetup3P: yyw/yyk/yyk/{myResources:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: LAW_129
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2
NOEXTRAACTION
