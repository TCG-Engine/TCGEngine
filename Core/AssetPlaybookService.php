<?php

function AssetPlaybookDefaultDocument() {
  return [
    'schemaVersion' => 1,
    'revision' => 0,
    'updatedAt' => null,
    'lines' => []
  ];
}

function AssetPlaybookLimitText($value, $maxLength) {
  if (!is_scalar($value)) return '';
  $text = trim((string)$value);
  if (function_exists('mb_substr')) return mb_substr($text, 0, $maxLength, 'UTF-8');
  return substr($text, 0, $maxLength);
}

function AssetPlaybookNormalizeID($value, $fallbackPrefix, $index) {
  $id = AssetPlaybookLimitText($value, 80);
  if ($id !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $id)) return $id;
  return $fallbackPrefix . '-' . ($index + 1);
}

function AssetPlaybookNormalizeDocument($input, $revision = 0, $updatedAt = null) {
  $document = AssetPlaybookDefaultDocument();
  $document['revision'] = max(0, intval($revision));
  $document['updatedAt'] = $updatedAt === null ? null : AssetPlaybookLimitText($updatedAt, 40);

  if (!is_array($input)) return $document;
  $rawLines = isset($input['lines']) && is_array($input['lines']) ? $input['lines'] : [];

  foreach (array_slice($rawLines, 0, 64) as $lineIndex => $rawLine) {
    if (!is_array($rawLine)) continue;

    $line = [
      'id' => AssetPlaybookNormalizeID($rawLine['id'] ?? '', 'line', $lineIndex),
      'title' => AssetPlaybookLimitText($rawLine['title'] ?? '', 120),
      'summary' => AssetPlaybookLimitText($rawLine['summary'] ?? '', 300),
      'notes' => AssetPlaybookLimitText($rawLine['notes'] ?? '', 8000),
      'shared' => !empty($rawLine['shared']),
      'steps' => [],
      'values' => []
    ];
    if ($line['title'] === '') $line['title'] = 'Untitled line';

    $rawSteps = isset($rawLine['steps']) && is_array($rawLine['steps']) ? $rawLine['steps'] : [];
    foreach (array_slice($rawSteps, 0, 32) as $stepIndex => $rawStep) {
      if (!is_array($rawStep)) continue;
      $cards = [];
      $rawCards = isset($rawStep['cards']) && is_array($rawStep['cards']) ? $rawStep['cards'] : [];
      foreach (array_slice($rawCards, 0, 12) as $rawCardID) {
        $cardID = AssetPlaybookLimitText($rawCardID, 220);
        if ($cardID !== '' && !in_array($cardID, $cards, true)) $cards[] = $cardID;
      }
      $line['steps'][] = [
        'id' => AssetPlaybookNormalizeID($rawStep['id'] ?? '', $line['id'] . '-step', $stepIndex),
        'text' => AssetPlaybookLimitText($rawStep['text'] ?? '', 1200),
        'cards' => $cards
      ];
    }

    $rawValues = isset($rawLine['values']) && is_array($rawLine['values']) ? $rawLine['values'] : [];
    foreach (array_slice($rawValues, 0, 12) as $valueIndex => $rawValue) {
      if (!is_array($rawValue)) continue;
      $label = AssetPlaybookLimitText($rawValue['label'] ?? '', 80);
      if ($label === '') continue;
      $line['values'][] = [
        'id' => AssetPlaybookNormalizeID($rawValue['id'] ?? '', $line['id'] . '-value', $valueIndex),
        'label' => $label,
        'value' => AssetPlaybookLimitText($rawValue['value'] ?? '', 160),
        'unit' => AssetPlaybookLimitText($rawValue['unit'] ?? '', 40)
      ];
    }

    $document['lines'][] = $line;
  }

  return $document;
}

function AssetPlaybookStoragePath($assetsRoot, $assetID) {
  $assetID = trim((string)$assetID);
  if (!preg_match('/^\d+$/', $assetID)) return null;
  $assetDirectory = rtrim($assetsRoot, '/\\') . DIRECTORY_SEPARATOR . $assetID;
  if (!is_dir($assetDirectory)) return null;
  return $assetDirectory . DIRECTORY_SEPARATOR . 'Playbook.json';
}

function AssetPlaybookDecodeStoredDocument($encoded) {
  if (!is_string($encoded) || trim($encoded) === '') return AssetPlaybookDefaultDocument();
  $decoded = json_decode($encoded, true);
  if (!is_array($decoded)) return AssetPlaybookDefaultDocument();
  return AssetPlaybookNormalizeDocument(
    $decoded,
    intval($decoded['revision'] ?? 0),
    $decoded['updatedAt'] ?? null
  );
}

function AssetPlaybookLoad($assetsRoot, $assetID) {
  $path = AssetPlaybookStoragePath($assetsRoot, $assetID);
  if ($path === null) {
    return ['success' => false, 'error' => 'Asset storage was not found.'];
  }
  if (!is_file($path)) {
    return ['success' => true, 'playbook' => AssetPlaybookDefaultDocument()];
  }

  $handle = @fopen($path, 'rb');
  if ($handle === false) return ['success' => false, 'error' => 'The playbook could not be opened.'];
  if (!flock($handle, LOCK_SH)) {
    fclose($handle);
    return ['success' => false, 'error' => 'The playbook is currently unavailable.'];
  }
  $encoded = stream_get_contents($handle);
  flock($handle, LOCK_UN);
  fclose($handle);

  return ['success' => true, 'playbook' => AssetPlaybookDecodeStoredDocument($encoded)];
}

function AssetPlaybookSave($assetsRoot, $assetID, $input, $expectedRevision) {
  $path = AssetPlaybookStoragePath($assetsRoot, $assetID);
  if ($path === null) {
    return ['success' => false, 'status' => 404, 'error' => 'Asset storage was not found.'];
  }

  $handle = @fopen($path, 'c+b');
  if ($handle === false) {
    return ['success' => false, 'status' => 500, 'error' => 'The playbook could not be opened for saving.'];
  }
  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return ['success' => false, 'status' => 503, 'error' => 'The playbook is currently busy.'];
  }

  rewind($handle);
  $current = AssetPlaybookDecodeStoredDocument(stream_get_contents($handle));
  $currentRevision = intval($current['revision'] ?? 0);
  if (intval($expectedRevision) !== $currentRevision) {
    flock($handle, LOCK_UN);
    fclose($handle);
    return [
      'success' => false,
      'status' => 409,
      'error' => 'This playbook changed in another tab.',
      'playbook' => $current
    ];
  }

  $next = AssetPlaybookNormalizeDocument($input, $currentRevision + 1, gmdate('c'));
  $encoded = json_encode($next, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($encoded === false || strlen($encoded) > 524288) {
    flock($handle, LOCK_UN);
    fclose($handle);
    return ['success' => false, 'status' => 422, 'error' => 'The playbook is too large to save.'];
  }

  rewind($handle);
  if (!ftruncate($handle, 0) || fwrite($handle, $encoded . PHP_EOL) === false || !fflush($handle)) {
    flock($handle, LOCK_UN);
    fclose($handle);
    return ['success' => false, 'status' => 500, 'error' => 'The playbook could not be saved.'];
  }

  flock($handle, LOCK_UN);
  fclose($handle);
  return ['success' => true, 'status' => 200, 'playbook' => $next];
}

?>
