# Reprint051
#// IBH_051 Tauntaun Mount (reprint of IBH_015) — When Defeated: heal 2 from your base. Confirms duplicate.

## GIVEN
CommonSetup: ggw/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: IBH_051:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1

---

# WhenDefeated_HealsBase
#// IBH_015 Tauntaun Mount (Ground, 2/2, Command) — When Defeated: heal 2 damage from your base. The
#//   Tauntaun attacks a 3/3 and dies to the 3 counter; its When Defeated heals P1's base (3 → 1). Driven
#//   as P1's own attack so the trigger resolves inline.

## GIVEN
CommonSetup: ggw/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: IBH_015:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDefeatedUnderENEMYControl_HealsTheNEWControllersBase
#// IBH_015 Tauntaun Mount — "heal 2 damage from YOUR base" resolves for whoever CONTROLS it when it
#// dies, not its owner. P1 plays JTL_043 No Glory, Only Results on P2's Tauntaun: P1 takes control and
#// the defeat follows immediately, so the heal lands on P1's base (5 -> 3) while P2's base keeps its 5.
#// P2's base staying damaged is the discriminating half — an owner-scoped implementation would have
#// healed P2 instead.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;myBaseDamage:5;theirBaseDamage:5}
WithActivePlayer: 1
WithP1Hand: JTL_043
WithP2GroundArena: IBH_015:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:3
P2BASEDMG:5

---

# Reprint028
#// IBH_028 Tauntaun Mount (a THIRD printing alongside IBH_015 and IBH_051) — When Defeated: heal 2 from
#// your base. Confirms this duplicate CardID is wired too. It was the one copy with no section: the
#// reference only ever exercises a single printing, so a missed duplicate is invisible from that side
#// and only shows up by diffing the set's CardIDs against the CardIDs the tests actually name.

## GIVEN
CommonSetup: ggw/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: IBH_028:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2
