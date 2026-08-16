# UseForce_PlayDiscounted
#// LOF_094 Jedi Consular — Action [Exhaust, use the Force]: play a unit from hand at −2. With the Force and
#// SOR_095 (cost 3 → 1) in hand, P1 exhausts the Consular, uses the Force, and plays SOR_095 for 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_094:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DeclineDoesNotDiscountNextUnit
#// LOF_094 Jedi Consular — activating the ability still exhausts the Consular and spends the Force even if
#// the controller declines to play a unit ("choose nothing"), and the -2 discount does NOT leak onto the
#// next unit played that turn. With two Battlefield Marines (cost 2) in hand and 4 resources, P1 activates
#// then declines, then plays a Marine for its FULL cost of 2 → 2 resources left — the next unit gets no
#// discount after the controller declines.
#// (Two units in hand are required: with a single playable unit the target auto-resolves and is played,
#//  so there is no choose-nothing prompt to exercise.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:SOR_095,SOR_095}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_094:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# CannotUseWithoutTheForce
#// LOF_094 Jedi Consular — the ability's cost includes "use the Force (lose your Force token)"; without a
#// Force token the ability can't be activated. With no Force, P1 attempts to use it: it's a no-op — the
#// Consular stays ready, the unit in hand is not played, and no resources are spent. Intended: the
#// ability cannot be used without having the Force.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_094:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# UnaffordableAfterDiscount_NotSelectable
#// LOF_094 Jedi Consular — the −2 is applied at the affordability gate, so a unit that is still too
#// expensive after the discount must not be offered. With ZERO resources only Battlefield Marine
#// (SOR_095, cost 2 → 0) is selectable; Plo Koon (LOF_050, cost 6, +2 off-aspect under a Command base
#// and Command/Heroism leader → 8, −2 → 6) is not. Two copies of the affordable unit on purpose: with a
#// single legal pick the choice auto-resolves and there is no offer left to inspect.
## GIVEN
CommonSetup: ggw/rrk/{myResources:0;handCardIds:SOR_095,SOR_095,LOF_050}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_094:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1
