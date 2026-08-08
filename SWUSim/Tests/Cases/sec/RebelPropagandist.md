# WhenPlayed_BuffAndSaboteur
#// SEC_202 Rebel Propagandist (Ground, 2/4, Cunning/Heroism) — When Played/When Defeated: give another
#//   friendly unit +1/+0 and Saboteur for this phase. Buffs SOR_095 → 4/3 with Saboteur.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_202

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1NODECISION

---

# NoOtherFriendly_AutoPass
#// SEC_202 Rebel Propagandist — with no OTHER friendly unit to target, the When Played ability finds no
#//   legal target and simply fizzles; Propagandist stays in play and no decision is pending.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_202
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_202
P1NODECISION

---

# Buff_ExpiresNextPhase
#// SEC_202 Rebel Propagandist — the +1/+0 and Saboteur are "for this phase". After passing to the next
#//   action phase, the Wampa is back to its printed 4/5.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1Hand: SEC_202
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5

---

# WhenDefeatedByCombat_BuffsSurvivor
#// SEC_202 Rebel Propagandist — the When Defeated side also fires when Propagandist dies in COMBAT.
#//   Propagandist (2/4) attacks a 5/4 and takes 5 → defeated. Its When Defeated then buffs the surviving
#//   friendly (a lone "another friendly" target auto-resolves) → 3/7 Consular Security Force becomes 4/7
#//   with Saboteur. (Regression: the defeated unit's positional slot must not be mistaken for the ally.)

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1GroundArena: [SEC_202:1:0 SOR_046:1:0]
WithP2GroundArena: SEC_167:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# WhenDefeated_UnderEnemyControl_BuffsTheNewControllersUnit
#// SEC_202 Rebel Propagandist — "give ANOTHER FRIENDLY unit +1/+0 and Saboteur" resolves for whoever
#// controls it when it dies. P2 plays JTL_043 No Glory, Only Results on it, so P2 owns the When
#// Defeated: P2's own SOR_095 gets the +1/+0 and Saboteur, and P1's does not.

## GIVEN
CommonSetup: yyw/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_202:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
