# EnemyUnit_GrantMillsControllerFoe
#// TWI_047 Satine Kryze — the grant applies to ENEMY units too, and each unit's controller uses it to mill
#// THEIR opponent's deck. P1 controls Satine; P2 controls SOR_095 (3/3). On P2's turn, P2 activates their
#// SOR_095's granted Action → discards ceil(3/2) = 2 from P1's deck (P2's opponent). Proves enemy units
#// receive the grant and target the granting player's own deck.
## GIVEN
CommonSetup: bbw/rrk/{}
WithActivePlayer: 2
WithP1GroundArena: TWI_047:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P2>UseUnitAbility:myGroundArena-0
## EXPECT
P1DISCARDCOUNT:2
P1DECKCOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# FriendlyUnit_RoundedUp
#// TWI_047 Satine Kryze — a friendly non-Satine unit also gains the Action, and the amount is rounded UP.
#// SOR_095 (3/3, undamaged) uses the granted Action → ceil(3/2) = 2 (not 1) discarded from P2's deck.
#// Proves both the field-wide grant to an ordinary unit and the round-up.
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P2DISCARDCOUNT:2
P2DECKCOUNT:3
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# NoSatine_NoGrantedAction
#// TWI_047 Satine Kryze — the granted Action is a constant ability that only applies while a Satine is in
#// play. With NO Satine on the board, an ordinary SOR_095 has no activated Action: using it is a no-op —
#// nothing is milled and the unit stays ready. (Guards that the grant is conditional on Satine's presence.)
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:3
P1GROUNDARENAUNIT:0:READY

---

# SelfGrant_MillsHalfHP
#// TWI_047 Satine Kryze (Unit, 0/6, cost 4, Vigilance/Heroism) — "Each unit (including enemy units) gains:
#// Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, rounded
#// up." Satine grants the Action to herself too: with 6 remaining HP she discards ceil(6/2) = 3 from the
#// opponent's (P2's) deck and exhausts.
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P2DISCARDCOUNT:3
P2DECKCOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# UsesRemainingHP_NotPrinted
#// TWI_047 Satine Kryze — the amount uses the unit's REMAINING HP, not printed HP. SOR_046 (3/7) with 2
#// damage has 5 remaining HP → ceil(5/2) = 3 discarded from P2's deck. (Printed 7 would give ceil(7/2) = 4,
#// so 3 confirms remaining-HP is used.)
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP1GroundArena: SOR_046:1:2
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P2DISCARDCOUNT:3
P2DECKCOUNT:3
P1GROUNDARENAUNIT:1:EXHAUSTED
