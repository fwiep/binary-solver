<?php
/**
 * Demo page of the BinaryPuzzle solver
 *
 * PHP version 7
 *
 * @category DemoPage
 * @package  BinaryPuzzle
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
use FWieP\BinaryPuzzle\Puzzle as P;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('date.timezone', 'Europe/Amsterdam');
date_default_timezone_set('Europe/Amsterdam');
ini_set('intl.default_locale', 'nl-NL');
setlocale(LC_ALL, array('nl_NL.utf8', 'nl_NL', 'nl', 'dutch', 'nld'));
mb_internal_encoding('UTF-8');

require_once __DIR__.'/vendor/autoload.php';
@session_start();

$toSolve = "...0...0....0.
...........11.
.0...0.1...1.0
.0..1...00....
1...1.........
1..........0..
.............0
..1...........
.....11....1..
..0......0....
..01.....00..1
1.....0.1.....
...0........00
1........0..0.";

$unsolvedGrid = '';
$steps = [];
$currentStep = 0;
$stepsCount = 0;
$strats = P::STRATEGY_ALL_BUT_5;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $token = filter_input(INPUT_POST, '_token', FILTER_DEFAULT);
    if (!$token
        || !array_key_exists('token', $_SESSION)
        || $token !== $_SESSION['token']
    ) {
        header($_SERVER['SERVER_PROTOCOL'].' 405 Method Not Allowed');
        exit;
    }
    $k = 'txt-tosolve';
    $toSolve = (array_key_exists($k, $_POST) ? $_POST[$k] : '');
    $strats = 0;

    if (array_key_exists('chk-strat-1', $_POST)) {
        $strats += P::STRATEGY_1;
    }
    if (array_key_exists('chk-strat-2', $_POST)) {
        $strats += P::STRATEGY_2;
    }
    if (array_key_exists('chk-strat-3', $_POST)) {
        $strats += P::STRATEGY_3;
    }
    if (array_key_exists('chk-strat-4', $_POST)) {
        $strats += P::STRATEGY_4;
    }
    if (array_key_exists('chk-strat-5', $_POST)) {
        $strats += P::STRATEGY_5;
    }
    $p = new P($toSolve);
    $unsolvedGrid = $p->getUnsolvedHTML();
    $p->solve($strats);
    $steps = $p->getSteps();
    $stepsCount = count($steps);

} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_SESSION['token'] = md5(uniqid(mt_rand(), true));
}
/**
 * Gets the checked-attribute of strategy-checkbox
 *
 * @param int $num the strategy number
 *
 * @global $strats
 * @return string
 */
function isStrat(int $num = 0) : string
{
    global $strats;
    $constantName = P::class.'::STRATEGY_'.$num;
    
    if (!defined($constantName)) {
        return '';
    }
    $stratCode = constant($constantName);
    return (($strats & $stratCode) > 0) ? ' checked="checked"' : '';
}
$root = str_replace($_SERVER ['DOCUMENT_ROOT'], '', $_SERVER ['SCRIPT_FILENAME']);
$root = str_replace(basename($_SERVER ['SCRIPT_FILENAME']), '', $root);

$hrefbase = sprintf(
    '%1$s://%2$s%3$s',
    !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['HTTP_HOST'],
    $root
);
$bsCssUrl = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/'.
    'bootstrap.min.css';
$bsCssHash = 'sha384-'.
    'B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l';

$jqUrl = 'https://code.jquery.com/jquery-3.6.0.min.js';
$jqHash = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';

$bsJsUrl = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/'.
    'bootstrap.bundle.min.js';
$bsJsHash = 'sha384-'.
    'Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns';

?><!DOCTYPE html>
<html lang="nl">
<head>
    <base href="<?php print $hrefbase ?>" />
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width,
      initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Frans-Willem Post" />
    <meta name="robots" content="no-index, no-follow" />
    
    <link rel="stylesheet" href="<?php print $bsCssUrl ?>"
      integrity="<?php print $bsCssHash ?>" crossorigin="anonymous" />
    
    <style>
      a {
        color: black;
        text-decoration: underline;
      }
      .btn {
        background-color: #123b0e;
        color: white;
      }
      .pzl {
        font-family: monospace;
      }
      .flashit {
        animation: 1s linear 2 flash;
      }
      @keyframes flash {
        0% { background-color: transparent; }
        50% { background-color: green; }
        100% { background-color: transparent; }
      }
    </style>

    <title>Binaire puzzel oplossen</title>
</head>
<body>

<main class="container">
<h1>Binaire puzzels oplossen</h1>

<p>Deze pagina is bedoeld om binaire puzzels stap voor stap op te lossen en
de gebruiker inzicht te geven in die stappen. Het programma maakt gebruik
van een vijftal strategiën die na elkaar worden toegepast op de rijen en
kolommen van de puzzel. Met dank aan <a target="_blank"
href="http://www.binarypuzzle.com/tips.php">binarypuzzle.com</a>, <a
target="_blank" href="https://binarypuzzle.nl/strategy">binarypuzzle.nl</a>, 
Karin&nbsp;Schaap en John&nbsp;Segers. Meer informatie en een technische analyse
vindt u op <a target="_blank"
href="https://www.fwiep.nl/blog/binaire-puzzels-oplossen-in-php">FWieP's
weblog</a>.</p>

<h2>Uitleg</h2>
<p>
<a href="#collapseUitleg" data-toggle="collapse" role="button"
  aria-expanded="false" aria-controls="collapseUitleg">In- of uitklappen</a>
</p>

<div class="collapse" id="collapseUitleg">

<p>De opgave moet uit meerdere regels bestaan en dient alleen de tekens
<code>0</code>, <code>1</code> en <code>.</code> te bevatten. Het programma
accepteert vierkante of rechthoekige puzzels met zijden van 4, 6, 8, 10, 12, 14
of 16 cellen. Maak een keuze welke strategie(n) moet worden toegepast en klik
op 'Oplossen'.</p>

<p>Met de knoppen <kbd>-</kbd>, <kbd>+</kbd> en de schuifregelaar kan door de
verschillende stappen worden genavigeerd. Bij elke nieuwe stap lichten de cel
of cellen die bij deze stap veranderen kort op. Ook wordt aangegeven met welke
strategie deze stap de puzzel verder heeft ingevuld.</p>

<p>De verschillende strategiën zijn als volgt samen te vatten:</p>
<ol>

  <li>Tussen twee nullen staat altijd een één: <code>0.0</code> wordt
  <code>010</code>. Tussen twee enen staat altijd een nul: <code>1.1</code>
  wordt <code>101</code>. Naast twee nullen staat altijd een één:
  <code>00.</code> wordt <code>001</code>, <code>.00</code> wordt
  <code>100</code>. Naast twee enen staat altijd een nul: <code>11.</code>
  wordt <code>110</code>, <code>.11</code> wordt <code>011</code>. 
  </li>

  <li>Een rij (kolom) bevat altijd evenveel nullen als enen. Als de nullen
  'op zijn', moeten de resterende cellen met enen worden gevuld; en vice
  versa.</li>

  <li>Tussen een nul en één met twee cellen tussenruimte, staan altijd een
  nul en een één. Bijvoorbeeld: <code>0..1</code> of <code>1..0</code>. Dit
  gegeven kan worden gebruikt om met strategie 2 deze twee lege cellen uit
  te sluiten.</li>

  <li>Elke rij (kolom) is uniek. Als een rij (kolom) nog maar twee open
  cellen heeft, en er  is een volledige rij (kolom) die verder identiek
  is, moeten de lege cellen 'andersom' worden ingevuld.</li>

  <li>Maak een lijst van alle mogelijkheden voor het invullen van een rij
  (kolom) met lege cellen. Komen één of meer cellen daarvan overeen in alle
  mogelijkheden, dan is de inhoud van die cel zeker.</li>

</ol>

</div><!-- /.collapse -->

<form method="post" action="<?php print $_SERVER['PHP_SELF']?>">

<input type="hidden" name="_token" value="<?php print $_SESSION['token'] ?>" />

<div class="row">

<div class="card col-12 col-md-4">
  <div class="card-body">
    <h2 class="card-title">Opgave</h2>
    <label class="sr-only" for="txt-tosolve">Opgave:</label>
    <textarea class="pzl" rows="16" cols="16" id="txt-tosolve"
      name="txt-tosolve"><?php print htmlspecialchars($toSolve) ?></textarea>
  </div>
</div>

<script>
var unsolvedGrid = <?php print json_encode($unsolvedGrid) ?>;
var steps = <?php print json_encode($steps) ?>;
var currentStep = <?php print $currentStep?>;
</script>

<div class="card col-12 col-md-4">
  <div class="card-body">
  
    <h2 class="card-title">Strategiën</h2>
    
    <div class="form-check">
      <input class="form-check-input" type="checkbox"
        name="chk-strat-1" id="chk-strat-1" <?php print isStrat(1)?> />
      <label class="form-check-label" for="chk-strat-1">Strategie 1</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox"
        name="chk-strat-2" id="chk-strat-2" <?php print isStrat(2)?>/>
      <label class="form-check-label" for="chk-strat-2">Strategie 2</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox"
        name="chk-strat-3" id="chk-strat-3" <?php print isStrat(3)?>/>
      <label class="form-check-label" for="chk-strat-3">Strategie 3</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox"
        name="chk-strat-4" id="chk-strat-4" <?php print isStrat(4)?>/>
      <label class="form-check-label" for="chk-strat-4">Strategie 4</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox"
        name="chk-strat-5" id="chk-strat-5" <?php print isStrat(5)?>/>
      <label class="form-check-label" for="chk-strat-5">Strategie 5</label>
    </div>
    
    <input type="submit" class="btn btn-primary" value="Oplossen" />
    
    <hr />
    
    <h2 class="card-title">Stappen</h2>
    <p>Totaal aantal: <?php print $stepsCount?></p>
    <p>Huidige stap: <span id="spn-current-step"><?php
        print $currentStep?></span>, strategie <span id="spn-strat"><?php
        print 0 ?></span></p>

      <input type="button" id="btn-min" class="btn btn-primary" value="-" />
      <label class="sr-only" for="rng-steps">Huidige stap:</label>
      <input type="range" id="rng-steps" min="0" value="<?php print $currentStep ?>"
        step="1" max="<?php print $stepsCount?>" />
      <input type="button" id="btn-plus" class="btn btn-primary" value="+" />
     
  </div>
  
</div>

<div class="card col-12 col-md-4">
  <div class="card-body">
    <h2 class="card-title">Oplossing</h2>
    <div id="div-solution">
        <?php print $unsolvedGrid; ?>
    </div>
  </div>
</div>

</div><!-- /.row -->

</form>

</main><!-- /.container -->

<script src="<?php print $jqUrl ?>" integrity="<?php print $jqHash ?>"
  crossorigin="anonymous"></script>

<script src="<?php print $bsJsUrl ?>" integrity="<?php print $bsJsHash ?>"
  crossorigin="anonymous"></script>

<script>
//<![CDATA[
  function updateGrid() {
      $('#rng-steps').val(currentStep);
      $('#spn-current-step').html(currentStep);

      var grid = $('#div-solution table');
      $(grid).html(unsolvedGrid);

      if (currentStep == 0) {
          $('#spn-strat').html('0');
          return;
      }
      for (var i = 0; i < currentStep; i++) {
        var step = $(steps[i]).get(0);
        var strat = step.strategy;
        
        for (var j = 0; j < step.coordinateX.length; j++) {
            var x = step.coordinateX[j];
            var y = step.coordinateY[j];
            var newV = step.newCellValue[j];

            var cell = $('td.cell'+x+'_'+y, grid);
            $(cell).html(newV);

            if (i == currentStep -1) {
                $(cell).addClass('flashit');
                $('#spn-strat').html(strat);
            }  
        }
    }
  }
  $(function(){
      $('#btn-min').on('click', function(){
          if (currentStep > 0) {
              currentStep--;
          }
          updateGrid();
      });
      $('#rng-steps').on('input change', function(){
          currentStep = $(this).val();
          updateGrid();
      });
      $('#btn-plus').on('click', function(){
          if (currentStep < <?php print $stepsCount?>) {
              currentStep++;
          }
          updateGrid();
      });
  });
//]]>
</script>

</body>
</html>
