# BobaFettsArmor_OnlyBobaFett
#// SHD_224 Boba Fett's Armor — the prevention is gated on the host being Boba Fett. On a non-Boba host
#// (SOR_046), SHD_180's 3 damage is NOT reduced → full 3 sticks.
#// COVERAGE: offer=N/A (a continuous prevention — there is no target pick and no prompt) ·
#//           decline=N/A (not a "you may"; the prevention is automatic) · control=N/A (the prevention
#//           keys off the HOST's title, not off who controls the upgrade, so a control change cannot
#//           move it) · boundary=BobaFettsArmor_Prevents2 (host IS Boba Fett → 2 prevented) vs
#//           BobaFettsArmor_OnlyBobaFett (host is not Boba Fett → nothing prevented); second pair
#//           BobaFettsArmor_PreventionRunsBeforeShield (damage ≤ 2 → Shield untouched) vs
#//           BobaFettsArmor_LethalAfterPrevention_LeaderUndeploys (prevention applies and the residue
#//           is still lethal) · reqboundary=N/A (no player decision inside the prevention)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP1Hand: SHD_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# BobaFettsArmor_Prevents2
#// SHD_224 Boba Fett's Armor — "If attached unit is Boba Fett and damage would be dealt to him, prevent
#// 2 of that damage." Boba Fett (SOR_179) wearing the armor is dealt 3 by SHD_180 → 2 prevented → 1
#// damage sticks.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP1Hand: SHD_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# BobaFettsArmor_PreventionRunsBeforeShield
#// SHD_224 Boba Fett's Armor is a PREVENTION, so it settles before a Shield token gets a chance to fire.
#// Boba Fett (SOR_179, 3/5) wears the armor AND a Shield; he attacks a 2-power defender (SHD_209) and
#// takes 2 back → the armor prevents all 2 → no damage "would be dealt" → the Shield is NOT spent.
#// Boba's own On Attack does not fire (the defender is ready), so there is no decision.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SHD_209:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# BobaFettsArmor_LethalAfterPrevention_LeaderUndeploys
#// SHD_224 Boba Fett's Armor — prevention reduces but does not cancel a lethal hit. Deployed Boba Fett
#// (SHD_008, 4/7, +2/+2 from the armor = 6/9) already at 8 damage is dealt 3 by SHD_180 → 2 prevented →
#// the remaining 1 is still lethal → the leader unit leaves play and the leader returns to its
#// undeployed side.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3; myLeader:SHD_008:1:1:1:8}
P1OnlyActions: true
WithP1GroundArenaUpgrade: 0:SHD_224
WithP1Hand: SHD_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
#// CR 9.3: the leader unit left play, so its attached upgrade is DEFEATED and goes to its owner's
#// discard — alongside the event that killed it. It used to CEASE TO EXIST (in no zone at all).
P1DISCARDCOUNT:2
