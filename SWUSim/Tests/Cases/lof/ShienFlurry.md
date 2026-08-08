# PlayAmbushPrevent2
#// LOF_220 Shien Flurry — Play a Force unit from hand; it gains Ambush this phase and the next time it would
#// be dealt damage, prevent 2. Plo Koon enters, Ambush-attacks SOR_046 (3/7) for 6; the 3 counter damage is
#// reduced to 1 by the prevention. (Shien Flurry auto-plays the lone Force unit; Plo Koon has no When Played,
#// so his single Ambush entry trigger auto-dispatches straight to the "Ambush attack?" prompt.)

## GIVEN
CommonSetup: yyw/ggk/{myResources:12;handCardIds:LOF_220,LOF_050}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# Vader_Prevent2RetainsShield
#// LOF_220 Shien Flurry + LOF_037 Darth Vader combo — played live, and the prevent-2 marker is spent BEFORE
#// the Shield. P1 plays Shien Flurry from hand (Cunning, cost 1) and it plays Vader from hand (cost 6) — 7
#// resources on a yellow (Cunning) base + Vigilance/Villainy leader keeps everything on-aspect. Vader enters
#// with Ambush + LOF_220's "prevent 2 of the next damage". His When Played shields himself (friendly) and the
#// enemy Leia leader unit (SOR_009, deployed at ground idx 1). His Ambush attack targets Gungi (LOF_093, 2/5,
#// idx 0) — NOT the shielded leader — so real combat happens: Vader deals 5 and defeats Gungi (5 HP), and
#// Gungi deals 2 counter. Because 2 is FULLY covered by prevent-2, that reduction is used and Vader KEEPS his
#// Shield. His On Attack separately defeats the shielded Leia leader unit (idx 1, so its defeat doesn't
#// reindex the Gungi attack target). End: Vader unharmed with his Shield; both enemies gone.
## GIVEN
CommonSetup: ybk/ggw/{
  myResources:7;
  handCardIds:LOF_220,LOF_037;
  theirLeaderDeployed:true;
}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_037
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P2GROUNDARENACOUNT:0

---

# NoForceUnitInHand_JustExhausts
#// LOF_220 Shien Flurry — "Play a Force unit from your hand." With NO Force unit in hand (only an AT-ST,
#// SOR_232, which is not a Force unit), the event resolves with nothing to play: no unit enters, the AT-ST
#// stays in hand. Intended: "should allow playing a Force unit from hand ... (move to next phase)" async setup
#// where only a non-Force unit is available.

## GIVEN
CommonSetup: yyw/ggk/{myResources:5;handCardIds:LOF_220,SOR_232}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# PreventPersistsToLaterDamage
#// LOF_220 Shien Flurry — the "prevent 2 the next time it would be dealt damage this phase" persists until a
#// real damage instance occurs. P1 plays Shien Flurry (auto-plays lone Force unit Plo Koon, LOF_050 6/8) and
#// Ambush-attacks a 0-power Moisture Farmer (SHD_055) — Plo takes 0 counter, so the prevention is NOT spent.
#// Later, P2's Wampa (SOR_164, 4 power) attacks Plo Koon: 4 damage minus the prevented 2 = 2 damage. Intended: #// "prevent 2 damage the next time it would be dealt damage (no damage from ambush)". (The P1>Pass reconciles
#// the harness's turn accounting after the nested Ambush attack so P2 can take the follow-up attack.)

## GIVEN
CommonSetup: yyw/ggk/{myResources:12;handCardIds:LOF_220,LOF_050}
WithP2GroundArena: SHD_055:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:DAMAGE:2
