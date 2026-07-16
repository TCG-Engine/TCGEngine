# FriendlyDefeated_Sentinel
#// TWI_033 Calculating MagnaGuard — "When a friendly unit is defeated: this unit gains Sentinel for this
#// phase." SOR_095 attacks SOR_046 and dies to the counter; the MagnaGuard then has Sentinel.
## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_033:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_033
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_Sentinel
#// TWI_033 Calculating MagnaGuard (Unit, Ground, Vigilance/Villainy) — "When Played: This unit gains
#// Sentinel for this phase."
## GIVEN
CommonSetup: bbk/rrk/{myResources:3;handCardIds:TWI_033}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_033
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
