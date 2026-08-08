# PutUnitOnDeck
#// LOF_200 Qui-Gon Jinn (7/5) — Ambush + When Defeated: may choose a non-leader ground unit; its owner
#// puts it on the top or bottom of their deck. Pre-damaged Qui-Gon attacks and defeats the enemy 3/1,
#// dying to the counter; on death P1 chooses the surviving enemy 3/7, whose owner (P2) puts it on top of
#// their deck.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Top

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:1

---

# PutUnitOnBottomOfDeck
#// LOF_200 Qui-Gon Jinn — the owner may instead put the chosen unit on the BOTTOM of their deck. Same trade
#// as the top-of-deck case (pre-damaged Qui-Gon defeats the enemy 3/1 and dies to the counter), but P2 sends
#// the surviving 3/7 to the bottom: the seeded SOR_111 stays on top and the deck grows to 2. (Intended: "...put it
#// on bottom of their deck".)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_111

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Bottom

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_111

---

# ChooseFriendlyUnit_OwnerP1Chooses
#// LOF_200 Qui-Gon Jinn — "its owner" can be P1. Pre-damaged Qui-Gon defeats an enemy 3/3 and dies to the
#// counter; on death P1 targets their OWN Battlefield Marine (SOR_095), and because P1 owns it P1 is the one
#// prompted for top/bottom. P1 puts it on top of P1's own deck. (Intended: mirror of the "choose a non-leader ground
#// unit and put it on top/bottom" cases with a friendly target.)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: SOR_111

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Top

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095

---

# DeclineSingleLegalTarget_NoCrash
#// LOF_200 Qui-Gon Jinn — the When Defeated is a "YOU MAY choose", so even when exactly ONE legal target
#// exists the prompt must still offer a decline, and declining must resolve cleanly (no crash, no dangling
#// decision, unit untouched). Same trade as PutUnitOnDeck: pre-damaged Qui-Gon defeats the enemy 3/1 and
#// dies to the counter, leaving the enemy 3/7 as the only non-leader ground unit — and P1 declines.
## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DECKCOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION
P2NODECISION

---

# StolenUnitChosen_ItsOWNERPicksTopOrBottom
#// LOF_200 Qui-Gon Jinn — "its OWNER puts it on the top or bottom of THEIR deck". Ownership, not control:
#// P1 controls a P2-OWNED Battlefield Marine (SOR_095). Qui-Gon dies in the trade and P1 chooses that
#// stolen unit — but P2 is the one prompted for top/bottom, and the card goes onto P2's deck, not P1's.
## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_111
WithP2Deck: SOR_111
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:Top
## EXPECT
P1GROUNDARENACOUNT:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_095
P1DECKCOUNT:1

---

# WhenDefeated_UnderEnemyControl_NewControllerChooses
#// LOF_200 Qui-Gon Jinn — the When Defeated resolves for whoever CONTROLS him at the moment of defeat.
#// P2 plays No Glory, Only Results (JTL_043) to take control of P1's Qui-Gon and defeat him, so P2 — not
#// P1 — makes the "choose a non-leader ground unit" call. The only one left is P2's own Battlefield
#// Marine (SOR_095), which P2 owns, so P2 is also the one prompted for top/bottom and it goes onto P2's
#// deck. (Qui-Gon is P1's only non-leader unit, so NGOR's own target choice auto-resolves with no prompt.
#// P2 needs 12 resources: under an Aggression base + Aggression/Villainy leader, NGOR's Vigilance half is
#// uncovered, so its cost 5 becomes 7.)
## GIVEN
CommonSetup: yyw/rrk/{theirResources:12}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043
WithP1Deck: SOR_111
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:Top
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DECKTOPCARD:SOR_095
