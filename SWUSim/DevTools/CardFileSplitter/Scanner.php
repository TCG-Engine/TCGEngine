<?php
// Dev-time statement scanner for the card-file splitter.
//
// Splits a monolith PHP source into top-level statements using the tokenizer,
// tagging each with the CardIDs it references, its assignment target, any
// top-level-closure use() captures, and an exact byte span (leading contiguous
// comment lines included, so a card's banner moves with its code).

function splitter_scan(string $phpSource): array {
    $tokens = token_get_all($phpSource);

    // Precompute the byte offset of each token.
    $offsets = [];
    $pos = 0;
    foreach ($tokens as $i => $tok) {
        $offsets[$i] = $pos;
        $pos += strlen(is_array($tok) ? $tok[1] : $tok);
    }
    $total = $pos;

    $stmts = [];
    $depth = 0;                 // combined () [] {} nesting depth
    $stmtStartTok = null;       // token index of first meaningful token of current statement
    $isFunctionDecl = false;    // top-level `function name(){}` declaration
    $funcBraceDepth = null;     // brace depth captured at `{` of a function decl

    $n = count($tokens);
    $i = 0;
    while ($i < $n) {
        $tok = $tokens[$i];
        $id  = is_array($tok) ? $tok[0] : null;
        $txt = is_array($tok) ? $tok[1] : $tok;

        $isTrivia = in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_INLINE_HTML], true);

        // Begin a statement at the first non-trivia token after a boundary.
        if ($stmtStartTok === null && !$isTrivia) {
            $stmtStartTok = $i;
            $isFunctionDecl = ($id === T_FUNCTION);
        }

        // Bracket depth: count ONLY real punctuation. A single-char string token
        // ('{','}','(',')','[',']') is real. String INTERPOLATION braces open with
        // T_CURLY_OPEN / T_DOLLAR_OPEN_CURLY_BRACES (array tokens) and close with a
        // plain-char '}' — so count those opens too. Everything else (notably
        // T_ENCAPSED_AND_WHITESPACE, whose text can literally be ')' or '{') is
        // string content and must NOT affect depth.
        $isChar = !is_array($tok);
        $isOpen  = ($isChar && ($txt === '(' || $txt === '[' || $txt === '{'))
                 || ($id === T_CURLY_OPEN) || ($id === T_DOLLAR_OPEN_CURLY_BRACES);
        $isClose = $isChar && ($txt === ')' || $txt === ']' || $txt === '}');

        if ($isOpen) {
            $depth++;
            if ($isFunctionDecl && $isChar && $txt === '{' && $funcBraceDepth === null) {
                $funcBraceDepth = $depth; // remember the function body's opening brace depth
            }
        } elseif ($isClose) {
            $depth--;
            // End of a top-level function declaration body.
            if ($isFunctionDecl && $funcBraceDepth !== null && $depth === $funcBraceDepth - 1 && $txt === '}') {
                $endOffset = $offsets[$i] + strlen($txt);
                $stmts[] = splitter_make_stmt($phpSource, $tokens, $offsets, $stmtStartTok, $i, $endOffset);
                $stmtStartTok = null; $isFunctionDecl = false; $funcBraceDepth = null;
                $i++; continue;
            }
        }

        // End of an ordinary statement at a top-level `;`.
        if ($isChar && $txt === ';' && $depth === 0 && $stmtStartTok !== null && !$isFunctionDecl) {
            $endOffset = $offsets[$i] + strlen($txt);
            $stmts[] = splitter_make_stmt($phpSource, $tokens, $offsets, $stmtStartTok, $i, $endOffset);
            $stmtStartTok = null;
        }

        $i++;
    }

    return $stmts;
}

// Build a statement record from the token index range, extending the start
// backward over contiguous leading comment lines.
function splitter_make_stmt(string $src, array $tokens, array $offsets, int $startTok, int $endTok, int $endOffset): array {
    $startOffset = $offsets[$startTok];

    // Walk backward over immediately-preceding comment lines (allow single
    // newlines between them, but stop at a blank line or non-comment code).
    $j = $startTok - 1;
    $candidateStart = $startOffset;
    while ($j >= 0) {
        $t = $tokens[$j];
        $tid = is_array($t) ? $t[0] : null;
        $ttxt = is_array($t) ? $t[1] : $t;
        if ($tid === T_WHITESPACE) {
            // Stop if this whitespace contains a blank line (two+ newlines).
            if (substr_count($ttxt, "\n") >= 2) break;
            $j--; continue;
        }
        if ($tid === T_COMMENT || $tid === T_DOC_COMMENT) {
            $candidateStart = $offsets[$j];
            $j--; continue;
        }
        break; // any real code token → stop
    }
    $startOffset = $candidateStart;

    $text = substr($src, $startOffset, $endOffset - $startOffset);

    // CardIDs: distinct SET_NNN tokens in source order.
    $cardIDs = [];
    if (preg_match_all('/\b[A-Z0-9]{2,4}_\d+\b/', $text, $m)) {
        foreach ($m[0] as $cid) if (!in_array($cid, $cardIDs, true)) $cardIDs[] = $cid;
    }

    // Kind + LHS.
    $codeText = ltrim(preg_replace('#^(\s*//[^\n]*\n|\s*/\*.*?\*/\s*)*#s', '', $text));
    $kind = 'other';
    $lhs = '';
    if (preg_match('/^function\b/', $codeText)) {
        $kind = 'function';
    } elseif (preg_match('/^(\$[A-Za-z_]\w*(?:\[[^\]]*\])?)\s*=/', $codeText, $lm)) {
        $kind = 'assign';
        $lhs = $lm[1];
    }

    // Top-level closure use() captures: the closure directly on the RHS of the
    // first `=`. Match `= [static] fn|function (...) use (...)`.
    $topLevelUses = [];
    if ($kind === 'assign' &&
        preg_match('/=\s*(?:static\s+)?(?:fn|function)\s*\([^)]*\)\s*use\s*\(([^)]*)\)/s', $codeText, $um)) {
        foreach (explode(',', $um[1]) as $u) {
            $u = trim($u);
            if ($u === '') continue;
            $topLevelUses[] = ltrim($u, '&$');
        }
    }

    return [
        'text' => $text,
        'cardIDs' => $cardIDs,
        'kind' => $kind,
        'lhs' => $lhs,
        'topLevelUses' => $topLevelUses,
        'span' => [$startOffset, $endOffset],
    ];
}
