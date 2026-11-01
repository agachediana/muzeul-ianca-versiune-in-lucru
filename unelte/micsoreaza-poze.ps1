<#
  Micsoreaza pozele site-ului: cel mult 1600 px latime si, daca se poate,
  sub 200 KB fiecare (tinta din ghidul lui Cristian). Incearca intai calitate 80,
  apoi 72, apoi latimi mai mici, si pastreaza varianta doar daca e mai mica.
  Originalele se copiaza inainte intr-un folder de backup, cu aceeasi structura.
  Folderul iconite nu se atinge.

  Folosire:
    powershell -File unelte\micsoreaza-poze.ps1 C:\xampp\htdocs\joomla\images\muzeu C:\xampp\backup_imagini
#>
param(
  [Parameter(Mandatory = $true)][string]$Folder,
  [Parameter(Mandatory = $true)][string]$Backup
)

Add-Type -AssemblyName System.Drawing
$jpeg = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
$tinta = 200KB

# salveaza poza la latimea si calitatea date si intoarce octetii
function Encode($img, [int]$latime, [long]$calitate) {
  $w = [Math]::Min($latime, $img.Width)
  $h = [int][Math]::Round($img.Height * $w / $img.Width)
  $bmp = New-Object System.Drawing.Bitmap $w, $h
  $g = [System.Drawing.Graphics]::FromImage($bmp)
  $g.InterpolationMode = 'HighQualityBicubic'
  $g.PixelOffsetMode = 'HighQuality'
  $g.DrawImage($img, 0, 0, $w, $h)
  $g.Dispose()
  $p = New-Object System.Drawing.Imaging.EncoderParameters(1)
  $p.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, $calitate)
  $ms = New-Object IO.MemoryStream
  $bmp.Save($ms, $jpeg, $p)
  $bmp.Dispose()
  return , $ms.ToArray()
}

$poze = Get-ChildItem $Folder -Recurse -File -Include *.jpg, *.jpeg | Where-Object { $_.FullName -notmatch '\\iconite\\' }
foreach ($f in $poze) {
  $rel = $f.FullName.Substring($Folder.TrimEnd('\').Length + 1)
  $copie = Join-Path $Backup $rel
  if (-not (Test-Path $copie)) {
    New-Item -ItemType Directory -Force (Split-Path $copie) | Out-Null
    Copy-Item $f.FullName $copie
  }

  $bytes = [IO.File]::ReadAllBytes($f.FullName)
  try {
    $ms = New-Object IO.MemoryStream(, $bytes)
    $img = [System.Drawing.Image]::FromStream($ms)
  } catch {
    "sarit (nu e jpg obisnuit): $rel"; continue
  }

  # pozele deja mici nu se mai ating (fiecare recomprimare pierde din calitate)
  if ($bytes.Length -le $tinta -and $img.Width -le 1600) { $img.Dispose(); $ms.Dispose(); continue }

  # incercarile, de la cea mai buna calitate la cea mai mica (afisele cu mult text ajung la ultimele)
  $best = $null
  foreach ($pas in @(@(1600, 80), @(1600, 72), @(1400, 72), @(1200, 70), @(1000, 68), @(900, 65))) {
    $best = Encode $img $pas[0] $pas[1]
    if ($best.Length -le $tinta) { break }
  }
  $img.Dispose(); $ms.Dispose()

  if ($best.Length -lt $bytes.Length) {
    [IO.File]::WriteAllBytes($f.FullName, $best)
    "{0,-70} {1,5} KB -> {2,4} KB" -f $rel, [Math]::Round($bytes.Length / 1KB), [Math]::Round($best.Length / 1KB)
  }
}
