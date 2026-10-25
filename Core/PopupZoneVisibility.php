<?php
// Who may see a zone's cards in a zone popup (GetPopupContent.php). The board poll bakes each zone's
// "Display: Visibility=" into the generated GetNextTurn.php at generation time; the popup reads the same
// values from Schemas/<root>/GameSchema.txt, so a popup never shows what the board would not:
//   Public  — everyone.
//   Self    — the zone's OWNING seat; for a hand also a spectator allowed to see hands (caster mode), and
//             a seat whose own pending decision is looking at that seat's hand / resources (a reveal).
//   Private — nobody, the owner included (a deck: its order is never public).
//   None    — nobody. (Every game schema marks Versions — the undo stack, whole-game snapshots — None.)
// Ownership comes from the zone NAME, not the asker: my… = the perspective seat, their… = the other seat of
// a two-seat game (as GetZone resolves it), pN… = seat N. Masking only "their…" names let any seat read an
// opponent's hand as p2Hand and every deck in draw order (fixed 2026-10-11).
// Deckbuilders keep the legacy rule (their schemas mark everything Self, but a shared deck view must stay
// readable): the schema rules apply where the schema's Deck is Private, i.e. to games with hidden decks.

// [zone => Visibility] for a root; a zone without a Display line is Public (as the generator defaults it).
function PopupZoneVisibilityMap(string $rootName): array {
  static $cache = [];
  if (isset($cache[$rootName])) return $cache[$rootName];
  $map = [];
  $path = __DIR__ . '/../Schemas/' . $rootName . '/GameSchema.txt';
  if (preg_match('/^[A-Za-z0-9_]+$/', $rootName) === 1 && is_file($path)) {
    $zone = null;
    foreach (file($path) ?: [] as $line) {
      if (preg_match('/^([A-Za-z][A-Za-z0-9]*) - /', $line, $m)) { $zone = $m[1]; $map[$zone] = $map[$zone] ?? 'Public'; continue; }
      if ($zone !== null && preg_match('/^Display:.*\bVisibility=([A-Za-z]+)/', $line, $m)) $map[$zone] = $m[1];
    }
  }
  return $cache[$rootName] = $map;
}

// [owning seat or null, bare zone name] for a popup name.
function PopupZoneOwner(string $popupType, int $perspective): array {
  if (preg_match('/^my([A-Z][A-Za-z0-9]*)$/', $popupType, $m)) return [$perspective, $m[1]];
  if (preg_match('/^their([A-Z][A-Za-z0-9]*)$/', $popupType, $m)) return [$perspective == 1 ? 2 : 1, $m[1]];
  if (preg_match('/^p([1-9])([A-Z][A-Za-z0-9]*)$/', $popupType, $m)) return [intval($m[1]), $m[2]];
  if (preg_match('/^g([A-Z][A-Za-z0-9]*)$/', $popupType, $m)) return [null, $m[1]];
  return [null, $popupType];
}

// Does the viewing seat's own pending decision look at $owner's $zone (a reveal)? Mirrors the poll's scan.
function _PopupViewerRevealsZone(int $viewerSeat, int $owner, string $zone): bool {
  if ($viewerSeat < 1 || !function_exists('GetDecisionQueue')) return false;
  $twoSeat = !function_exists('SeatCountForGame') || SeatCountForGame() <= 2;
  foreach ((array)GetDecisionQueue($viewerSeat) as $d) {
    if (!is_object($d) || !empty($d->removed)) continue;
    $p = (string)($d->Param ?? '');
    if (preg_match('/\bp' . $owner . $zone . '\b/', $p)) return true;
    if ($twoSeat && $owner !== $viewerSeat && str_contains($p, 'their' . $zone)) return true;
  }
  return false;
}

// May this viewer see the cards of popup zone $popupType?
//   $viewerInfo: NormalizeViewerIdentity's array; $spectatorCanSeeHands: SimGameViewerCanSeeHands().
function PopupMayShowZone(string $rootName, string $popupType, array $viewerInfo, int $perspective, bool $spectatorCanSeeHands): bool {
  [$owner, $zone] = PopupZoneOwner($popupType, $perspective);
  $isSpectator = !empty($viewerInfo['isSpectator']);
  $viewerSeat = $isSpectator ? 0 : intval($viewerInfo['viewerSeat'] ?? 0);
  $map = PopupZoneVisibilityMap($rootName);
  $legacyHidden = in_array($zone, ['Hand', 'Memory', 'Material', 'TempZone', 'DecisionQueue'], true);
  if (($map['Deck'] ?? '') !== 'Private') {
    // Deckbuilders and anything without a game schema: the historical rule, unchanged.
    return !($legacyHidden && ($isSpectator || str_starts_with($popupType, 'their')));
  }
  if ($zone === 'Versions') return false;   // in a game, snapshots of the whole board (a schema may not declare it)
  $vis = $map[$zone] ?? 'Public';
  if ($legacyHidden && $vis === 'Public') $vis = 'Self';
  if ($rootName === 'FaBSim' && $zone === 'Arsenal') $vis = 'Public';   // FaB arsenal is face up (as the poll sends it)
  if ($vis === 'Public') return true;
  if ($vis !== 'Self') return false;
  if ($owner === null) return !$isSpectator;
  if ($viewerSeat === $owner) return true;
  if ($zone === 'Hand' && $spectatorCanSeeHands) return true;
  return in_array($zone, ['Hand', 'Resources'], true) && _PopupViewerRevealsZone($viewerSeat, $owner, $zone);
}
