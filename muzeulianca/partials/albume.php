<?php
/*
  Albumele foto ale muzeului, pe corpuri. Pozele stau in images/muzeu/albume/<folder>/.
  Aici se scrie titlul fiecarui album (cu diacritice) si corpul in care e.
  Un folder nou care nu e in lista apare tot, cu titlul facut din numele folderului,
  iar prima litera a numelui (a, b, c, d) spune corpul.

  Se foloseste asa:
    $albume = require __DIR__ . '/albume.php';
*/

defined('_JEXEC') or die;

return [
	'a-georgeta-crainic'      => ['titlu' => 'Tablouri de Georgeta Crainic', 'corp' => 'A'],
	'a-marea-unire'           => ['titlu' => 'Expoziția Marii Uniri și a Centenarului', 'corp' => 'A'],
	'a-expozitie'             => ['titlu' => 'Expoziție temporară', 'corp' => 'A'],
	'b-scoala-de-altadata'    => ['titlu' => 'Școala de altădată', 'corp' => 'B'],
	'b-biserica-stramoseasca' => ['titlu' => 'Biserica strămoșească', 'corp' => 'B'],
	'b-sectia-aviatie'        => ['titlu' => 'Secția Aviație', 'corp' => 'B'],
	'b-sectia-etnografie'     => ['titlu' => 'Secția Etnografie', 'corp' => 'B'],
	'c-casa-taraneasca'       => ['titlu' => 'Casa țărănească din Oprișenești', 'corp' => 'C'],
	'd-depozitul-de-unelte'   => ['titlu' => 'Depozitul de unelte din câmpia Brăilei', 'corp' => 'D'],
	'd-curtea-interioara'     => ['titlu' => 'Curtea interioară și gospodăria țărănească', 'corp' => 'D'],
];
