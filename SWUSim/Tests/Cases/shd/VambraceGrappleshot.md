# VambraceGrappleshot_ExhaustDefender
#// SHD_074 Vambrace Grappleshot — attached unit gains "On Attack: Exhaust the defender." The host
#// (SOR_046 3/7 + SHD_074 +2/+2 = 5/9) attacks a ready SOR_046 (3/7): deals 5 (defender survives with 2)
#// and the granted On Attack exhausts the defender.
#// COVERAGE: offer=DEFERRED — the printed "Attach to a non-Vehicle unit" host pool is an open engine gap
#//           for this card (the pool it currently builds neither drops Vehicles nor reaches the enemy
#//           board), so asserting it would encode the wrong pool; every host below is seated directly ·
#//           control=EnemyControlledHost_ExhaustsTheOtherSidesUnit (host seated under P2's control; the
#//           granted trigger resolves for the HOST's controller and exhausts P1's unit) ·
#//           boundary=VambraceGrappleshot_ExhaustDefender vs DefenderAlreadyExhausted_LegalNoOp (ready vs
#//           already-exhausted defender) and vs BaseAttack_NothingToExhaust (unit defender vs base, the
#//           "there is no defender to exhaust" edge), plus MultiTargetAttacker_SingleDefenderBranch_Still
#//           Exhausts for the one-defender branch of a multi-target attacker (its two-defender branch is
#//           the open gap) · decline=N/A — neither clause is a "you may": the attach is a plain host pick
#//           and the granted On Attack exhausts with no opt-out and no choice of target ·
#//           reqboundary=N/A — the granted ability raises no decision at all (the defender is fixed by the
#//           attack that triggered it), so there is no pending state for a request boundary to split.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# DefenderAlreadyExhausted_LegalNoOp
#// "Exhaust the defender" carries no ready/exhausted qualifier, so attacking an ALREADY exhausted unit is
#// a legal no-op rather than a fizzle — the attack itself resolves normally and the defender simply stays
#// exhausted. Same fixture as VambraceGrappleshot_ExhaustDefender with the defender seated exhausted: the
#// 5/9 host still deals its 5, and an exhausted defender still deals its own 3 back (CR: readiness gates
#// attacking, not defending). If the granted trigger treated "already exhausted" as no legal target and
#// aborted the ability, the combat damage would go missing.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# UpgradeDefeated_GrantedAbilityGoesWithIt
#// The On Attack is GRANTED BY the upgrade, so removing the upgrade must remove the ability with it — a
#// grant that outlived its source would keep exhausting defenders forever. SHD_262 Confiscate ("Defeat an
#// upgrade") takes the Grappleshot off SOR_095 (the only upgrade in play, so the pick auto-resolves), and
#// the now-bare 3/3 marine attacks P2's 3/7 SOR_046: 3 damage in, 3 back — the marine dies and the
#// defender is left READY. The ready defender is the whole assertion; with the upgrade still granting, it
#// would have been exhausted. Confiscate has no aspect, so it costs its printed 1 with nothing left over.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SHD_262
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:0

---

# MultiTargetAttacker_SingleDefenderBranch_StillExhausts
#// TWI_135 Darth Maul "can attack 2 units instead of 1", and that choice routes the attack through a
#// DIFFERENT code path than an ordinary attack. This section pins the branch of that path that takes only
#// ONE defender: Maul (5/6) wears the Grappleshot for 7/8, the Base-vs-Units choice answers "Units" and
#// the 1-or-2 multi-select picks a single TWI_054 Duchess's Champion (1/8, no Sentinel here — P1 controls
#// only one unit). The chosen defender takes Maul's full 7, survives on 8 HP and must end EXHAUSTED; the
#// unpicked one is untouched and READY, which is what proves the exhaust came from being the defender
#// rather than from a sweep. The TWO-defender branch of the same action is a known open gap.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: [TWI_054:1:0 TWI_054:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:7
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# EnemyControlledHost_ExhaustsTheOtherSidesUnit
#// THE CONTROL AXIS. The granted "On Attack: Exhaust the defender" belongs to whoever controls the ATTACHED
#// unit, so a Grappleshot sitting on a unit P2 controls exhausts P1's units, not P2's. P2's host (SOR_046
#// 3/7 + SHD_074 +2/+2 = 5/9) attacks P1's ready 3/7 SOR_046: the defender takes 5 and is left EXHAUSTED
#// while the attacker takes 3 back and stays untouched by its own trigger. The mirror of
#// VambraceGrappleshot_ExhaustDefender with the seats swapped — nothing in the ability is seat-anchored.

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_074
WithP1GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# BaseAttack_NothingToExhaust
#// "Exhaust the DEFENDER" needs a defending UNIT; a base is not one, so attacking a base is a clean no-op
#// for the granted trigger rather than an error or a stray exhaust of some other unit. The 5/9 host swings
#// at P2's base for 5 while P2's ready SOR_046 stands by in the same arena — it must still be READY and
#// undamaged afterwards, which is what rules out the trigger falling back to "any enemy unit" when the
#// defender slot holds a base.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AttachOffer_NonVehicleUnitsOnEitherSide
#// SHD_074 — "Attach to a non-Vehicle unit." names no controller, so per CR 2.e the pool is every
#// non-Vehicle unit on EITHER side, and Vehicles are excluded on both. P1 fields a Snowspeeder (Vehicle)
#// and a Battlefield Marine; P2 fields a Consular Security Force. The offer is exactly the two
#// non-Vehicles — the friendly Vehicle is out, the ENEMY non-Vehicle is in. Same family as Jetpack
#// SHD_225, whose pool this now matches.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SHD_074
WithP1GroundArena: [SOR_244:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# MultiTargetAttacker_TWODefenderBranch_ExhaustsBOTH
#// ⚠⚠ KNOWN ENGINE BUG — THIS SECTION IS EXPECTED TO BE RED. Restored 2026-09-01 from the SHD worklist,
#// where it had existed only as PROSE since 2026-08-15 because the old practice deleted failing sections
#// to keep the file green. It asserts the CORRECT behaviour.
#//
#// The section directly above (MultiTargetAttacker_SingleDefenderBranch_StillExhausts) is the CONTROL:
#// the same Maul + Grappleshot board taking ONE defender exhausts it correctly. This is the TWO-defender
#// branch of the same action, and the granted "On Attack: Exhaust the defender" reaches NEITHER of them.
#//
#// Maul (5/6) wears the Grappleshot for 7/8 and attacks both TWI_054 Duchess's Champions (1/8): each
#// takes 7 and survives, and their combined 2 comes back at Maul. Both were defenders, so both must end
#// EXHAUSTED.
#// EXPECTED: both exhausted. ACTUAL: both READY — the trigger reads an empty defender and returns.
#// ROOT CAUSE: _SWUMaulBeginDoubleAttack (CombatLogic.php ~3662) never writes SWU_CURRENT_DEFENDER /
#// SWU_CURRENT_DEFENDER_UID, which ExecuteSWUAttack sets at ~2165/2171 — so ANY "the defender"-targeting
#// On Attack reads empty on this path. Blast radius is the whole family (SOR_054 Jedi Lightsaber, …).
#// FIXED 2026-09-02 — and the "NOT a one-liner" note above was WRONG about the shape, which is why the
#// double-fire worry evaporated. OFFICIAL RULING, Darth Maul - Revenge At Last (10/31/2024): "If Darth
#// Maul attacks two units instead of one, BOTH UNITS ARE CONSIDERED DEFENDERS OF ONE ATTACK. Each step of
#// the attack and any triggered abilities only occur ONCE, as usual." So the trigger does NOT run once per
#// defender — it runs once, and "the defender" simply DENOTES BOTH. The fix is therefore a defender SET
#// (SWU_CURRENT_DEFENDER_UIDS + the SWUCurrentDefenderMzIDs() accessor), not a second trigger pass, so an
#// attacker-side On Attack that never mentions the defender ("deal 2 to a base") still fires exactly once.
#// The accessor returns ONE mzID on every ordinary attack, so converted cards are byte-identical there.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: [TWI_054:1:0 TWI_054:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:7
P2GROUNDARENAUNIT:1:DAMAGE:7
P1GROUNDARENAUNIT:0:DAMAGE:2


---

# MaulDefenderSet_DoesNotLEAKIntoTheNextAttack
#// ⚠ THE LEAK GUARD, and it is invisible without this section (mutation-proven: deleting the normal
#// attack's defender-set publish left the whole suite green).
#// SWU_CURRENT_DEFENDER_UIDS is a persisted gamestate var. If an ordinary attack did not overwrite it,
#// the PAIR from a preceding TWI_135 Maul two-defender attack would still be sitting there, and the next
#// attacker's "the defender" would resolve to Maul's two victims instead of its own defender.
#// Maul carries NO Grappleshot here, so his double attack exhausts nobody — it exists only to publish the
#// stale pair. Then SOR_095 (3/3, +2/+2 from the Grappleshot = 5/5) attacks the THIRD Champion.
#// EXPECTED (guarded): only Champion 2 — the one actually attacked — is exhausted.
#// UNDER THE LEAK: Champions 0 and 1 exhaust and Champion 2 stays READY, which is why all three READY/
#// EXHAUSTED assertions are needed rather than just the one.
#// TWI_054 Duchess's Champion is 1/8, so nothing dies and the arena never re-indexes.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: [TWI_135:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:SHD_074
WithP2GroundArena: [TWI_054:1:0 TWI_054:1:0 TWI_054:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P1>AttackGroundArena:1:2

## EXPECT
P2GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:2:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
