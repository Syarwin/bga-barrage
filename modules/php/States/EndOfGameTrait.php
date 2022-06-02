<?php
namespace BRG\States;
use BRG\Map;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Scores;
use BRG\Managers\Actions;
use BRG\Managers\Contracts;

trait EndOfGameTrait
{
  function stEndScoring()
  {
    $flow = ['type' => NODE_SEQ, 'childs' => []];
    $flow['childs'] = array_merge($this->calculateObjectiveTile(), $flow['childs']);
    foreach (Companies::getAll() as $cId => $company) {
      $count = 0;
      foreach ([CREDIT, EXCAVATOR, MIXER, EXCAMIXER] as $type) {
        $count += $company->countReserveResource($type);
      }
      if ($count >= 5) {
        $flow['childs'][] = [
          'action' => GAIN,
          'source' => clienttranslate('bundle of 5 resources'),
          'args' => [
            'cId' => $company->getId(),
            VP => intdiv($count, 5),
          ],
        ];
      }
      // count number of water on barrage
      $drops = 0;
      foreach (Meeples::getFilteredQuery($cId, null, [BASE]) as $mId => $m) {
        if ($m['location'] == 'company') {
          continue;
        }
        $drops += count(Meeples::getOnSpace($m['location'], DROPLET, $cId));
      }
      if ($drops != 0) {
        $flow['childs'][] = [
          'action' => GAIN,
          'source' => clienttranslate('droplets retained by dams'),
          'args' => [
            'cId' => $company->getId(),
            VP => $drops,
          ],
        ];
      }
    }
    // debug
    // Engine::setup($flow, ['state' => ST_BEFORE_START_OF_ROUND]);

    Engine::setup($flow, ['state' => ST_END_GAME]);
    Engine::proceed();
  }
}
