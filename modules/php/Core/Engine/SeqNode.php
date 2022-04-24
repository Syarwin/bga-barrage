<?php
namespace BRG\Core\Engine;

use BRG\Core\Engine;
/*
 * SeqNode: a class that represent a sequence of actions
 */
class SeqNode extends AbstractNode
{
  public function __construct($infos = [], $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_SEQ;
  }

  /**
   * The description of the node is the sequence of description of its children
   */
  public function getDescriptionSeparator()
  {
    return ', ';
  }

  /**
   * A SEQ node is doable if all its children are doable (or if the SEQ node itself is optional)
   * WARNING: this is a very basic check that does not cover the case where the first action might make the second one doable
   *  -> maybe it would make more sense to only check first action ?
   */
  public function isDoable($company, $ignoreResources = false)
  {
    return $this->childsReduceAnd(function ($child) use ($company, $ignoreResources) {
      return $child->isDoable($company, $ignoreResources) || $child->isOptional();
    });
  }

  /**
   * An SEQ node is resolved either when marked as resolved, either when all children are resolved already
   */
  public function isResolved()
  {
    return parent::isResolved() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isResolved();
      });
  }

  /**
   * Just return the first unresolved children, unless the node itself is optional
   */
  public function getNextUnresolved()
  {
    if ($this->isResolved()) {
      return null;
    }

    if ($this->isOptional()) {
      return $this;
    }

    // Edge case where an action leaf is turned into a SEQ but not yet resolved
    if ($this->getAction() != null && !$this->isActionResolved()) {
      return $this;
    }

    foreach ($this->childs as $child) {
      if (!$child->isResolved()) {
        return $child->getNextUnresolved();
      }
    }
  }

  /**
   * We only enter this function if the user decide to enter the SEQ (in the case where the node is optional)
   */
  public function choose($childIndex)
  {
    if ($childIndex != 0) {
      throw new \BgaVisibleSystemException('SEQ Choice shouldnt happen with $childIndex different from 0');
    }
    $this->infos['optional'] = false; // Mark the node as mandatory
  }
}
