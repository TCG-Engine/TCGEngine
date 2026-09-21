# Event_UnqualifiedPool_IncludesTheTeammatesUnit
#// ─── UNQUALIFIED target pools must span the WHOLE table, teammates included ──────────────────────
#//
#// An effect that says "a unit" with no enemy/friendly qualifier can target ANY unit in play. These
#// pools were hand-rolled as `ZoneSearch('my*') + ZoneSearch('their*')`, which covered the table at two
#// seats. Once `their*` became team-aware it stopped including a TEAMMATE — so `my` + `their` no longer
#// adds up to "everything", and a teammate's units silently vanished from the pool. No error, no
#// refusal: the card simply could not see one seat.
#//
#// The fix is SWUAllUnits(), which starts from 'team'. Outside a team game 'team' degrades to 'my', so
#// every one of these cards is byte-identical in Premier and Twin Suns — which is also why the whole
#// existing suite stayed green through the conversion and could not have caught this.
#//
#// ⚠ THE POOL IS THE ASSERTION, not the outcome. Every one of these cards works perfectly well on an
#// enemy unit, so any test that simply plays the card and checks a result passes either way. The defect
#// is only visible as an ABSENCE from the offer, so each section leaves the decision PENDING and pins
#// the exact selectable set.
#//
#// Seats: WithTeams puts 1+3 against 2+4, so p3 is P1's teammate and p2 an opponent. Each board fields
#// one unit per seat, so a correct pool is exactly {own, enemy, teammate} — a set that also catches
#// over-widening (a pool that wrongly dropped the enemy, say) rather than just under-inclusion.
#//
#// One section per POOL SHAPE rather than per card, because the 17 converted cards now all call the
#// same helper with the same arguments; what differs between them is the call site's shape, and that
#// is what these four cover: plain event, non-leader-filtered event, leader Action, and On Attack.
#//
#// ── SHAPE 1: plain event, AnyUnitFilter. SOR_124 Tactical Advantage — "Give a unit +2/+2 for this
#// phase." Cost 1, so no aspect-penalty arithmetic can interfere.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_124
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0

---

# Event_NonLeaderFilteredPool_IncludesTheTeammatesUnit
#// ── SHAPE 2: the same fix with a NON-DEFAULT filter. JTL_043 No Glory, Only Results — "Take control
#// of a non-leader unit, then defeat it." It passes NonLeaderUnitFilter rather than relying on
#// SWUAllUnits()'s AnyUnitFilter default, so it pins that the filter is still threaded through after
#// the conversion — dropping it would silently widen the pool to include leader units.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_043
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0

---

# LeaderAction_UnqualifiedPool_IncludesTheTeammatesUnit
#// ── SHAPE 3: a leader ACTION ability rather than an event. SOR_004 Chirrut Îmwe — "Action [Exhaust]:
#// Give a unit +0/+2 for this phase." Reached through UseLeaderAbility, a different entry point into
#// the same pool-building code, and the shape used by 9 of the 17 converted cards.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:SOR_004; myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0

---

# OnAttack_UnqualifiedPool_IncludesTheTeammatesUnit
#// ── SHAPE 4: an On Attack trigger on a DEPLOYED leader. SOR_005 Luke Skywalker — "On Attack: You may
#// give another unit a Shield token." "Another" excludes the attacker itself, which is why Luke is
#// absent from the expected set while all three board units are present — the exclusion and the
#// teammate inclusion are pinned together, so a conversion that dropped the array_filter would red here.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:SOR_005; myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:p2Base-0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0
