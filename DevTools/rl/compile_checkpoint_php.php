<?php

function CompileCheckpointFail($message) {
  fwrite(STDERR, strval($message) . PHP_EOL);
  exit(1);
}

function CompileCheckpointArgs($argv) {
  $args = [
    'checkpoint' => '',
    'output' => '',
    'shard-prefix-length' => 2,
    'progress-every' => 25000,
  ];
  $items = array_slice($argv, 1);
  for ($i = 0; $i < count($items); ++$i) {
    $item = strval($items[$i]);
    if (!str_starts_with($item, '--')) continue;
    $key = substr($item, 2);
    $value = ($i + 1 < count($items) && !str_starts_with(strval($items[$i + 1]), '--')) ? strval($items[++$i]) : '1';
    if (array_key_exists($key, $args)) $args[$key] = $value;
  }
  $args['shard-prefix-length'] = max(1, min(3, intval($args['shard-prefix-length'])));
  $args['progress-every'] = max(0, intval($args['progress-every']));
  return $args;
}

function CompileCheckpointEnsureDir($path) {
  if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
    CompileCheckpointFail('Unable to create directory: ' . $path);
  }
}

function CompileCheckpointDeleteTree($path) {
  if (!is_dir($path)) return;
  $items = scandir($path);
  if (!is_array($items)) return;
  foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $child = $path . DIRECTORY_SEPARATOR . $item;
    if (is_dir($child)) CompileCheckpointDeleteTree($child);
    else @unlink($child);
  }
  @rmdir($path);
}

function CompileCheckpointSkipWhitespace($handle) {
  while (($ch = fgetc($handle)) !== false) {
    if (!ctype_space($ch)) return $ch;
  }
  return false;
}

function CompileCheckpointFindToken($handle, $token, &$captured, $captureLimit = 1048576) {
  $captured = '';
  $matched = 0;
  $length = strlen($token);
  while (($ch = fgetc($handle)) !== false) {
    if (strlen($captured) < $captureLimit) $captured .= $ch;
    if ($ch === $token[$matched]) {
      ++$matched;
      if ($matched === $length) return true;
    } else {
      $matched = $ch === $token[0] ? 1 : 0;
    }
  }
  return false;
}

function CompileCheckpointEnterObjectAfterToken($handle, $label) {
  $ch = CompileCheckpointSkipWhitespace($handle);
  if ($ch !== ':') CompileCheckpointFail('Expected colon after ' . $label . '.');
  $ch = CompileCheckpointSkipWhitespace($handle);
  if ($ch !== '{') CompileCheckpointFail('Expected object after ' . $label . '.');
}

function CompileCheckpointReadJsonString($handle, $openingQuote) {
  if ($openingQuote !== '"') CompileCheckpointFail('Expected JSON string.');
  $raw = '"';
  $escaped = false;
  while (($ch = fgetc($handle)) !== false) {
    $raw .= $ch;
    if ($escaped) {
      $escaped = false;
      continue;
    }
    if ($ch === '\\') {
      $escaped = true;
      continue;
    }
    if ($ch === '"') {
      try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
      } catch (Throwable $e) {
        CompileCheckpointFail('Invalid JSON string: ' . $e->getMessage());
      }
      return strval($decoded);
    }
  }
  CompileCheckpointFail('Unterminated JSON string.');
}

function CompileCheckpointReadJsonContainer($handle, $opening) {
  if ($opening !== '{' && $opening !== '[') CompileCheckpointFail('Expected JSON container.');
  $raw = $opening;
  $stack = [$opening];
  $inString = false;
  $escaped = false;
  while (($ch = fgetc($handle)) !== false) {
    $raw .= $ch;
    if ($inString) {
      if ($escaped) $escaped = false;
      else if ($ch === '\\') $escaped = true;
      else if ($ch === '"') $inString = false;
      continue;
    }
    if ($ch === '"') {
      $inString = true;
      continue;
    }
    if ($ch === '{' || $ch === '[') {
      $stack[] = $ch;
      continue;
    }
    if ($ch === '}' || $ch === ']') {
      $expected = $ch === '}' ? '{' : '[';
      if (array_pop($stack) !== $expected) CompileCheckpointFail('Mismatched JSON container.');
      if (empty($stack)) return $raw;
    }
  }
  CompileCheckpointFail('Unterminated JSON container.');
}

function CompileCheckpointPhpArrayLiteral($values) {
  if (!is_array($values)) return '[]';
  $parts = [];
  foreach ($values as $key => $value) {
    if (!is_numeric($value)) CompileCheckpointFail('Checkpoint logit is not numeric.');
    $parts[] = var_export(strval($key), true) . '=>' . var_export(floatval($value), true);
  }
  return '[' . implode(',', $parts) . ']';
}

function CompileCheckpointParseLogitsObject($handle, $emit, $progressEvery, $label) {
  $count = 0;
  $ch = CompileCheckpointSkipWhitespace($handle);
  if ($ch === '}') return 0;
  while (true) {
    $stateKey = CompileCheckpointReadJsonString($handle, $ch);
    $ch = CompileCheckpointSkipWhitespace($handle);
    if ($ch !== ':') CompileCheckpointFail('Expected colon after state key in ' . $label . '.');
    $ch = CompileCheckpointSkipWhitespace($handle);
    $rawValue = CompileCheckpointReadJsonContainer($handle, $ch);
    try {
      $values = json_decode($rawValue, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
      CompileCheckpointFail('Invalid action map for state: ' . $e->getMessage());
    }
    if (!is_array($values)) CompileCheckpointFail('State action map is not an object.');
    $emit($stateKey, $values);
    ++$count;
    if ($progressEvery > 0 && $count % $progressEvery === 0) {
      fwrite(STDOUT, '[compile] ' . $label . ' states ' . $count . PHP_EOL);
    }
    $ch = CompileCheckpointSkipWhitespace($handle);
    if ($ch === '}') break;
    if ($ch !== ',') CompileCheckpointFail('Expected comma after state action map.');
    $ch = CompileCheckpointSkipWhitespace($handle);
  }
  return $count;
}

function CompileCheckpointMetadataValue($text, $key, $fallback = '') {
  $pattern = '/"' . preg_quote($key, '/') . '"\s*:\s*"([^"]*)"/';
  return preg_match($pattern, $text, $matches) ? strval($matches[1]) : strval($fallback);
}

function CompileCheckpointEdgeHash($path, $edgeBytes = 4096) {
  $size = intval(filesize($path));
  $handle = fopen($path, 'rb');
  if (!is_resource($handle)) CompileCheckpointFail('Unable to read checkpoint edges.');
  $first = $size > 0 ? fread($handle, min($edgeBytes, $size)) : '';
  $last = '';
  if ($size > $edgeBytes) {
    fseek($handle, max(0, $size - $edgeBytes));
    $last = fread($handle, min($edgeBytes, $size));
  }
  fclose($handle);
  return hash('sha256', strval($first) . strval($last));
}

function CompileCheckpointWriteAtomic($path, $contents) {
  $temp = $path . '.tmp.' . getmypid();
  if (file_put_contents($temp, $contents) === false) CompileCheckpointFail('Unable to write: ' . $temp);
  if (is_file($path) && !@unlink($path)) CompileCheckpointFail('Unable to replace: ' . $path);
  if (!@rename($temp, $path)) CompileCheckpointFail('Unable to publish: ' . $path);
}

$args = CompileCheckpointArgs($argv);
$checkpoint = realpath(strval($args['checkpoint']));
if ($checkpoint === false || !is_file($checkpoint)) CompileCheckpointFail('--checkpoint must name an existing JSON checkpoint.');
$manifestPath = trim(strval($args['output'])) !== '' ? strval($args['output']) : ($checkpoint . '.php');
if (!preg_match('/^[A-Za-z]:[\\\\\/]/', $manifestPath) && !str_starts_with($manifestPath, DIRECTORY_SEPARATOR)) {
  $manifestPath = getcwd() . DIRECTORY_SEPARATOR . $manifestPath;
}
$manifestDir = dirname($manifestPath);
CompileCheckpointEnsureDir($manifestDir);

$checkpointHash = hash_file('sha256', $checkpoint);
$checkpointSize = intval(filesize($checkpoint));
$edgeHash = CompileCheckpointEdgeHash($checkpoint);
$safeStem = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($checkpoint, PATHINFO_FILENAME));
$relativeBundleDir = 'Compiled/' . $safeStem . '/' . substr($checkpointHash, 0, 16);
$bundleDir = $manifestDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeBundleDir);
$tempBundleDir = $bundleDir . '.tmp-' . getmypid();
$shardDir = $tempBundleDir . DIRECTORY_SEPARATOR . 'shards';
if (is_dir($tempBundleDir)) CompileCheckpointDeleteTree($tempBundleDir);

if (!is_file($bundleDir . DIRECTORY_SEPARATOR . 'manifest.php')) {
  CompileCheckpointEnsureDir($shardDir);
  $writers = [];
  $shardCounts = [];
  $shardNames = [];
  $prefixLength = intval($args['shard-prefix-length']);
  $emit = function($stateKey, $values) use (&$writers, &$shardCounts, &$shardNames, $shardDir, $prefixLength) {
    $prefix = substr(hash('sha256', strval($stateKey)), 0, $prefixLength);
    if (!isset($writers[$prefix])) {
      $path = $shardDir . DIRECTORY_SEPARATOR . $prefix . '.php';
      $handle = fopen($path, 'wb');
      if (!is_resource($handle)) CompileCheckpointFail('Unable to write shard: ' . $path);
      fwrite($handle, "<?php\nreturn [\n");
      $writers[$prefix] = $handle;
      $shardCounts[$prefix] = 0;
      $shardNames[$prefix] = $prefix . '.php';
    }
    $line = var_export(strval($stateKey), true) . '=>' . CompileCheckpointPhpArrayLiteral($values) . ",\n";
    if (fwrite($writers[$prefix], $line) !== strlen($line)) CompileCheckpointFail('Unable to append shard ' . $prefix . '.');
    ++$shardCounts[$prefix];
  };

  $handle = fopen($checkpoint, 'rb');
  if (!is_resource($handle)) CompileCheckpointFail('Unable to open checkpoint.');
  $prefixText = '';
  if (!CompileCheckpointFindToken($handle, '"logits"', $prefixText)) CompileCheckpointFail('Checkpoint has no logits object.');
  $stateKeyVersion = CompileCheckpointMetadataValue($prefixText, 'state_key_version', 'lite-v2');
  $actionKeyVersion = CompileCheckpointMetadataValue($prefixText, 'action_key_version', 'index-v1');
  $logitsFormat = CompileCheckpointMetadataValue($prefixText, 'logits_format', 'sparse_index_map');
  CompileCheckpointEnterObjectAfterToken($handle, 'logits');
  $tacticalStates = CompileCheckpointParseLogitsObject($handle, $emit, intval($args['progress-every']), 'tactical');

  $betweenText = '';
  $hasStrategyLogits = CompileCheckpointFindToken($handle, '"logits"', $betweenText);
  $strategyMode = CompileCheckpointMetadataValue($betweenText, 'strategy_mode', 'none');
  $strategyStates = 0;
  if ($hasStrategyLogits) {
    CompileCheckpointEnterObjectAfterToken($handle, 'strategy logits');
    $strategyStates = CompileCheckpointParseLogitsObject($handle, $emit, intval($args['progress-every']), 'strategy');
  }
  fclose($handle);

  foreach ($writers as $writer) {
    fwrite($writer, "];\n");
    fclose($writer);
  }
  ksort($shardCounts);
  sort($shardNames, SORT_STRING);
  $bundleManifest = [
    'format' => 'tcgengine-rl-php-shards-v1',
    'checkpoint_basename' => basename($checkpoint),
    'checkpoint_sha256' => $checkpointHash,
    'checkpoint_edge_sha256' => $edgeHash,
    'checkpoint_size' => $checkpointSize,
    'state_key_version' => $stateKeyVersion,
    'action_key_version' => $actionKeyVersion,
    'logits_format' => $logitsFormat,
    'strategy_mode' => $strategyMode,
    'shard_prefix_length' => $prefixLength,
    'tactical_states' => $tacticalStates,
    'strategy_states' => $strategyStates,
    'shard_counts' => $shardCounts,
    'shards' => $shardNames,
  ];
  $manifestCode = "<?php\n\$manifest = " . var_export($bundleManifest, true) . ";\n\$manifest['shard_dir'] = __DIR__ . DIRECTORY_SEPARATOR . 'shards';\nreturn \$manifest;\n";
  CompileCheckpointWriteAtomic($tempBundleDir . DIRECTORY_SEPARATOR . 'manifest.php', $manifestCode);
  CompileCheckpointEnsureDir(dirname($bundleDir));
  if (!@rename($tempBundleDir, $bundleDir)) CompileCheckpointFail('Unable to publish compiled bundle: ' . $bundleDir);
}

$relativeManifest = str_replace('\\', '/', $relativeBundleDir . '/manifest.php');
$pointerCode = "<?php\nreturn require __DIR__ . DIRECTORY_SEPARATOR . " . var_export($relativeManifest, true) . ";\n";
CompileCheckpointWriteAtomic($manifestPath, $pointerCode);
$publishedManifest = require $manifestPath;
echo json_encode([
  'success' => true,
  'checkpoint' => $checkpoint,
  'manifest' => $manifestPath,
  'bundleDir' => $bundleDir,
  'checkpointSha256' => $checkpointHash,
  'checkpointBytes' => $checkpointSize,
  'tacticalStates' => intval($publishedManifest['tactical_states'] ?? 0),
  'strategyStates' => intval($publishedManifest['strategy_states'] ?? 0),
  'shards' => is_array($publishedManifest['shards'] ?? null) ? count($publishedManifest['shards']) : 0,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
