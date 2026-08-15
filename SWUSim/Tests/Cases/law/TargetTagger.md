# AttackBountyHunterBuff
#// LAW_157 Target Tagger (Command, cost 3) — When Played: you may attack with a unit. If it's a Bounty
#// Hunter, it gets +2/+0 for this attack. LAW_124 (Bounty Hunter, power 4) attacks the base for 4+2 = 6.

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6

---

# AttackWithANonBountyHunter_NoBuff
#// LAW_157 Target Tagger — the +2/+0 is conditional on the attacker being a BOUNTY HUNTER. The negative
#// side of that gate: SOR_095 Battlefield Marine (3/3, no Bounty Hunter trait) attacks the base for its
#// printed 3, NOT 5. Pairs with AttackBountyHunterBuff above to pin the condition as load-bearing.

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3

---

# PassAtTheAttackerSelectStage
#// "You MAY attack with a unit" — declining at the attacker-select stage means no attack happens at all.
#// The Tagger still enters play; the base takes nothing and the would-be attacker stays READY (an attack
#// would have exhausted it), which is what proves the attack never started rather than fizzling later.

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:READY

---

# TargetSelectCannotBePassed_OnceAnAttackerIsChosen
#// USER RULING (2026-08-14): you may pass on the ATTACK itself — LAW_157 reads "you MAY attack with a
#// unit", and declining the attacker choose is covered by the pass section above — but ONCE YOU HAVE
#// CHOSEN THE ATTACKER YOU MUST CHOOSE THE TARGET. So the target stage is a mandatory MZCHOOSE: its
#// pool is exactly the legal attack targets with no decline among them, and the decision is left
#// PENDING here rather than answered.
#// This replaces a section named PassAtTheTargetSelectStage which asserted the opposite (that the
#// player could decline at target select, abandoning the attack with the attacker still READY).

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_an_attack_target
P1SELECTABLEEXACT:theirGroundArena-0&theirBase-0
