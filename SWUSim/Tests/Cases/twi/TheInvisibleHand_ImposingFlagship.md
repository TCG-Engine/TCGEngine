# OnAttack_ExhaustSeparatists_BaseDamage
#// TWI_234 The Invisible Hand — "On Attack: Exhaust any number of friendly Separatist units. Deal 1
#// damage to the defending player's base for each unit exhausted this way." Invisible Hand (ready)
#// attacks P2's base; On Attack offers the 2 ready friendly Battle Droid tokens (Separatist) to exhaust.
#// Choosing both exhausts them and deals 2 to P2's base — on top of Invisible Hand's own 4 attack damage
#// → P2 base takes 6 total.

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_234:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# WhenPlayed_Create4Droids
#// TWI_234 The Invisible Hand (Unit 4/7, Space, cost 8, Villainy) — "When Played: Create 4 Battle Droid
#// tokens." Invisible Hand enters the space arena; its When Played creates 4 Battle Droids (Ground).
#// Leader yk covers the Villainy pip → no penalty.

## GIVEN
CommonSetup: gyk/grw/{myResources:8;handCardIds:TWI_234}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_234
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
