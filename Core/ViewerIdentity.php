<?php

// Max seats a sim supports. Twin Suns (SWUSim) runs up to 4 seats; every other sim is 2-player.
// Callers pass this into the viewer helpers so a real seat 3/4 is recognized as a player instead of
// falling through to the legacy "3 = spectator" convention (see NormalizeViewerIdentity).
function SimGameMaxSeats($rootName)
{
  return (strval($rootName) === 'SWUSim') ? 4 : 2;
}

function NormalizeViewerIdentity($rawPlayerID, $maxSeats = 2)
{
  $maxSeats = max(2, intval($maxSeats));
  $normalized = strtoupper(trim(strval($rawPlayerID)));
  if ($normalized === '') $normalized = 'S';
  // Legacy: before spectators used 'S', playerID=3 meant "spectator". Preserve that ONLY for sims that
  // don't have a real seat 3 (<=2-seat). Multi-seat (Twin Suns) games treat 3/4 as real seats; a
  // spectator there always uses 'S'.
  if ($normalized === '3' && $maxSeats < 3) $normalized = 'S';

  if (ctype_digit($normalized)) {
    $seat = intval($normalized);
    if ($seat >= 1 && $seat <= $maxSeats) {
      return [
        'viewerID' => $normalized,
        'viewerSeat' => $seat,
        'isSpectator' => false,
        'canAct' => true,
        'label' => 'P' . $normalized,
      ];
    }
  }

  if ($normalized === 'S') {
    return [
      'viewerID' => 'S',
      'viewerSeat' => null,
      'isSpectator' => true,
      'canAct' => false,
      'label' => 'Spec',
    ];
  }

  return [
    'viewerID' => '',
    'viewerSeat' => null,
    'isSpectator' => false,
    'canAct' => false,
    'label' => '',
  ];
}

function IsViewerIdentityValid($rawPlayerID, $maxSeats = 2)
{
  return NormalizeViewerIdentity($rawPlayerID, $maxSeats)['viewerID'] !== '';
}

function NormalizeViewerPerspective($viewerInfo, $rawPerspective = '', $maxSeats = 2)
{
  if (!is_array($viewerInfo)) $viewerInfo = NormalizeViewerIdentity($viewerInfo, $maxSeats);
  // A seated viewer's perspective is their own seat (1..N). Spectators pick a perspective from the
  // request. (2-seat sims only ever produce 1/2, so this stays byte-identical for them.)
  if (!$viewerInfo['isSpectator']) return intval($viewerInfo['viewerSeat'] ?? 1);

  $perspective = intval($rawPerspective);
  return $perspective >= 1 ? $perspective : 1;
}

function ViewerLabelForChat($rawPlayerID, $maxSeats = 2)
{
  $viewerInfo = NormalizeViewerIdentity($rawPlayerID, $maxSeats);
  return $viewerInfo['label'];
}

function ViewerCanAct($rawPlayerID, $maxSeats = 2)
{
  return NormalizeViewerIdentity($rawPlayerID, $maxSeats)['canAct'];
}

function ViewerCanChat($rawPlayerID, $maxSeats = 2)
{
  return IsViewerIdentityValid($rawPlayerID, $maxSeats);
}

function ViewerIdentityForStorage($rawPlayerID, $maxSeats = 2)
{
  return NormalizeViewerIdentity($rawPlayerID, $maxSeats)['viewerID'];
}

?>
