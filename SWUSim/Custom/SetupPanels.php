<?php
// Real data for the main menu's four setup modals (saved decks, Twin Suns pre-cons, Arenabot
// bot pre-cons). Before this, all three were mockup fixtures hard-coded into MainMenu.php:
// three invented deck names that belonged to nobody, and one Twin Suns pre-con out of the four
// that were already sitting in TwinSunsPreCons.json.
//
// EVERY row carries an `input` string that SWUResolveDeckInput() can read, because that is the
// same function the queue uses. A row whose input does not resolve renders perfectly and cannot
// be played, which is the failure mode DevTools/tdd-regression/test_swusim_setup_panels.php
// exists to catch — it resolves all 27 of them on every run.
//
// Pure data + markup. No session, no DB connection of its own: the caller passes the user id.

require_once __DIR__ . '/../../Database/functions.inc.php';   // LoadSavedDecks()

// "TS26_02" -> "Anakin Skywalker, Protect Her At All Costs". The comma form is what the mockup
// used and what the pre-con rows read as. An id we cannot name is returned as-is rather than
// blanked, so a missing card shows up in the UI instead of vanishing.
function SWUSetupCardLabel(string $id): string {
    $id = trim($id);
    if ($id === '') return '';
    $title = $GLOBALS['titleData'][$id] ?? '';
    if ($title === '') return $id;
    $sub = $GLOBALS['subtitleData'][$id] ?? '';
    return $sub !== '' ? "$title, $sub" : $title;
}

// The five archetypes, in the wording the Arenabot modal's own select uses. SYNC_ACTIVE_SETUP()
// slugs the label back ("Soft Control" -> softcontrol), so these two lists must not drift.
function SWUSetupStyleLabel(string $style): string {
    return [
        'hyperaggro'  => 'Hyper Aggro',
        'softaggro'   => 'Soft Aggro',
        'midrange'    => 'Midrange',
        'softcontrol' => 'Soft Control',
        'hardcontrol' => 'Hard Control',
    ][$style] ?? ucfirst($style);
}

// Build the standardized SWU JSON that SWUNormalizeStandardJSON() reads, from ids + counts.
function _SWUSetupDeckJSON(array $leaders, string $base, array $counts): string {
    $deck = [];
    foreach ($counts as $id => $n) $deck[] = ['id' => (string)$id, 'count' => (int)$n];
    $out = ['leader' => ['id' => $leaders[0] ?? '', 'count' => 1]];
    if (isset($leaders[1]) && $leaders[1] !== '') $out['secondleader'] = ['id' => $leaders[1], 'count' => 1];
    $out['base'] = ['id' => $base, 'count' => 1];
    $out['deck'] = $deck;
    $out['sideboard'] = [];
    return json_encode($out);
}

// ─── Twin Suns pre-cons ──────────────────────────────────────────────────────
// SWUSim/Custom/TwinSunsPreCons.json, authored by hand. Its entries are ALREADY in the
// standardized shape SWUResolveDeckInput() accepts, so the file's own text is the deck input —
// no conversion, nothing to drift.
function SWUSetupTwinSunsPreCons(): array {
    $path = __DIR__ . '/TwinSunsPreCons.json';
    if (!is_readable($path)) return [];
    $raw = json_decode((string)file_get_contents($path), true);
    if (!is_array($raw)) return [];

    $out = [];
    foreach ($raw as $i => $d) {
        if (!is_array($d)) continue;
        $leaders = [];
        foreach (['leader', 'secondleader'] as $k) {
            $id = trim((string)($d[$k]['id'] ?? ''));
            if ($id !== '') $leaders[] = $id;
        }
        $count = 0;
        foreach (($d['deck'] ?? []) as $e) $count += max(0, (int)($e['count'] ?? 0));
        $out[] = [
            'key'     => 'ts' . ($i + 1),
            'name'    => trim((string)($d['metadata']['name'] ?? ('Pre-Con ' . ($i + 1)))),
            'author'  => trim((string)($d['metadata']['author'] ?? '')),
            'leaders' => $leaders,
            'base'    => trim((string)($d['base']['id'] ?? '')),
            'count'   => $count,
            'input'   => json_encode($d),
        ];
    }
    return $out;
}

// ─── Arenabot bot pre-cons ───────────────────────────────────────────────────
// SWUSim/Custom/BotDeckLabels.json is regenerated from the meta fixtures by
// SWUSim/DevTools/regen-deck-labels.php and already carries leader, base, style and the full
// card counts — so the deck input is built from the SAME rows the classifier scores, rather
// than re-reading Tests/BotFixtures/*.txt and risking the two disagreeing.
function SWUSetupBotPreCons(): array {
    $path = __DIR__ . '/BotDeckLabels.json';
    if (!is_readable($path)) return [];
    $raw = json_decode((string)file_get_contents($path), true);
    if (!is_array($raw) || !isset($raw['decks'])) return [];

    $out = [];
    foreach ($raw['decks'] as $d) {
        $counts = (array)($d['cards'] ?? []);
        $leader = trim((string)($d['leader'] ?? ''));
        $base   = trim((string)($d['base'] ?? ''));
        if ($leader === '' || $base === '' || !$counts) continue;
        $style = trim((string)($d['style'] ?? ''));
        $out[] = [
            'key'        => trim((string)($d['file'] ?? '')),
            // "aurra_datavault" -> "Aurra Datavault". Derived, so a deck added to the fixture
            // set shows up named instead of needing a second hand-written list here.
            'name'       => ucwords(str_replace('_', ' ', (string)($d['file'] ?? ''))),
            'leader'     => $leader,
            'base'       => $base,
            'style'      => $style,
            'styleLabel' => SWUSetupStyleLabel($style),
            'count'      => array_sum(array_map('intval', $counts)),
            'input'      => _SWUSetupDeckJSON([$leader], $base, $counts),
        ];
    }
    return $out;
}

// ─── Saved decks ─────────────────────────────────────────────────────────────
// SWUSim stores these on the ACCOUNT (SiteDef deckLibrary.storage = 'account'), so a guest
// legitimately has none and the modal must say so rather than invent three.
function SWUSetupSavedDecks($userId): array {
    $userId = (int)$userId;
    if ($userId <= 0) return [];

    $out = [];
    foreach (LoadSavedDecks($userId) as $row) {
        $link = (string)($row['decklink'] ?? '');
        if ($link === '') continue;
        // Raw-JSON decks keep the list itself in deckContent; their 'decklink' is only the
        // 'raw:'+sha1 identity and would resolve to nothing.
        $input = $link;
        if (strpos($link, 'raw:') === 0) {
            $decoded = base64_decode((string)($row['deckContent'] ?? ''), true);
            if ($decoded === false || trim($decoded) === '') continue;   // unplayable; do not offer it
            $input = $decoded;
        }
        // A Twin Suns deck stores both leaders in `hero`; the separator has varied, so split on
        // anything that is not part of a card id.
        $leaders = array_values(array_filter(preg_split('/[^A-Za-z0-9_]+/', (string)($row['hero'] ?? ''))));
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') $name = SWUSetupCardLabel($leaders[0] ?? '') ?: 'Untitled deck';
        $out[] = [
            'key'     => substr(sha1($link), 0, 12),
            'name'    => $name,
            'leaders' => $leaders,
            'base'    => (string)($row['baseId'] ?? ''),
            // The row carries no card count, and resolving every deck on page load would mean
            // one network fetch per saved deck. 0 renders without the count chip; the client
            // fills it in from the detection response once a deck is actually chosen.
            'count'   => 0,
            'input'   => $input,
        ];
    }
    return $out;
}

// ─── markup ──────────────────────────────────────────────────────────────────
function _SWUSetupCards(array $leaders, string $base): string {
    $out = '<span class="cards" aria-hidden="true">';
    foreach ($leaders as $id) {
        $out .= '<span class="tc tc--leader"><img src="/TCGEngine/AppCore/SWU/Images/WebpImages/'
              . rawurlencode($id) . '.webp" alt="" loading="lazy" decoding="async" width="628" height="450"></span>';
    }
    if ($base !== '') {
        $out .= '<span class="tc tc--base"><img src="/TCGEngine/AppCore/SWU/Images/WebpImages/'
              . rawurlencode($base) . '.webp" alt="" loading="lazy" decoding="async" width="628" height="450"></span>';
    }
    return $out . '</span>';
}

// The whole .deckpick block: the <select> plus one .deckprev row per deck. `data-deck-input` is
// what the submission actually sends — SYNC_ACTIVE_SETUP() copies it into #deck-link, so the
// deck a player PICKS is the deck they play.
// $noneLabel, when given, adds a leading "no deck chosen" row — the Arenabot bot picker needs
// one, because leaving the bot's deck empty is a real choice (it then plays the pre-con below).
function SWUSetupDeckPicker(string $selectId, array $decks, string $emptyLabel = 'No saved decks yet',
                            string $noneLabel = '', string $slot = ''): string {
    $e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $sid = $e($selectId);
    if ($slot === '') $slot = _SWUSetupSlot($selectId);

    // ⚠ $emptyLabel and $noneLabel are NOT interchangeable, and this used to substitute one for
    // the other when there were no decks. That was harmless while the none-option read "Use a
    // pre-con below", and became a lie the moment it read "Use one of your saved decks or use a
    // pre-con below" — told to someone who has none. The empty state gets its own honest copy,
    // which still points at the pre-con well the way that substitution was meant to.
    if (!$decks) {
        // No <select> at all. An empty (even disabled) dropdown still reads as a control that
        // does something, and the listbox enhancement would dress it up into a blank bar — which
        // is exactly what a guest saw. [data-empty] tells that enhancement to leave this alone.
        // A GUEST's decks live in this browser, so the server cannot know about them: the client
        // replaces this block from localStorage on load (GUEST_DECKS in MainMenu.php).
        return '<div class="deckpick deckpick--empty" data-empty data-slot="' . $e($slot) . '"'
             . ' data-select-id="' . $sid . '" data-none-label="' . $e($noneLabel) . '">'
             . '<p class="deckpick__empty">' . $e($emptyLabel) . '</p></div>'
             . SWUSetupPickMsg($slot);
    }

    $opts = '';
    $rows = '';
    if ($noneLabel !== '') {
        // data-deck-input="" is deliberate: picking this CLEARS the field rather than leaving
        // whatever the previous choice wrote there.
        $opts .= '<option value="none" data-deck-input="" selected>' . $e($noneLabel) . '</option>';
        $rows .= '<span class="deckprev deckprev--none ch"><span>' . $e($noneLabel) . '</span></span>';
    }
    foreach ($decks as $i => $d) {
        $key  = (string)$d['key'];
        $subs = [];
        foreach ((array)$d['leaders'] as $id) $subs[] = SWUSetupCardLabel($id);
        if (!empty($d['base'])) $subs[] = SWUSetupCardLabel($d['base']);
        $sub   = implode(' &middot; ', array_map($e, $subs));
        $plain = implode('. ', $subs);
        $count = (int)($d['count'] ?? 0);

        $opts .= '<option value="' . $e($key) . '" data-deck-input="' . $e($d['input']) . '"'
               . (($i === 0 && $noneLabel === '') ? ' selected' : '') . '>' . $e($d['name']) . '</option>';

        $rows .= '<span class="deckprev deckprev--' . $e($key) . ' ch">'
               . _SWUSetupCards((array)$d['leaders'], (string)$d['base'])
               . '<span class="deck__text"><span class="deck__name">' . $e($d['name']) . '</span>'
               . '<span class="deck__sub">' . $sub . '</span></span>'
               . '<span class="deck__end">'
               . ($count > 0 ? '<span class="deck__n">' . $count . '&nbsp;cards</span>' : '')
               . '</span>'
               . '<span class="u-vh">' . $e($plain) . ($count > 0 ? '. ' . $count . ' cards.' : '') . '</span>'
               . '</span>';
    }
    return '<div class="deckpick ch" data-slot="' . $e($slot) . '"><span class="selwrap ch">'
         . '<select class="select" id="' . $sid . '" name="' . $sid . '"'
         . ' data-slot="' . $e($slot) . '">' . $opts . '</select>'
         . '</span>' . $rows . '</div>'
         . SWUSetupPickMsg($slot);
}

// Where "you are now playing X" is announced after a pick. role="status" so it reaches assistive
// tech: the dialog is already open by then, so unlike the .whyline (which is the dialog's
// accessible DESCRIPTION, read at open time) a live region is the right channel here and cannot
// race the dialog's own announcement.
function SWUSetupPickMsg(string $slot): string {
    $s = htmlspecialchars($slot, ENT_QUOTES, 'UTF-8');
    return '<p class="pickmsg ch" data-pickmsg="' . $s . '" role="status" hidden>'
         . '<span class="pickmsg__t"></span></p>';
}

// Which deck a control feeds. Arenabot is the only mode with two: the player's and the bot's,
// and every control for the bot's deck carries "bot" in its id ("ab-bot-saved", "ab-precon"
// sitting under the "Bot Pre-Cons" legend). Stated in the markup so the client never has to
// re-derive it from a naming rule that a later id could quietly break.
function _SWUSetupSlot(string $id): string {
    return stripos($id, 'bot') !== false || stripos($id, 'ab-precon') !== false ? 'bot' : 'own';
}

// One pre-con row (Twin Suns and Arenabot share the shape; Twin Suns adds card art and an
// author, Arenabot adds a style tag). `data-deck-input` carries the playable deck.
function SWUSetupPreConRow(string $name, string $inputName, array $p, bool $rich, bool $checked = false): string {
    $e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $id = $inputName . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$p['key']);

    $leaders = isset($p['leaders']) ? (array)$p['leaders'] : [(string)($p['leader'] ?? '')];
    $bits = [];
    foreach ($leaders as $l) if ($l !== '') $bits[] = SWUSetupCardLabel($l);
    $meta = implode(' &middot; ', array_map($e, $bits));
    if (!empty($p['base'])) $meta .= ' / ' . $e(SWUSetupCardLabel($p['base']));
    $meta .= ' &middot; <span class="pc__n">' . (int)$p['count'] . ' cards</span>';
    if ($rich) $meta .= ' &middot; singleton';

    $mark = '<span class="mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"'
          . ' stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 6.5"/></svg></span>';

    // data-style is the archetype BotDeckLabels.json already assigns this fixture. The Bot Style
    // auto-picker reads it instead of shipping the list off to the classifier: the label IS the
    // classifier's own answer, so asking would cost a round trip to be told what we just rendered.
    $style = trim((string)($p['style'] ?? ''));

    $out = '<li class="pc">'
         . '<input class="pc__in u-vh" type="radio" name="' . $e($name) . '" id="' . $e($id) . '"'
         . ' data-slot="' . _SWUSetupSlot($name) . '"'
         . ($style !== '' ? ' data-style="' . $e($style) . '"' : '')
         . ' data-deck-input="' . $e($p['input']) . '"' . ($checked ? ' checked' : '') . '>'
         . '<label class="pc__row' . ($rich ? ' pc__row--rich' : '') . ' ch" for="' . $e($id) . '">';
    // The two row shapes are NOT the same markup with a flag. .pc__row is a grid whose tracks
    // are its DIRECT children: the rich row nests name+meta inside .pc__text so the card art can
    // sit beside them as one block, while the plain row lays name, meta and .pc__end out as three
    // tracks of its own. Wrapping the plain row too collapsed its name and meta onto one line
    // with no gap ("Ahsoka BlueAhsoka Tano, Trust in the Force").
    $name = '<span class="pc__name">' . $e($p['name'])
          . (!empty($p['author']) ? ' <span class="pc__by">by ' . $e($p['author']) . '</span>' : '');
    if ($rich) {
        $out .= _SWUSetupCards($leaders, (string)($p['base'] ?? ''));
        $out .= '<span class="pc__text">' . $name . $mark . '</span>'
              . '<span class="pc__meta">' . $meta . '</span></span>';
    } else {
        $out .= $name . '</span><span class="pc__meta">' . $meta . '</span>'
              . '<span class="pc__end"><span class="tag">' . $e($p['styleLabel'] ?? '') . '</span>' . $mark . '</span>';
    }
    return $out . '</label></li>';
}

// The no-script fallback reveals a preview with `.deckpick:has(option[value=K]:checked)`, one
// rule per key. The mockup could hard-code its ten fixture keys; real keys are per-account, so
// the rules are emitted with the page. (With script, the listbox takes over and this is unused.)
function SWUSetupPreviewStyles(array $keySets): string {
    $keys = [];
    foreach ($keySets as $set) foreach ($set as $k) $keys[$k] = true;
    if (!$keys) return '';
    $rules = '';
    foreach (array_keys($keys) as $k) {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', (string)$k)) continue;   // never interpolate a raw key
        $rules .= ".deckpick:has(option[value=\"$k\"]:checked) .deckprev--$k{display:grid}";
    }
    return $rules === '' ? '' : "<style>$rules</style>\n";
}
