<?php
/*
  Pagina Evenimente (aspect separat pentru categoria evenimente)

  Sus e pus in fata urmatorul eveniment. Daca nu e niciunul anuntat, apare cel
  in desfasurare, iar daca nici asta, ultimul realizat.
  Dedesubt sunt toate evenimentele, pe grupe: Urmeaza, In desfasurare, Realizate.
  Cele realizate sunt o arhiva pe ani, cu lista anilor in stanga.
  Filtrele de sus arata doar o grupa (animatia e in template.js, cu GSAP Flip).
  Nu apar autorul, categoria sau numarul de vizualizari.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @var \Joomla\Component\Content\Site\View\Category\HtmlView $this */

$app = Factory::getApplication();
// titlul paginii il pune fisierul asta, nu tema (vezi index.php)
$app->set('muzeulianca.ownHead', true);
$this->document->getWebAssetManager()->useScript('template.muzeulianca.flip');

// descrierea pentru Google: cea a categoriei, altfel textul de sub titlu
if (!trim((string) $this->category->metadesc)) {
	$this->document->setDescription('Lecții de istorie, școli de vară, expoziții, lansări de carte și sărbători ale comunității: evenimentele realizate și cele care urmează la Muzeul Ianca.');
}

$e          = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$loadEvents = require dirname(__DIR__, 3) . '/partials/events-data.php';
$size       = require dirname(__DIR__, 3) . '/partials/img-size.php';
$ev         = $loadEvents((string) $this->category->alias);
$focus      = $ev['focus'];
$total      = count($ev['all']);
$active     = $app->getMenu()->getActive();
$title      = $active && $active->title ? $active->title : $this->category->title;

// numarul fiecarui eveniment, ca intr-un registru (cel mai vechi e 1)
$nr = [];
foreach ($ev['all'] as $i => $it) {
	$nr[$it->id] = $total - $i;
}

// arhiva pe ani, cei mai noi primii
$years = [];
foreach ($ev['trecut'] as $it) {
	$years[$it->year][] = $it;
}

$first = $ev['all'] ? end($ev['all'])->year : '';
$last  = $ev['all'] ? $ev['all'][0]->year : '';

$statusLabel = [
	'viitor' => 'Urmează',
	'acum'   => 'În desfășurare acum',
	'trecut' => 'Cel mai recent eveniment',
];
$lunaLung = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];

$num = static fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
// 1 eveniment, 10 evenimente, 30 de evenimente
$cate = static function (int $n): string {
	$r = $n % 100;

	return $n === 1 ? '1 eveniment' : $n . (($r === 0 && $n > 0) || $r >= 20 ? ' de' : '') . ' evenimente';
};
$datetime = static fn ($it) => $it->start->format($it->monthOnly ? 'Y-m' : 'Y-m-d');

// cardul unui eveniment
$card = static function ($it, bool $wide = false, int $d = 0) use ($e, $nr, $num, $datetime, $size) {
	ob_start(); ?>
	<li class="evc reveal<?php echo $wide ? ' evc--wide' : ''; ?>" style="--d:<?php echo $d % 4; ?>" data-status="<?php echo $it->status; ?>">
		<a class="evc__link" href="<?php echo $e($it->link); ?>">
			<span class="evc__img<?php echo $it->image ? '' : ' ev-noimg'; ?>">
				<?php if ($it->image) : ?><img src="<?php echo $e($it->image); ?>"<?php echo $size($it->image); ?> alt="" loading="lazy" decoding="async"><?php endif; ?>
				<span class="evc__nr" aria-hidden="true">N° <?php echo $num($nr[$it->id]); ?></span>
			</span>
			<span class="evc__body">
				<time class="evc__date" datetime="<?php echo $datetime($it); ?>"><?php echo $e($it->dateLong); ?><?php echo $it->hasTime ? ', ora ' . $it->start->format('H:i') : ''; ?></time>
				<span class="evc__title"><?php echo $e($it->title); ?></span>
				<?php if ($wide && $it->teaser) : ?><span class="evc__teaser"><?php echo $e($it->teaser); ?></span><?php endif; ?>
				<span class="evc__more" aria-hidden="true">Vezi evenimentul <svg><use href="#i-arrow"/></svg></span>
			</span>
		</a>
	</li>
	<?php return ob_get_clean();
};

$filters = [
	'toate'  => ['Toate', $total],
	'viitor' => ['Viitoare', count($ev['viitor'])],
	'acum'   => ['În desfășurare', count($ev['acum'])],
	'trecut' => ['Realizate', count($ev['trecut'])],
];
?>
<div class="evp" data-evp>

	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Știri și evenimente</p>
			<h1 class="evp-head__title"><span><?php echo $e($title); ?></span></h1>
			<div class="evp-head__meta">
				<?php if (trim(strip_tags((string) $this->category->description))) : ?>
					<div class="evp-head__lead"><?php echo $this->category->description; ?></div>
				<?php else : ?>
					<p class="evp-head__lead">Lecții de istorie, școli de vară, expoziții, lansări de carte și sărbători ale comunității: tot ce s-a întâmplat și tot ce urmează la Muzeul Ianca.</p>
				<?php endif; ?>
				<?php if ($total) : ?>
					<p class="evp-head__range"><?php echo $e($cate($total)); ?><?php echo $first !== $last ? ' · ' . $e($first) . '–' . $e($last) : ''; ?></p>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<?php if ($focus) : ?>
		<?php
		$big   = $focus->monthOnly ? $lunaLung[(int) $focus->start->format('n') - 1] : $focus->start->format('j');
		$small = $focus->monthOnly ? $focus->start->format('Y') : $lunaLung[(int) $focus->start->format('n') - 1] . ' ' . $focus->start->format('Y');
		?>
		<section class="evp-focus evp-focus--<?php echo $focus->status; ?>" aria-labelledby="evp-focus-title">
			<div class="wrap evp-focus__grid">
				<a class="evp-focus__media develop<?php echo $focus->image ? '' : ' ev-noimg'; ?>" href="<?php echo $e($focus->link); ?>" tabindex="-1" aria-hidden="true">
					<?php if ($focus->image) : ?><img src="<?php echo $e($focus->image); ?>"<?php echo $size($focus->image); ?> alt="" fetchpriority="high"><?php endif; ?>
					<span class="evc__nr">N° <?php echo $num($nr[$focus->id]); ?></span>
				</a>
				<div class="evp-focus__body">
					<p class="evp-status evp-status--<?php echo $focus->status; ?>"><?php echo $statusLabel[$focus->status]; ?></p>
					<p class="evp-focus__date<?php echo $focus->monthOnly ? ' is-month' : ''; ?>">
						<time datetime="<?php echo $datetime($focus); ?>"><span class="evp-focus__big"><?php echo $e($big); ?></span><span class="evp-focus__small"><?php echo $e($small); ?></span></time>
					</p>
					<h2 class="evp-focus__title" id="evp-focus-title"><a href="<?php echo $e($focus->link); ?>"><?php echo $e($focus->title); ?></a></h2>
					<?php if ($focus->teaser) : ?><p class="evp-focus__teaser"><?php echo $e($focus->teaser); ?></p><?php endif; ?>

					<?php if ($focus->status === 'viitor') : ?>
						<div class="countdown countdown--dark" data-countdown="<?php echo $focus->start->format(DATE_ATOM); ?>" aria-label="Timp rămas până la eveniment">
							<div><strong data-d>0</strong><span>zile</span></div>
							<div><strong data-h>00</strong><span>ore</span></div>
							<div><strong data-m>00</strong><span>min</span></div>
							<div><strong data-s>00</strong><span>sec</span></div>
						</div>
					<?php elseif ($focus->status === 'acum') : ?>
						<p class="evp-focus__until">Se poate vizita până pe <?php echo $e($focus->end->format('j') . ' ' . $lunaLung[(int) $focus->end->format('n') - 1] . ' ' . $focus->end->format('Y')); ?></p>
					<?php endif; ?>

					<a class="btn btn--ghost" href="<?php echo $e($focus->link); ?>">Vezi evenimentul <svg><use href="#i-arrow"/></svg></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="evp-bar" data-evp-bar>
		<div class="wrap evp-bar__inner">
			<div class="tabs evp-filter" role="group" aria-label="Arată evenimentele">
				<span class="tabs__pill" aria-hidden="true"></span>
				<?php foreach ($filters as $key => [$label, $count]) : ?>
					<button type="button" class="tabs__btn" data-filter="<?php echo $key; ?>" aria-pressed="<?php echo $key === 'toate' ? 'true' : 'false'; ?>"><?php echo $label; ?><span class="tabs__count"><?php echo $count; ?></span></button>
				<?php endforeach; ?>
			</div>
			<p class="sr-only" aria-live="polite" data-evp-note></p>
		</div>
	</div>

	<div class="wrap evp-groups">

		<!-- urmeaza -->
		<section class="evg evg--viitor" data-group="viitor" aria-labelledby="evg-viitor">
			<header class="evg__head draw">
				<h2 class="evg__title" id="evg-viitor"><span>Urmează</span></h2>
				<p class="evg__count"><?php echo $ev['viitor'] ? $cate(count($ev['viitor'])) : 'Niciun eveniment anunțat'; ?></p>
			</header>
			<?php if ($ev['viitor']) : ?>
				<ul class="evc-grid evc-grid--next">
					<?php foreach ($ev['viitor'] as $i => $it) { echo $card($it, $i === 0 && count($ev['viitor']) > 1, $i); } ?>
				</ul>
			<?php else : ?>
				<div class="evg__empty">
					<svg aria-hidden="true"><use href="#i-cal"/></svg>
					<p>Următoarele evenimente vor fi anunțate aici. Până atunci, vă așteptăm la muzeu în programul de vizitare.</p>
				</div>
			<?php endif; ?>
		</section>

		<!-- in desfasurare -->
		<section class="evg evg--acum<?php echo $ev['acum'] ? '' : ' is-empty'; ?>" data-group="acum" aria-labelledby="evg-acum"<?php echo $ev['acum'] ? '' : ' hidden'; ?>>
			<header class="evg__head draw">
				<h2 class="evg__title" id="evg-acum"><span>În desfășurare</span></h2>
				<p class="evg__count"><?php echo $ev['acum'] ? $cate(count($ev['acum'])) : 'Niciun eveniment acum'; ?></p>
			</header>
			<?php if ($ev['acum']) : ?>
				<ul class="evc-grid">
					<?php foreach ($ev['acum'] as $i => $it) { echo $card($it, false, $i); } ?>
				</ul>
			<?php else : ?>
				<div class="evg__empty">
					<svg aria-hidden="true"><use href="#i-clock"/></svg>
					<p>În acest moment nu are loc niciun eveniment. Expozițiile permanente ale muzeului pot fi vizitate oricând în program.</p>
				</div>
			<?php endif; ?>
		</section>

		<!-- realizate, arhiva pe ani -->
		<section class="evg evg--trecut" data-group="trecut" aria-labelledby="evg-trecut">
			<header class="evg__head draw">
				<h2 class="evg__title" id="evg-trecut"><span>Realizate</span></h2>
				<p class="evg__count"><?php echo $cate(count($ev['trecut'])); ?>, din <?php echo $e($first); ?> până azi</p>
			</header>

			<?php if ($years) : ?>
				<div class="evp-arch">
					<nav class="evp-years" aria-label="Anii arhivei">
						<span class="evp-years__thread" aria-hidden="true"><span class="evp-years__fill" data-evp-fill></span></span>
						<ol>
							<?php foreach ($years as $year => $items) : ?>
								<li><a href="#an-<?php echo $e($year); ?>" data-year-link="<?php echo $e($year); ?>"><span class="evp-years__y"><?php echo $e($year); ?></span><span class="evp-years__n"><?php echo count($items); ?></span></a></li>
							<?php endforeach; ?>
						</ol>
					</nav>

					<div class="evp-arch__list">
						<?php foreach ($years as $year => $items) : ?>
							<section class="evp-year" id="an-<?php echo $e($year); ?>" data-year="<?php echo $e($year); ?>" aria-label="Evenimente din <?php echo $e($year); ?>">
								<h3 class="evp-year__num" aria-hidden="true"><?php echo $e($year); ?></h3>
								<ul class="evc-grid">
									<?php
									// in fiecare an primul eveniment cu poza primeste un card mai mare,
									// ales pe o pozitie para, ca sa nu ramana goluri in grila si ordinea sa fie tot cronologica
									$wideId = null;
									foreach ($items as $k => $it) {
										if ($it->image && count($items) > 2 && $k % 2 === 0) {
											$wideId = $it->id;
											break;
										}
									}
									foreach ($items as $i => $it) {
										echo $card($it, $it->id === $wideId, $i);
									}
									?>
								</ul>
							</section>
						<?php endforeach; ?>
					</div>
				</div>
			<?php else : ?>
				<div class="evg__empty"><p>Evenimentele realizate vor apărea aici.</p></div>
			<?php endif; ?>
		</section>
	</div>
</div>
