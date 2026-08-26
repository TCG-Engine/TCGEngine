# AttackEndAoeOnBaseHit
#// ASH_183 Whistling Birds (Upgrade, non-Vehicle) — Attached unit gains "When Attack Ends: if this unit
#// dealt combat damage to an opponent's base, deal 2 to each unit that opponent controls in this unit's
#// arena." SOR_095 (3/3 + Whistling Birds +2/+2 → 5 power) attacks P2's base for 5; afterward the enemy
#// SEC_080 (in the ground arena) takes 2.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:0
P2BASEDMG:5
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# AttackUnit_NoBaseHit_NoAoe
#// ASH_183 Whistling Birds — the AoE requires combat damage to an opponent's BASE. When the host attacks a
#// unit (SEC_080) instead, no base damage is dealt, so the bystander SOR_046 takes nothing.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AttachRestriction_NonVehicleOnly
#// ASH_183 Whistling Birds — "Attach to a non-Vehicle unit." When played from hand, only non-Vehicle units
#// are valid targets. With a friendly non-Vehicle SOR_095 (ground), a friendly Vehicle SOR_244 (Snowspeeder,
#// ground), a friendly Vehicle SOR_178 (Cartel Spacer, space) and an enemy non-Vehicle SOR_164 (Wampa, ground)
#// in play, only the two non-Vehicle units are selectable — the Vehicles are excluded.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_183}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_244:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# AttackEndAoe_MultipleEnemyGroundUnits_ArenaScoped
#// ASH_183 Whistling Birds — on a base hit, the AoE deals 2 to EACH enemy unit in the attacker's arena only.
#// Host SOR_095 (3/3 + 2/2 = 5) attacks P2's base for 5; each enemy GROUND unit (SEC_080 and SHD_258) takes 2,
#// but the enemy SPACE unit SOR_178 (different arena) and both friendly units (host + SEC_164) take nothing.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: [SOR_095:1:0 SEC_164:1:0]
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [SEC_080:1:0 SHD_258:1:0]
WithP2SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# OverwhelmExcessToBase_Triggers
#// ASH_183 Whistling Birds — Overwhelm excess dealt to the base counts as combat damage to the base, so the
#// AoE fires. Host SOR_164 (Wampa 4/5 Overwhelm + 2/2 = 6/7) attacks SOR_140 (SpecForce 2/2): SpecForce is
#// defeated and 4 excess combat damage overwhelms into P2's base, then the remaining enemy SHD_258 takes 2.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [SOR_140:1:0 SHD_258:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_258
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ShieldedEnemyConsumesShield
#// ASH_183 Whistling Birds — the AoE damage interacts with Shield tokens normally. Host SOR_095 (5 power)
#// attacks P2's base for 5. The AoE's 2 damage to the shielded SOR_207 (Crafty Smuggler + Shield token) is
#// absorbed: the Shield is consumed and it takes 0, while the unshielded SOR_164 takes the full 2.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [SOR_207:1:0 SOR_164:1:0]
WithP2GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:CARDID:SOR_207
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:SOR_164
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# AoeDefeatsLowHpUnits
#// ASH_183 Whistling Birds — AoE damage defeats units with 2 or fewer remaining HP. Host SOR_095 (5 power)
#// attacks P2's base; the 2 AoE damage defeats SOR_140 (SpecForce, 2 HP) while SOR_164 (Wampa, 5 HP) survives
#// with 2 damage.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [SOR_140:1:0 SOR_164:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# AbilityDamageToBase_NoTrigger
#// ASH_183 Whistling Birds — only COMBAT damage to the base arms the AoE. Host LAW_181 (Cloud-Rider Veteran
#// 1/4 + 2/2 = 3/6) attacks SOR_140 (SpecForce) and its On Attack deals 2 ability damage to P2's base. The
#// base takes 2 (ability, not combat), so Whistling Birds does NOT fire and the bystander SOR_164 takes 0.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: LAW_181:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [SOR_140:1:0 SOR_164:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# HostDefeatedInCombat_AoeStillFires
#// ASH_183 — the "When Attack Ends" AoE has no "and survives" clause, so it fires even when the host dies in the
#// same combat. Wampa (SOR_164, 4/5 Overwhelm, pre-damaged 4) wears Whistling Birds and attacks SOR_108 (1/2):
#// Wampa deals 4 (SOR_108 dies, 2 Overwhelm to base) and dies to the 1 counter. Base took 2 combat → the AoE
#// deals 2 to the surviving enemy SOR_046.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: [LOF_168:1:0 SHD_258:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# TwinSuns_HitsOnlyTHATOpponentsUnits
#// ⚠ REPORTED BUG (2026-08-25): "Whistling birds ASH was hitting 2 players instead of just the defending
#// player's units."
#//
#// ASH_183 reads "...deal 2 damage to each unit THAT OPPONENT controls in this unit's arena." "That
#// opponent" is the one whose base was just damaged — a seat the combat has already DETERMINED. The
#// handler collected its victims with ZoneSearch("their{$arena}"), and since the Twin Suns fan-out
#// "their<Zone>" spans ALL live opponents at 3+ seats, one base hit sprayed every opponent's board.
#//
#// The fix scopes the search to the defending seat (SWUCurrentDefendingSeat) — p{n}<Arena> at 3+ seats,
#// theirs at two, so Premier is untouched.
#//
#// Seat 1's host (SOR_046, 3/7, +2/+2 from ASH_183 = 5/9) attacks seat 4's base for 5 combat damage,
#// which satisfies "dealt combat damage to an opponent's base". Seat 4's ground unit must take 2;
#// seats 2 and 3 field identical units that must be untouched — they are what the section exists for.

## GIVEN
CommonSetup: rrk/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:P4B

## EXPECT
SEATCOUNT:4
P4BASEDMG:5
P4GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P3GROUNDARENAUNIT:0:DAMAGE:0
