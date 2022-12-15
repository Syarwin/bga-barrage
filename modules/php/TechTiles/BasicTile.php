<?php
namespace BRG\TechTiles;
use BRG\Managers\Companies;

/*
 * Basic Tile: all utility functions concerning a tech tile
 */

class BasicTile extends \BRG\Helpers\DB_Model
{
  protected $table = 'technology_tiles';
  protected $primary = 'tile_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'location' => 'tile_location',
    'state' => ['tile_state', 'int'],
    'type' => 'type',
    'cId' => ['company_id', 'int'],
  ];

  protected $staticAttributes = ['automatic', 'ignoreCostMalus', 'alternativeAction', 'alternativeActionDesc', 'lvl'];
  protected $automatic = true;
  protected $ignoreCostMalus = false;
  protected $alternativeAction = false;
  protected $alternativeActionDesc = '';
  protected $lvl = 0;

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['descs'] = $this->getDescs();
    return $data;
  }

  // For a basic tile, structure type = type
  public function getStructureType()
  {
    return $this->type;
  }

  public function canConstruct($structure)
  {
    return $this->getStructureType() == JOKER || $this->getStructureType() == $structure;
  }

  public function getDescs()
  {
    $descs = [
      BASE => clienttranslate('Construct a Base.'),
      ELEVATION => clienttranslate('Construct an Elevation on one of your Dam'),
      CONDUIT => clienttranslate('Construct a Conduit.'),
      POWERHOUSE => clienttranslate('Construct a Powerhouse.'),
      JOKER => clienttranslate('Construct any type of structure.'),
      BUILDING => clienttranslate('Construct a Building.'),
    ];

    return [$descs[$this->getStructureType()]];
  }

  public function getCompany()
  {
    return Companies::get($this->cId);
  }

  /**************** Tile Power **************/
  public function getPowerFlow($slot)
  {
    return null;
  }

  public function applyConstructCostModifier(&$costs, $slot)
  {
  }

  public function ignoreCostMalus()
  {
    return $this->ignoreCostMalus;
  }
}
