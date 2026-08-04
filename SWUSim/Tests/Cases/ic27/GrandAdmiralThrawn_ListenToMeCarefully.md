# WhenPlayed_GivesExperienceAndSentinel
#// IC27_024 Grand Admiral Thrawn (Listen to Me Carefully) — 6 cost, 4/4, Vigilance+Villainy, Ground,
#//   Imperial/Official (unique).
#// Text: "When Played / On Attack / When Defeated: You may give an Experience token to a friendly
#//   unit. It gains Sentinel for this phase."
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
#// window. He may target HIMSELF here ("a friendly unit", not "another"), but the Marine is chosen.

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

# OnAttack_MayTargetHimself
#// SCOPE: "a friendly unit" carries no "another" qualifier, so Thrawn is a legal target for his own
#// ability — he becomes 5/5 with Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_024
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeated_GivesExperienceToASurvivor
#// THE THIRD DISPATCH PATH. Thrawn attacks into a body that kills him, and the ability still resolves
#// for a surviving friendly unit. ⚠ The collection runs BEFORE cleanup, so the dying Thrawn is still
#// occupying ground index 0 and the survivor is addressed as index 1 — and Thrawn himself must NOT be
#// offered as a recipient (a unit leaving play cannot take the token).

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_024:1:3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

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
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

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
