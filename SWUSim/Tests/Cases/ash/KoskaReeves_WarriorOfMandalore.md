# NoToken_NoSentinel
#// ASH_079 Koska Reeves — negative guard: with NO token unit controlled, she does NOT have Sentinel.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_079:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenPlayed_FriendlyDefeated
#// ASH_079 Koska Reeves (Ground, 4/4) — When Played: if a friendly unit was defeated this phase, create a
#// Mandalorian token. P1's Stormtrooper dies attacking first (sets the phase flag); then ASH_079 is played
#// → a Mandalorian token is created. The created token (a Token Unit) ALSO turns on Koska's "while you
#// control a token unit, gain Sentinel" passive.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_079}
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# WhenPlayed_NoFriendlyDefeated_NoToken
#// ASH_079 Koska Reeves — When Played only creates a token if a friendly unit was defeated THIS phase. With
#// nothing defeated, Koska enters alone: no Mandalorian token, and (no token unit) no Sentinel.
## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_079}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenPlayed_StolenEnemyDefeated_CountsAsFriendly
#// ASH_079 Koska Reeves — a unit taken under your control and then defeated counts as a friendly defeat. P1
#// plays No Glory, Only Results (JTL_043: take control of a non-leader unit, then defeat it) on the enemy
#// Wampa (SOR_164), so the Wampa dies under P1's control. Playing Koska then creates a Mandalorian token
#// (and the token unit turns on her Sentinel).
## GIVEN
CommonSetup: yrw/grw/{myResources:16}
WithP1Hand: [JTL_043 ASH_079]
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# WhenPlayed_OpponentStealsFriendly_NoToken
#// ASH_079 Koska Reeves — control at the moment of defeat is what matters. P2 plays No Glory, Only Results
#// (JTL_043) on P1's Porg (LOF_254), taking control of it and then defeating it — so Porg dies under P2's
#// control, NOT as a friendly unit. P1 then plays Koska: no friendly unit was defeated, so no Mandalorian
#// token is created and Koska has no Sentinel.
## GIVEN
CommonSetup: yrw/grw/{myResources:9;theirResources:9}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: ASH_079
WithP2Hand: JTL_043
WithP1GroundArena: LOF_254:1:0
## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ExperienceUpgrade_IsNotTokenUnit_NoSentinel
#// ASH_079 Koska Reeves — her Sentinel keys on controlling a token UNIT, not a token upgrade. Koska carrying
#// an Experience token (SOR_T01, a token UPGRADE) with no token unit in play does NOT gain Sentinel.
## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_079:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SelfConditionUnmet_StillGetsSentinelFromAnUPGRADE
#// ASH_079 Koska Reeves — "While you control a TOKEN unit, this unit gains Sentinel." Here she controls
#// NO token, so her own condition fails — but she wears SOR_057 Protector ("Attached unit gains
#// Sentinel"), which is a completely independent grant. Both sources must be consulted.
#// ⚠ REGRESSION GUARD for an ORDERING bug found while restructuring HasConditionalKeyword_Sentinel:
#// the self-conditional switch used to run BEFORE the upgrade grants, and Koska's case `return false`
#// (no token) short-circuited the whole function — so the Protector on her back was never reached and
#// she had no Sentinel at all. The self switch is now LAST, so a failing self-condition means "no SELF
#// grant", not "no Sentinel from any source".
#// Verified against both orderings: PASSES with the switch last, FAILS with it first.

## GIVEN
CommonSetup: bbw/bgw/{}
WithP1GroundArena: ASH_079:1:0
WithP1GroundArenaUpgrade: 0:SOR_057

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SelfConditionUnmet_AndNoUpgrade_NoSentinel
#// ASH_079 Koska Reeves — the control that makes the section above discriminating: same board, upgrade
#// REMOVED. With neither a token unit nor a Protector she has no Sentinel from any source.
#// Without this pair, a "Sentinel is always on" bug would satisfy the section above.

## GIVEN
CommonSetup: bbw/bgw/{}
WithP1GroundArena: ASH_079:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
