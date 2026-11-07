<?php
/*
  Cele patru corpuri ale muzeului, folosite pe Acasa si pe pagina Tur virtual.
  Linkul turului 360 pentru fiecare corp se pune in setarile temei (tabul Vizită și tur).

  Se foloseste asa:
    $sectiuni = require __DIR__ . '/sectiuni.php';
*/

defined('_JEXEC') or die;

return [
	['corp' => 'A', 'titlu' => 'Artă și expoziții temporare',               'text' => 'Secția de artă, biblioteca și expoziția dedicată Marii Uniri.',              'img' => 'images/muzeu/sectiuni/arta.jpg'],
	['corp' => 'B', 'titlu' => 'Biserica, Armata, Școala și Viața Satului', 'text' => 'Piese de uz curent, unelte, costume populare și scoarțe.',                  'img' => 'images/muzeu/sectiuni/biserica.jpg'],
	['corp' => 'C', 'titlu' => 'Casa țărănească',                           'text' => 'Casă strămutată din satul Oprișenești, refăcută cu sprijinul Muzeului Brăilei.', 'img' => 'images/muzeu/sectiuni/casa.jpg'],
	['corp' => 'D', 'titlu' => 'Depozitul de unelte',                       'text' => 'Căruțe tradiționale pictate, cu motive specifice regiunii.',                'img' => 'images/muzeu/sectiuni/unelte.jpg'],
];
