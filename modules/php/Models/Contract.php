<?php
namespace BRG\Models;
use BRG\Core\Stats;
use BRG\Core\Preferences;
use BRG\Helpers\FlowConvertor;

/*
 * Contract: all utility functions concerning a contract
 */

class Contract extends \BRG\Helpers\DB_Model
{
  protected $table = 'contracts';
  protected $primary = 'contract_id';
  protected $attributes = [
    'id' => ['contract_id', 'int'],
    'location' => ['contract_location', 'str'],
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

  public function pick($company)
  {
    $this->setLocation('hand_' . $company->getId());
  }

  public function fulfill($company)
  {
    $this->setLocation('fulfilled_' . $company->getId());
  }

  public function computeRewardFlow()
  {
    return FlowConvertor::computeRewardFlow($this->reward);
  }
}
