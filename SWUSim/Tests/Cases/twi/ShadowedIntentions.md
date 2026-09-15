# ImmuneToEnemyBounce
#// TWI_220 Shadowed Intentions (Upgrade, cost 3) — "Attached unit gains: 'This unit can't be captured,
#// defeated, or returned to its owner's hand by enemy card abilities.'" P2's Waylay (TWI_226, "Return a
#// non-leader unit to its owner's hand") cannot return the TWI_220-protected SOR_095, which stays in play.

## GIVEN
CommonSetup: rrk/yyk/{theirResources:3;theirhandCardIds:TWI_226}
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_220

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TeamSuns_ATeammatesDefeatIsNotAnEnemyAbility
#// TWI_220 — Team Suns (1+3 vs 2+4): the protection is against ENEMY card abilities, and a teammate is not
#// an enemy. P1 plays IBH_095 You Have Failed Me ("Defeat a friendly unit" — a teammate's counts, user
#// ruling 2026-08-25) on teammate P3's protected SOR_095: it IS defeated. Until 2026-09-15 SWUDefeatUnit
#// read "enemy" as "any other seat" and refused it. Found building HMW_099 Always a Bigger Fish; fixed via
#// SWUIsEnemySeat. (IBH_095's own ready clause then has no friendly unit left — it fizzles.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: IBH_095
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0

---

# TeamSuns_AnEnemysDefeatIsStillRefused
#// TWI_220 — the other half of the same fix, so it cannot have over-widened: in a team game an OPPOSING
#// seat's defeat effect is still refused. P1 plays SOR_078 Vanquish ("Defeat a non-leader unit") on enemy
#// P2's protected SOR_095 (the only non-leader unit, so it auto-resolves): it stays in play.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_078
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
