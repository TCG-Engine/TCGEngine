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

# PassAtTheTargetSelectStage
#// The SECOND pass stage: the player picks an attacker, then declines at TARGET select. An enemy unit is
#// present so the target choice is genuinely offered (base + unit) rather than auto-resolving onto the
#// base. No damage anywhere, and both units survive untouched.

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
