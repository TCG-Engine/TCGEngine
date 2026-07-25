# ShieldEachShielded
#// ASH_064 The Armorer (Ground, 5/5, Shielded) — When Played: give a Shield token to each friendly unit
#// with Shielded (including this one). With another Shielded unit (SOR_207) in play, The Armorer enters:
#// her own Shielded keyword gives her 1 Shield AND her When Played gives a Shield to each Shielded unit, so
#// she ends with 2 Shields and SOR_207 with 1. (Resolve the entry-trigger order via EffectStack-0.)
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_064}
WithP1GroundArena: SOR_207:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_207
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:ASH_064
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2

---

# NonShieldedUnit_NoShield
#// ASH_064 The Armorer — the When Played shields only friendly units WITH Shielded. The plain SOR_095 (no
#// Shielded) gets no Shield token.
## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:ASH_064}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# GainedShielded_GetsShield
#// ASH_064 The Armorer — the When Played gives a Shield to each friendly unit WITH Shielded, and a Shielded
#// keyword GAINED from another source counts. Admiral Yularen (JTL_047) grants Shielded to friendly
#// Vehicles, so SOR_237 (Alliance X-Wing) gains Shielded; The Armorer then gives it 1 Shield token. The
#// Armorer herself ends with 2 (her own Shielded keyword on entry plus her When Played). Yularen (not a
#// Vehicle) gains nothing. (Resolve The Armorer's simultaneous entry triggers via EffectStack-0.)
## GIVEN
CommonSetup: bbw/bbw
WithP1Resources: 12
WithP1Hand: [JTL_047 ASH_064]
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shielded
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:ASH_064
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2
