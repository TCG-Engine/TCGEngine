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
