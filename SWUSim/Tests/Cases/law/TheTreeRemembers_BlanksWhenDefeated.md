# LAW_132 The Tree Remembers — a blanked unit fires NO When Defeated.
#
# Bug report #1027 (game 4162): "Tree Remembers not blanking Droid Missile Platform's When Defeated."
#
# THE CARDS, and they interlock exactly:
#   LAW_132 The Tree Remembers (cost 4) — "An enemy unit loses all abilities for this phase. If it costs
#                                          3 or less, defeat it."
#   JTL_162 Droid Missile Platform (cost 3) — "When Defeated: Deal 3 indirect damage to a player."
# Cost exactly 3 means one card does BOTH: it blanks the unit and then defeats it, so the ability is
# already gone when the defeat that would have used it happens. Official ruling (03/27/2026) underlines
# how total the loss is — "Losing all abilities means the chosen unit also can't gain abilities for this
# phase."
#
# ROOT CAUSE. `CollectWhenDefeatedTriggers` (GameLogic.php) gated the trigger on
#     HasWhenDefeatedAbility($d['cardID']) && !_SWUGalenSuppressesCard(...)
# — a PRINTED-CardID lookup plus ONE hand-written special case, for SEC_046 Galen Erso's name-based
# blanking. It never called `LostAbilities()`, even though that helper already recognises the 'LAW_132'
# TurnEffect token this card stamps. So every GENERIC "loses all abilities" effect left the When
# Defeated firing: LAW_132, SOR_138, JTL_244, JTL_018, SHD_072, SEC_054, TWI_255.
# ⚠ Galen having its own special case is exactly what made this look covered.

---

# ExactReportedBoard_4162_NoIndirectDamage
#// THE REPORTED BOARD, rebuilt from game 4162's gamestate rather than invented: P1's Droid Missile
#// Platform sits exhausted in space, P2 holds The Tree Remembers with 4 resources, P2 to act.
#// Playing it blanks and defeats the Platform, and NOBODY may be left owing an indirect-damage
#// assignment. Before the fix P1 was handed a pending CUSTOM — the When Defeated resolving.
#// Kept alongside the minimal sections below because a minimal fixture can always drift from the board
#// that was actually reported.
## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:HMW_011:false:false:false:0;
  myBase:JTL_024;
  myBaseDamage:2;
  theirLeader:HMW_004:true:false:false:0;
  theirBase:HMW_029;
  theirBaseDamage:5;
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1Resources: 2:SOR_095:1,2:SOR_095:0
WithP2Resources: 4:SOR_095:1
WithP1SpaceArena: [JTL_162:0:0]
WithP2GroundArena: [ASH_030:0:1]
WithP2Hand: [LAW_132 LOF_070 JTL_043 JTL_043]
## WHEN
- P2>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1NODECISION
P2NODECISION
#// The reported board already carries damage, so these pin "no NEW indirect damage" rather than zero.
P1BASEDMG:2
P2BASEDMG:5

---

# BlankedByTreeRemembers_NoWhenDefeated
#// The same claim on a minimal board, so a failure points at the rule rather than at anything else the
#// reported position happens to contain.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: [LAW_132]
WithP1SpaceArena: [JTL_162:1:0]
## WHEN
- P2>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1NODECISION
P2NODECISION
P1BASEDMG:0
P2BASEDMG:0

---

# NotBlanked_WhenDefeatedSTILLFires
#// THE CONTROL, and without it every section above passes on a board where the When Defeated could not
#// have fired anyway. The SAME Droid Missile Platform, defeated by ordinary combat instead of by Tree
#// Remembers: nothing blanked it, so its ability resolves and P1 is asked to choose a player for the 3
#// indirect damage.
#// ⚠ THE ASSERTION IS THE PENDING DECISION, NOT BASE DAMAGE. The damage has not landed yet — P1 still
#// owes the "choose a player" step — so `P2BASEDMG` reads 0 here either way and cannot tell the two
#// apart. `P1HASDECISION` is what actually distinguishes "fired" from "suppressed".
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: [JTL_162:1:0]
WithP2SpaceArena: [SOR_046:1:0]
## WHEN
- P2>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1HASDECISION

---

# BlankedButNOTDefeated_KilledLaterThatPhase_StillNoWhenDefeated
#// THE OTHER CLAUSE, which the reported card cannot reach: JTL_162 costs exactly 3, so Tree Remembers
#// always defeats it and the blanking is never observed on its own. LOF_213 The Legacy Run costs 5, so
#// it is blanked but SURVIVES — then P2 kills it in combat later the same phase (3 power vs its 3 HP).
#// Its "When Defeated: Deal 6 damage divided as you choose among enemy units" must still be gone.
#// ⚠ This separates "the trigger was suppressed" from "LAW_132's own defeat path skipped collection".
#// The defeat here is an ordinary attack with nothing to do with LAW_132, so only a real blank explains
#// the silence — and the blank has had to survive from one action to the next to do it.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: [LAW_132]
WithP1SpaceArena: [LOF_213:1:0]
WithP2SpaceArena: [SOR_046:1:0]
## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1NODECISION
P2NODECISION

---

# BlankedUnitDiesAsTheATTACKER_StillNoWhenDefeated
#// THE OTHER SIDE OF COMBAT. The section above kills the blanked unit as the DEFENDER; here it dies as
#// the ATTACKER, to the defender's counter-damage. That is a different route into the defeat — the
#// attacker's death is resolved by the combat finaliser rather than by the defender-defeat path — and
#// "the same-side case passes while the cross-frame case is broken" is a standing hazard for every
#// "when this unit is defeated" card, so both directions are pinned rather than assumed.
#// Blanked LOF_213 (3/3) attacks SOR_046 (3/7): the Legacy Run dies, SOR_046 survives.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: [LAW_132]
WithP1SpaceArena: [LOF_213:1:0]
WithP2SpaceArena: [SOR_046:1:0]
## WHEN
- P2>PlayHand:0
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P1NODECISION
P2NODECISION

---

# BlankedUnitTRADES_AsAttacker_StillNoWhenDefeated
#// THE SIMULTANEOUS-DEFEAT CELL, which is its own bug family: when the blanked unit and the unit that
#// kills it die in the SAME batch, the trigger collection walks a list of several defeated cards at
#// once. A gate that reads the wrong entry's object — or reads none because the zone is mid-cleanup —
#// shows up here and nowhere else.
#// TWI_132 Confederate Tri-Fighter is 3/3 with NO triggers of its own, so it trades exactly with the
#// blanked 3/3 Legacy Run and contributes nothing that could be mistaken for LOF_213's ability.
#//
#// ⚠ P2 KEEPS A SECOND, SURVIVING UNIT, AND THE SECTION IS WORTHLESS WITHOUT IT. LOF_213's When
#// Defeated deals its 6 damage "among ENEMY UNITS", so if the trade empties P2's board the ability has
#// no legal target and fizzles SILENTLY — identical output whether it was suppressed or merely had
#// nowhere to point. Measured: the first draft stayed GREEN under the mutation that reds every other
#// blanking section here. SOR_046 (3/7) survives the trade and gives the ability something to aim at,
#// so a fired trigger now shows up as a real pending decision.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: [LAW_132]
WithP1SpaceArena: [LOF_213:1:0]
WithP2SpaceArena: [TWI_132:1:0]
WithP2SpaceArena: [SOR_046:1:0]
## WHEN
- P2>PlayHand:0
#// index 0 is the Tri-Fighter — the trade partner. SOR_046 at index 1 is the survivor.
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P1NODECISION
P2NODECISION

---

# BlankedUnitTRADES_AsDefender_StillNoWhenDefeated
#// The same trade with the roles swapped — the blanked unit is the DEFENDER and both still die in one
#// batch. Paired with the section above so neither who-attacks-whom direction is left to inference.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: [LAW_132]
WithP1SpaceArena: [LOF_213:1:0]
WithP2SpaceArena: [TWI_132:1:0]
## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1NODECISION
P2NODECISION
