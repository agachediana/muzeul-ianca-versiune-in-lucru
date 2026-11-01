<?php
/*
  Afisarea campurilor de tip Calendar (de exemplu data evenimentului)
  in romana si fara ora cand evenimentul nu are ora, adica "24 ianuarie 2020".
  Joomla salveaza data in UTC, aici se trece in ora site-ului.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$value = $field->value;
if ($value === '' || $value === null) {
	return;
}
if (is_array($value)) {
	$value = reset($value);
}

$luni = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];

try {
	$date = new DateTime($value, new DateTimeZone('UTC'));
	$date->setTimezone(new DateTimeZone(Factory::getApplication()->get('offset', 'Europe/Bucharest')));
} catch (\Exception $ex) {
	echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

	return;
}

$text = $date->format('j') . ' ' . $luni[(int) $date->format('n') - 1] . ' ' . $date->format('Y');
if ($date->format('H:i') !== '00:00') {
	$text .= ', ora ' . $date->format('H:i');
}

echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
