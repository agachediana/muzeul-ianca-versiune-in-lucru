<?php
/*
  Logo-ul Mi, desenat doar din contur
  - prima linie a lui M e o maneca de ie, cu altita si randuri brodate
  - i e un stalp de lemn, cu capitel, baza si o cruce de sprijin
  - punctul de pe i e o roata de caruta (se invarte la hover, vezi CSS)
*/

defined('_JEXEC') or die;
?>
<svg class="logo" viewBox="0 10 106 86" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true" focusable="false">
	<!-- M, prima linie: maneca de ie -->
	<rect x="5" y="20" width="15" height="72" rx="1.5"/>
	<path d="M5 26h15M5 33h15M5 88h15" stroke-width="1.5"/>
	<path d="M5 31.5l2.5-3.5 2.5 3.5 2.5-3.5 2.5 3.5 2.5-3.5 2.5 3.5" stroke-width="1.3"/>
	<path d="M12.5 37.5l4.5 6-4.5 6-4.5-6zM12.5 49.5l4.5 6-4.5 6-4.5-6zM12.5 61.5l4.5 6-4.5 6-4.5-6zM12.5 73.5l4.5 6-4.5 6-4.5-6z" stroke-width="1.5"/>
	<!-- M, diagonalele si a doua linie -->
	<path d="M20 20h6l12 30 12-30h6L41 58h-6z"/>
	<rect x="56" y="20" width="14" height="72" rx="1.5"/>
	<!-- i, stalpul de lemn -->
	<path d="M76 44h24l-3 5H79z"/>
	<rect x="81" y="49" width="14" height="37"/>
	<path d="M81 55l14 14M95 55L81 69M81 72h14M88 75v8" stroke-width="1.5"/>
	<path d="M79 86h18l3 6H76z"/>
	<!-- punctul de pe i, roata de caruta -->
	<g class="logo__wheel">
		<circle cx="88" cy="25" r="11"/>
		<circle cx="88" cy="25" r="8.2" stroke-width="1.4"/>
		<circle cx="88" cy="25" r="2.4" stroke-width="1.6"/>
		<path d="M90.4 25h5.8M89.7 26.7l4.1 4.1M88 27.4v5.8M86.3 26.7l-4.1 4.1M85.6 25h-5.8M86.3 23.3l-4.1-4.1M88 22.6v-5.8M89.7 23.3l4.1-4.1" stroke-width="1.4"/>
	</g>
</svg>
