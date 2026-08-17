# WhenPlayedHealTwo
#// LAW_035 Ezra Bridger (4/5, Raid 1) — When Played: heal 2 from a unit (4 if you control an Aggression
#// or Cunning unit). Here P1 controls neither -> heal 2 from the damaged SOR_046 (4 -> 2).

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:4
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayedHealFourWithAggression
#// LAW_035 Ezra Bridger — heals 4 (instead of 2) while controlling an Aggression unit. P1 controls SOR_128
#// (Aggression) alongside the damaged SOR_046 (5 damage) -> heal 4 -> 1 damage remains.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SOR_128:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenPlayedHealFourWithCunning
#// LAW_035 Ezra Bridger — heals 4 while controlling a Cunning unit. P1 controls SOR_213 (Cunning) alongside
#// the damaged SOR_046 (5 damage) -> heal 4 -> 1 damage remains.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SOR_213:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# HealFourEnabledByAControlledEnemyOwnedAggressionUnit
#// LAW_035 Ezra Bridger — "If you CONTROL a Aggression or Cunning unit": the aspect check reads control,
#// not ownership. SOR_128 Death Star Stormtrooper (Aggression/Villainy) sits in P1's ground arena but is
#// OWNED by P2 — the end state after a control-take — and it is the only Aggression/Cunning body on the
#// board. Ezra himself (Vigilance/Command/Heroism) and the damaged SOR_046 (Vigilance/Heroism) supply
#// neither aspect, so the upgraded heal can only come from the P2-owned unit P1 controls. SOR_046 starts
#// at 5 damage and ends at 1, i.e. 4 healed, not 2.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP1GroundArenaControlled: SOR_128:2
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# OwnedButEnemyControlledAggressionUnitDoesNotEnableHealFour
#// LAW_035 Ezra Bridger — the mirror that makes the control read provable in both directions. The same
#// SOR_128 (Aggression) is now OWNED BY P1 but CONTROLLED BY P2, sitting in P2's arena. P1 owns an
#// Aggression unit and still does not CONTROL one, so the "instead" clause must not fire: SOR_046 goes
#// from 5 damage to 3, a heal of 2. An ownership-based check would have healed 4 and left 1 — the exact
#// value the section above asserts, so the two together pin the check to control.
#//
#// COVERAGE: control=HealFourEnabledByAControlledEnemyOwnedAggressionUnit + this section (the
#//           Aggression/Cunning gate counts units you CONTROL: a P2-owned unit in P1's arena enables the
#//           4-heal, a P1-owned unit in P2's arena does not) · offer="a unit" is unqualified and the heal
#//           target is named explicitly in every section, but the exact pool is not pinned with
#//           SELECTABLEEXACT (no both-sides offer section) · decline=not encoded (no PASS on the "you may
#//           heal") · boundary pair=WhenPlayedHealTwo (no enabling aspect -> 2) vs
#//           WhenPlayedHealFourWithAggression (enabling aspect -> 4), and the control pair above ·
#//           reqboundary=not encoded

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
WithP1GroundArena: SOR_046:1:5
WithP2GroundArenaControlled: SOR_128:1
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
