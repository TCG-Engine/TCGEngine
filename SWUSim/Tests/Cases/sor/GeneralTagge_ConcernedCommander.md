# GivesExperienceToTroopers
#// SOR_080 General Tagge (2/2) — When Played: give an Experience token to each of
#// up to 3 Trooper units. P1 controls two Troopers — Battlefield Marine (SOR_095,
#// 3/3) and Scout Bike Pursuer (SOR_032, 1/4). Playing Tagge prompts a multi-select;
#// choosing both gives each an Experience token (+1/+1): Marine → 4/4, Scout → 2/5.

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
