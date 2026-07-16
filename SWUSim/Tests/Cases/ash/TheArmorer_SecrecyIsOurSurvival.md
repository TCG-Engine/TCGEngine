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
