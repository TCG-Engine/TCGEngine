# PlayingACheapUnitHealsOne
#// HMW_115 Leia Organa, These Are My Friends (2/3, Rebel/Official) — "When you play another unit that
#// costs 3 or less: Heal 1 damage from your base."
#// P1's base starts on 5 damage; playing SOR_095 Battlefield Marine (printed cost 2) heals it to 4.
#// COVERAGE: offer=N/A — STRUCTURAL: neither half selects anything. The trigger is "when you play
#//           another unit that costs 3 or less" (an observed event, not a choice) and the heal is fixed
#//           on your own base. ·
#//           decline=N/A (mandatory, no cost) · reqboundary=N/A (nothing written across a decision) ·
#//           modes=2P ONLY ("you play" / "your base" are both self-scoped).
#//           control=ControlChange_P1ControlsP2OwnedLeia_P1PlaysCheapUnit_HealsP1BaseOnly +
#//             ControlChange_OwnerP2PlaysCheapUnit_NothingHeals (controller, not owner, in both directions)

## GIVEN
CommonSetup: ggw/bgw/{
  myResources:6;
  myBaseDamage:5
}
P1OnlyActions: true
WithP1GroundArena: HMW_115:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:2

---

# CostExactlyThreeStillHeals
#// The boundary: "3 or less" includes 3. SOR_237 Alliance X-Wing is printed cost 3.
#// COST is always the PRINTED cost, so no discount or aspect penalty shifts this comparison.

## GIVEN
CommonSetup: ggw/bgw/{
  myResources:8;
  myBaseDamage:5
}
P1OnlyActions: true
WithP1GroundArena: HMW_115:1:0
WithP1Hand: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4

---

# CostFourDoesNotHeal
#// The other side of the boundary: SOR_046 Consular Security Force is printed cost 4, so nothing heals.

## GIVEN
CommonSetup: ggw/bgw/{
  myResources:8;
  myBaseDamage:5
}
P1OnlyActions: true
WithP1GroundArena: HMW_115:1:0
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:2

---

# LeiaHerselfEnteringDoesNotHeal
#// "ANOTHER unit" — Leia is cost 1, so without the self-exclusion her own arrival would heal. Playing
#// her into an empty board must NOT heal.

## GIVEN
CommonSetup: ggw/bgw/{
  myResources:6;
  myBaseDamage:5
}
P1OnlyActions: true
WithP1Hand: HMW_115

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_115

---

# AnOpponentPlayingACheapUnitDoesNotHeal
#// "When YOU play" — an enemy playing a cheap unit heals nothing, and it must not heal the opponent's
#// base either.

## GIVEN
CommonSetup: ggw/ggw/{
  myBaseDamage:5;
  theirResources:6;
  theirBaseDamage:5
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArena: HMW_115:1:0
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P1BASEDMG:5
P2BASEDMG:5

---

# HealOnAnUndamagedBaseIsACleanNoOp
#// The heal clamps at 0 — playing a cheap unit with an undamaged base does nothing and raises no
#// decision (it is not optional and has no target choice).

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_115:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1NODECISION
P1GROUNDARENACOUNT:2

---

# ACheapSPACEUnitAlsoHeals
#// "another unit" carries no arena restriction, so a cheap unit entering the SPACE arena counts too.
#// SOR_225 TIE/ln Fighter is printed cost 2.

## GIVEN
CommonSetup: ggw/bgw/{
  myResources:8;
  myBaseDamage:5
}
P1OnlyActions: true
WithP1GroundArena: HMW_115:1:0
WithP1Hand: SOR_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P1SPACEARENACOUNT:1

---

# ControlChange_P1ControlsP2OwnedLeia_P1PlaysCheapUnit_HealsP1BaseOnly
#// CONTROL CHANGE. "When YOU play another unit … Heal 1 damage from YOUR base." — both "you" and "your"
#// are Leia's CONTROLLER, not her owner. P1 controls a Leia OWNED by P2 (the end state after a
#// take-control effect) and plays SOR_095 Battlefield Marine (printed cost 2): P1's base heals 5 → 4 and
#// P2's (the owner's) base is untouched at 5. An observer keyed on Owner would heal the wrong base or
#// never fire.
#// PREVIEW SET: no official ruling; per CR a triggered ability of an in-play unit belongs to its controller.

## GIVEN
CommonSetup: ggw/ggw/{
  myResources:6;
  myBaseDamage:5;
  theirBaseDamage:5
}
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_115:2
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:4
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1NODECISION

---

# ControlChange_OwnerP2PlaysCheapUnit_NothingHeals
#// The control-change NEGATIVE: P2 OWNS the Leia but P1 controls her, so P2 playing a cheap unit is not
#// "you play" from Leia's point of view — neither base heals. Pairs with the section above: together
#// they pin the observer to the CONTROLLER in both directions (P1 playing heals P1; P2 playing does
#// nothing).

## GIVEN
CommonSetup: ggw/ggw/{
  myBaseDamage:5;
  theirResources:6;
  theirBaseDamage:5
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArenaControlled: HMW_115:2
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P1BASEDMG:5
P2BASEDMG:5
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
