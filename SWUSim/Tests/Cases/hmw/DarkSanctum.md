# Fortify_AttachesToTheBaseNotAUnit
#// HMW_070 Dark Sanctum — Upgrade, cost 3, [Vigilance][Villainy], trait Fortification, non-unique.
#// "Fortify (Attach this to your base, not a unit.)
#//  Attached base gains: 'When the regroup phase starts: Draw a card and deal 2 damage to this base.'"
#// Fortify makes myBase-0 the only legal host, so the attach auto-resolves even with a friendly unit
#// on the board — that unit being untouched is the proof it was never offered.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:HMW_070}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Fortify_NeverAttachesToTheEnemyBase
#// "Attach this to YOUR base, not a unit." The section above rules out UNITS; nothing ruled out the
#// ENEMY BASE except incidentally — it asserts that no prompt appeared, never that theirBase-0 was
#// outside the host pool. Both enemy bodies are on the board here (an enemy unit AND the enemy base),
#// so myBase-0 is the only legal host and the attach still auto-resolves onto it. Were theirBase-0 ever
#// offered, a second legal host would stop the play for a prompt and P1NODECISION would fail.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myhandCardIds:HMW_070}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_070
P2BASE:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Regroup_DrawsOneAndDealsTwoToOwnBase
#// The granted ability fires at the START of the regroup phase — an EXTRA draw, distinct from the
#// regroup draw step. Two passes end the action phase and reach regroup.
#// Baseline for this fixture without Dark Sanctum is the sibling section below; here the base takes 2.
#// The deck is seeded because a draw on an EMPTY deck damages the base instead, which would look
#// exactly like the ability's own base damage.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:2
#// The DRAW half of the clause: 4 seeded, the regroup draw step takes 2, Dark Sanctum takes one more.
#// The paired no-Sanctum section below ends at 2, so the difference of exactly 1 IS the extra draw —
#// asserting only the base damage would leave half this clause untested.
P1DECKCOUNT:1

---

# Regroup_NoDarkSanctum_BaseUndamaged
#// The negative that makes the section above load-bearing: without the upgrade attached, the regroup
#// phase deals the base nothing. Same fixture otherwise.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:0
P1DECKCOUNT:2

---

# Regroup_OnlyTheAttachedBaseIsAffected
#// "this base" is the ATTACHED base, not either base — the opponent's base must be untouched.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:2
P2BASEDMG:0

---

# Regroup_TwoCopiesFireTwice
#// Dark Sanctum is NON-unique, so a base can carry two. Each copy grants its own instance of the
#// ability, so the base takes 4. A boolean "does the base have one?" implementation caps at 2 and
#// this is the only section that can tell the difference.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_070
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080 SOR_237 SOR_225]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:4
#// Both copies DRAW as well as damage: 6 seeded − 2 regroup − 2 granted = 2.
P1DECKCOUNT:2

---

# Regroup_SelfDamageCanDefeatYourOwnBase
#// The ability damages YOUR OWN base, so it can kill you. Standard base is 30 HP; at 28 damage the
#// regroup tick takes it to 30 and P1 loses. This is the consequence half of the card and nothing else
#// tests it.
#// myBaseDamage is the CommonSetup option for pre-damage — `myBase:ID:damage` silently drops it.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myBaseDamage:28}
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P2WIN

---

# Regroup_OneShortOfLethal_BaseSurvives
#// Boundary partner: at 27 the tick reaches 29 and the game continues. Without this pair the section
#// above proves nothing about the threshold — any lethal-looking number would pass it.

## GIVEN
CommonSetup: bbk/grw/{myResources:5;myBaseDamage:27}
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:29
