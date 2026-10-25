<?php
// Component utility: extract/evaluate named functions, never include game/bootstrap files.
function ComponentExtractFunction(string $path, string $name): string {
    $source = file_get_contents($path);
    if ($source === false) throw new RuntimeException('Could not read component source.');
    $tokens = token_get_all($source); $offsets = []; $offset = 0;
    foreach ($tokens as $i => $token) { $offsets[$i] = $offset; $offset += strlen(is_array($token) ? $token[1] : $token); }
    $matches = [];
    foreach ($tokens as $i => $token) {
        if (!is_array($token) || $token[0] !== T_FUNCTION) continue;
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true)) ++$j;
        if (!isset($tokens[$j]) || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $depth = 0; $opened = false;
        for ($k = $j + 1; $k < count($tokens); ++$k) {
            $part = $tokens[$k];
            if (is_array($part) && in_array($part[0], [T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES], true)) { ++$depth; continue; }
            if (!is_array($part) && $part === '{') { ++$depth; $opened = true; }
            if (!is_array($part) && $part === '}' && $opened && --$depth === 0) {
                $matches[] = substr($source, $offsets[$i], $offsets[$k] + 1 - $offsets[$i]);
                break;
            }
        }
    }
    if (count($matches) !== 1) throw new RuntimeException('Expected exactly one extracted function: '.$name);
    $GLOBALS['component_function_hashes'][$name] = ['path'=>$path,'source_sha256'=>hash('sha256',$source),'function_sha256'=>hash('sha256',$matches[0])];
    return $matches[0];
}
function ComponentLoadFunction(string $path, string $name, array $replacements = []): void {
    $body = ComponentExtractFunction($path, $name);
    foreach ($replacements as $before => $after) {
        if (substr_count($body, $before) !== 1) throw new RuntimeException('Mutant anchor not exact for '.$name);
        $body = str_replace($before, $after, $body);
    }
    $GLOBALS['component_function_hashes'][$name]['evaluated_sha256'] = hash('sha256',$body);
    eval($body);
}
function ComponentCheck(bool $passed, string $name): void {
    ++$GLOBALS['component_checks'];
    if (!$passed) throw new RuntimeException($name);
}
