<?php

function NormalizeViewerIdentity($rawPlayerID)
{
  $normalized = strtoupper(trim(strval($rawPlayerID)));
  if ($normalized === '3') $normalized = 'S';
  if ($normalized === '') $normalized = 'S';

  if ($normalized === '1' || $normalized === '2') {
    return [
      'viewerID' => $normalized,
      'viewerSeat' => intval($normalized),
      'isSpectator' => false,
      'canAct' => true,
      'label' => 'P' . $normalized,
    ];
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

function IsViewerIdentityValid($rawPlayerID)
{
  return NormalizeViewerIdentity($rawPlayerID)['viewerID'] !== '';
}

function NormalizeViewerPerspective($viewerInfo, $rawPerspective = '')
{
  if (!is_array($viewerInfo)) $viewerInfo = NormalizeViewerIdentity($viewerInfo);
  if (!$viewerInfo['isSpectator']) return intval($viewerInfo['viewerSeat'] ?? 1) === 2 ? 2 : 1;

  $perspective = intval($rawPerspective);
  return $perspective === 2 ? 2 : 1;
}

function ViewerLabelForChat($rawPlayerID)
{
  $viewerInfo = NormalizeViewerIdentity($rawPlayerID);
  return $viewerInfo['label'];
}

function ViewerCanAct($rawPlayerID)
{
  return NormalizeViewerIdentity($rawPlayerID)['canAct'];
}

function ViewerCanChat($rawPlayerID)
{
  return IsViewerIdentityValid($rawPlayerID);
}

function ViewerIdentityForStorage($rawPlayerID)
{
  return NormalizeViewerIdentity($rawPlayerID)['viewerID'];
}

?>
