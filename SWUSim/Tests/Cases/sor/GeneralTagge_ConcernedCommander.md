# GivesExperienceToTroopers
#// SOR_080 General Tagge (2/2) — When Played: give an Experience token to each of
#// up to 3 Trooper units. P1 controls two Troopers — Battlefield Marine (SOR_095,
#// 3/3) and Scout Bike Pursuer (SOR_032, 1/4). Playing Tagge prompts a multi-select;
#// choosing both gives each an Experience token (+1/+1): Marine → 4/4, Scout → 2/5.
#// COVERAGE: offer=Offer_IncludesEnemyTrooper_ExcludesNonTrooperAndTagge (pending SELECTABLEEXACT)
#//           reqboundary=GivesExperienceToTroopers (the multi-pick answer arrives on a later request
#//           than the play) · control=N/A (one-shot When Played; tokens are given outright, no
#//           lingering per-unit marker to survive a control change) · boundary pair=
#//           CapAtThree_FourthTrooperGetsNothing (max 3) + ChooseNone_UpToIncludesZero (zero)
#//           · decline=ChooseNone_UpToIncludesZero

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine (Trooper, 3/3) — index 0
WithP1GroundArena: SOR_032:1:0    # Scout Bike Pursuer (Trooper, 1/4) — index 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENACOUNT:3

---

# NoTroopers_NoDecision
#// SOR_080 General Tagge (2/2) — When Played with no Trooper units in play: the
#// ability fizzles (no targets), so no decision is queued and Tagge simply enters
#// play. P1's only other unit is a non-Trooper (Restored ARC-170, Vehicle).

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0    # Restored ARC-170 (Vehicle — not a Trooper)

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1SPACEARENAUNIT:0:POWER:2

---

# Offer_IncludesEnemyTrooper_ExcludesNonTrooperAndTagge
#// Intended: the pool is "up to 3 TROOPER units" — ANY Trooper, either side's. With P1's Battlefield
#// Marine (SOR_095, Trooper) + Vanguard Infantry (SOR_108, Trooper) + Wampa (SOR_164, Creature) and
#// P2's Volunteer Soldier (SOR_248, Trooper) in play, the offer is exactly the three Troopers: the
#// Wampa and Tagge himself (Imperial Official, not a Trooper) are outside the pool. The decision is
#// left PENDING so the offer itself is what gets asserted.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_108:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_248:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# ChooseTwoOfThree_ThirdTrooperGetsNoToken
#// Intended: "up to 3" allows stopping short of the available Troopers. Three friendly Troopers in
#// play; only two are picked — the third stays bare while the two picked each gain one Experience.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_108:1:0
WithP1GroundArena: SOR_032:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1NODECISION

---

# CapAtThree_FourthTrooperGetsNothing
#// Intended: with four Troopers in play (three friendly + the enemy Volunteer Soldier, all in the
#// pool per the offer section above), the cap is 3 — picking the three friendly ones leaves the
#// enemy Trooper without a token.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_108:1:0
WithP1GroundArena: SOR_032:1:0
WithP2GroundArena: SOR_248:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# ChooseNone_UpToIncludesZero
#// Intended: "up to 3" includes zero — declining the multi-pick gives no tokens to anyone and the
#// play completes cleanly.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_108:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENACOUNT:3
P1NODECISION
