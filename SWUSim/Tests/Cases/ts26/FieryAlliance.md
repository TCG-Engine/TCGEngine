# Deal1ToAnotherAndAttack
#// TS26_25 Fiery Alliance (Upgrade, cost 2) — When Played: you may deal 1 damage to another friendly unit
#// and attack with it. Attached to SEC_080; the "another" unit SOR_046 takes 1 and attacks the enemy base
#// for 3.
## GIVEN
CommonSetup: grk/rrk/{myResources:2;handCardIds:TS26_25}
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:3

---

# AnEXHAUSTEDUnitIsAValidChoice_DoAsMuchAsYouCan
#// TS26_25 Fiery Alliance — "another friendly unit" has no readiness condition. The exhausted Aethersprite
#// (TWI_048, 4/6) in space is offered alongside the ready SOR_046, and choosing it still deals the 1
#// damage; the attack half simply cannot happen, so P2's base takes nothing.
#// Discriminating: the pool used to be filtered to READY units, which hid the exhausted target entirely.

## GIVEN
CommonSetup: grk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_25
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1SpaceArena: TWI_048:0:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:0

---

# TheOfferSpansBothArenasAndExcludesOnlyTheHost
#// TS26_25 Fiery Alliance — the "another friendly unit" pool: the ground SOR_046 and the exhausted space
#// TIE token, with the host SEC_080 excluded as "another". Both arenas are in scope.

## GIVEN
CommonSetup: grk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_25
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1SpaceArena: JTL_T01:0:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0

---

# DecliningTheMayDoesNothing
#// TS26_25 Fiery Alliance — "You MAY deal 1 damage…". Declining leaves SOR_046 undamaged and P2's base
#// untouched; the upgrade itself still attached to SEC_080.

## GIVEN
CommonSetup: grk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_25
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ThePingKillsTheChosenUnit_NoAttackAndNoCrash
#// TS26_25 Fiery Alliance — "deal 1 damage to another friendly unit AND attack with it" on a unit the ping
#// kills. SOR_046 (3/7) is already at 6 damage, so the 1 finishes it: the arena is left with just the host
#// and no attack happens. Guards the dead-attacker path rather than letting the effect chase a gone unit.

## GIVEN
CommonSetup: grk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_25
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:6]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:0

---

# PlayedOnAnENEMYUnit_TheFriendlyPoolIsStillYours
#// TS26_25 Fiery Alliance — the upgrade may be attached to an enemy unit, and "another FRIENDLY unit"
#// still means units YOU control. With the host being P2's Wampa, both of P1's units are offered (the
#// host is not among them to be excluded).

## GIVEN
CommonSetup: grk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_25
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
