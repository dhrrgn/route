<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Route;

trait RouteStrategyTrait
{
    /**
     * @var integer
     */
    protected $strategy;

    /**
     * Tells the implementor which strategy to use, this should override any higher
     * level setting of strategies, such as on specific routes
     *
     * @param  integer $strategy
     * @return void
     */
    public function setStrategy($strategy)
    {
        $this->strategy = (integer) $strategy;
    }

    /**
     * Gets global strategy
     *
     * @return integer
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
