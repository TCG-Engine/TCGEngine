# BobaFettsArmor_OnlyBobaFett
#// SHD_224 Boba Fett's Armor — the prevention is gated on the host being Boba Fett. On a non-Boba host
#// (SOR_046), SHD_180's 3 damage is NOT reduced → full 3 sticks.

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
