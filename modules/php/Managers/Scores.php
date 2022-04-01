<?php
namespace BRG\Managers;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Core\Notifications;
use BRG\Managers\PlayerCards;

/*
 * Scores manager : allows to easily update/notify scores
 *   -> could have been inside Players.php but better structure this way
 */
class Scores extends \BRG\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \BRG\Models\Player($row);
  }

  /*
   * Update scores UI
   */
  protected static $scores = [];
  protected static function init()
  {
    self::$scores = [];
    foreach (Players::getAll() as $pId => $player) {
      self::$scores[$pId] = [
        'total' => 0,
      ];
      foreach (SCORING_CATEGORIES as $category) {
        self::$scores[$pId][$category] = [
          'total' => 0,
          'entries' => [],
        ];
      }
    }
  }

  /**
   * Add a scoring entry in the corresponding category
   */
  public static function addEntry($player, $category, $score, $description, $qty = null, $source = null)
  {
    $pId = is_int($player) ? $player : $player->getId();
    // Add entry
    $data = [
      'score' => $score,
      //      'desc' => $description,
    ];
    if (!is_null($qty)) {
      $data['quantity'] = $qty;
    }
    if (!is_null($source)) {
      $data['source'] = $source;
    }
    self::$scores[$pId][$category]['entries'][] = $data;

    // Update scores
    self::$scores[$pId][$category]['total'] += $score;
    self::$scores[$pId]['total'] += $score;
  }

  /**
   * Generic case that covers scores that depends on number of STUFF
   */
  public static function addQuantityEntry(
    $player,
    $category,
    $n,
    $scoresMap,
    $descSingular,
    $descPlural,
    $source = null
  ) {
    // Syntaxic sugar for basic categories that always have the same scoring sequence : -1, 1, 2, 3, 4
    $map = [];
    if (isSequential($scoresMap)) {
      $map[$scoresMap[0]] = -1;
      $map[$scoresMap[1]] = 1;
      $map[$scoresMap[2]] = 2;
      $map[$scoresMap[3]] = 3;
      $map[$scoresMap[4]] = 4;
    } else {
      $map = $scoresMap; // Already of the form qtyDescription => score
    }

    // First we compute the score by going through the map which is an arry : qtyDescription => score
    $score = null;
    foreach ($map as $qty => $gain) {
      $lower = 0;
      $upper = null;

      // Quantity of the form : X-Y
      if (\stripos($qty, '-') !== false) {
        $t = \explode('-', $qty);
        $lower = (int) $t[0];
        $upper = (int) $t[1];
      }
      // Quantity of the form : +X
      elseif (\stripos($qty, '+') !== false) {
        $t = \explode('+', $qty);
        $lower = (int) $t[0];
      }
      // Quantity is just an int
      else {
        $lower = (int) $qty;
        $upper = (int) $qty;
      }

      // Check $n against $lower and $upper
      if ($n >= $lower && ($upper === null || $n <= $upper)) {
        if ($score != null) {
          throw new \feException("Duplicate score found for quantity entry : $category, $n");
        }
        $score = $gain;
      }
    }

    if ($score == null) {
      return;
    }

    // Now log the entry with default message
    $desc = self::getQtyDesc($player, $n, $score, $descSingular, $descPlural);
    self::addEntry($player, $category, $score, $desc, $n, $source);
    return $score;
  }

  /**
   * Get standard description for score that depends on quantity of STUFF
   */
  public function getQtyDesc($player, $n, $score, $descSingular, $descPlural)
  {
    $desc = [
      'log' => '',
      'args' => [
        'player_name' => $player->getName(),
        'n' => $n,
        'category' => $n > 1 ? $descPlural : $descSingular,
        'score' => $score,
      ],
    ];
    if ($score < 0) {
      $desc['log'] = clienttranslate('${player_name} has ${n} ${category} and hence loses ${score} point(s)');
      $desc['args']['score'] = -$score;
    } elseif ($score == 0) {
      $desc['log'] = clienttranslate('${player_name} does not have any ${category} and hence earns no point');
    } else {
      $desc['log'] = clienttranslate('${player_name} has ${n} ${category} and hence earns ${score} point(s)');
    }

    return $desc;
  }

  /**
   * Update every player score in DB and on UI
   */
  public function update($bypassCheck = false)
  {
    if (!$bypassCheck && !Globals::isLiveScoring()) {
      return;
    }

    $scores = self::compute();
    foreach (Players::getAll() as $pId => $player) {
      self::DB()->update(['player_score' => self::$scores[$pId]['total']], $pId);
    }

    Notifications::updateScores(self::$scores);
    // TODO Stats::updateScores($scores);
  }

  /**
   * Compute the scores and return them
   */
  public function compute()
  {
    self::init();
    foreach (Players::getAll() as $pId => $player) {
      self::computePlayer($player);
    }

    // Specific case of score computing
    foreach (Players::getAll() as $pId => $player) {
      self::computeC135($player, self::$scores);
    }

    // update of Stats
    foreach (Players::getAll() as $pId => $player) {
      Stats::setScoreCards($player, self::$scores[$player->getId()][SCORING_CARDS]['total']);
      Stats::setScoreCardsBonus($player, self::$scores[$player->getId()][SCORING_CARDS_BONUS]['total']);
    }

    return self::$scores;
  }

  /**
   * Compute the score of an individual player
   */
  public function computePlayer($player)
  {
    self::computeFields($player);
    self::computePastures($player);

    self::computeGrains($player);
    self::computeVegetables($player);

    self::computeSheeps($player);
    self::computePigs($player);
    self::computeCattles($player);

    self::computeEmptyCells($player);
    self::computeFencedStables($player);

    self::computeRooms($player);

    self::computeFarmers($player);

    self::computeCards($player);
    self::computeBeggings($player);

    self::computeAuxScore($player);
  }

  protected function computeAuxScore($player)
  {
    $aux = 0;
    foreach ([WOOD, CLAY, STONE, REED] as $res) {
      $aux += $player->countReserveResource($res);
    }
    self::DB()->update(['player_score_aux' => $aux], $player->getId());
  }

  protected function computeFields($player)
  {
    $n = count($player->board()->getFieldTiles());
    $map = ['0-1', '2', '3', '4', '5+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_FIELDS,
      $n,
      $map,
      clienttranslate('field'),
      clienttranslate('fields')
    );
    Stats::setScoreFields($player, $score);
  }

  protected function computePastures($player)
  {
    $n = count($player->board()->getPastures());
    $map = ['0', '1', '2', '3', '4+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_PASTURES,
      $n,
      $map,
      clienttranslate('pasture'),
      clienttranslate('pastures')
    );
    Stats::setScorePastures($player, $score);
  }

  protected function computeGrains($player)
  {
    $n =
      $player->countReserveResource(GRAIN) +
      $player
        ->board()
        ->getGrowingCrops(GRAIN)
        ->count();
    $map = ['0', '1-3', '4-5', '6-7', '8+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_GRAINS,
      $n,
      $map,
      clienttranslate('grain'),
      clienttranslate('grains')
    );
    Stats::setScoreGrains($player, $score);
  }

  protected function computeVegetables($player)
  {
    $n =
      $player->countReserveResource(VEGETABLE) +
      $player
        ->board()
        ->getGrowingCrops(VEGETABLE)
        ->count();
    $map = ['0', '1', '2', '3', '4+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_VEGETABLES,
      $n,
      $map,
      clienttranslate('vegetable'),
      clienttranslate('vegetables')
    );
    Stats::setScoreVegetables($player, $score);
  }

  protected function computeSheeps($player)
  {
    $n = $player->getExchangeResources()[SHEEP];
    $map = ['0', '1-3', '4-5', '6-7', '8+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_SHEEPS,
      $n,
      $map,
      clienttranslate('sheep'),
      clienttranslate('sheeps')
    );
    Stats::setScoreSheeps($player, $score);
  }
  protected function computePigs($player)
  {
    $n = $player->getExchangeResources()[PIG];
    $map = ['0', '1-2', '3-4', '5-6', '7+'];
    $score = self::addQuantityEntry($player, SCORING_PIGS, $n, $map, clienttranslate('pig'), clienttranslate('pigs'));
    Stats::setScorePigs($player, $score);
  }
  protected function computeCattles($player)
  {
    $n = $player->getExchangeResources()[CATTLE];
    $map = ['0', '1', '2-3', '4-5', '6+'];
    $score = self::addQuantityEntry(
      $player,
      SCORING_CATTLES,
      $n,
      $map,
      clienttranslate('cattle'),
      clienttranslate('cattles')
    );
    Stats::setScoreCattles($player, $score);
  }

  protected function computeEmptyCells($player)
  {
    $n = count($player->board()->getFreeZones());
    if ($player->hasPlayedCard('D132_HideFarmer')) {
      $card = PlayerCards::get('D132_HideFarmer');
      $n -= $card->getExtraDatas('hiddenSpaces') ?? 0;
    }

    $score = $n * -1;
    $desc = self::getQtyDesc($player, $n, $score, clienttranslate('unused space'), clienttranslate('unused spaces'));
    self::addEntry($player, SCORING_EMPTY, $score, $desc, $n);
    Stats::setScoreUnused($player, -$score);
  }

  protected function computeFencedStables($player)
  {
    // Go trhough all pastures and sum number of stables inside
    $pastures = $player->board()->getPastures();
    $n = \array_reduce(
      $pastures,
      function ($acc, $pasture) {
        return $acc + count($pasture['stables']);
      },
      0
    );
    $score = $n;
    $desc = self::getQtyDesc($player, $n, $score, clienttranslate('fenced stable'), clienttranslate('fenced stables'));
    self::addEntry($player, SCORING_STABLES, $score, $desc, $n);
    Stats::setScoreStables($player, $score);
  }

  protected function computeRooms($player)
  {
    $n = $player->countRooms();
    $type = $player->getRoomType();

    $nClay = $type == 'roomClay' ? $n : 0;
    $scoreClay = $nClay * 1;
    $descClay = self::getQtyDesc(
      $player,
      $nClay,
      $scoreClay,
      clienttranslate('clay room'),
      clienttranslate('clay rooms')
    );
    self::addEntry($player, SCORING_CLAY_ROOMS, $scoreClay, $descClay, $nClay);
    Stats::setScoreClayRooms($player, $scoreClay);

    $nStone = $type == 'roomStone' ? $n : 0;
    $scoreStone = $nStone * 2;
    $descStone = self::getQtyDesc(
      $player,
      $nStone,
      $scoreStone,
      clienttranslate('stone room'),
      clienttranslate('stone rooms')
    );
    self::addEntry($player, SCORING_STONE_ROOMS, $scoreStone, $descStone, $nStone);
    Stats::setScoreStoneRooms($player, $scoreStone);
  }

  protected function computeFarmers($player)
  {
    $n = $player->countFarmers();
    $score = 3 * $n;
    $desc = self::getQtyDesc($player, $n, $score, clienttranslate('person'), clienttranslate('people'));
    self::addEntry($player, SCORING_FARMERS, $score, $desc, $n);
    Stats::setScoreFarmers($player, $score);
  }

  protected function computeCards($player)
  {
    foreach ($player->getCards() as $card) {
      if ($card->isPlayed()) {
        $card->computeScore();
      }
    }
    Stats::setScoreCards($player, self::$scores[$player->getId()][SCORING_CARDS]['total']);
    Stats::setScoreCardsBonus($player, self::$scores[$player->getId()][SCORING_CARDS_BONUS]['total']);
  }

  protected function computeC135($player, $score)
  {
    if ($player->hasPlayedCard('C135_Constable')) {
      PlayerCards::get('C135_Constable')->computeSpecialScore($score);
    }
  }

  protected function computeBeggings($player)
  {
    $n = $player->countReserveResource(BEGGING);
    $score = -3 * $n;
    $desc = self::getQtyDesc($player, $n, $score, clienttranslate('begging'), clienttranslate('beggings'));
    self::addEntry($player, SCORING_BEGGINGS, $score, $desc, $n);
    Stats::setScoreBeggings($player, -$score);
  }
}

// Utils
function isSequential(array $arr)
{
  if ([] === $arr) {
    return true;
  }
  return array_keys($arr) === range(0, count($arr) - 1);
}
