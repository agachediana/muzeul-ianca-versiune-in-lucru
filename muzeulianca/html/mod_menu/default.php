<?php
/*
  Meniul principal (inlocuieste afisarea standard a modulului mod_menu)

  Elementele de pe primul nivel care au si sub-submeniuri devin automat un
  meniu mai lat (de exemplu Despre, Muzeul in imagini). Se poate forta asta
  punand clasa mega in campul Link Class al elementului de meniu.

  Variabilele vin de la mod_menu: $list, $path, $active_id, $default_id, $params
*/

defined('_JEXEC') or die;

$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// nivelul de baza al meniului, de obicei 1
$base = $list ? (int) reset($list)->level : 1;

// elementele de pe primul nivel care au nepoti primesc meniul lat
$mega = [];
foreach ($list as $item) {
	if ((int) $item->level >= $base + 2 && isset($item->tree[$base - 1])) {
		$mega[$item->tree[$base - 1]] = true;
	}
}

$chev = '<svg class="nav__chev" aria-hidden="true"><use href="#i-chev"/></svg>';
?>
<ul class="nav__list">
<?php foreach ($list as $i => $item) :
	$itemParams = $item->getParams();
	$isTop      = (int) $item->level === $base;
	$isCurrent  = $item->id == $active_id
		|| ($item->type === 'alias' && $itemParams->get('aliasoptions') == $active_id);
	$isActive   = in_array($item->id, $path);
	$anchorCss  = trim((string) $itemParams->get('menu-anchor_css', ''));
	$hasSub     = (bool) $item->deeper;

	$classes = [$isTop ? 'nav__item' : 'nav__subitem', 'item-' . $item->id];
	if ($hasSub && $isTop) {
		$classes[] = 'has-sub';
	}
	if ($isTop && (!empty($mega[$item->id]) || preg_match('/\bmega\b/', $anchorCss))) {
		$classes[] = 'nav__item--mega';
	}
	if ($isActive) {
		$classes[] = 'is-active';
	}

	// linkul (separatoarele si titlurile de grup nu au)
	$title  = $item->title;
	$href   = in_array($item->type, ['separator', 'heading'], true) ? '' : $item->flink;
	$target = $href !== '' && (int) $item->browserNav === 1 ? ' target="_blank" rel="noopener"' : '';
	$aria   = $isCurrent ? ' aria-current="page"' : '';

	echo '<li class="' . $e(implode(' ', $classes)) . '">';

	if ($isTop) {
		// elementele cu submeniu sunt butoane, submeniul se deschide la hover sau click
		if ($hasSub) {
			echo '<button class="nav__link" type="button" aria-expanded="false">' . $e($title) . ' ' . $chev . '</button>';
		} else {
			echo '<a class="nav__link" href="' . $e($href) . '"' . $target . $aria . '>' . $e($title) . '</a>';
		}
	} elseif ($href === '') {
		echo '<a role="presentation">' . $e($title) . '</a>';
	} else {
		echo '<a href="' . $e($href) . '"' . $target . $aria . '>' . $e($title) . '</a>';
	}

	if ($item->deeper) {
		echo '<ul class="nav__sub">';
	} elseif ($item->shallower) {
		echo '</li>';
		echo str_repeat('</ul></li>', $item->level_diff);
	} else {
		echo '</li>';
	}
endforeach; ?>
</ul>
