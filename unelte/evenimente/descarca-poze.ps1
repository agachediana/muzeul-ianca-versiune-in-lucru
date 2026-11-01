<#
  Importul evenimentelor, pasul 4 din 5.
  Descarca pozele evenimentelor de pe serverele HelloWeb, le micsoreaza la cel mult
  1600 px si le pune in Joomla, in images/muzeu/evenimente/<an>/.

  Folosire:
    powershell -File descarca-poze.ps1 date\evenimente.json C:\xampp\htdocs\joomla C:\temp\poze
#>
param([string]$EventsJson, [string]$JoomlaRoot, [string]$TempDir)

Add-Type -AssemblyName System.Drawing
$enc = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
$p = New-Object System.Drawing.Imaging.EncoderParameters(1)
$p.Param[0] = [System.Drawing.Imaging.EncoderParameter]::new([System.Drawing.Imaging.Encoder]::Quality, [long]82)
New-Item -ItemType Directory -Force $TempDir | Out-Null

$events = Get-Content $EventsJson -Raw -Encoding UTF8 | ConvertFrom-Json
foreach ($ev in $events) {
	foreach ($ph in $ev.photos) {
		$target = Join-Path $JoomlaRoot ($ph.file -replace '/', '\')
		New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
		$tmp = Join-Path $TempDir ([IO.Path]::GetFileName($ph.file))
		try {
			Invoke-WebRequest $ph.src -OutFile $tmp -UseBasicParsing -TimeoutSec 60
			$src = [System.Drawing.Image]::FromFile($tmp)
			$w = [Math]::Min(1600, $src.Width)
			$h = [int]($src.Height * $w / $src.Width)
			$bmp = New-Object System.Drawing.Bitmap($w, $h)
			$g = [System.Drawing.Graphics]::FromImage($bmp)
			$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
			$g.DrawImage($src, 0, 0, $w, $h)
			$src.Dispose(); $g.Dispose()
			$bmp.Save($target, $enc, $p); $bmp.Dispose()
			"{0}  {1}x{2}  {3} KB" -f $ph.file, $w, $h, [math]::Round((Get-Item $target).Length / 1KB)
		} catch {
			"EȘUAT {0}: {1}" -f $ph.file, $_.Exception.Message
		}
	}
}
