<?php
namespace BRG\Models;

/*
 * Automa Card: all utility functions concerning an automa card
 */

class AutomaCard extends \BRG\Helpers\DB_Model
{
  protected $table = 'cards';
  protected $primary = 'card_id';
  protected $attributes = [
    'id' => ['card_id', 'int'],
    'location' => ['card_location', 'str'],
  ];

  protected $flow = [];
}
