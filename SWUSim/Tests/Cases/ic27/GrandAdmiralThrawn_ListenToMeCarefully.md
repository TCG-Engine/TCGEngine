# WhenPlayed_GivesExperienceAndSentinel
#// IC27_024 Grand Admiral Thrawn (Listen to Me Carefully) — 6 cost, 4/4, Vigilance+Villainy, Ground,
#//   Imperial/Official (unique).
#// Text: "When Played/On Attack/When Defeated: You may give an Experience token to another friendly
#//   unit. It gains Sentinel for this phase." (The preview mock read "a friendly unit"; the official
#//   text, 2026-11-07, says "another" — Thrawn is never his own target.)
#// ONE ability on THREE trigger windows — each is a separate dispatch path and needs its own section.
#// ⚠ The stub generator detected only the WhenDefeated window: it matched the tight slash form
#// ("When Played/") but not the SPACED one this card uses, so the other two halves were silent no-ops.
#// Experience is +1/+1, so the Marine reads 4/4; Sentinel is a phase grant.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_Declined_NothingGiven
#// TAKE/DECLINE: it is a "may", so passing must leave the unit untouched — no token, no Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# OnAttack_GivesExperienceAndSentinel
#// THE SECOND DISPATCH PATH. Thrawn attacks the base; the same ability fires from the On Attack
#// window and gives the Marine its token.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# OnAttack_CannotTargetHimself
#// SCOPE: "ANOTHER friendly unit". Thrawn attacks alone, so there is no legal target: no prompt, and he
#// stays 4/4 without Sentinel. (Until the official text this section asserted the opposite — the mock
#// read "a friendly unit".)

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:IC27_024
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION

---

# WhenPlayed_OffersOnlyOtherFriendlyUnits
#// OFFER ASSERTED: two friendly Marines, an enemy unit, and Thrawn himself (index 2 once played). The pool
#// is exactly the two Marines — "another" drops Thrawn, "friendly" drops the enemy.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:IC27_024
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WhenPlayed_AloneNoPrompt
#// "Another" on the When Played path: Thrawn is in play when it resolves, but is the only friendly unit —
#// nothing to offer, no prompt, no token on himself.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_024
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION

---

# WhenDefeated_GivesExperienceToASurvivor
#// THE THIRD DISPATCH PATH. Thrawn attacks into a body that kills him, and BOTH halves resolve for the
#// surviving friendly unit. On Attack (Thrawn still in play, survivor at ground index 1) gives SOR_095 its
#// first token; Thrawn dies to the counter-damage, and When Defeated — resolving after cleanup, so the
#// survivor has shifted to index 0 — gives it a second. 3 + 2 = 5 power, and nothing is left pending.
#// (Rewritten 2026-09-11, user-approved: this section used to answer only the On Attack prompt, so its
#// POWER:4 came from On Attack alone and When Defeated was never reached — which hid the positional
#// self-exclusion bug that excluded the survivor.)

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# EnemyUnitIsNotAFriendlyTarget
#// THE LOAD-BEARING NEGATIVE on "friendly": with only an enemy unit available there is no legal
#// target, so Thrawn played alone gives nothing away and raises no prompt.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION

---

# SentinelExpiresButExperienceDoesNot
#// DURATION EDGE, and the two halves have DIFFERENT lifetimes: Sentinel is "for this phase" and must
#// expire at regroup, while the Experience token is a permanent upgrade that must survive it.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myhandCardIds:IC27_024}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenDefeated_OnAttackDeclined_SurvivorStillOffered
#// Game-log triage (2026-09-11): the When Defeated half "had no effect" with a friendly unit still in play.
#// By the time it resolves after a combat death, Thrawn has been cleaned up and the survivor has SHIFTED
#// into his slot — so the positional self-exclusion excluded the SURVIVOR and left nothing to offer.
#// (WhenDefeated_GivesExperienceToASurvivor above never reaches this: its one answer goes to the On
#// Attack prompt.) Here P1 declines On Attack, Thrawn dies to SOR_046, and When Defeated still gives
#// SOR_095 the Experience token and Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:PASS
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
LOGCOUNT:0:had no effect
