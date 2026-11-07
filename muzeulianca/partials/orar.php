<?php
/*
  Programul muzeului pe zile, din campul "Program pe zile" al temei.
  Fiecare rand e o zi, de exemplu "Marți: 10:00-14:00" sau "Luni: închis".
  Intoarce cele 7 zile, de luni pana duminica:
    ['zi' => 'Marți', 'deschis' => '10:00', 'inchis' => '14:00']
  La zilele inchise deschis si inchis sunt null.

  Se foloseste asa:
    $orar = (require __DIR__ . '/orar.php')($params);
*/

defined('_JEXEC') or die;

return static function ($params): array {
	$zile  = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
	$cheie = static fn (string $s) => strtr(mb_strtolower(trim($s)), ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't']);

	$text = (string) $params->get('orar', '');
	if (trim($text) === '') {
		$text = "Luni: închis\nMarți: 10:00-14:00\nMiercuri: 10:00-14:00\nJoi: 10:00-14:00\nVineri: 10:00-14:00\nSâmbătă: 10:00-14:00\nDuminică: 10:00-14:00";
	}

	// randurile scrise in setari, dupa numele zilei
	$scris = [];
	foreach (preg_split('/\R/u', $text) as $rand) {
		if (!str_contains($rand, ':')) {
			continue;
		}
		[$zi, $ore] = explode(':', $rand, 2);
		$scris[$cheie($zi)] = $ore;
	}

	$orar = [];
	foreach ($zile as $zi) {
		$ore = $scris[$cheie($zi)] ?? '';
		preg_match('/(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})/u', $ore, $m);
		$orar[] = [
			'zi'      => $zi,
			'deschis' => $m ? sprintf('%02d:%s', $m[1], $m[2]) : null,
			'inchis'  => $m ? sprintf('%02d:%s', $m[3], $m[4]) : null,
		];
	}

	return $orar;
};
