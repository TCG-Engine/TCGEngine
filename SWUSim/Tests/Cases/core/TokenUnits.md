# CreatedTokens_EnterExhausted
#// CORE TOKEN RULE — created token units enter play EXHAUSTED by default. TWI_237 Droid
#// Deployment creates 2 Battle Droid tokens (TWI_T01, 1/1); both sit in the ground arena
#// exhausted the moment they arrive.
#//
#// COVERAGE: offer=Captured_Ceases_NotDefeated (tokens appear in enemy-unit pick pools like real
#//           units) · reqboundary=ReadyAtRegroup_ThenFightsLikeAUnit (token state crosses the
#//           full phase round-trip) · control=N/A here (a created token always enters under its
#//           creator's control; the token-upgrade control rule is covered in
#//           core/ExperienceToken.md and core/ShieldToken.md) · boundary pair=
#//           CombatDefeat_FiresWhenDefeatedObservers + DirectDefeat_FiresWhenDefeatedObservers +
#//           StatReduction_FiresWhenDefeatedObservers (leaves play AS a defeat) vs
#//           Captured_Ceases_NotDefeated (leaves play WITHOUT a defeat) · decline=the Krell
#//           When-Defeated is "you may" and is exercised on its YES branch; token creation itself
#//           has no decline
#// Token-never-enters-discard is covered by core/TokensCeaseOnLeavingPlay.md; token
#// return-to-hand ceasing by sor/Waylay.md. Intended: a token moved out of the arena by a
#// NON-defeat effect (bounce, capture) ceases WITHOUT registering as a defeat — no When-Defeated
#// observers fire. Those observer-silence scenarios are deferred: the engine currently raises
#// granted When-Defeated triggers on a token bounce and on a token capture (real units are
#// handled correctly on both paths).

## GIVEN
CommonSetup: gyk/grw/{myResources:2;handCardIds:TWI_237}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# ReadyAtRegroup_ThenFightsLikeAUnit
#// CORE TOKEN RULE — token units ready at the regroup phase and attack/take damage like normal
#// units. Two exhausted Battle Droids cross the phase boundary and ready; one then attacks the
#// enemy SOR_046 (3/7), dealing its 1 power and dying to the 3 back-damage — it ceases (no
#// discard entry). The other droid ends the flow still ready and in the arena.

## GIVEN
CommonSetup: gyk/grw
P1OnlyActions: true
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:0

---

# CombatDefeat_FiresWhenDefeatedObservers
#// CORE TOKEN RULE — a token unit's defeat REGISTERS as a defeat: it fires When-Defeated
#// observers. SOR_105 General Krell grants each other friendly unit "When Defeated: You may draw
#// a card"; the Battle Droid dies attacking the enemy Marine (3/3) and the granted trigger fires
#// — YES draws a card. The token itself ceases (P1 discard stays empty).

## GIVEN
CommonSetup: ggw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DirectDefeat_FiresWhenDefeatedObservers
#// CORE TOKEN RULE — a token defeated by a defeat EFFECT (SOR_078 Vanquish on P1's own Battle
#// Droid) also registers as a defeat: the Krell-granted When-Defeated fires and draws. Only the
#// spent Vanquish reaches a discard pile.

## GIVEN
CommonSetup: bbw/grw/{myResources:5;handCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# StatReduction_FiresWhenDefeatedObservers
#// CORE TOKEN RULE — a token defeated by an HP-reducing static (SHD_037 Supreme Leader Snoke:
#// "Each enemy non-leader unit gets -2/-2") registers as a defeat too. P2 plays Snoke; P1's 1/1
#// Battle Droid drops to 0-or-less HP and is defeated, Krell survives at 3/2, and the granted
#// When-Defeated fires on P1's seat (cross-player reaction — drained on P1 before answering).

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: SHD_037
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Deck: SOR_128

## WHEN
- P2>PlayHand:0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SHD_037

---

# Captured_Ceases_NotDefeated
#// CORE TOKEN RULE — a token that would be CAPTURED (SHD_131 Take Captive) is moved out of the
#// arena and ceases: the captor holds NO captive afterwards and the token reaches no discard
#// pile. The capture offer is real (two P1 ground units) and P2 picks the token.
#// Intended: this removal is NOT a defeat, so When-Defeated observers must stay silent — that
#// half is deferred: the engine currently raises granted When-Defeated triggers when a token is
#// captured or bounced (see the note in the file header).

## GIVEN
CommonSetup: bbw/ggk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: SHD_131
WithP2GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1

---

# Bounced_NoWhenDefeatedObservers
#// JUDGE RULING (2026-08-13, CR 5.8/§369): a bounced token is NOT defeated — it is removed from the
#// game. With SOR_105 General Krell in play (grants "When Defeated: you may draw"), Waylaying the
#// friendly Clone Trooper token raises NO draw prompt and draws nothing; the token just ceases.

## GIVEN
CommonSetup: ggw/yyk/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Hand: SOR_222

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:0

---

# Captured_NoWhenDefeatedObservers
#// The capture route under the same ruling: P2 plays SHD_131 Take Captive on the token with Krell in
#// play — the token ceases (captor gains NO captive), no draw prompt, nothing drawn.

## GIVEN
CommonSetup: ggw/ggk/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_164:1:0
WithP2Hand: SHD_131

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
