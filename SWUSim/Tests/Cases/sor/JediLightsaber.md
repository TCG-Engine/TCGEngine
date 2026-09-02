# AttachToNonVehicleBuffs
#// SOR_054 Jedi Lightsaber — Upgrade (+3/+3), "Attach to a non-VEHICLE unit."
#// P1 has a Vehicle (AT-AT idx 0) and a non-Vehicle (Battlefield Marine idx 1).
#// The Vehicle is filtered out, so the only valid target is the Marine → auto-attach.
#// COVERAGE: offer=AttachPool_NonVehicleOnly_BothControllersBothArenas (four units, exactly the two
#//           non-Vehicles offered — one Vehicle excluded on EACH side, so the filter is the trait and
#//           not a controller filter) · reqboundary=SimulateRequestBoundary_AttachTargetSurvivesFreshProcess
#//           (the attach prompt ends the request in production, so the in-flight upgrade play is
#//           serialized) · boundary pair=ForceHostDebuffsDefender vs NonForceHostNoDebuff (the
#//           value-CLASS pair for the conditional grant: Force host shrinks the defender −2/−2, a
#//           non-Force host grants nothing; the card prints no number or threshold, so there is no
#//           N vs N±1 to pin) · control=N/A, specifically: every clause on this card is HOST-derived —
#//           the +3/+3 and the "if attached unit is a FORCE unit" grant read the host's traits, never
#//           a seat, and the offer section proves the host pool itself ignores controller (an enemy
#//           unit is a legal host), so a control change has no seat-dependent branch to flip; the
#//           upgrade simply travels with its host · decline=N/A (no "you may" anywhere on the card and
#//           no optional cost — the host choice is mandatory once the saber is played, and the only
#//           "decline" available is not playing it).
#// Marine becomes 3+3 / 3+3 = 6/6 with one upgrade; the Vehicle is untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;handCardIds:SOR_054}
WithP1GroundArena: SOR_148:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_054
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:6

---

# ForceHostDebuffsDefender
#// SOR_054 Jedi Lightsaber — when attached to a FORCE unit it grants:
#//   "On Attack: Give the defender −2/−2 for this phase."
#// Host = Mace Windu (SOR_149, Force, 5/7) + saber → 8/10 attacker.
#// Defender = SOR_119 (6/9) carrying a Shield so the 8 combat damage is fully
#// absorbed (shield), letting us read its post-attack stats cleanly:
#//   On-Attack shrink −2/−2 → power 6−2=4, HP 9−2=7. (Shrink is not damage, so the
#//   defender survives at 7 HP with 0 damage; the shield only stopped the combat hit.)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_149:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# NonForceHostNoDebuff
#// SOR_054 Jedi Lightsaber — the On-Attack shrink is granted ONLY to FORCE hosts.
#// Host = SOR_046 (Rebel/Trooper, non-Force, 3/7) + saber → 6/10 attacker.
#// Defender = SOR_119 (6/9) carrying a Shield (combat damage absorbed).
#// Host is not a Force unit, so no grant fires → defender keeps its printed 6/9.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:9
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# SimulateRequestBoundary_AttachTargetSurvivesFreshProcess
#// SOR_054 Jedi Lightsaber — with TWO non-VEHICLE hosts in play the attach target stays a real prompt
#// (AttachToNonVehicleBuffs' lone target auto-resolves), and in production that prompt ends the request,
#// so the in-flight upgrade play must be serialized. Mirrors AttachToNonVehicleBuffs with a boundary
#// before the answer: the AT-AT (Vehicle) is still filtered out, the chosen Marine still becomes 6/6
#// wearing the saber, the other non-Vehicle host is untouched, and the cost is still fully paid.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;handCardIds:SOR_054}
WithP1GroundArena: SOR_148:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_054
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:6
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0

---

# AttachPool_NonVehicleOnly_BothControllersBothArenas
#// SOR_054 Jedi Lightsaber — the OFFER axis for "Attach to a non-VEHICLE unit." The restriction names
#// a TRAIT and nothing else: no controller and no arena, so the legal-host pool spans both sides and
#// both arenas and its only exclusion is the Vehicle trait. Board: P1's Guerilla Attack Pod (Vehicle,
#// ground) and Battlefield Marine (non-Vehicle, ground); P2's Wampa (non-Vehicle, ground) and
#// Alliance X-Wing (Vehicle, space). Four units, and exactly the two non-Vehicles are offered — a
#// Vehicle is excluded on each side, so the filter cannot be a controller filter in disguise. The
#// attach decision is left PENDING (no answer): the offer itself is the assertion, so the saber is
#// still in flight and nothing is wearing it.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;handCardIds:SOR_054}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_148:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# MaulTwoDefenders_BOTHAreShrunk
#// FAMILY GENERALIZATION of the SHD_074 Vambrace Grappleshot fix, and the second card proving the
#// defender-SET seam is not a one-card special case.
#// OFFICIAL RULING (Darth Maul - Revenge At Last, 10/31/2024): under TWI_135 Maul's "attack 2 units
#// instead of 1", "both units are considered defenders of one attack" and "any triggered abilities only
#// occur once". So this ONE On Attack firing gives -2/-2 to BOTH defenders.
#// Maul is a FORCE unit (traits Force, Sith), which is what switches the Lightsaber's grant on.
#// Maul 5/6 + the Lightsaber's +3/+3 = 8/9. Each TWI_054 Duchess's Champion is 1/8; -2/-2 makes it
#// -1/6, so its power floors at 0 and Maul takes NO counter-damage, while 8 damage still defeats both.
#// ⚠ THE COUNTER-DAMAGE IS A 3-WAY DISCRIMINATOR, which a stat assertion on the defenders could not be
#// (they die either way): 0 damage on Maul = both shrunk · 1 = only the lead shrunk (the half-fix where
#// SWU_CURRENT_DEFENDER is published but the defender SET is not) · 2 = neither (the original bug, where
#// the Maul path published no defender at all and the ability silently no-opped).

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: [TWI_054:1:0 TWI_054:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:0
