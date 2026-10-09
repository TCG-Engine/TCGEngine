<?php
// SWU-PGN: conformance report (spec §5, §7, §9, §15, §18). See SwuPgn.php.
//
// The format's JSON Schemas are carried here as PHP data and checked by a small evaluator for
// exactly the keywords they use (type, enum, const, pattern, minimum, required, properties,
// additionalProperties, items, prefixItems, min/maxItems, allOf with if/then, local $ref).
// Records are decoded to stdClass for this, so a JSON {} and [] stay distinguishable.
//
// An unknown event type or %%% section is a WARNING (a later version's, spec §18); a shape the fold
// would trip over, a closed-vocabulary value outside its set, or a missing required field is an ERROR.

const SWUPGN_KNOWN_EVENT_TYPES = [
  'PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'ATTACK', 'PASS',
  'CLAIM_INITIATIVE', 'CHOICE', 'MULLIGAN', 'KEEP_HAND', 'MODAL_CHOICE', 'ABILITY_ACTIVATE',
  'DAMAGE', 'HEAL', 'DEFEAT', 'EXHAUST', 'READY', 'DRAW', 'DISCARD', 'RESOURCE', 'SHUFFLE',
  'EXHAUST_RESOURCES', 'READY_RESOURCES', 'STATS', 'LEADER_FLIP',
  'CREATE_TOKEN', 'MOVE', 'CAPTURE', 'RESCUE', 'TAKE_CONTROL', 'SHIELD_GAIN', 'SHIELD_USE',
  'EXPERIENCE_GAIN', 'STATUS_TOKEN', 'OVERWHELM', 'SEARCH', 'REVEAL', 'TRIGGER',
  'PHASE_START', 'PHASE_END', 'ROUND_START', 'ROUND_END', 'GAME_END', 'INIT', 'UNDO',
];

// The two closed vocabularies a reader may branch on (spec §6.4, §10.1).
const SWUPGN_DEFEAT_REASONS = ['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect', 'unknown'];
const SWUPGN_ABILITY_KINDS = ['action', 'epic', 'triggered', 'keyword', 'replacement', 'constant', 'event', 'delayed'];

function _SwuPgnHeaderSchema(): array {
  return [
    'type' => 'object',
    'required' => ['Game', 'GameId', 'Date', 'CardPool', 'Engine', 'Seed', 'P1Id', 'P2Id', 'P1', 'P2', 'P1Leader', 'P1Base', 'P2Leader', 'P2Base', 'Result', 'Reason', 'Rounds'],
    'properties' => [
      // Any 1.x: a reader accepts a higher MINOR version and refuses a different major (spec §18).
      'Game' => ['type' => 'string', 'pattern' => '^SWU-PGN/1\.\d+$'],
      'Result' => ['enum' => ['P1', 'P2', 'Draw', 'Incomplete']],
      'Perspective' => ['enum' => ['P1', 'P2']],
      'Rounds' => ['type' => 'integer'],
      'Undos' => ['type' => 'integer', 'minimum' => 0],
    ],
  ];
}

function _SwuPgnDeckSchema(): array {
  $pairs = ['type' => 'array', 'items' => ['type' => 'array', 'prefixItems' => [['type' => 'string'], ['type' => 'integer']], 'minItems' => 2, 'maxItems' => 2]];
  return [
    'type' => 'object',
    'required' => ['p', 'leader', 'base', 'deck'],
    'properties' => ['p' => ['enum' => [1, 2]], 'leader' => ['type' => 'string'], 'base' => ['type' => 'string'], 'deck' => $pairs, 'sideboard' => $pairs],
    'additionalProperties' => false,
  ];
}

function _SwuPgnAnnotationSchema(): array {
  return [
    'type' => 'object',
    'required' => ['ref'],
    'properties' => [
      'ref' => ['type' => 'string'], 'nag' => ['type' => 'string'], 'text' => ['type' => 'string'], 'by' => ['type' => 'string'],
      'line' => ['type' => 'array'], 'id' => ['type' => 'string'], 'parent' => ['type' => 'string'], 'ts' => ['type' => 'integer'],
    ],
  ];
}

function _SwuPgnEventSchema(): array {
  $str = ['type' => 'string'];
  $int = ['type' => 'integer'];
  $bool = ['type' => 'boolean'];
  $strList = ['type' => 'array', 'items' => $str];
  $when = fn(array $types, array $then) => ['if' => ['properties' => ['t' => count($types) === 1 ? ['const' => $types[0]] : ['enum' => $types]], 'required' => ['t']], 'then' => $then];
  return [
    'type' => 'object',
    'required' => ['seq', 't'],
    'properties' => [
      'seq' => ['type' => 'string', 'pattern' => '^R\d+\.(S|A|G)(\.[A-Za-z0-9-]+)?$|^R\d+\.(start|end)(-undo)?$'],
      't' => $str, 'p' => ['enum' => [1, 2]], 'for' => $str, 'ms' => ['type' => 'integer', 'minimum' => 0],
    ],
    'allOf' => [
      $when(['ROUND_START', 'ROUND_END'], ['properties' => ['round' => $int, 'keyframe' => ['$ref' => 'keyframe']]]),
      $when(['DRAW', 'DISCARD', 'REVEAL'], ['properties' => ['cards' => $strList], 'required' => ['cards']]),
      $when(['SEARCH'], ['properties' => ['found' => $strList]]),
      $when(['DAMAGE', 'HEAL', 'OVERWHELM'], ['properties' => ['src' => $str, 'tgt' => $str, 'amt' => $int, 'hp' => $int], 'required' => ['tgt', 'amt', 'hp']]),
      $when(['UNDO'], ['properties' => ['at' => $str, 'by' => ['enum' => [1, 2]]], 'required' => ['at']]),
      $when(['DEFEAT'], ['properties' => ['reason' => ['enum' => SWUPGN_DEFEAT_REASONS], 'defeatedBy' => $str], 'required' => ['reason']]),
      $when(['STATUS_TOKEN', 'EXPERIENCE_GAIN'], ['properties' => ['card' => $str, 'count' => $int], 'required' => ['card', 'count']]),
      $when(['SHIELD_GAIN', 'SHIELD_USE'], ['properties' => ['card' => $str, 'count' => $int], 'required' => ['card']]),
      $when(['MOVE'], ['properties' => ['card' => $str, 'from' => $str, 'to' => $str, 'kind' => ['enum' => ['unit', 'upgrade']], 'attachedTo' => $str, 'exhausted' => $bool], 'required' => ['card', 'from', 'to']]),
      $when(['EXHAUST_RESOURCES', 'READY_RESOURCES'], ['properties' => ['amount' => ['type' => 'integer', 'minimum' => 0]], 'required' => ['p', 'amount']]),
      $when(['STATS'], ['properties' => ['card' => $str, 'power' => $int, 'hp' => $int, 'keywords' => $strList], 'required' => ['card', 'power', 'hp']]),
      $when(['LEADER_FLIP'], ['properties' => ['card' => $str, 'onStartingSide' => $bool], 'required' => ['card', 'onStartingSide']]),
      $when(['ABILITY_ACTIVATE'], ['properties' => ['ability' => $str, 'kind' => ['enum' => SWUPGN_ABILITY_KINDS], 'title' => $str, 'epic' => $bool]]),
      $when(['CAPTURE'], ['properties' => ['by' => $str]]),
      $when(['TAKE_CONTROL'], ['properties' => ['zone' => $str, 'from' => ['enum' => [1, 2]], 'exhausted' => $bool]]),
      $when(['CREATE_TOKEN'], ['properties' => ['token' => $str, 'zone' => $str, 'kind' => ['enum' => ['unit', 'upgrade']]], 'required' => ['token', 'zone']]),
      $when(['PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'EXHAUST', 'READY', 'DEFEAT', 'RESOURCE', 'TRIGGER', 'ABILITY_ACTIVATE', 'CAPTURE', 'RESCUE', 'TAKE_CONTROL'],
        ['properties' => ['card' => $str], 'required' => ['card']]),
      $when(['ATTACK'], ['properties' => ['atk' => $str, 'def' => $str], 'required' => ['atk', 'def']]),
    ],
    '$defs' => [
      'keyframe' => ['type' => 'object', 'required' => ['players'], 'properties' => [
        'round' => $int, 'initiativeTaken' => $bool,
        'players' => ['type' => 'object', 'properties' => ['1' => ['$ref' => 'playerState'], '2' => ['$ref' => 'playerState']]],
      ]],
      'playerState' => ['type' => 'object', 'required' => ['cards', 'hand', 'discard'], 'properties' => [
        'cards' => ['type' => 'array', 'items' => ['$ref' => 'cardState']], 'hand' => $strList, 'discard' => $strList,
        'baseHp' => $int, 'handSize' => $int, 'resourcesReady' => $int, 'resourcesExhausted' => $int, 'credits' => $int,
        'hasForce' => $bool, 'deckSize' => $int,
        'leader' => ['type' => 'object', 'properties' => ['id' => $str, 'deployed' => $bool, 'exhausted' => $bool, 'epicActionUsed' => $bool, 'onStartingSide' => $bool]],
      ]],
      'cardState' => ['type' => 'object', 'properties' => [
        'id' => $str, 'zone' => $str, 'upgrades' => $strList, 'captured' => $strList,
        'statusTokens' => ['type' => 'object', 'additionalProperties' => $int],
        'power' => $int, 'hp' => $int, 'keywords' => $strList,
      ]],
    ],
  ];
}

// JSON Schema `type`, on json_decode(..., false) data.
function _SwuPgnSchemaType($x, string $type): bool {
  switch ($type) {
    case 'object': return $x instanceof stdClass;
    case 'array': return is_array($x);
    case 'string': return is_string($x);
    case 'boolean': return is_bool($x);
    case 'integer': return is_int($x) || (is_float($x) && is_finite($x) && floor($x) == $x);
    case 'number': return is_int($x) || is_float($x);
  }
  return true;
}

// Deep JSON equality, with 1 and 1.0 equal as they are in JSON.
function _SwuPgnJsonEqual($a, $b): bool {
  if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) return $a == $b;
  return $a === $b;
}

// Validate $data against $schema, appending ['path' => instancePath, 'message' => …] to $errors.
function _SwuPgnSchemaCheck(array $schema, $data, string $path, array $defs, array &$errors): void {
  if (isset($schema['$ref'])) { _SwuPgnSchemaCheck($defs[$schema['$ref']], $data, $path, $defs, $errors); return; }
  if (isset($schema['type']) && !_SwuPgnSchemaType($data, $schema['type'])) {
    $errors[] = ['path' => $path, 'message' => "must be {$schema['type']}"];
    return;
  }
  if (isset($schema['enum'])) {
    $hit = false;
    foreach ($schema['enum'] as $v) if (_SwuPgnJsonEqual($data, $v)) { $hit = true; break; }
    if (!$hit) $errors[] = ['path' => $path, 'message' => 'must be equal to one of the allowed values'];
  }
  if (array_key_exists('const', $schema) && !_SwuPgnJsonEqual($data, $schema['const'])) {
    $errors[] = ['path' => $path, 'message' => 'must be equal to constant'];
  }
  if (isset($schema['pattern']) && is_string($data) && preg_match('#' . str_replace('#', '\#', $schema['pattern']) . '#u', $data) !== 1) {
    $errors[] = ['path' => $path, 'message' => "must match pattern \"{$schema['pattern']}\""];
  }
  if (isset($schema['minimum']) && (is_int($data) || is_float($data)) && $data < $schema['minimum']) {
    $errors[] = ['path' => $path, 'message' => "must be >= {$schema['minimum']}"];
  }
  if (is_array($data)) {
    if (isset($schema['minItems']) && count($data) < $schema['minItems']) $errors[] = ['path' => $path, 'message' => "must NOT have fewer than {$schema['minItems']} items"];
    if (isset($schema['maxItems']) && count($data) > $schema['maxItems']) $errors[] = ['path' => $path, 'message' => "must NOT have more than {$schema['maxItems']} items"];
    $prefix = $schema['prefixItems'] ?? [];
    foreach ($data as $i => $item) {
      if (isset($prefix[$i])) _SwuPgnSchemaCheck($prefix[$i], $item, "$path/$i", $defs, $errors);
      elseif (isset($schema['items'])) _SwuPgnSchemaCheck($schema['items'], $item, "$path/$i", $defs, $errors);
    }
  }
  if ($data instanceof stdClass) {
    $props = get_object_vars($data);
    foreach ($schema['required'] ?? [] as $req) {
      if (!array_key_exists($req, $props)) $errors[] = ['path' => $path, 'message' => "must have required property '$req'"];
    }
    $known = $schema['properties'] ?? [];
    foreach ($props as $key => $value) {
      if (array_key_exists($key, $known)) _SwuPgnSchemaCheck($known[$key], $value, "$path/$key", $defs, $errors);
      elseif (($schema['additionalProperties'] ?? true) === false) $errors[] = ['path' => $path, 'message' => 'must NOT have additional properties'];
      elseif (is_array($schema['additionalProperties'] ?? null)) _SwuPgnSchemaCheck($schema['additionalProperties'], $value, "$path/$key", $defs, $errors);
    }
  }
  foreach ($schema['allOf'] ?? [] as $sub) {
    if (isset($sub['if'])) {
      $probe = [];
      _SwuPgnSchemaCheck($sub['if'], $data, $path, $defs, $probe);
      if ($probe !== []) continue;
      $before = count($errors);
      _SwuPgnSchemaCheck($sub['then'], $data, $path, $defs, $errors);
      if (count($errors) > $before) $errors[] = ['path' => $path, 'message' => 'must match "then" schema'];
    } else {
      _SwuPgnSchemaCheck($sub, $data, $path, $defs, $errors);
    }
  }
}

function _SwuPgnSchemaErrors(array $schema, $data): array {
  $errors = [];
  _SwuPgnSchemaCheck($schema, $data, '', $schema['$defs'] ?? [], $errors);
  return $errors;
}

/**
 * A conformance report: {valid, formatVersion, issues: [{severity: error|warning, message}]}.
 * Never throws — a parse failure is reported as an error issue.
 */
function SwuPgnValidate(string $text): array {
  $issues = [];
  $formatVersion = null;
  try {
    $doc = _SwuPgnParse($text, false);
    $formatVersion = $doc['header']['game'] ?? null;
  } catch (Throwable $t) {
    return ['valid' => false, 'formatVersion' => $formatVersion, 'issues' => [['severity' => 'error', 'message' => $t->getMessage()]]];
  }
  $error = function (string $message) use (&$issues) { $issues[] = ['severity' => 'error', 'message' => $message]; };
  $warn = function (string $message) use (&$issues) { $issues[] = ['severity' => 'warning', 'message' => $message]; };

  $h = $doc['header'];
  $header = (object)array_filter([
    'Game' => $h['game'], 'GameId' => $h['gameId'], 'Date' => $h['date'], 'CardPool' => $h['cardPool'], 'Engine' => $h['engine'], 'Seed' => $h['seed'],
    'Perspective' => $h['perspective'],
    'P1Id' => $h['p1Id'], 'P2Id' => $h['p2Id'], 'P1' => $h['p1'], 'P2' => $h['p2'],
    'P1Leader' => $h['p1Leader'], 'P1Base' => $h['p1Base'], 'P2Leader' => $h['p2Leader'], 'P2Base' => $h['p2Base'],
    'Result' => $h['result'], 'Reason' => $h['reason'], 'Rounds' => $h['rounds'],
    'Format' => $h['format'] ?? null, 'RecorderErrors' => $h['recorderErrors'] ?? null, 'Undos' => $h['undos'] ?? null,
  ], fn($v) => $v !== null);
  foreach (_SwuPgnSchemaErrors(_SwuPgnHeaderSchema(), $header) as $err) $error("header{$err['path']} {$err['message']}");

  foreach ($doc['decks'] as $d) {
    foreach (_SwuPgnSchemaErrors(_SwuPgnDeckSchema(), $d) as $err) $error("deck {$err['path']} {$err['message']}");
  }

  $eventSchema = _SwuPgnEventSchema();
  foreach (['setup' => $doc['setup'], 'event' => $doc['events']] as $label => $records) {
    foreach ($records as $rec) {
      // A line that is not a JSON object (null, 42, "x", [1,2]) is reported, never dereferenced.
      if (!($rec instanceof stdClass)) { $error("$label record is not a JSON object"); continue; }
      $seq = _SwuPgnStr(property_exists($rec, 'seq') && $rec->seq !== null ? $rec->seq : '');
      $t = _SwuPgnStr(property_exists($rec, 't') && $rec->t !== null ? $rec->t : '');
      foreach (_SwuPgnSchemaErrors($eventSchema, $rec) as $err) $error("$label $seq {$err['path']} {$err['message']}");
      if (!in_array($t, SWUPGN_KNOWN_EVENT_TYPES, true)) $warn("$label $seq unknown type \"$t\" (tolerated for forward compatibility)");
    }
  }

  // parse() skips a section it does not know (a later version's); a typo'd banner stays visible here.
  foreach (explode("\n", $text) as $i => $line) {
    $trimmed = _SwuPgnTrim($line);
    if (!str_starts_with($trimmed, '%%%')) continue;
    $name = strtoupper(_SwuPgnTrim(substr($trimmed, 3)));
    if (!in_array($name, SWUPGN_KNOWN_SECTIONS, true)) $warn('line ' . ($i + 1) . ": unknown section \"%%% $name\" — its records were skipped (tolerated for forward compatibility)");
  }

  foreach ($doc['annotations'] as $a) {
    if (!($a instanceof stdClass)) { $error('annotation record is not a JSON object'); continue; }
    foreach (_SwuPgnSchemaErrors(_SwuPgnAnnotationSchema(), $a) as $err) $error("annotation {$err['path']} {$err['message']}");
  }

  $hasErrors = count(array_filter($issues, fn($i) => $i['severity'] === 'error')) > 0;
  return ['valid' => !$hasErrors, 'formatVersion' => $formatVersion, 'issues' => $issues];
}
