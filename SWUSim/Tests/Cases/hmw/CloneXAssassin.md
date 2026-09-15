# WhenDefeated_MayGiveWeaknessToEnemyUnit
#// HMW_059 Clone X Assassin (1/3) — "When Defeated: You may give a Weakness token to a unit." It attacks
#// a 3/3 (SEC_080) and dies to the counter (attacker self-defeat resolves inline), then gives a Weakness
#// (-1/-1) to that enemy unit — proving "a unit" allows an ENEMY target. SEC_080 becomes 2/2 with 1 combat
#// damage (survives at 1 remaining HP).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenDefeated_WeaknessDefeatsAOneHpUnit
#// The -1 HP can be lethal: given to a unit at 1 remaining HP (SOR_128, a 3/1), it drops to 0 and is
#// defeated by the shrink sweep. HMW_059 dies attacking the 3/3, then targets the separate 1-HP unit.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: [SEC_080:0:0 SOR_128:0:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# WhenDefeated_Decline_NoToken
#// The "may" decline: no Weakness token is attached.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3

---

# WhenDefeated_OfferSpansBOTHSides
#// "You may give a Weakness token to A UNIT" — unqualified, so FRIENDLY units are legal targets too and
#// the pool is the whole table. Every existing section here answers an enemy, which a pool narrowed to
#// enemies satisfies identically.
#// The Assassin is defeated in combat; the surviving friendly and the enemy attacker are both offered.
## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: [HMW_059:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ControlChange_NoGloryOnlyResults_OfferGoesToTheNEWController
#// CONTROL CHANGE. "When Defeated: YOU may give a Weakness token to a unit." — "you" is whoever controls
#// the Assassin when it is defeated (CR: a When Defeated ability is resolved by the unit's last
#// controller). P1 plays JTL_043 No Glory, Only Results ("Take control of a non-leader unit, then defeat
#// it") on P2's Assassin: control moves to P1 first, so the Assassin dies under P1 and the offer is P1's,
#// not its owner's. Left PENDING so the pool is the assertion: P1's Wampa and P2's AT-ST (the Assassin
#// itself is already gone — P2's arena compacts to the AT-ST at index 0).
#// PREVIEW SET: no official ruling; reasoned from CR + the released analogue in shd/InspiringMentor.md
#// (a granted When Defeated resolved for the NGOR caster).
#// ⚠ FIXTURE: JTL_043 costs 5 (Vigilance/Villainy) — bbk covers both; 5 resources.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [HMW_059:1:0 SOR_232:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENACOUNT:1

---

# ControlChange_NoGloryOnlyResults_NewControllerWeakensTheOwnersUnit
#// The same line resolved: the new controller (P1) gives the Weakness to P2's AT-ST (6/7 → 5/6). The
#// Assassin itself goes to its OWNER's discard (P2), alongside nothing else; P1's discard holds only the
#// event. P1's own Wampa is untouched.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [HMW_059:1:0 SOR_232:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:HMW_059
P1DISCARDCOUNT:1
P1NODECISION
