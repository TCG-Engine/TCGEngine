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

---

# TwinSuns_ExhaustDamageHitsTheACTUALDefendingPlayersBase
#// "On Attack: Exhaust any number of friendly Separatist units. Deal 1 damage to THE DEFENDING PLAYER's
#// base for each unit exhausted this way." The rider ran in the TWI_234#0 continuation as
#// SWUDealDamageToBase($count, OtherPlayer($player)) — seat 2 regardless of the attack.
#//
#// One Separatist (TWI_183 Rush Clovis) is exhausted, so seat 4's base takes 4 combat + 1 = 5 and
#// seat 2's stays 0. The exhaust itself is asserted so a decline can't pass for a fix.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: TWI_234:1:0
WithP1GroundArena: TWI_183:1:0

## WHEN
- P1>AttackSpaceArena:0:P4B
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P4BASEDMG:5
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
