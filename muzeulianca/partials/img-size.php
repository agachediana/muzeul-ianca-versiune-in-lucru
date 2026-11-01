<?php
/*
  Latimea si inaltimea unei poze de pe site, gata de pus in <img>.
  Cu ele browserul tine locul pozei pana se incarca, asa ca pagina nu mai sare.
  Pozele de pe alt site sau care nu se gasesc raman fara (intoarce text gol).

  Se foloseste asa:
    $size = require __DIR__ . '/img-size.php';
    <img src="..."<?php echo $size($url); ?> alt="">
*/

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

return static function (string $url): string {
	static $cache = [];

	if ($url === '' || preg_match('#^(https?:)?//#', $url)) {
		return '';
	}
	if (!isset($cache[$url])) {
		// adresa pozei (/joomla/images/...) devine calea fisierului de pe disc
		$path = strtok($url, '?#');
		$base = Uri::root(true);
		if ($base !== '' && str_starts_with($path, $base . '/')) {
			$path = substr($path, strlen($base));
		}
		$file = JPATH_ROOT . '/' . ltrim(rawurldecode($path), '/');
		$info = is_file($file) ? @getimagesize($file) : false;

		$cache[$url] = $info ? ' width="' . $info[0] . '" height="' . $info[1] . '"' : '';
	}

	return $cache[$url];
};
