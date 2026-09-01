# OnAttack_NameDiscard
#// SOR_185 Chimaera (Space Unit 8/7, cost 8, Cunning/Villainy, Shielded) — "On Attack: Name a card.
#// An opponent reveals their hand and discards a card with that name from it." Chimaera (in play,
#// ready) attacks P2's base; the On Attack trigger fires first: P1 names "Mission Briefing"
#// (SOR_171). P2 reveals their hand and discards the matching card (SOR_171), keeping the other
#// (SEC_080). Then combat deals Chimaera's 8 power to P2's base.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_171
WithP2Hand: SEC_080

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND

---

# OnAttack_NameDuplicate
#// SOR_185 Chimaera — the text discards "A card with that name" (one copy), not all copies. P2's
#// hand is two Death Star Stormtroopers (SOR_128). P1 names "Death Star Stormtrooper"; exactly ONE
#// copy is discarded (hand 2 → 1, discard 1).

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_128
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Death Star Stormtrooper
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128

---

# OnAttack_NameMiss
#// SOR_185 Chimaera — name a card NOT in the opponent's hand. P1 names "Mission Briefing", but P2's
#// hand is SOR_095 + SOR_128 (neither matches). The opponent still reveals their hand (public log),
#// but nothing is discarded.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SEC_080
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:2
P2DISCARDCOUNT:0
LOGCONTAINS:revealed

---

# OnAttack_RevealPopupOnWhiff
#// SOR_185 Chimaera — name a card NOT in the opponent's hand (a "whiff"). P1 names "Mission Briefing",
#// but P2's hand is SOR_095 + SOR_128 (neither matches), so nothing is discarded. Even on a whiff the
#// player still gets the saved-hand OK popup (mirrors SOR_201 Bodhi Rook), so they can confirm the
#// revealed hand. This test stops BEFORE answering the popup: nothing was discarded
#// (P2DISCARDCOUNT:0), the popup is pending (P1HASDECISION), and combat is not yet dealt (P2BASEDMG:0).

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SEC_080
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing

## EXPECT
P1HASDECISION
P2BASEDMG:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0
LOGCONTAINS:revealed

---

# OnAttack_SavedHandShownAfterAutoDiscard
#// SOR_185 Chimaera (Space Unit 8/7, cost 8, Cunning/Villainy, Shielded) — "On Attack: Name a card.
#// An opponent reveals their hand and discards a card with that name from it." The discard always
#// auto-resolves (copies are identical, so the first matching copy is picked with no player choice),
#// which means the player would never otherwise see the revealed hand. Behavior (mirrors SOR_201
#// Bodhi Rook): a snapshot of the hand is SAVED before the auto-discard, the discard resolves, and the
#// saved snapshot is then shown as a Viper-Probe-Droid (SOR_228) OK popup. This test stops BEFORE
#// answering the popup: the discard has ALREADY happened (P2DISCARDCOUNT:1) and the saved-hand popup
#// is pending (P1HASDECISION) — and combat damage has NOT yet been dealt (P2BASEDMG:0), proving the
#// popup resolves after the discard and before combat.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_171
WithP2Hand: SEC_080

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing

## EXPECT
P1HASDECISION
P2BASEDMG:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
LOGCONTAINS:revealed

---

# TwinSuns_PickerPrecedesTheNAMECARD_ForATransportReason
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Name a card. AN OPPONENT reveals their hand and discards a
#// card with that name from it."
#// ⚠⚠ THE PICKER MUST COME FIRST, and for a HARD TRANSPORT REASON as well as a game one: a card TITLE
#// CONTAINS SPACES, and a DecisionQueue Param row is SPACE-DELIMITED. So the name can only ever travel in
#// $lastDecision, never in a Param — which forces NAMECARD to be the LAST decision in the chain, with
#// everything else the handler needs (the seat) already carried in its Param.
#// ⚠ FILTER to opponents holding a card — an empty hand reveals and discards nothing.
#// Seats 2 and 3 hold cards; SEAT 4 IS EMPTY-HANDED and must NOT be offered.
#// Mutation check: drop the filter and P1OPTIONNOT:P4 reds.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: [SOR_171 SEC_080]
WithP3Hand: [SOR_171 SEC_080]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackSpaceArena:0:P3B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1

---

# Shielded_OnPlayGivesItselfAShield
#// SOR_185 Chimaera — the SHIELDED clause, which had no section of its own (every other section seeds
#// Chimaera straight into the arena, where no play ever happens). "Shielded (When you play this unit,
#// give a Shield token to it.)" P1 plays the 8-cost Chimaera on-aspect (Cunning/Villainy) and it lands
#// in the space arena carrying exactly one Shield token (SOR_T02) as its only upgrade. Shielded is not
#// a When Played ABILITY, so nothing is prompted and the On Attack naming decision does not fire on a
#// play.
#// COVERAGE (whole card, both clauses):
#//   offer — Shielded: N/A (it names its own unit; there is no target choice). On Attack: the seat
#//           picker's exact pool is TwinSuns_PickerPrecedesTheNAMECARD_ForATransportReason
#//           (P2/P3 offered, the empty-handed P4 and P1 itself excluded); the NAMED CARD is a free-text
#//           dropdown, not a board pool, and the DISCARD is auto-resolved from the named title
#//           (OnAttack_NameDuplicate proves only ONE matching copy is taken).
#//   decline — N/A for both clauses: Shielded is not a "you may", and the On Attack name/reveal/discard
#//           is mandatory once the attack is declared. The nearest branch is the whiff
#//           (OnAttack_NameMiss / OnAttack_RevealPopupOnWhiff), where the reveal still happens and
#//           nothing is discarded.
#//   boundary — Shielded_AbsorbsTheFirstDamageThenPops (1 shield → one damage source absorbed, then 0)
#//           and, on the naming clause, OnAttack_NameDuplicate (2 copies named → 1 discarded) vs
#//           OnAttack_NameDiscard (1 copy → 1) vs OnAttack_NameMiss (0 copies → 0).
#//   control — N/A: Shielded resolves at play on the player's own unit, and the On Attack reads "AN
#//           OPPONENT's" hand, a zone reached through the seat picker rather than a "your" word, so
#//           there is no owner-vs-controller reading to get wrong.
#//   reqboundary — Shielded_AbsorbsTheFirstDamageThenPops (the token is written by P1's play request
#//           and consumed by P2's attack in the next one), plus OnAttack_SavedHandShownAfterAutoDiscard
#//           (the saved-hand snapshot is left pending across the discard, before combat damage).

## GIVEN
CommonSetup: yyk/yyk/{myResources:8;handCardIds:SOR_185}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_185
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_T02
P1NODECISION

---

# Shielded_AbsorbsTheFirstDamageThenPops
#// SOR_185 Chimaera — what the Shielded token is FOR. P1 plays Chimaera (it shields itself), the turn
#// passes, and P2's TIE/ln Fighter attacks it: the Shield absorbs all of the incoming combat damage and
#// is then defeated, so Chimaera ends on 0 damage with 0 shields, while Chimaera's own 8 power kills the
#// 2/1 attacker. The token is written in P1's play request and consumed in P2's attack request.

## GIVEN
CommonSetup: yyk/yyk/{myResources:8;handCardIds:SOR_185;theirResources:5}
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_185
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENACOUNT:0
