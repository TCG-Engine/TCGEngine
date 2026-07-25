# WhenDefeatedExpAggression
#// LAW_059 Highsinger (4/2) — When Defeated: give an Experience token to a friendly Aggression unit.
#// Highsinger attacks SOR_046 (3/7) and dies (takes 3 vs 2 HP); SOR_128 (Aggression) gets the Experience.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_059:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenPlayedExpCommand
#// LAW_059 Highsinger (4/2) — When Played: give an Experience token to another friendly Command unit.
#// SOR_095 (Command,Heroism) is the only one -> auto.

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeatedGivesExpToNewControllersAggression
#// LAW_059 Highsinger — "When Defeated: give an Experience token to a friendly Aggression unit." If an
#// opponent steals & defeats Highsinger (via No Glory, Only Results, JTL_043), the token goes to the new
#// controller's Aggression unit. P2 steals & defeats P1's Highsinger; P2's SOR_128 gets the Experience.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_059:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
