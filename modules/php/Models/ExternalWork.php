<?php
namespace BRG\Models;
use BRG\Core\Stats;
use BRG\Core\Preferences;
use BRG\Helpers\FlowConvertor;

/*
 * Work: all utility functions concerning a work
 */

class ExternalWork extends \BRG\Helpers\DB_Model
{
  protected $table = 'works';
  protected $primary = 'work_id';
  protected $attributes = [
    'id' => ['work_id', 'int'],
    'location' => ['work_location', 'str'],
    'type' => ['type', 'int'],
  ];
  protected $staticAttributes = ['cost', 'reward'];

  public function __construct($row, $datas)
  {
    parent::__construct($row);
    $this->cost = $datas['cost'];
    $this->reward = $datas['reward'];
  }

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['reward'] = $this->reward;
    $data['cost'] = $this->cost;
    return $data;
  }

  public function fulfill($company)
  {
    $this->setLocation('fulfilled_' . $company->getId());
  }

  public function getVp()
  {
    return FlowConvertor::getVp($this->reward);
  }

  public function computeRewardFlow()
  {
    return FlowConvertor::computeRewardFlow($this->reward);
  }
}
