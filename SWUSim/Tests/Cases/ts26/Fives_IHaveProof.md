# CopiesAnotherWhenPlayed
#// TS26_34 Fives (Unit 6/6, cost 6) — Sentinel + "You may have this unit enter play with the When Played
#// abilities of another unit in play." Copying the Assault Lander LAAT's When Played (create 2 Clone
#// Troopers) makes Fives create 2 Clones on entry → ground goes from 1 (LAAT) to 4 (LAAT + Fives + 2).
## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
WithP1GroundArena: TS26_23:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:2:CARDID:TS26_T02

---

# DeclineCopy
#// TS26_34 Fives — the copy is optional ("you may"). Declining copies nothing, so only Fives enters play
#// alongside the LAAT (ground count 2).
## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
WithP1GroundArena: TS26_23:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:2

---

# NoUnitHasAWhenPlayed_NoPromptAtAll
#// TS26_34 Fives — the copy is only offered when there is something to copy. With only vanilla bodies on
#// board (SOR_095, SOR_164) no unit has a When Played, so Fives enters with no prompt and no dangling
#// decision. Ground = the Marine + Fives.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

---

# OfferIsOnlyUnitsThatHaveAWhenPlayed_AndSpansBothSides
#// TS26_34 Fives — "another unit in play" is filtered to units that actually HAVE a When Played, and is
#// not restricted to friendlies. The board carries a vanilla SOR_095 (no When Played), a friendly
#// JTL_117 General Draven and an ENEMY SEC_206 Emissaries from Ryloth: the pool is exactly the latter
#// two. Asserted as the offer, since answering resolves the decision.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: JTL_117:1:0
WithP2GroundArena: SEC_206:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# CopiesBOTHWhenPlayedAbilities_WhenTheChosenCardHasTwo
#// TS26_34 Fives — "abilities" is plural. LOF_070 Anakin Skywalker prints TWO separate When Played
#// clauses (one gated on a Heroism card in your discard, one on a Villainy card), so with SOR_095
#// (Heroism) and SEC_080 (Villainy) both in the discard, copying him fires BOTH -3/-3 effects.
#// LAW_124 (4/7) takes -6/-6: power floors at 0 and HP lands on 1. A single-ability copy would leave
#// it at 1/4, so the numbers discriminate.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: LOF_070:1:0
WithP1Discard: SOR_095
WithP1Discard: SEC_080
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:1

---

# CopiesTheWhenPlayedHalfOnly_NotTheOnAttackHalf
#// TS26_34 Fives — a COMBINED "When Played/On Attack:" header is two windows, and only the When Played
#// one is copied. JTL_117 General Draven reads "When Played/On Attack: Create an X-Wing token": copying
#// him creates exactly ONE X-Wing as Fives enters (space arena 1), and Fives does not carry the On
#// Attack half forward.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: JTL_117:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2

---

# CopiedWhileInPlayBuff_AppliesToTheChosenUnit
#// TS26_34 Fives copying TWI_110 Huyang ("Choose another friendly unit. While this unit is in play, the
#// chosen unit gets +2/+2"). SOR_095 goes 3/3 -> 5/5.
#// BUG THIS PINS: the buff was recorded but permanently INVISIBLE when the source was Fives. The link is
#// stored as SWU_TWI110_{sourceUID}_{targetUID}, but the reader scanned only in-play units whose CardID
#// is TWI_110 — Fives is TS26_34, so his UID was never checked and the target simply never got +2/+2.
#// Huyang played normally worked, which is what hid it. Fixed by scanning every friendly unit by UID
#// (the flag is only ever written by Huyang's handler, so it is self-authenticating).
#// Note "another friendly unit" resolves against FIVES, so the offer is Huyang + the Marine, not Fives.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: TWI_110:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:0:POWER:2

---

# CopiedWhileInPlayBuff_ENDSWhenFIVESLeavesPlay
#// TS26_34 Fives — the copied effect's duration follows FIVES, not the card it was copied from. Same
#// Huyang copy as above, then LOF_264 It's Worse defeats FIVES: the +2/+2 disappears and SOR_095 is back
#// to 3 power, even though the real Huyang is still standing. This is the half that proves "while THIS
#// unit is in play" re-homes to the copier — and it is only observable now that the buff applies at all.

## GIVEN
CommonSetup: byw/rrk/{myResources:14;handCardIds:TS26_34}
P1OnlyActions: true
WithP1Hand: LOF_264
WithP1GroundArena: TWI_110:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:POWER:3

---

# CopiedForThisPhaseEffect_SURVIVESFivesBeingDefeated
#// TS26_34 Fives — the contrast with the section above. A copied "for this phase" effect is NOT tied to
#// the source staying in play, so it outlives Fives. Copying SEC_206 Emissaries from Ryloth applies
#// -3/-0 to LAW_124 (4 -> 1 power); LOF_264 then defeats Fives and the debuff REMAINS at 1.
#// Together these two sections pin the duration rule: "while this unit is in play" ends with Fives,
#// "for this phase" does not.

## GIVEN
CommonSetup: byw/rrk/{myResources:14;handCardIds:TS26_34}
P1OnlyActions: true
WithP1Hand: LOF_264
WithP1GroundArena: SEC_206:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:1

---

# CopiesOnlyTheWhenPlayed_FromACardWithSEPARATEAbilityLines
#// TS26_34 Fives — the companion to the combined-header case. LOF_037 Darth Vader prints TWO SEPARATE
#// ability lines ("When Played: Give a Shield token to a friendly unit and to an enemy unit." and
#// "On Attack: Defeat an enemy unit with a Shield token on it."). Copying him takes the When Played
#// only: Fives and Vader each end with one Shield, and Fives keeps HIS OWN 6/6 rather than Vader's 5/6
#// — the copy takes the ability, never the stats.
#// Both shield picks auto-resolve here: Fives is the only friendly and Vader the only enemy.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP2GroundArena: LOF_037:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TheCopiedCardsONATTACKIsNotCarriedForward
#// TS26_34 Fives — proving the negative half of the section above with an ATTACK, not just an inspection.
#// After copying LOF_037 Darth Vader's When Played, the next action phase Fives attacks the enemy base.
#// If Vader's "On Attack: defeat an enemy unit with a Shield token on it" had come along, it would fire
#// and defeat Vader (who is carrying the Shield Fives just gave him). Instead Vader survives WITH his
#// shield, the base takes Fives' 6, and no decision is raised.
#// The pass chain advances one action phase so Fives (played last phase) is able to attack.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP2GroundArena: LOF_037:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:6
P1NODECISION

---

# ABLANKEDUnitIsNotAValidCopyTarget
#// TS26_34 Fives — the copy pool must read LIVE ability state, not the printed card. JTL_018 Kazuda
#// Xiono's action ("a friendly unit loses all abilities for this round, then take an extra action")
#// blanks SEC_206 Emissaries from Ryloth; with the extra action P1 plays Fives, and since the only card
#// that printed a When Played now has none, Fives enters with NO prompt at all.
#// BUG THIS PINS: the filter used HasWhenPlayedAbility($cardID) — a printed-card lookup that cannot see
#// the blank — so Fives still offered the blanked unit and copying it resolved nothing. Now also gated
#// on LostAbilities($obj).

## GIVEN
CommonSetup: byw/rrk/{myLeader:JTL_018;myResources:8;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: SEC_206:1:0

## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_34
P1NODECISION

---

# CopiesAWhenPlayedASAUNITAbility_NotThePilotingHalf
#// TS26_34 Fives copying JTL_210 The Mandalorian, a dual-mode Pilot card with THREE ability lines:
#// "When played as a unit: Exhaust up to 2 ground units", a Piloting cost, and "When played as an
#// upgrade: Exhaust an enemy unit in this arena". Fives enters as a UNIT, so he takes the
#// "when played as a unit" half — the offer is every ground unit (himself included) and he exhausts the
#// two enemies. The piloting / as-upgrade line is not his to copy: he is not an upgrade.
#// (Fives is exhausted at the end simply because a unit enters play exhausted, not from the copied
#// ability — which is why the assertion is on the two ENEMY units.)

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP2GroundArena: JTL_210:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENACOUNT:1
P1NODECISION

---

# CopiesCorvus_ThePilotAttachesToFIVESNotToCorvus
#// TS26_34 Fives copying JTL_038 Corvus ("When Played: You may attach a friendly Pilot unit or upgrade
#// to THIS unit"). "This unit" re-homes to the copier, so JTL_255 Sullustan Spacer attaches to FIVES —
#// Corvus itself ends with zero upgrades. The Spacer leaves the ground arena to become the upgrade, so
#// P1's ground is just Fives, and the Pilot's stat modifiers apply to him: 6/6 -> 7/7.
#// Legal if odd, per ruling: copying an attach-to-self ability makes the COPIER the host.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: JTL_255:1:0
WithP1SpaceArena: JTL_038:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_34
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# CopiesSidonIthano_FIVESAttachesHIMSELFToAnEnemyVehicle
#// TS26_34 Fives copying JTL_213 Sidon Ithano ("When played as a unit: You may attach THIS unit as an
#// upgrade to an enemy Vehicle unit without a Pilot on it"), with Sidon in play as a UNIT. "This unit"
#// re-homes to Fives, so FIVES leaves P1's arena and becomes an upgrade on the enemy JTL_064 Omicron
#// Strike Craft — Sidon stays put as P1's only ground unit.
#// Fives contributes NO stat modifiers as an upgrade (he is not a Pilot card): the Omicron stays 2/3.
#// Legal if odd, per ruling.

## GIVEN
CommonSetup: byw/rrk/{myResources:6;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: JTL_213:1:0
WithP2SpaceArena: JTL_064:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_213
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:POWER:2
P2SPACEARENAUNIT:0:HP:3

---

# FivesAttachedAsAnUpgradeIsNOTAPilot_TheHostCanStillTakeARealOne
#// TS26_34 Fives — continuation of the Sidon copy, and the discriminating half.
#// Fives has no Pilot trait, so the Vehicle he is attached to is still "without a Pilot on it": its
#// controller can go on to attach a REAL Pilot. P2 plays JTL_255 Sullustan Spacer with Piloting onto the
#// same Omicron, which then carries BOTH upgrades and gains the Spacer's +1/+1 (2/3 -> 3/4).
#// Finally SHD_262 Confiscate proves Fives is an ordinary targetable upgrade: it defeats him, leaving the
#// Omicron with just the Spacer at 3 power, and Fives in P1's discard alongside the event.
#// BUG THIS PINS: SWUMoveUnitToUpgrade flagged the attached card IsPilot=true unconditionally. Every
#// call site passes true because the ability's own card IS a Pilot — but a COPIER need not be. Flagging
#// Fives as a Pilot made the host count as "has a Pilot", and P2's Piloting play was silently downgraded
#// to an ordinary unit play. The flag is now derived from the moved card's actual Pilot trait.

## GIVEN
CommonSetup: byw/rrk/{myResources:14;handCardIds:TS26_34}
WithActivePlayer: 1
WithP2Resources: 4
WithP1Hand: SHD_262
WithP1GroundArena: JTL_213:1:0
WithP2Hand: JTL_255
WithP2SpaceArena: JTL_064:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P2>PlayHand:0
- P2>AnswerDecision:Pilot
- P2>AnswerDecision:mySpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:POWER:3
P1DISCARDCOUNT:2

---

# CopiesPantoranStarshipThief_FIVESAttachesAndTAKESCONTROL
#// TS26_34 Fives copying JTL_083 Pantoran Starship Thief ("When Played: You may pay 3 resources. If you
#// do, attach THIS unit as an upgrade to a Fighter or Transport unit without a Pilot on it. Take control
#// of that unit."), with the Thief in play as a UNIT.
#// "This unit" re-homes to Fives: he leaves P1's arena, becomes an upgrade on the enemy SHD_152 Desperado
#// Freighter, and P1 TAKES CONTROL of it — P2's space arena empties and P1's holds the stolen ship.
#// The optional 3-resource cost is really paid: 12 - 6 (Fives) - 3 = 3 ready. Fives adds no stats, so the
#// Freighter stays 5/6. Legal if odd, per ruling.

## GIVEN
CommonSetup: byw/rrk/{myResources:12;handCardIds:TS26_34}
P1OnlyActions: true
WithP1GroundArena: JTL_083:1:0
WithP2SpaceArena: SHD_152:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SHD_152
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
P1RESAVAILABLE:3

---

# DefeatingTheCopiedFives_DoesNOTReturnTheStolenShip
#// TS26_34 Fives — the sharp edge of the Pantoran copy. JTL_083 has a SECOND, separate ability on its
#// upgrade side: "When this upgrade detaches from a unit: That unit's owner takes control of it." That is
#// NOT a When Played, so Fives never gained it.
#// P2 Confiscates Fives off the stolen SHD_152 Desperado Freighter: Fives goes to P1's discard and the
#// Freighter is left with no upgrades — but P1 KEEPS CONTROL of it, because the return-control clause
#// stayed behind with the real Thief. P2's space arena is still empty.
#// (Confiscate auto-resolves here: Fives is the only upgrade in play.)

## GIVEN
CommonSetup: byw/rrk/{myResources:12;handCardIds:TS26_34}
WithActivePlayer: 1
WithP2Resources: 3
WithP1GroundArena: JTL_083:1:0
WithP2Hand: SHD_262
WithP2SpaceArena: SHD_152:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# CopiesAWHENPLAYEDUSINGSMUGGLEAbility_AndItFiresBecauseFivesWasSmuggledToo
#// TS26_34 Fives — "When played using Smuggle" IS a When Played ability; it just carries an extra
#// condition on HOW the card was played. So Fives may copy it, and it fires only because FIVES himself
#// came in via Smuggle.
#// SHD_248 Tech gives every friendly resource Smuggle at "that card's cost plus 2 and its aspect icons",
#// so Fives smuggles out of the resource row for 6 + 2 = 8 (base+leader cover all three of his aspects,
#// so no penalty): 8 of the 9 resources exhaust and the row stays at 9 (he is replaced from the deck).
#// He copies the ENEMY SHD_148 Cassian Andor's "When played using Smuggle: Ready this unit" — and the
#// proof is that Fives is READY, since a unit entering play is normally exhausted.
#// TWO BUGS THIS PINS:
#//   1. The copy pool was built from HasWhenPlayedAbility() alone. Cassian's ability lives in the separate
#//      whenPlayedUsingSmuggle registry/stub, so he was silently absent from the pool and Fives got no
#//      prompt at all. The filter now accepts either window.
#//   2. Nothing recorded that a unit had entered via Smuggle, so the copied ability had no way to check
#//      its own condition. _SWUSmuggleFireEntry now stamps SWU_SMUGGLED_{uid} on the entering unit — a
#//      UID-keyed flag rather than a transient variable, because the entry triggers are only BAGGED at
#//      that point and the copy is answered later (possibly across a request boundary).

## GIVEN
CommonSetup: byw/rrk
WithP1GroundArena: SHD_248
WithP1Resources: 1:TS26_34:0,8:SOR_095:1
WithP1Deck: SOR_095
WithP2GroundArena: SHD_148:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_34
P1GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:0
P1RESCOUNT:9
