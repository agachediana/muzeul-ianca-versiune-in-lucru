<?php
/*
  Harta Google, folosita pe Acasa si pe pagina Vizită și bilete.
  Se incarca doar cand vizitatorul apasa butonul (vezi initMapGate din template.js),
  ca pana atunci sa nu plece nicio cerere spre Google.
  Are nevoie de $params (setarile temei) si $e (escape), din fisierul care o include.
*/

defined('_JEXEC') or die;

$mapQ = rawurlencode((string) $params->get('mapQuery'));
?>
<div class="visit__map reveal" style="--d:1" data-map="https://www.google.com/maps?q=<?php echo $mapQ; ?>&amp;output=embed">
	<div class="map-gate">
		<span class="map-gate__pin" aria-hidden="true"><svg><use href="#i-pin"/></svg></span>
		<p class="map-gate__title">Te așteptăm la muzeu</p>
		<?php if ($params->get('adresa')) : ?><p class="map-gate__addr"><?php echo $e($params->get('adresa')); ?></p><?php endif; ?>
		<button type="button" class="btn map-gate__btn" data-map-load><span data-map-label>Vreau să văd locația</span> <svg><use href="#i-arrow"/></svg></button>
		<p class="map-gate__note">Harta Google apare doar după ce apeși butonul. Poți să o deschizi și direct în <a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo $mapQ; ?>" target="_blank" rel="noopener">Google Maps</a>.</p>
	</div>
</div>
