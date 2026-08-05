param(
    [string]$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$Output = (Join-Path $PSScriptRoot '..\..\HellbreakSim\CardData\CardFaceReviewQueue.json')
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
$null = [Windows.Storage.StorageFile,Windows.Storage,ContentType=WindowsRuntime]
$null = [Windows.Storage.Streams.IRandomAccessStreamWithContentType,Windows.Storage.Streams,ContentType=WindowsRuntime]
$null = [Windows.Graphics.Imaging.BitmapDecoder,Windows.Graphics.Imaging,ContentType=WindowsRuntime]
$null = [Windows.Graphics.Imaging.SoftwareBitmap,Windows.Graphics.Imaging,ContentType=WindowsRuntime]
$null = [Windows.Media.Ocr.OcrEngine,Windows.Foundation,ContentType=WindowsRuntime]
$null = [Windows.Media.Ocr.OcrResult,Windows.Foundation,ContentType=WindowsRuntime]

$asTask = ([System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
    $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1
})[0]
function Wait-WinRt($operation, [Type]$resultType) {
    $task = $asTask.MakeGenericMethod($resultType).Invoke($null, @($operation))
    $task.Wait()
    return $task.Result
}
function Read-OcrText([string]$path, $engine) {
    $file = Wait-WinRt ([Windows.Storage.StorageFile]::GetFileFromPathAsync($path)) ([Windows.Storage.StorageFile])
    $stream = Wait-WinRt ($file.OpenReadAsync()) ([Windows.Storage.Streams.IRandomAccessStreamWithContentType])
    try {
        $decoder = Wait-WinRt ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
        $bitmap = Wait-WinRt ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
        try {
            $result = Wait-WinRt ($engine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])
            return (($result.Text -replace '\s+', ' ').Trim())
        } finally { if ($bitmap) { $bitmap.Dispose() } }
    } finally { $stream.Dispose() }
}
function Normalize-Words([string]$value) {
    return @(($value.ToLowerInvariant() -replace '[^a-z0-9]+', ' ').Split(' ', [StringSplitOptions]::RemoveEmptyEntries) | Where-Object { $_.Length -ge 3 })
}

$cache = Get-Content -Raw (Join-Path $Root 'HellbreakSim\GeneratedCode\cardArrayCache.json') | ConvertFrom-Json
$reviewed = Get-Content -Raw (Join-Path $Root 'HellbreakSim\CardData\ReviewedCardFaces.json') | ConvertFrom-Json
$reviewedIds = @{}
$reviewed.cards.psobject.Properties | ForEach-Object { $reviewedIds[$_.Name] = $true }
$engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
if (-not $engine) { throw 'Windows OCR is unavailable for the current language profile.' }

$tempRoot = Join-Path ([IO.Path]::GetTempPath()) 'TCGEngine-HellbreakOcr'
[IO.Directory]::CreateDirectory($tempRoot) | Out-Null
$converter = Join-Path $Root 'DevTools\Hellbreak\convert-review-image.php'
$records = [ordered]@{}
foreach ($card in $cache.cardArray) {
    if ($reviewedIds.ContainsKey([string]$card.id)) { continue }
    $webp = Join-Path $Root ('HellbreakSim\WebpImages\' + $card.id + '.webp')
    if (-not (Test-Path -LiteralPath $webp) -or (Get-Item -LiteralPath $webp).Length -lt 8000) { continue }
    $png = Join-Path $tempRoot ($card.id + '.png')
    try {
        & php $converter $webp $png
        if ($LASTEXITCODE -ne 0) { throw "Image conversion failed for $($card.id)." }
        $ocrText = Read-OcrText (Resolve-Path $png).Path $engine
        $nameWords = Normalize-Words ([string]$card.name)
        $ocrWords = Normalize-Words $ocrText
        $matched = @($nameWords | Where-Object { $ocrWords -contains $_ }).Count
        $score = if ($nameWords.Count) { [Math]::Round($matched / $nameWords.Count, 2) } else { 0 }
        $typeMatch = $ocrWords -contains ([string]$card.type).ToLowerInvariant()
        $confidence = if ($score -ge 0.8 -and $typeMatch) { 'high' } elseif ($score -ge 0.5) { 'medium' } else { 'low' }
        $records[[string]$card.id] = [ordered]@{
            name = [string]$card.name
            type = [string]$card.type
            status = 'needs_review'
            identityConfidence = $confidence
            nameWordMatch = $score
            typeMatched = $typeMatch
            ocrText = $ocrText
            image = 'HellbreakSim/WebpImages/' + $card.id + '.webp'
            imageSha256 = (Get-FileHash -Algorithm SHA256 -LiteralPath $webp).Hash.ToLowerInvariant()
        }
    } finally {
        if (Test-Path -LiteralPath $png) { Remove-Item -LiteralPath $png }
    }
}

$overridesPath = Join-Path $Root 'HellbreakSim\CardData\CardFaceReviewOverrides.json'
if (Test-Path -LiteralPath $overridesPath) {
    $overrides = Get-Content -Raw $overridesPath | ConvertFrom-Json
    foreach ($overrideProperty in $overrides.cards.psobject.Properties) {
        if (-not $records.Contains($overrideProperty.Name)) { continue }
        foreach ($field in $overrideProperty.Value.psobject.Properties) {
            $records[$overrideProperty.Name][$field.Name] = $field.Value
        }
    }
}

$payload = [ordered]@{
    version = 1
    extractedAt = (Get-Date).ToUniversalTime().ToString('o')
    method = 'Windows.Media.Ocr candidate extraction; no gameplay fields promoted without manual visual review'
    cards = $records
}
$json = $payload | ConvertTo-Json -Depth 12
$outputPath = if ([IO.Path]::IsPathRooted($Output)) { [IO.Path]::GetFullPath($Output) } else { Join-Path $Root $Output }
[IO.File]::WriteAllText($outputPath, $json + [Environment]::NewLine, [Text.UTF8Encoding]::new($false))
Write-Output ("Queued {0} card faces: {1} high, {2} medium, {3} low identity confidence." -f @(
    $records.Count,
    @($records.Values | Where-Object identityConfidence -eq 'high').Count,
    @($records.Values | Where-Object identityConfidence -eq 'medium').Count,
    @($records.Values | Where-Object identityConfidence -eq 'low').Count
))
