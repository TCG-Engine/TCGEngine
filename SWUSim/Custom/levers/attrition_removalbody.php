<?php
// LEVER 'removalbody' (theme 'attrition'). Ninin vs Axium R7, replayed: Ninin cast Devastator ("Deal 4 indirect damage to each opponent", assigned
// by me) and put 2 into the deployed enemy Hemlock and 2 into a Mandalorian token — a leader gone for good plus a token, and a 9/6 left on the
// board. The replay bot's fallback agreed (Devastator 6.87 vs Lost and Forgotten 3.40), but rule shrink-first ('removalfirst', p53) only
// weighs plays SWUBotHandCardKills can see (indirect damage it cannot), so it cast Lost and Forgotten on Koska Reeves. "Removal before the body"
// is the rule's point; a body whose own When Played is removal is not the body it meant. shrinkveto — the rule's pick is handed back to the
// fallback when an INDIRECT-damage play (the removal the rule cannot see) scores at least SWU_BOT_LV_REMOVALBODY_RATIO x the pick there
// and is the fallback's best play.
// HMW run 2026-10-27: vetoing for ANY removal-tagged play handed the turn to an over-valued Chimaera, which then paired ITSELF ("Chimaera
// defeated itself", 2 of soft control's 5 losses) — so only the indirect-damage blind spot is handed back.
const SWU_BOT_LV_REMOVALBODY_RATIO = 1.5;

SWUBotLeverRegister('attrition', 'removalbody', [
    'shrinkveto' => function (array $ctx, array $pick): ?bool {
        $seat = intval($ctx['seat'] ?? 0);
        // No indirect-damage play in hand → nothing is scored at all. Scoring inside the rule is NOT side-effect free (the per-decision memo
        // carries values into the fallback): an earlier version changed 3 HMW games without ever firing (Hyperspace Disaster ↔ Chimaera).
        $isIndirect = function (string $cid): bool { return in_array('indirect-damage', SWUBotCardTags($cid), true) || (bool)preg_match('/indirect damage/i', strval(CardText($cid))); };
        $any = false;
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) !== 'play' || strval($a['cardID'] ?? '') === strval($pick['cardID'] ?? '')) continue;
            $o = _SWUBotHandObject($seat, $a);
            if ($o !== null && $isIndirect(strval($o->CardID ?? ''))) { $any = true; break; }
        }
        if (!$any) return null;
        $c = $ctx; $c['_guides'] = $c['_guides'] ?? _SWUBotGuides($c);
        $pickScore = null; $best = 0.0; $other = 0.0;
        foreach ($c['actions'] as $k => $a) {
            if (SWUBotActionKind($a) !== 'play') continue;
            $s = SWUBotScoreAction($c, $a, intval($k));
            if (strval($a['cardID'] ?? '') === strval($pick['cardID'] ?? '')) { $pickScore = $s; continue; }
            $o = _SWUBotHandObject($seat, $a);
            if ($o === null) continue;
            $cid = strval($o->CardID ?? '');
            if (!$isIndirect($cid)) { $other = max($other, $s); continue; }
            $best = max($best, $s);
        }
        if ($pickScore === null || $best <= 0.0 || $best < $other) return null;   // only when the fallback will take THAT play
        return $best >= SWU_BOT_LV_REMOVALBODY_RATIO * max(0.01, $pickScore) ? true : null;
    },
]);
