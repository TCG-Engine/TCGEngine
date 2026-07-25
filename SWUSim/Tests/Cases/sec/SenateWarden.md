# WhenDefeated_DiscloseVigilance_ExpToken
#// DISCLOSE (CR §38) — SEC_059 Senate Warden (Unit 2/2, cost 2, Vigilance)
#//   "When Defeated: You may disclose Vigilance (reveal a card from your hand with this aspect
#//    icon). If you do, give an Experience token to a unit."
#// Proves disclose fires from a When Defeated trigger.
#//
#// All P1-driven: SEC_059 (2/2) attacks an enemy Battlefield Marine (3/3). Simultaneous combat
#// damage defeats SEC_059 (takes 3, has 2 HP) while the marine survives with 2 damage. SEC_059's
#// When Defeated offers the disclose; P1 reveals SEC_062 (Vigilance) from hand → gives an
#// Experience token (+1/+1) to a unit. A friendly survivor is also in play, so the (mandatory)
#// Experience target prompts — P1 puts the token on the enemy marine (any unit is legal).
#//
#// Result: marine becomes 4/4 with 2 damage and 1 upgrade (the token); SEC_059 is in the
#// discard; the disclosed SEC_062 stays in hand.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_059:1
WithP1GroundArena: SOR_095:1
WithP2GroundArena: SOR_095:1
WithP1Hand: SEC_062

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P2NODECISION

---

# WhenDefeatedByEnemy_PassiveOwnerDisclose
#// SEC_059 Senate Warden — the When Defeated disclose ALSO fires when the Warden is defeated by the
#//   OPPONENT (a cross-player, non-active-player reaction). P2's Battlefield Marine attacks and defeats
#//   P1's Warden; P1's When Defeated is queued on P1's own queue and is raised via P1's static drain, then
#//   P1 discloses SEC_062 (Vigilance) and puts the Experience token on the surviving friendly (SOR_046 →
#//   4/8, +1 upgrade). (Cross-player When-Defeated reactions need the defender-controller's Drain.)

## GIVEN
CommonSetup: bbk/grw
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: [SEC_059:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_062
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
