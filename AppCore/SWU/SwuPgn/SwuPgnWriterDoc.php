<?php
// SWU-PGN document writer — doc → `.swupgn` text, the inverse of SwuPgnParse (SWU-PGN/1.0 spec §4). The doc has the
// parser's shape: ['headers' => [tag => value], 'story' => ?list<string>, 'decks'|'cards'|'setup'|'events'|
// 'annotations' => list of records]. Sections are written in the canonical order; STORY is verbatim text, the rest
// one JSON object per line.

const SWUPGN_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

// §4: inside a header value a backslash takes the next character literally, so `\` and `"` are escaped.
function _SwuPgnEscapeHeader(string $v): string {
    return str_replace(['\\', '"'], ['\\\\', '\\"'], $v);
}

function SwuPgnWriteText(array $doc): string {
    $out = '';
    foreach ($doc['headers'] ?? [] as $tag => $value) {
        if (!preg_match('/^[A-Za-z0-9]+$/', (string)$tag)) continue;   // §4: tag names are letters and digits
        $out .= '[' . $tag . ' "' . _SwuPgnEscapeHeader((string)$value) . "\"]\n";
    }
    // STORY lines are kept verbatim by the parser, blank ones included, so no separator follows them.
    $afterStory = false;
    if (isset($doc['story']) && is_array($doc['story'])) {
        $out .= "\n%%% STORY\n";
        foreach ($doc['story'] as $line) $out .= str_replace(["\r", "\n"], ' ', (string)$line) . "\n";
        $afterStory = true;
    }
    foreach (['decks' => 'DECKS', 'cards' => 'CARDS', 'setup' => 'SETUP', 'events' => 'EVENTS', 'annotations' => 'ANNOTATIONS'] as $key => $banner) {
        if ($key === 'annotations' && empty($doc[$key])) continue;
        $out .= ($afterStory ? '' : "\n") . "%%% $banner\n";
        $afterStory = false;
        foreach ($doc[$key] ?? [] as $record) $out .= json_encode($record, SWUPGN_JSON_FLAGS) . "\n";
    }
    return $out;
}
