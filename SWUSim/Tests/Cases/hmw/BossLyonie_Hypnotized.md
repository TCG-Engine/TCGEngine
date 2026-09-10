# WhenPlayed_GivesAnotherShield
#// HMW_057 Boss Lyonie, Hypnotized (Unit, Ground, cost 5, 5/5, [Cunning][Vigilance], Gungan/Official,
#// unique) — "When Played/On Attack: You may choose a token upgrade attached to another unit. Give another
#// one of those tokens to that unit."
#//
#// COVERAGE: offer=OnAttack_OfferIsEveryTokenUpgradeOnAnotherUnit (SELECTABLEEXACT — excludes a token on
#//           Lyonie herself and a non-token upgrade; includes an enemy unit's token) ·
#//           decline=Decline_NothingGiven · boundary=N/A (structural: exactly one token is given, of the
#//           chosen kind — pinned by every positive's count) ·
#//           control=N/A (structural: no "friendly"/"your" wording and no owner-scoped zone — the pool is
#//           every unit but Lyonie, excluded by identity, and the giver is simply her controller) ·
#//           reqboundary=RequestBoundary_BeforeTheAnswer ·
#//           modes=2P,TwinSuns,TeamSuns — "another unit" is unqualified: every seat's units, a far seat's
#//           (TwinSuns_AFarSeatsTokenIsOffered) and a teammate's (TeamSuns_ATeammatesTokenIsOffered).
#//
#// The pick is the TOKEN itself, addressed on its host as "<host>.u<sub>" (the JTL_242 subcard form).
#// "Another one of those tokens" is given by the token's KIND (title), so a reprint Shield gives a Shield.
#// This section drives the real When Played: Lyonie is played; the Marine's Shield is the only token on
#// the table, and as a "you may" the offer still prompts. The Marine ends with two Shields.

## GIVEN
CommonSetup: ybk/rrk/{myResources:5}
WithActivePlayer: 1
WithP1Hand: HMW_057
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_057
P1GLOBALEFFECT:SWU_GAVE_TOKEN_UPGRADE
TURNPLAYER:2
NOEXTRAACTION

---

# OnAttack_OfferIsEveryTokenUpgradeOnAnotherUnit
#// HMW_057 — the OFFER, through the On Attack window. Lyonie (ground 0) carries a Shield of her own —
#// EXCLUDED ("another unit"). The Marine (ground 1) carries a Shield (u0), an Experience (u1) and a
#// non-token Academy Training (u2 — EXCLUDED, not a token upgrade). P2's Dark Trooper carries a Weakness
#// (INCLUDED — "another unit" is unqualified). Left pending.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP1GroundArenaUpgrade: 1:SOR_T01
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1.u0&myGroundArena-1.u1&theirGroundArena-0.u0

---

# OnAttack_GivesAnotherExperience
#// HMW_057 — On Attack, positive: choose the Marine's Experience → a second Experience (3/3 → 5/5).

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:UPGRADE:1:CARDID:SOR_T01
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
TURNPLAYER:2
NOEXTRAACTION

---

# AnEnemysWeaknessGetsAnotherWeakness
#// HMW_057 — the offensive use: an ENEMY unit's Weakness token (HMW_T02, -1/-1) → a second one. P2's Dark
#// Trooper goes 3/3 → 2/2 → 1/1.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:UPGRADE:1:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1

---

# AnotherWeaknessCanDefeatTheUnit
#// HMW_057 — a Weakness that takes a unit to 0 HP defeats it. P2's Dark Trooper already carries two
#// (1/1); a third leaves it at 0 HP and it must be defeated, not linger on the board.
#// ⚠ Measured: on this On Attack path the attack's own resolution sweeps the unit too, so this section
#// stays green without the handler's sweep — the _WhenPlayed section below is the one that pins it.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SEC_080

---

# AnotherWeaknessCanDefeatTheUnit_WhenPlayed
#// HMW_057 — the same 0-HP defeat on the WHEN PLAYED path, where no combat follows the give. (On the On
#// Attack path above, the attack's own resolution would sweep the unit anyway; here only the handler's
#// shrink sweep can.)

## GIVEN
CommonSetup: ybk/rrk/{myResources:5}
WithActivePlayer: 1
WithP1Hand: HMW_057
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SEC_080

---

# AnAdvantageGetsAnotherAdvantage
#// HMW_057 — the fourth token kind: an Advantage token (ASH_T02) → a second Advantage.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:ASH_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:ASH_T02
P1GROUNDARENAUNIT:1:UPGRADE:1:CARDID:ASH_T02

---

# AReprintShieldGivesAShield
#// HMW_057 — "another one of THOSE tokens" is the token's KIND. A reprint Shield (SHD_T02) is a Shield
#// token, so the Marine gains a Shield (the engine's Shield, SOR_T02) — not a stray copy of whatever
#// CardID the reprint happens to carry, which the Shield-specific paths would not recognise.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SHD_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1.u0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:UPGRADE:1:CARDID:SOR_T02

---

# Decline_NothingGiven
#// HMW_057 — the DECLINE: no token is given (and the "gave a token" flag stays unset).

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1NOGLOBALEFFECT:SWU_GAVE_TOKEN_UPGRADE
TURNPLAYER:2

---

# NoTokenOnAnotherUnit_NoPrompt
#// HMW_057 — "ANOTHER unit", observable: the only token on the table is on Lyonie herself, so there is
#// nothing to choose — no prompt, and the action closes normally.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
TURNPLAYER:2

---

# NoTokenAnywhere_WhenPlayedNoPrompt
#// HMW_057 — NO VALID TARGET on the When Played path: Lyonie is played onto a table with no token upgrades.

## GIVEN
CommonSetup: ybk/rrk/{myResources:5}
WithActivePlayer: 1
WithP1Hand: HMW_057
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_057
P1NODECISION
TURNPLAYER:2

---

# RequestBoundary_BeforeTheAnswer
#// HMW_057 — the REQUEST-BOUNDARY cell: the On Attack Experience section with a boundary before the pick.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1.u0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:5
TURNPLAYER:2

---

# LostAbilities_NoOffer
#// HMW_057 — a Lyonie that has lost her abilities (SOR_138 Force Lightning's marker) has no On Attack.

## GIVEN
CommonSetup: ybk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_057:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# TwinSuns_AFarSeatsTokenIsOffered
#// ⚠ HMW_057 — "another unit" spans every seat. The only token is a Weakness on seat 3's unit; it is
#// offered, chosen, and seat 3's unit gains the second Weakness. Cannot pass at two seats.

## GIVEN
CommonSetup: ybk/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: HMW_057:1:0
WithP3GroundArena: SEC_080:1:0
WithP3GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0.u0

## EXPECT
SEATCOUNT:3
P3GROUNDARENAUNIT:0:UPGRADECOUNT:2
P3GROUNDARENAUNIT:0:HP:1

---

# TeamSuns_ATeammatesTokenIsOffered
#// ⚠ HMW_057 — "another unit" is unqualified, so a TEAMMATE's unit is in the pool too. (JTL_242's scan is
#// my + their, which in a team game skips the teammate — this card builds from SWUAllUnits instead.) The
#// only token is a Shield on seat 3's Marine; it is offered and doubled.

## GIVEN
CommonSetup: ybk/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1GroundArena: HMW_057:1:0
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0.u0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:SHIELDCOUNT:2
