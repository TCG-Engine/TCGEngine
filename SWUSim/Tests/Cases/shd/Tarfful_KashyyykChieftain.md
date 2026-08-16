# Tarfful_WookieeCombatDamage_DealsBack
#// SHD_250 Tarfful — Unit, cost 7, 3/9, unique, Ground, [Heroism], trait Wookiee. "Restore 2" +
#// "When a friendly Wookiee unit is dealt combat damage and isn't defeated: That unit deals that much
#// damage to an enemy ground unit."
#// COVERAGE: offer=Tarfful_WookieeCombatDamage_DealsBack — the pick is a real choice there (two enemy
#//           ground units are on the board when the observer fires) ·
#//           request boundary=Tarfful_WookieeCombatDamage_DealsBack — the retaliation target is chosen
#//           after the combat-damage step has already settled, and the amount is read from the damage
#//           actually dealt ·
#//           control=N/A ("friendly Wookiee" and "enemy ground unit" are both read from Tarfful's
#//           controller and neither side changes hands in this card's text) ·
#//           boundary pair=Tarfful_WookieeCombatDamage_DealsBack (damage IS dealt → retaliate for that
#//           much) + Tarfful_ShieldAbsorbsCombatDamage_NoTriggerBack (a Shield absorbs the hit so ZERO
#//           combat damage is dealt → the observer never fires at all) ·
#//           decline=N/A (mandatory, no "you may").
#// SHD_249 (Wookiee, 2/5) attacks SOR_046 (3/7): it deals 2 (SOR_046 survives) and takes 3
#// counter-damage, surviving. Tarfful's observer then has SHD_249 deal 3 to the enemy SHD_095 (2/3),
#// defeating it.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_250:1:0
WithP1GroundArena: SHD_249:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1

---

# Tarfful_ShieldAbsorbsCombatDamage_NoTriggerBack
#// SHD_250 Tarfful — the observer needs combat damage to actually be DEALT, not merely aimed. A Shield
#// token on the friendly Wookiee absorbs the whole hit, so ZERO damage lands on it and Tarfful's ability
#// never fires: no retaliation target is offered even though two enemy ground units are available.
#// P2's SOR_046 (3/7) attacks P1's shielded SHD_249 (Wookiee Warrior, 2/5). The Shield is spent, SHD_249
#// is left at 0 damage, and the only damage on the board is SHD_249's own 2 counter-damage on SOR_046.

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 2
WithP1GroundArena: SHD_250:1:0
WithP1GroundArena: SHD_249:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SHD_249
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:SHD_095
P2GROUNDARENAUNIT:1:DAMAGE:0
