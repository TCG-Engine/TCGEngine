# GrantedAttackAction
#// SHD_155 Heroic Resolve (Upgrade, +1/+1) grants the host: "Action [2 resources, defeat a Heroic Resolve
#// on this unit]: Attack with this unit. It gets +4/+0 and gains Overwhelm for this attack." SOR_046 (3/7)
#// wears it (→4/8); using the action pays 2 resources, defeats the upgrade (host back to 3 base power),
#// then attacks with 3+4=7 power and Overwhelm at the enemy SOR_160 (2 HP): 2 defeats it and 5 overwhelms
#// to P2's base. Afterward the host is back to 3 power (upgrade gone, attack bonus expired).
#// COVERAGE: offer=AttackTargetOffer_TheGrantedAttackIsAnOrdinaryAttack (the granted attack's target pool
#//           left PENDING and read with P1SELECTABLEEXACT: both enemy ground units + the enemy base, the
#//           enemy space unit out of reach). The "which copy of Heroic Resolve is defeated" pick is NOT an
#//           offer in SWUSim — the copies are indistinguishable, so the cost auto-spends one and
#//           TwoCopiesOnOneHost_ExactlyOneIsSpentPerActivation asserts the outcome instead ·
#//           control=EnemyControlledHost_TheGrantedActionBelongsToTheHostsController (the host sits on the
#//           other player's board; THEY activate it and THEIR resources pay, CR 2.e) ·
#//           boundary=BuffAndOverwhelmAreBothForThisAttackOnly vs this section (defender survives, so
#//           Overwhelm carries nothing / defender dies, so it carries the excess) and
#//           TwoCopiesOnOneHost_ExactlyOneIsSpentPerActivation vs SecondCopyPowersASecondActivationThe
#//           FollowingRound (the copy-count pair either side of a regroup: 2 copies → 1 → 0) ·
#//           decline=N/A — the granted ability is an ACTION, so declining it is simply not taking it; once
#//           activated neither the cost nor the attack is a "you may", and the target pick is mandatory ·
#//           reqboundary=N/A here — the only pending state is the ordinary attack-target decision, which
#//           the shared attack machinery already carries across the boundary (core coverage), and this
#//           card adds no ability-specific state to it.
#// KNOWN GAP: activating with an EXHAUSTED host (the CR "you may pay a cost even if the effect does
#//           nothing" branch) is not portable today — the action is gated on the host being ready, so no
#//           cost is paid and no upgrade is defeated. Left unencoded pending a ruling.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3

---

# BothHalvesOfTheCostArePaid_TwoResourcesAndTheUpgrade
#// THE COST IS TWO THINGS: "[2 resources, defeat a Heroic Resolve on this unit]". GrantedAttackAction
#// proves the ATTACK half; this section proves the PAYMENT half, which a test that only watches the attack
#// leaves entirely unasserted. P1 starts with THREE resources so the spend is visible as a remainder
#// rather than as "everything is gone": exactly 2 are exhausted and 1 stays ready. The upgrade is not just
#// detached — it is DEFEATED, so it lands in the discard pile, and the host drops the +1/+1 before the
#// attack: SOR_046 3/7 (not 4/8) swinging at 3+4 = 7. SOR_140 SpecForce Soldier (2/2) dies to 2 of that
#// and Overwhelm carries the other 5 to P2's base; the 2 damage back leaves the host on 2 of its 7 HP.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_155
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# TwoCopiesOnOneHost_ExactlyOneIsSpentPerActivation
#// Heroic Resolve is non-unique, so a host can wear several — and the cost is "defeat A Heroic Resolve on
#// this unit", singular. One activation spends exactly ONE copy and leaves the other (and any unrelated
#// upgrade) attached. SOR_046 3/7 wears two SHD_155 plus SOR_120 Academy Training (+2/+2) for 7/11; paying
#// the cost drops it to 6/10 wearing SHD_155 + SOR_120, and the attack goes in at 6+4 = 10. SOR_140
#// SpecForce Soldier (2/2) dies to 2 of it with Overwhelm carrying 8 to P2's base, and the 2 back leaves
#// the host on 2 damage. Exactly one Heroic Resolve reaches the discard — a cost that swept every copy, or
#// one that took the Academy Training instead, would show up as a different UPGRADECOUNT and power.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP1GroundArenaUpgrade: 0:SHD_155
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_155
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_155
P1GROUNDARENAUNIT:0:UPGRADE:1:CARDID:SOR_120
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:10
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P2BASEDMG:8

---

# SecondCopyPowersASecondActivationTheFollowingRound
#// The other half of TwoCopiesOnOneHost_ExactlyOneIsSpentPerActivation: the surviving copy is a fully
#// live second activation, not a spent shell. Round 1 plays out exactly as that section (first copy
#// defeated, 10-power swing, 8 to P2's base); then both players pass into the regroup, decline the extra
#// resource, and the host readies with its 2 damage intact and its resources refreshed. Round 2 spends
#// the SECOND Heroic Resolve: the host is 5/9 by then (3/7 + Academy Training only) and swings at 5+4 = 9,
#// killing the other SpecForce Soldier and overwhelming 7 more into the base for 15 total. Both copies end
#// in the discard, only the Academy Training is still attached, and the host carries 4 damage.
#// Both decks are seeded past the regroup draw so no empty-deck damage pollutes the base counts.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP1GroundArenaUpgrade: 0:SHD_155
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: [SOR_140:1:0 SOR_140:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1DISCARDCOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0
P2BASEDMG:15

---

# EnemyControlledHost_TheGrantedActionBelongsToTheHostsController
#// THE CONTROL AXIS. Heroic Resolve grants the ability to the ATTACHED UNIT, so it is the host's
#// CONTROLLER who may activate it and whose resources pay for it (CR 2.e) — an upgrade played onto an
#// opponent's unit hands them the action. Here the host sits on P2's board: P2's SOR_046 (3/7 + 1/1 = 4/8)
#// activates, spends P2's two resources, defeats the Heroic Resolve and swings at 3+4 = 7 into P1's
#// SOR_140 SpecForce Soldier (2/2) — dead, with Overwhelm carrying 5 into P1's base. P1 spends nothing and
#// takes the damage; the mirror of BothHalvesOfTheCostArePaid_TwoResourcesAndTheUpgrade with seats swapped.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP2Resources: 2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_155
WithP1GroundArena: SOR_140:1:0

## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2RESAVAILABLE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0
P1BASEDMG:5

---

# AttackTargetOffer_TheGrantedAttackIsAnOrdinaryAttack
#// THE OFFER AXIS. "Attack with this unit" grants no special targeting, so once the cost is paid the
#// target pool is exactly what an ordinary attack by a ground unit sees: every enemy GROUND unit plus the
#// enemy base, with the enemy SPACE unit (SOR_180 Seventh Fleet Defender) out of reach. Two enemy ground
#// units keep the pick interactive so the offer can be read while still PENDING — the decision is
#// deliberately left unanswered here and resolved in the neighbouring sections. Neither TWI_054 nor
#// SOR_140 has Sentinel on this board (TWI_054's needs an opponent with 3+ units), so nothing is
#// artificially forced into or out of the pool.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: [SOR_140:1:0 TWI_054:1:0]
WithP2SpaceArena: SOR_180:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirBase-0&theirGroundArena-0&theirGroundArena-1

---

# BuffAndOverwhelmAreBothForThisAttackOnly
#// "It gets +4/+0 and gains Overwhelm FOR THIS ATTACK" — a duration, and both halves have to expire
#// together when the attack ends. The host swings at 3+4 = 7 into TWI_054 Duchess's Champion (1/8, no
#// Sentinel with only one enemy unit on the board), which SURVIVES on 7 damage: nothing is in excess, so
#// Overwhelm has nothing to carry and P2's base stays untouched at 0 — the flip side of the base damage
#// every other section here reads. Afterwards the host is back to its printed 3 power and no longer has
#// Overwhelm, which is the assertion a base-damage-only test cannot make.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: TWI_054:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:7
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:0

---

# ExhaustedHost_TheCostsAreStillPayable_AsASoftPass
#// SHD_155 — USER RULING (2026-08-15): the granted Action has NO readiness requirement. Its costs
#// (2 resources + defeat a Heroic Resolve on this unit) are payable even when the attack cannot happen,
#// and doing so is a legitimate SOFT PASS — the game state genuinely changed. Here the host is already
#// exhausted: P1 pays 2 (3 -> 1 ready), the upgrade is defeated to the discard, and no attack occurs.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_046:0:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_155
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
