<?php
namespace BRG\Models;

use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Core\Engine;

/*
 * Action space
 */

class ActionSpace
{
  /*
   * STATIC INFORMATIONS
   *  they are overwritten by children
   */
  protected $type = ACTION;
  protected $actionType = null; // Useful to declare several action space as an X action

  protected $desc = []; // UI
  protected $tooltipDesc = null; // UI
  protected $size = 'm'; // UI
  protected $container = 'central'; // UI
  protected $accumulate = ''; // UI

  // Constraints
  protected $players = null; // Players requirements => null if none, integer if only one, array otherwise
  protected $isAdditional = false;
  protected $isBeginner = false; // Will ONLY be there on the beginner variant
  protected $isNotBeginner = false; // Will NOT be there on the beginner variant

  /*
   * DYNAMIC INFORMATIONS
   */

  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['component'] = $this->isBoardComponent();
    $data['container'] = $this->container;
    $data['desc'] = $this->desc;
    $data['tooltipDesc'] = $this->tooltipDesc ?? $this->desc;
    $data['size'] = $this->size;
    $data['accumulate'] = $this->accumulate;

    return $data;
  }

  public function getActionCardType()
  {
    return $this->actionCardType ?? substr($this->id, 6);
  }

  public function isSupported($players, $options)
  {
    return ($this->players == null || in_array(count($players), $this->players)) &&
      (!$this->isAdditional || $options[OPTION_ADDITIONAL_SPACES] == OPTION_ADDITIONAL_SPACES_ENABLED) &&
      (!$this->isBeginner || $options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_BEGINNER) &&
      (!$this->isNotBeginner || $options[OPTION_COMPETITIVE_LEVEL] != OPTION_COMPETITIVE_BEGINNER);
  }


  public function canBePlayed($player, $onlyCheckSpecificPlayer = null)
  {
    // What cards should we check ?
    $actionList = [$this->id];

    // Is there a farmer here ?
    foreach ($actionList as $action) {
      $farmers = Farmers::getOnCard($action);
      if ($farmers->count() > 0 && $onlyCheckSpecificPlayer == null) {
        return false;
      }

      $pIds = $farmers
        ->map(function ($farmer) {
          return $farmer['pId'];
        })
        ->toArray();
      if (in_array($onlyCheckSpecificPlayer, $pIds)) {
        return false;
      }
    }

    // Check that the action is doable
    $flow = $this->getFlow($player);
    $flowTree = Engine::buildTree($flow);
    return $flowTree->isDoable($player);
  }

  /**
   * Tag all the subtree flow with the information about this card so we can access it in the ctx later
   */
  protected function tagTree($t, $player)
  {
    $t['cardId'] = $this->id;
    $t['pId'] = $player->getId();
    if (isset($t['childs'])) {
      $t['childs'] = array_map(function ($child) use ($player) {
        return $this->tagTree($child, $player);
      }, $t['childs']);
    }
    return $t;
  }

  public function getFlow($player)
  {
    return $this->tagTree($this->flow, $player); // Add card context for listeners
  }
}
