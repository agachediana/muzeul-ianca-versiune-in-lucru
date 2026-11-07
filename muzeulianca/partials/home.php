<?php
/*
  Continutul paginii de Acasa

  Bannerul cu felinarul, anunturile, evenimentele, despre muzeu si scurtul
  istoric, corpurile muzeului si vizita. Orice sectiune se poate inlocui cu
  un modul pus in pozitia ei: home-anunturi, home-prezentare, home-sectiuni,
  home-evenimente, home-istoric, home-vizita.

  Fisierul e inclus din index.php, de unde vin $this, $app, $params, $e,
  $telefon, $telLink si $bilete.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

$img = static function ($path) {
	if (!$path) {
		return '';
	}
	$url = HTMLHelper::_('cleanImageURL', $path)->url;

	return preg_match('#^(https?:)?//#', $url) ? $url : Uri::root(true) . '/' . ltrim($url, '/');
};
// width si height pentru poze (vezi partials/img-size.php)
$size = require __DIR__ . '/img-size.php';


/*
  Textele fixe de pe Acasa, se pot schimba direct aici
*/
// cardurile duc la pagina Corpurile muzeului (elementul de meniu cu acest alias)
$corpuri     = $app->getMenu()->getItems('alias', 'corpurile-muzeului', true);
$corpuriLink = $corpuri ? Route::_('index.php?Itemid=' . $corpuri->id) : '#';

// corpurile muzeului (partials/sectiuni.php), toate duc la pagina Corpurile muzeului
$sectiuni = array_map(static fn ($s) => $s + ['link' => $corpuriLink], require __DIR__ . '/sectiuni.php');

$repere = [
	['an' => '1995', 'titlu' => 'Primul pas',          'text' => 'Consiliul Local aprobă deschiderea unui muzeu în localul Școlii nr. 1.'],
	['an' => '1998', 'titlu' => 'O casă a muzeului',   'text' => 'Muzeul primește clădirea din strada Gării nr. 9.'],
	['an' => '1999', 'titlu' => 'Deschiderea',         'text' => 'La 1 decembrie, prof. Ion M. Bărbuceanu deschide oficial muzeul.'],
	['an' => '2003', 'titlu' => 'Instituție culturală', 'text' => 'Ministerul Culturii aprobă înființarea „Muzeului Ianca”.'],
	['an' => '2009', 'titlu' => 'Acreditarea',         'text' => 'Ordinul ministrului culturii nr. 2465 din 3 noiembrie 2009.'],
	['an' => '2012', 'titlu' => 'Casa țărănească',     'text' => 'O casă din Oprișenești este strămutată în curtea muzeului.'],
	['an' => 'Prezent', 'titlu' => '', 'text' => ''],
];

$aniActivitate = (int) date('Y') - 1999 - ((int) date('md') < 1201 ? 1 : 0);

/*
  Articolele dintr-o categorie, dupa alias (folosit pentru anunturi).
  Data se ia din campul data-eveniment, iar daca lipseste, din data publicarii.
*/
$loadArticles = static function (string $alias, int $limit) use ($app) {
	$db    = Factory::getContainer()->get(DatabaseInterface::class);
	$now   = Factory::getDate()->toSql();
	$query = $db->getQuery(true);

	$query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.introtext', 'a.images', 'a.catid', 'a.language', 'a.publish_up', 'a.featured']))
		->select($db->quoteName('fv.value', 'event_date'))
		->from($db->quoteName('#__content', 'a'))
		->join('INNER', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
		->join(
			'LEFT',
			$db->quoteName('#__fields', 'f'),
			$db->quoteName('f.name') . ' = ' . $db->quote('data-eveniment')
			. ' AND ' . $db->quoteName('f.context') . ' = ' . $db->quote('com_content.article')
			. ' AND ' . $db->quoteName('f.state') . ' = 1'
		)
		->join(
			'LEFT',
			$db->quoteName('#__fields_values', 'fv'),
			$db->quoteName('fv.field_id') . ' = ' . $db->quoteName('f.id')
			. ' AND ' . $db->quoteName('fv.item_id') . ' = ' . $query->castAs('CHAR', $db->quoteName('a.id'))
		)
		->where($db->quoteName('c.alias') . ' = :alias')
		->where($db->quoteName('c.published') . ' = 1')
		->where($db->quoteName('a.state') . ' = 1')
		->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :now1)')
		->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' > :now2)')
		->whereIn($db->quoteName('a.access'), $app->getIdentity()->getAuthorisedViewLevels())
		->bind(':alias', $alias)
		->bind(':now1', $now)
		->bind(':now2', $now)
		->order($db->quoteName('a.publish_up') . ' DESC')
		->setLimit($limit);

	if ($app->getLanguageFilter()) {
		$query->whereIn($db->quoteName('a.language'), [$app->getLanguage()->getTag(), '*'], ParameterType::STRING);
	}

	try {
		return $db->setQuery($query)->loadObjectList();
	} catch (\RuntimeException $ex) {
		return [];
	}
};

// evenimentele viitoare, in desfasurare si realizate (vezi partials/events-data.php)
$loadEvents = require __DIR__ . '/events-data.php';
$evData    = $loadEvents((string) $params->get('evenimenteCategorie', 'evenimente'));
$upcoming  = array_slice(array_merge($evData['acum'], $evData['viitor']), 0, 4);
$past      = array_slice($evData['trecut'], 0, 6);

$anunturi = $loadArticles((string) $params->get('anunturiCategorie', 'anunturi'), 6);
$anunturi = array_map(static function ($row) {
	$row->link = Route::_(RouteHelper::getArticleRoute($row->id . ':' . $row->alias, $row->catid, $row->language));

	return $row;
}, $anunturi);

// linkuri spre pagini din meniu dupa alias, goale daca pagina nu exista
$menuLink = static function (string $alias) use ($app) {
	$item = $app->getMenu()->getItems('alias', $alias, true);

	return $item ? Route::_('index.php?Itemid=' . $item->id) : '';
};
$evenimenteUrl = $params->get('evenimenteUrl', '') ?: $menuLink('evenimente');
$istoricUrl    = $menuLink('istoric');


$renderEvent = static function ($ev, bool $featured, int $delay) use ($e, $size) {
	$zi   = $ev->badgeTop;
	$luna = $ev->monthOnly ? $ev->badgeBottom : explode(' ', $ev->badgeBottom)[0];
	$lung = $ev->dateLong;
	ob_start(); ?>
	<article class="event<?php echo $featured ? ' event--featured' : ''; ?><?php echo $ev->image ? '' : ' event--noimg'; ?> reveal" style="--d:<?php echo $delay; ?>">
		<div class="event__media">
			<?php if ($ev->image) : ?><img src="<?php echo $e($ev->image); ?>"<?php echo $size($ev->image); ?> alt="" loading="lazy"><?php endif; ?>
			<div class="event__date"><strong><?php echo $zi; ?></strong><span><?php echo $e($luna); ?></span></div>
		</div>
		<div class="event__body">
			<div class="event__meta">
				<span><svg><use href="#i-cal"/></svg><?php echo $e($lung); ?></span>
				<?php if ($ev->hasTime) : ?><span><svg><use href="#i-clock"/></svg><?php echo $ev->date->format('H:i'); ?></span><?php endif; ?>
			</div>
			<h3><a href="<?php echo $e($ev->link); ?>"><?php echo $e($ev->title); ?></a></h3>
			<?php if ($ev->teaser) : ?><p><?php echo $e($ev->teaser); ?></p><?php endif; ?>
			<?php if ($featured) : ?>
				<div class="countdown" data-countdown="<?php echo $ev->date->format(DATE_ATOM); ?>" aria-label="Timp rămas până la eveniment">
					<div><strong data-d>0</strong><span>zile</span></div>
					<div><strong data-h>00</strong><span>ore</span></div>
					<div><strong data-m>00</strong><span>min</span></div>
					<div><strong data-s>00</strong><span>sec</span></div>
				</div>
			<?php endif; ?>
			<a class="link-arrow" href="<?php echo $e($ev->link); ?>">Detalii <svg width="16" height="16"><use href="#i-arrow"/></svg></a>
		</div>
	</article>
	<?php return ob_get_clean();
};

?>

<!-- bannerul cu fotografia si felinarul -->
<?php $hero = $img($params->get('heroImage', 'images/muzeu/hero-muzeu.jpg')); ?>
<section class="hero" aria-label="Bun venit">
	<div class="hero__media" aria-hidden="true">
		<img class="hero__img hero__img--bw" src="<?php echo $e($hero); ?>"<?php echo $size($hero); ?> alt="" fetchpriority="high">
		<!-- lumina: aceeasi fotografie, color, vazuta printr-un cerc cu margine moale -->
		<div class="hero__spot"><img src="<?php echo $e($hero); ?>" alt=""></div>
		<div class="hero__glow"></div>
	</div>
	<div class="hero__lantern" aria-hidden="true">
		<svg viewBox="0 0 34 58">
			<circle cx="17" cy="4" r="3" fill="none" stroke="#e3d5bc" stroke-width="1.4"/>
			<path d="M11 12h12l-3-5h-6z" fill="#3a2a22" stroke="#e3d5bc" stroke-width="1.2" stroke-linejoin="round"/>
			<rect x="8.5" y="12" width="17" height="30" rx="2" fill="rgba(255,196,110,.22)" stroke="#e3d5bc" stroke-width="1.3"/>
			<path d="M12.5 12v30M21.5 12v30" stroke="#e3d5bc" stroke-width=".9" opacity=".7"/>
			<g class="flame">
				<path d="M17 27c3 3.6 4.2 6.6 0 12-4.2-5.4-3-8.4 0-12z" fill="#ffb347"/>
				<path d="M17 31c1.5 2 2 3.6 0 6.4-2-2.8-1.5-4.4 0-6.4z" fill="#fff1c1"/>
			</g>
			<rect x="7" y="42" width="20" height="4" rx="1" fill="#3a2a22" stroke="#e3d5bc" stroke-width="1.2"/>
			<rect x="10" y="46" width="14" height="3" rx="1" fill="#3a2a22" stroke="#e3d5bc" stroke-width="1"/>
		</svg>
	</div>
	<div class="hero__content wrap">
		<h1 class="hero__title"><span class="hero__line"><?php echo $e(trim((string) $params->get('heroName', 'Muzeul Ianca'))); ?></span></h1>
		<p class="hero__sub"><?php echo $e($params->get('heroSub', 'Câmpia Brăilei · din 1999')); ?></p>
		<?php
		// linkurile interne (index.php?...) trec prin router; al doilea buton duce implicit la pagina Tur virtual
		$ctaUrl = static fn ($url) => str_starts_with((string) $url, 'index.php') ? Route::_($url) : (string) $url;
		$cta1   = $ctaUrl($params->get('heroCta1Url', '#vizita'));
		$cta2   = $ctaUrl($params->get('heroCta2Url', '')) ?: $menuLink('tur-virtual');
		?>
		<div class="hero__ctas">
			<?php if ($params->get('heroCta1Text')) : ?>
				<a class="btn" href="<?php echo $e($cta1); ?>"><?php echo $e($params->get('heroCta1Text')); ?> <svg><use href="#i-arrow"/></svg></a>
			<?php endif; ?>
			<?php if ($params->get('heroCta2Text') && $cta2) : ?>
				<a class="link-arrow" href="<?php echo $e($cta2); ?>"><?php echo $e($params->get('heroCta2Text')); ?> <svg width="16" height="16"><use href="#i-arrow"/></svg></a>
			<?php endif; ?>
		</div>
	</div>
	<div class="hero__scroll" aria-hidden="true">
		<span>Descoperă</span>
		<svg class="hero__scroll-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v15M6 13l6 6 6-6"/></svg>
	</div>
</section>

<!-- anunturi -->
<?php if ($this->countModules('home-anunturi')) : ?>
	<jdoc:include type="modules" name="home-anunturi" style="none" />
<?php elseif ($anunturi) : ?>
	<section class="notice" aria-label="Anunțuri">
		<div class="wrap">
			<span class="notice__label">Anunțuri</span>
			<div class="notice__track">
				<div class="notice__rail">
					<?php foreach ($anunturi as $a) : ?><a href="<?php echo $e($a->link); ?>"><?php echo $e($a->title); ?></a><?php endforeach; ?>
					<span aria-hidden="true" style="display:contents">
						<?php foreach ($anunturi as $a) : ?><a href="<?php echo $e($a->link); ?>" tabindex="-1"><?php echo $e($a->title); ?></a><?php endforeach; ?>
					</span>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- evenimente, primele pe pagina -->
<?php if ($this->countModules('home-evenimente')) : ?>
	<jdoc:include type="modules" name="home-evenimente" style="none" />
<?php else : ?>
	<section class="section events-section" id="evenimente">
		<div class="wrap">
			<div class="section__head">
				<h2 class="reveal">Evenimente</h2>
				<?php if ($evenimenteUrl) : ?><a class="link-arrow reveal" style="--d:1" href="<?php echo $e($evenimenteUrl); ?>">Toate evenimentele <svg width="16" height="16"><use href="#i-arrow"/></svg></a><?php endif; ?>
			</div>

			<?php if ($upcoming) : ?>
				<h3 class="ev-label reveal">Urmează</h3>
				<div class="events events--upcoming">
					<?php foreach ($upcoming as $i => $ev) { echo $renderEvent($ev, $i === 0, $i); } ?>
				</div>
			<?php endif; ?>

			<?php if ($past) : ?>
				<?php $lead = $past[0]; $side = array_slice($past, 1, 3); ?>
				<?php if ($upcoming) : ?><h3 class="ev-label reveal">Au avut loc</h3><?php endif; ?>
				<div class="ev-mag">
					<a class="ev-lead reveal" href="<?php echo $e($lead->link); ?>">
						<span class="ev-lead__img<?php echo $lead->image ? '' : ' ev-noimg'; ?>"><?php if ($lead->image) : ?><img src="<?php echo $e($lead->image); ?>"<?php echo $size($lead->image); ?> alt="" loading="lazy"><?php endif; ?></span>
						<span class="ev-date"><strong><?php echo $e($lead->badgeTop); ?></strong><span><?php echo $e($lead->badgeBottom); ?></span></span>
						<span class="ev-lead__body">
							<span class="ev-lead__title"><?php echo $e($lead->title); ?></span>
							<?php if ($lead->teaser) : ?><span class="ev-lead__teaser"><?php echo $e($lead->teaser); ?></span><?php endif; ?>
							<span class="ev-more">Citește <svg><use href="#i-arrow"/></svg></span>
						</span>
					</a>
					<?php if ($side) : ?>
						<ul class="ev-side">
							<?php foreach ($side as $i => $ev) : ?>
								<li class="reveal" style="--d:<?php echo $i + 1; ?>">
									<a class="ev-card" href="<?php echo $e($ev->link); ?>">
										<span class="ev-card__img<?php echo $ev->image ? '' : ' ev-noimg'; ?>"><?php if ($ev->image) : ?><img src="<?php echo $e($ev->image); ?>"<?php echo $size($ev->image); ?> alt="" loading="lazy"><?php endif; ?></span>
										<span class="ev-card__body">
											<time class="ev-card__date" datetime="<?php echo $ev->date->format($ev->monthOnly ? 'Y-m' : 'Y-m-d'); ?>"><?php echo $e($ev->dateLong); ?></time>
											<span class="ev-card__title"><?php echo $e($ev->title); ?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if (!$upcoming && !$past) : ?>
				<p class="ev-empty">Momentan nu sunt evenimente publicate.</p>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<div class="stitch" aria-hidden="true"></div>

<!-- despre muzeu -->
<?php if ($this->countModules('home-prezentare')) : ?>
	<jdoc:include type="modules" name="home-prezentare" style="none" />
<?php else : ?>
	<section class="section about-section" id="despre">
		<div class="wrap">
			<div class="intro">
				<div class="intro__media reveal">
					<figure class="reveal-img"><img src="<?php echo $e($img('images/muzeu/prezentare.jpg')); ?>"<?php echo $size($img('images/muzeu/prezentare.jpg')); ?> alt="Instrumente muzicale vechi și costum popular în secția de etnografie" loading="lazy"></figure>
				</div>
				<div class="intro__text">
					<h2 class="reveal">Despre muzeu</h2>
					<p class="lead reveal" style="--d:1">Deschis la 1 decembrie 1999 de prof. Ion M. Bărbuceanu, muzeul păstrează obiecte de uz casnic, unelte, costume populare, documente de istorie locală și o colecție de istoria aviației.</p>
					<div class="stats reveal" style="--d:2">
						<div class="stat"><span class="stat__num" data-count="<?php echo $aniActivitate; ?>"><?php echo $aniActivitate; ?></span><span class="stat__label">ani de activitate</span></div>
						<div class="stat"><span class="stat__num" data-count="4">4</span><span class="stat__label">corpuri de clădire</span></div>
						<div class="stat"><span class="stat__num" data-count="25">25</span><span class="stat__label">tablouri Georgeta Crainic</span></div>
					</div>
				</div>
			</div>

		</div>
	</section>
<?php endif; ?>

<!-- scurt istoric, pe fundal burgundy, imediat dupa despre muzeu -->
<?php if ($this->countModules('home-istoric')) : ?>
	<jdoc:include type="modules" name="home-istoric" style="none" />
<?php else : ?>
	<section class="section section--dark history-section" id="istoric">
		<div class="wrap">
			<div class="section__head">
				<h2 class="reveal">Scurt istoric</h2>
				<?php if ($istoricUrl) : ?><a class="link-arrow reveal" style="--d:1" href="<?php echo $e($istoricUrl); ?>">Toată cronologia <svg width="16" height="16"><use href="#i-arrow"/></svg></a><?php endif; ?>
			</div>
			<div class="timeline">
				<div class="timeline__line" aria-hidden="true"><span></span></div>
				<ol class="timeline__list">
					<?php foreach ($repere as $r) : ?>
						<li class="tl<?php echo $r['titlu'] === '' ? ' tl--now' : ''; ?>">
							<span class="tl__year"><?php echo $e($r['an']); ?></span>
							<?php if ($r['titlu'] !== '') : ?><h3><?php echo $e($r['titlu']); ?></h3><?php endif; ?>
							<?php if ($r['text'] !== '') : ?><p><?php echo $e($r['text']); ?></p><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
	</section>
<?php endif; ?>

<div class="stitch" aria-hidden="true"></div>

<!-- corpurile muzeului, panouri care se deschid la hover -->
<?php if ($this->countModules('home-sectiuni')) : ?>
	<jdoc:include type="modules" name="home-sectiuni" style="none" />
<?php else : ?>
	<section class="section halls-section" id="sectiuni">
		<div class="wrap">
			<div class="section__head">
				<h2 class="reveal">Secțiunile muzeului</h2>
			</div>
			<div class="halls reveal" style="--d:1">
				<?php foreach ($sectiuni as $i => $s) : ?>
					<a class="hall" href="<?php echo $e($s['link']); ?>">
						<span class="hall__img"><img src="<?php echo $e($img($s['img'])); ?>"<?php echo $size($img($s['img'])); ?> alt="" loading="lazy"></span>
						<span class="hall__body">
							<span class="hall__title"><?php echo $e($s['titlu']); ?></span>
							<span class="hall__text"><?php echo $e($s['text']); ?></span>
							<span class="hall__more">Descoperă <svg><use href="#i-arrow"/></svg></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- vizita si contact -->
<?php if ($this->countModules('home-vizita')) : ?>
	<jdoc:include type="modules" name="home-vizita" style="none" />
<?php else : ?>
	<section class="section" id="vizita">
		<div class="wrap visit">
			<div class="visit__card reveal">
				<h2>Vizitează muzeul</h2>
				<div class="visit__rows">
					<div class="visit__row"><svg><use href="#i-clock"/></svg><div><strong>Program</strong><?php echo $e($params->get('program')); ?></div></div>
					<div class="visit__row"><svg><use href="#i-pin"/></svg><div><strong>Adresă</strong><?php echo $e($params->get('adresa')); ?></div></div>
					<div class="visit__row"><svg><use href="#i-phone"/></svg><div><strong>Telefon</strong><a href="<?php echo $e($telLink); ?>"><?php echo $e($telefon); ?></a></div></div>
				</div>
				<?php if ($bilete) : ?><a class="btn" href="<?php echo $e($bilete); ?>" target="_blank" rel="noopener">Cumpără bilete <svg><use href="#i-arrow"/></svg></a><?php endif; ?>
			</div>
			<?php require __DIR__ . '/harta.php'; ?>
		</div>
	</section>
<?php endif; ?>
