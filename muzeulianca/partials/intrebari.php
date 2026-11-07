<?php
/*
  Imparte textul unui articol in doua: textul de la inceput si intrebarile.
  Fiecare subtitlu (h3) din articol devine o intrebare, iar tot ce e sub el,
  pana la urmatorul subtitlu, e raspunsul. Pe pagina apar ca o lista care se deschide.
  Intoarce ['inceput' => html, 'intrebari' => [['q' => text, 'a' => html], ...]].

  Se foloseste asa:
    $parti = (require __DIR__ . '/intrebari.php')($item->text);
*/

defined('_JEXEC') or die;

return static function (string $html): array {
	$parti = ['inceput' => '', 'intrebari' => []];
	if (trim(strip_tags($html, '<img><iframe>')) === '') {
		return $parti;
	}
	if (stripos($html, '<h3') === false) {
		$parti['inceput'] = $html;

		return $parti;
	}

	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="utf-8"?><div id="radacina">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();
	$root = $doc->getElementById('radacina');

	$cur = null;
	foreach (iterator_to_array($root->childNodes) as $node) {
		if ($node instanceof DOMElement && strtolower($node->nodeName) === 'h3') {
			$parti['intrebari'][] = ['q' => trim($node->textContent), 'a' => ''];
			$cur = count($parti['intrebari']) - 1;
			continue;
		}
		$bucata = $doc->saveHTML($node);
		if ($cur === null) {
			$parti['inceput'] .= $bucata;
		} else {
			$parti['intrebari'][$cur]['a'] .= $bucata;
		}
	}

	return $parti;
};
