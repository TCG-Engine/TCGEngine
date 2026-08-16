# OnAttack_DrawThenRegroupDiscard
#// SHD_203 Zorii Bliss (4/7) — "On Attack: Draw a card. At the start of the regroup phase, discard
#// a card from your hand." The attack draws 1; at regroup start the armed discard fires (MZCHOOSE
#// over the hand) before the regroup draw. Net: 1 drawn − 1 discarded + 2 regroup draws = hand 2.
#// COVERAGE: offer=OnAttack_DrawThenRegroupDiscard (the discard pick is an MZCHOOSE over the hand, named
#//           explicitly) · decline=N/A (the discard is mandatory once armed) · control=N/A (the delayed
#//           effect is bound to the player who attacked, not to Zorii's controller-at-regroup) ·
#//           boundary=OnAttack_DrawThenRegroupDiscard (a card to give up) vs
#//           SourceDefeated_EmptyHandAtRegroup_NothingToDiscard (nothing to give up, and the source is
#//           gone) · reqboundary=SourceDefeated_EmptyHandAtRegroup_NothingToDiscard (the armed delayed
#//           effect must survive Zorii's DEFEAT and the action-phase→regroup boundary, then fizzle
#//           cleanly rather than hang the regroup)

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1GroundArena: SHD_203:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>AnswerDecision:myHand-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DECKCOUNT:0

---

# SourceDefeated_EmptyHandAtRegroup_NothingToDiscard
#// Intended: the delayed discard belongs to the PLAYER once armed — it still fires with Zorii dead — and
#// with an empty hand it fizzles silently instead of hanging the regroup. Zorii (4/7, pre-damaged to 4)
#// attacks SOR_046 (3/7): the On Attack draw resolves first (hand 1, deck 2), then the 3 counter damage
#// takes her to 7 and she is defeated. P1 spends the drawn SOR_095 (on-aspect at cost 2) to empty the
#// hand, then passes into regroup with nothing to discard: no prompt, no discard, and the phase proceeds
#// normally through both resource passes to the 2-card draw. P1's discard holds Zorii alone.

## GIVEN
CommonSetup: gyw/gyw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_203:1:4
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_203
P1HANDCOUNT:2
P1DECKCOUNT:0
P1NODECISION
