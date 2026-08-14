# AttachToNonVader_NoEffect
#// COVERAGE: offer=Offer_AttachPool_ExcludesVehicles + Offer_DamagePool_GroundUnitsOnly (both
#//           pending SELECTABLEEXACT) · reqboundary=AttachToVader_Deals4 (attach answer and damage
#//           answer arrive in separate requests) · control=N/A (no section changes control; the
#//           host-is-Vader check reads the host card, not a seat) · boundary pair=
#//           AttachToVader_Deals4 vs AttachToNonVader_NoEffect (the named-host gate) +
#//           AttachedToVaderLeaderUnit_Deals4 (the deployed-leader body also matches) · decline=
#//           Decline_NoDamageDealt.
#// SOR_136 Vader's Lightsaber — the deal-4 is conditional on the host being Darth Vader.
#// Attached to Battlefield Marine (not Vader), the upgrade still attaches but its When Played
#// does nothing: the enemy unit takes no damage and no decision is pending. Absence guard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SEC_080:1:0    # non-Vader friendly host
WithP2GroundArena: SEC_080:1:0    # enemy unit — must be untouched

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# AttachToVader_Deals4
#// SOR_136 Vader's Lightsaber (Upgrade) — Attach to a non-Vehicle unit. When Played: If
#// attached unit is Darth Vader, you may deal 4 damage to a ground unit. P1 plays it onto
#// Darth Vader (SOR_087, the only friendly non-Vehicle unit); the host IS Vader, so on YES the
#// enemy Battlefield Marine (3 HP) is dealt 4 and defeated.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SOR_087:1:0    # Darth Vader (non-Vehicle host)
WithP2GroundArena: SEC_080:1:0    # enemy ground unit — the deal-4 target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to Vader (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Offer_AttachPool_ExcludesVehicles
#// SOR_136 Vader's Lightsaber — "Attach to a non-Vehicle unit": the host pool is every
#// non-Vehicle unit on BOTH sides (CR 2.e), and Vehicles in either space arena are excluded.
#// Pool left PENDING: exactly [friendly Vader, enemy Wampa].

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SOR_087:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Offer_DamagePool_GroundUnitsOnly
#// SOR_136 Vader's Lightsaber — with the host being Darth Vader, the deal-4 offer is every
#// GROUND unit on both sides (Vader himself included); space units are out. Damage decision
#// left PENDING: exactly [Vader, Wampa].

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SOR_087:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# AttachedToVaderLeaderUnit_Deals4
#// SOR_136 Vader's Lightsaber — "attached unit is Darth Vader" also matches the DEPLOYED Darth
#// Vader LEADER unit (SOR_010, title match). Attach to the leader unit, then deal 4 to the
#// enemy Wampa (4/5 — survives with 4 damage).

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# Decline_NoDamageDealt
#// SOR_136 Vader's Lightsaber — the deal-4 is "you may": declining the offer leaves the
#// lightsaber attached to Vader and every unit untouched.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SOR_087:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
