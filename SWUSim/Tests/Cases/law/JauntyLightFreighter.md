# ExpPerAspect
#// LAW_147 Jaunty Light Freighter (1/1, space) — When Played: give an Experience token to this unit for
#// each different aspect among units you control. SOR_095 (Command,Heroism) + SOR_225 (Villainy) + the
#// Freighter (Command,Heroism) = 3 distinct aspects -> 3 Experience (1/1 -> 4/4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:LAW_147
P1SPACEARENAUNIT:1:UPGRADECOUNT:3
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:4

---

# ExpItselfOnlyTwoAspects
#// LAW_147 — with no other friendly units, the Freighter counts only its own two aspects
#// (Command + Heroism) -> 2 Experience tokens (1/1 -> 3/3).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_147
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3

---

# ExpOverlappingAspectNoIncrease
#// LAW_147 — a friendly Battlefield Marine (Command) shares an aspect with the Freighter
#// (Command + Heroism) and adds no new aspect, so the count stays 2 -> 2 Experience (1/1 -> 3/3).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_147
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3

---

# AspectCountIncludesAControlledEnemyOwnedUnit
#// LAW_147 Jaunty Light Freighter — "for each different aspect among units YOU CONTROL": the census reads
#// control, not ownership. SOR_225 TIE/ln Fighter (Villainy) sits in P1's space arena but is OWNED by P2 —
#// the end state after a control-take — and it is the only source of a third aspect, since the Freighter
#// itself supplies only Command + Heroism. Counting it gives 3 distinct aspects and 3 Experience tokens
#// (1/1 -> 4/4); skipping it because P1 does not own it would stop at 2 and leave a 3/3, which is exactly
#// what ExpItselfOnlyTwoAspects records. The seeded unit takes index 0, so the Freighter lands at index 1.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1SpaceArenaControlled: SOR_225:2
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:LAW_147
P1SPACEARENAUNIT:1:UPGRADECOUNT:3
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:4

---

# AspectCountExcludesAnOwnedButEnemyControlledUnit
#// LAW_147 Jaunty Light Freighter — the mirror. The same SOR_225 (Villainy) is now OWNED BY P1 but
#// CONTROLLED BY P2 and sits in P2's space arena. P1 owns a Villainy unit and controls none, so Villainy
#// must not enter the census: the Freighter counts only its own Command + Heroism and gets 2 Experience
#// (1/1 -> 3/3). An ownership-based census would have produced the 4/4 the section above asserts, so the
#// pair pins the count to control in both directions.
#//
#// COVERAGE: control=AspectCountIncludesAControlledEnemyOwnedUnit + this section (the aspect census counts
#//           units you CONTROL: a P2-owned unit in P1's arena adds its aspect, a P1-owned unit in P2's
#//           arena does not) · offer=N/A (the ability targets nothing — the tokens always go to this unit)
#//           · decline=N/A (mandatory, no "you may") · boundary pair=ExpPerAspect (third aspect present ->
#//           3 tokens) vs ExpItselfOnlyTwoAspects / ExpOverlappingAspectNoIncrease (no new aspect -> 2),
#//           plus the control pair above · reqboundary=N/A (the trigger raises no decision)

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP2SpaceArenaControlled: SOR_225:1
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_147
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
