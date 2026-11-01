<#
  Blureaza pozele din poze-ascunse.txt in copia statica (docs/assets),
  ca fetele copiilor sa nu se mai recunoasca, si scrie pe ele
  "Poza blurata temporar". Poza ramane cu acelasi nume si aceeasi
  marime, deci paginile nu trebuie schimbate.

  Folosire:  powershell -File unelte\ascunde-poze.ps1 github-repo\docs\assets
#>
param([Parameter(Mandatory = $true)][string]$Assets)

Add-Type -AssemblyName System.Drawing
$lista = Join-Path $PSScriptRoot 'poze-ascunse.txt'
$nume = Get-Content $lista -Encoding UTF8 | Where-Object { $_ -and -not $_.StartsWith('#') } | ForEach-Object { $_.Trim() }

$jpeg = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
$calitate = New-Object System.Drawing.Imaging.EncoderParameters(1)
$calitate.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, [long]80)

foreach ($n in $nume) {
  $cale = Join-Path $Assets $n
  if (-not (Test-Path $cale)) { "lipseste: $n"; continue }

  # citesc poza in memorie, ca fisierul sa poata fi suprascris dupa
  $bytes = [IO.File]::ReadAllBytes($cale)
  $ms = New-Object IO.MemoryStream(, $bytes)
  $orig = [System.Drawing.Image]::FromStream($ms)
  $w = $orig.Width; $h = $orig.Height

  # estompare: o micsorez foarte mult (24 px lat) si o maresc la loc
  $mic = New-Object System.Drawing.Bitmap 24, ([Math]::Max(1, [int](24 * $h / $w)))
  $g = [System.Drawing.Graphics]::FromImage($mic)
  $g.InterpolationMode = 'HighQualityBicubic'
  $g.DrawImage($orig, 0, 0, $mic.Width, $mic.Height)
  $g.Dispose()

  $nou = New-Object System.Drawing.Bitmap $w, $h
  $g = [System.Drawing.Graphics]::FromImage($nou)
  $g.InterpolationMode = 'HighQualityBicubic'
  $g.PixelOffsetMode = 'HighQuality'
  $g.DrawImage($mic, (New-Object System.Drawing.Rectangle(-[int]($w * .04), -[int]($h * .04), [int]($w * 1.08), [int]($h * 1.08))))

  # un val inchis peste poza, ca textul sa se citeasca
  $val = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(110, 42, 13, 18))
  $g.FillRectangle($val, 0, 0, $w, $h)

  # textul, centrat
  $g.TextRenderingHint = 'AntiAliasGridFit'
  $centru = New-Object System.Drawing.StringFormat
  $centru.Alignment = 'Center'; $centru.LineAlignment = 'Center'
  $mare = New-Object System.Drawing.Font('Georgia', [single]([Math]::Max(18, $w / 24)), [System.Drawing.FontStyle]::Italic)
  $crem = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(251, 248, 242))
  # dimensiunile se calculeaza separat (in PowerShell virgula se leaga inaintea inmultirii)
  $g.DrawString('Poză blurată temporar', $mare, $crem, (New-Object System.Drawing.RectangleF([single]0, [single]0, [single]$w, [single]$h)), $centru)
  $g.Dispose()

  $orig.Dispose(); $ms.Dispose(); $mic.Dispose()
  $nou.Save($cale, $jpeg, $calitate)
  $nou.Dispose()
  "estompata: $n"
}
