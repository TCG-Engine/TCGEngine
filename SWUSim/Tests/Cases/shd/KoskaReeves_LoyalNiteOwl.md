# OnAttack_NotUpgraded_NoOffer
#// SHD_150 Koska Reeves — Unit, cost 4, 4/5, Ground, [Heroism][Aggression], traits Mandalorian/Trooper,
#// unique. "On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit."
#// COVERAGE: offer=OnAttack_Upgraded_OfferIsGroundArenaOnly (both sides' ground units, space excluded;
#//           left PENDING so the offer is the end state) · decline=OnAttack_Upgraded_Decline_NoDamage ·
#//           boundary=OnAttack_NotUpgraded_NoOffer vs OnAttack_Upgraded_Deal2 (the 0-upgrade / 1-upgrade
#//           pair across the "if this unit is upgraded" gate) · control=OnAttack_Upgraded_CanTargetFriendly
#//           (the printed text says "a ground unit" with NO friendly/enemy qualifier, so the pool spans
#//           both controllers — the friendly leg is the proof) · reqboundary=N/A (the ability owns no
#//           persistent state; it collects its pool and resolves inside one action).
#// With no upgrade attached, the "if this unit is upgraded" gate fails and there is
#// no offer. The enemy SOR_046 is untouched and no decision is pending.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnAttack_Upgraded_Deal2
#// SHD_150 Koska Reeves (4-cost 4/5 ground) — "On Attack: If this unit is upgraded, you may deal 2 damage
#// to a ground unit." Koska carries SOR_120, so on attacking the base the rider fires; P1 deals 2 to the
#// enemy SOR_046 (7 HP → 2 damage).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# OnAttack_Upgraded_OfferIsGroundArenaOnly
#// SHD_150 Koska Reeves — the target pool is "a ground unit": every ground unit on BOTH sides, and no
#// space unit at all. Koska (upgraded with SOR_120 Academy Training) plus the enemy SOR_046 are the only
#// two entries; the friendly SOR_237 and the enemy SOR_225 sit in the space arena and are absent.
#// Two ground candidates are seated on purpose so the pick stays interactive — a lone legal target would
#// auto-resolve and leave no offer to assert. The decision is left PENDING (no answer consumes it) because
#// EXPECT only ever evaluates the END state.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# OnAttack_Upgraded_Decline_NoDamage
#// SHD_150 Koska Reeves — the rider is "you MAY deal 2 damage", so the offer is declinable even though the
#// upgrade gate passed. P1 declines; nothing on either board takes the 2, and no decision is left pending.
#// The attack itself still lands: Koska is 4/5 printed and SOR_120 Academy Training is +2/+2, so the base
#// takes 6.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnAttack_Upgraded_CanTargetFriendly
#// SHD_150 Koska Reeves — the printed rider says "a ground unit" with no friendly/enemy qualifier, so the
#// controller's OWN ground units are legal targets. P1 aims the 2 damage at their own SOR_046 (ground index
#// 1) while the enemy SOR_046 stands untouched — the proof that the pool is not enemy-only.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
