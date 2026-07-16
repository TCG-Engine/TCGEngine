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
