# Exploit1_DeclineDamage
#// TWI_138 Count Dooku — each exploited-power damage is optional ("you may"). Dooku exploits one unit
#// (SOR_095, power 3) but DECLINES the deal-3, so the enemy takes no damage. (Proves the may-decline and
#// that a single exploit records exactly one power.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;handCardIds:TWI_138}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1

---

# Exploit2_DealEachPower
#// TWI_138 Count Dooku (Unit 6/6, cost 8, Aggression/Villainy) — "Exploit 2. Overwhelm. When Played: For
#// each unit you exploited while playing this card, you may deal damage to an enemy unit equal to the
#// power of the exploited unit." Dooku exploits SOR_095 (power 3) and SOR_140 (power 2); his When Played
#// then deals 3 to one enemy and 2 to another — proving it uses EACH exploited unit's own power.
## GIVEN
CommonSetup: rrk/bbw/{myResources:4;handCardIds:TWI_138}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_140:1:0
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_138

---

# NoExploit_NoDamage
#// TWI_138 Count Dooku — the When Played damage is gated on units actually exploited. Declining Exploit
#// (defeat 0 units) means no damage instances are offered: the enemy is untouched and the would-be fodder
#// stays in play.
## GIVEN
CommonSetup: rrk/bbw/{myResources:8;handCardIds:TWI_138}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2

---

# BothInstancesIntoOneShieldedUnit_OneInstance_ShieldStopsAll
#// CR 8.34.1: "for each" effects are resolved by "determining how many times an effect will be applied, choosing how
#// each instance will be applied, then resolving all effects simultaneously", and 8.34.1.a: "If an ability that uses
#// the phrase 'for each' … deals some amount of damage, all damage is calculated and dealt as ONE instance of damage."
#// Dooku exploits SOR_095 (power 3) and SOR_140 (power 2) and aims BOTH at P2's Shielded SOR_046: one instance of 5,
#// which the Shield prevents entirely — 0 damage, Shield gone. (Dealt as two instances, the Shield stopped the first 3
#// and the second 2 got through.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:4;handCardIds:TWI_138}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_140:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
