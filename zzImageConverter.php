<?php
include_once './AccountFiles/AccountSessionAPI.php';

// Skip auth check for CLI invocations (e.g., code generator running from MCP server).
// HTTP requests always require a valid mod session.
$isHTTPRequest = php_sapi_name() !== 'cli' && !empty($_SERVER['REQUEST_METHOD']);

$response = new stdClass();
if ($isHTTPRequest) {
    $error = CheckLoggedInUserMod();
    if ($error !== "") {
        $response->error = $error;
        echo json_encode($response);
        exit();
    }
}

// Imagick is the ONLY supported backend for asset generation. The old GD fallback was
// removed because it produced broken output on the deployed LAMPP box (no Docker). If
// Imagick is missing or an operation fails, we throw so the generator run aborts loudly
// rather than silently writing bad assets.
function _requireImagick()
{
    if (!class_exists('Imagick')) {
        throw new Exception("Imagick extension is required for asset generation but is not installed/enabled. Aborting run.");
    }
}

// CSS object-fit:cover equivalent — scale so the image fully fills a $targetW x $targetH box
// (the larger of the two scale factors, so the non-matching dimension overflows), then crop that
// overflow off centered. Unlike Imagick's resizeImage bestfit (CSS "contain": fits inside the box,
// but the output size varies with the source's exact aspect ratio), this always produces an
// identical $targetW x $targetH canvas regardless of the source's aspect ratio — needed so a fixed
// downstream pixel crop lands at the same relative spot on every card.
function _resizeCover($image, $targetW, $targetH)
{
    $srcW = $image->getImageWidth();
    $srcH = $image->getImageHeight();
    $scale = max($targetW / $srcW, $targetH / $srcH);
    $scaledW = (int) round($srcW * $scale);
    $scaledH = (int) round($srcH * $scale);
    $image->resizeImage($scaledW, $scaledH, Imagick::FILTER_LANCZOS, 1, false);
    $cropX = (int) round(($scaledW - $targetW) / 2);
    $cropY = (int) round(($scaledH - $targetH) / 2);
    $image->cropImage($targetW, $targetH, max(0, $cropX), max(0, $cropY));
    $image->setImagePage(0, 0, 0, 0);
}

// Card dimensions after resize: 450×628 (portrait) or 628×450 (landscape for Leader/Base).
//
// Concat crop specs (all produce 450×450 output from a 450×628 portrait card):
//   Unit    — skip top 14px chrome; take 450×450 from y=14
//   Event   — top section: skip 14px, take 184px (y=14); bottom section: skip bottom 44px, take 266px (y=318); stack top@0, bottom@184
//   Upgrade — top section: skip 14px, take 370px (y=14); bottom section: skip bottom 32px, take 80px (y=516); stack top@0, bottom@370

function CheckImage($cardID, $url, $definedType, $isBack = false, $set = "SOR", $rootPath = "", $squareCards = false, $overwriteImages = false)
{
    _requireImagick();

    // ImageMagick resolves relative paths against the native process working directory,
    // which can differ from PHP's script directory under Apache on Windows. Normalize the
    // game root once so downloads, Imagick reads/writes, and derived assets use absolute paths.
    if ($rootPath === "") {
        $candidateRootPath = __DIR__;
    } elseif (preg_match('~^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)~', $rootPath)) {
        $candidateRootPath = $rootPath;
    } else {
        $candidateRootPath = __DIR__ . DIRECTORY_SEPARATOR . $rootPath;
    }
    $resolvedRootPath = realpath($candidateRootPath);
    if ($resolvedRootPath === false || !is_dir($resolvedRootPath)) {
        throw new Exception("Invalid image root path: $rootPath");
    }
    $rootPath = rtrim($resolvedRootPath, "/\\") . DIRECTORY_SEPARATOR;

    $filename      = $rootPath . "WebpImages/" . $cardID . ".webp";
    $concatFilename = $rootPath . "concat/" . $cardID . ".webp";
    $cropFilename  = $rootPath . "crops/" . $cardID . "_cropped.png";
    $isNew = false;

    if ($overwriteImages) {
        foreach ([$filename, $concatFilename, $cropFilename] as $f) {
            if (file_exists($f)) unlink($f);
        }
    }

    // ── Download + resize to webp ──────────────────────────────────────────────
    if (!file_exists($filename)) {
        echo "Image for $cardID does not exist.<br>";

        $tempDir = $rootPath . "TempImages/";
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $urlExtension = pathinfo($url, PATHINFO_EXTENSION);
        $tempName = $tempDir . $cardID . "." . $urlExtension;

        $ch = curl_init($url);
        $fp = fopen($tempName, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($curlError) {
            echo "Failed to download image for $cardID from $url: $curlError<br>";
            if (file_exists($tempName)) unlink($tempName);
            return;
        }

        echo "Image for $cardID successfully retrieved.<br>";

        // new Imagick() throws if the download is corrupt / not an image, which aborts the run.
        $image = new Imagick($tempName);
        if (!$squareCards) {
            if ($definedType == "Base" || $definedType == "Leader") {
                if ($image->getImageHeight() > $image->getImageWidth()) {
                    $image->rotateimage(new ImagickPixel('none'), -90);
                }
                // bestfit=true resizeImage is CSS "contain" (fit inside the box, preserving
                // aspect) — it does NOT guarantee the output is exactly 628x450, only that it
                // fits within that box. Real card scans have per-card micro-variance in source
                // aspect ratio, so bestfit alone produces a slightly different canvas size per
                // card (e.g. 628x449 vs 627x450) with no fixed anchor point. Any fixed-pixel crop
                // downstream (the identity-banner Base/Leader crops below) then lands at a
                // slightly different spot on the card for every card, occasionally clipping into
                // the card's own border. _resizeCover forces an identical, deterministic
                // 628x450 canvas for every card (CSS "cover": scale to fill, crop the overflow),
                // so downstream fixed-pixel crops are reliable across the whole card pool.
                _resizeCover($image, 628, 450);
            } elseif ($definedType == "LeaderUnit") {
                // Leader unit-side arrives landscape; rotate to portrait before resizing.
                if ($image->getImageWidth() > $image->getImageHeight()) {
                    $image->rotateimage(new ImagickPixel('none'), 90);
                }
                $image->resizeImage(450, 628, Imagick::FILTER_LANCZOS, 1, true);
            } else {
                $image->resizeImage(450, 628, Imagick::FILTER_LANCZOS, 1, true);
            }
        }
        $image->setImageFormat('webp');
        if (!$image->writeImage($filename)) throw new Exception("Imagick failed to write webp for $cardID.");
        $image->clear(); $image->destroy();
        unlink($tempName);
        $isNew = true;
    }

    // ── Concat (450×450 square for arena display) ──────────────────────────────
    if (!file_exists($concatFilename) && file_exists($filename)) {
        echo "Concat image for $cardID does not exist. Converting: $filename<br>";

        if ($squareCards) {
            copy($filename, $concatFilename);
            echo "Image for $cardID successfully copied to concat (square cards).<br>";
        } elseif ($definedType === "Unit" || $definedType === "LeaderUnit") {
            // Inset 14px on top/left; take 420×420 (trims the card border off the
            // art), then scale back up to a uniform 450×450 like the other types.
            _concatSingleCrop($filename, $concatFilename, $cardID, 14, 14, 420, 420, 450, 450);
        } elseif ($definedType === "Event") {
            // Top 140px from y=20; bottom 260px from y=310; stacked → 400px,
            // then scaled up to a uniform 450×450 like the other types.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:20, topH:140, botSrcY:310, botH:260, outW:450, outH:450);
        } elseif ($definedType === "Upgrade" || $definedType === "Token Upgrade" || $definedType === "Token Unit") {
            // Top 342px from y=14; bottom 80px from y=516 (keeps the bottom stat boxes);
            // stacked → 422px, then scaled up to a uniform 450×450. Token Upgrades
            // (Experience SOR_T01) AND Token Units print their stats at the BOTTOM like
            // upgrades, so they use this crop — not the unit single-crop (which would
            // chop the stats) or the legacy default.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:14, topH:342, botSrcY:516, botH:80, outW:450, outH:450);
        } elseif ($definedType === "Leader" || $definedType === "Base") {
            // Leader-front and Base sources are landscape (628x450 post-resize), unlike every
            // other type here (portrait 450x628) — the two-section crop below assumes a portrait
            // source and would read past the bottom edge (y=595 on a 450-tall image) if reused.
            // Take a centered 420x420 square instead (same inset spirit as the Unit crop), then
            // scale up to the uniform 450×450 like the other types.
            _concatSingleCrop($filename, $concatFilename, $cardID, 104, 15, 420, 420, 450, 450);
        } else {
            // Fallback: top 400px from y=15 + bottom 10px from y=595; stacked → 410px, then
            // scaled up to a uniform 450×450. Only reachable for a type not covered above.
            _concatTwoSection($filename, $concatFilename, $cardID,
                topSrcY:15, topH:400, botSrcY:595, botH:10, outW:450, outH:450);
        }
    }

    // ── Crop (art thumbnail for hover/tooltip) ─────────────────────────────────
    if (!file_exists($cropFilename) && file_exists($filename)) {
        echo "Crop image for $cardID does not exist.<br>";
        $image = new Imagick($filename);
        if ($definedType == "Event") {
            $image->cropImage(350, 246, 50, 326);
        } elseif ($definedType == "Leader") {
            // Leader cards uniquely split the frame into a left-side character portrait (~0-210px)
            // and a right-side rules-text box (~210-450px) — the shared default crop window
            // straddles that boundary and mostly captures the text box. Crop tight to the portrait
            // instead, matching how Unit/Base art crops (which are full-bleed, no text overlap)
            // already look clean with the default window.
            $image->cropImage(200, 350, 10, 60);
        } elseif ($definedType == "Base") {
            // Base fronts are landscape (628x450 post-resize, now a deterministic canvas via
            // _resizeCover above): cost pip top-left, name banner across the top (~y=0-130), then
            // art, with an ability text box near the bottom on bases that have one. This crop is a
            // wide band of the scene BELOW the name banner. The identity banner then object-fit:cover
            // centers it, so the visible slice's HEIGHT (not width) sets the zoom — a taller crop
            // reveals more of the scene (an earlier 120px band mostly caught the empty top of the
            // art, e.g. the Chopper Base hangar ceiling instead of the ship). 175px tall from y=125
            // ends at y=300; the tallest text boxes (Sundari Palace TS26_012 / Nabat Village JTL_028,
            // 3-line, box top ~y=280) sit far enough to the sides that center-cover cropping keeps
            // them out of frame — verified against both.
            $image->cropImage(560, 175, 34, 125);
        } else {
            $image->cropImage(350, 270, 50, 100);
        }
        $image->setImageFormat('png');
        $image->writeImage($cropFilename);
        $image->clear(); $image->destroy();
        if (file_exists($cropFilename)) echo "Image for $cardID successfully converted to crops.<br>";
    }
}

// Single straight crop → ($outW×$outH) output. $outW/$outH default to the crop
// size; when larger, the crop is scaled up to fill (e.g. 420 crop → 450 output).
function _concatSingleCrop($src, $dest, $cardID, $srcX, $srcY, $w, $h, $outW = null, $outH = null)
{
    $outW = $outW ?? $w;
    $outH = $outH ?? $h;
    _requireImagick();
    $image = new Imagick($src);
    $image->cropImage($w, $h, $srcX, $srcY);
    $image->setImagePage($w, $h, 0, 0);
    if ($outW !== $w || $outH !== $h) {
        $image->resizeImage($outW, $outH, Imagick::FILTER_LANCZOS, 1);
    }
    $image->setImageFormat('webp');
    $image->writeImage($dest);
    $image->clear(); $image->destroy();
    if (file_exists($dest)) echo "Image for $cardID successfully converted to concat.<br>";
}

// Two-section stacked concat. Sections stack to 450×(topH+botH); when $outW/$outH
// are given and differ, the stacked result is scaled to that final size.
function _concatTwoSection($src, $dest, $cardID, $topSrcY, $topH, $botSrcY, $botH, $outW = null, $outH = null)
{
    $stackH = $topH + $botH;
    $finalW = $outW ?? 450;
    $finalH = $outH ?? $stackH;
    _requireImagick();
    $image = new Imagick($src);

    $imageTop = $image->clone();
    $imageTop->cropImage(450, $topH, 0, $topSrcY);
    $imageTop->setImagePage(450, $topH, 0, 0);

    $imageBot = $image->clone();
    $imageBot->cropImage(450, $botH, 0, $botSrcY);
    $imageBot->setImagePage(450, $botH, 0, 0);

    $out = new Imagick();
    $out->newImage(450, $stackH, new ImagickPixel('transparent'));
    $out->compositeImage($imageTop, Imagick::COMPOSITE_DEFAULT, 0, 0);
    $out->compositeImage($imageBot,  Imagick::COMPOSITE_DEFAULT, 0, $topH);
    if ($finalW !== 450 || $finalH !== $stackH) {
        $out->resizeImage($finalW, $finalH, Imagick::FILTER_LANCZOS, 1);
    }
    $out->setImageFormat('webp');
    $out->writeImage($dest);

    $image->clear();    $image->destroy();
    $imageTop->clear(); $imageTop->destroy();
    $imageBot->clear(); $imageBot->destroy();
    $out->clear();      $out->destroy();

    if (file_exists($dest)) echo "Image for $cardID successfully converted to concat.<br>";
}
