<?php
namespace BRG\Core;
use Barrage;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return Barrage::get();
  }
}
