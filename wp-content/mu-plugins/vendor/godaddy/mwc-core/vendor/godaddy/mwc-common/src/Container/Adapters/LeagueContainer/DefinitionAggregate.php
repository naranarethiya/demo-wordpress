<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer;

use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Definition\Definition;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Definition\DefinitionAggregate as LeagueDefinitionAggregate;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Definition\DefinitionAggregateInterface;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Definition\DefinitionInterface;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Exception\NotFoundException;
use function sprintf;

/**
 * A custom implementation of {@see DefinitionAggregateInterface} that indexes definitions by ID to support faster lookups.
 *
 * The {@see LeagueDefinitionAggregate} class does lookups by looping through all the definitions
 * and comparing the alias of each definition with the desired ID.
 *
 * This class, on the other hand, builds a map of definition ID to {@see Definition} instance as
 * definitions are added to the aggregate. As a result, the class is able to check whether a definition
 * exists or retrieve the definition instance with `isset()` checks.
 */
class DefinitionAggregate extends LeagueDefinitionAggregate
{
    /** @var array<string, DefinitionInterface> */
    protected $definitions = [];

    /**
     * @param mixed $definition
     */
    public function add(string $id, $definition) : DefinitionInterface
    {
        if (false === $definition instanceof DefinitionInterface) {
            $definition = new Definition($id, $definition);
        }

        $this->definitions[$id] = $definition->setAlias($id);

        return $definition;
    }

    public function has(string $id) : bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * This method always returns false because we currently don't support adding tags to definitions.
     *
     * If we decide to add tags support, we should consider also adding a subclass of Definition that can expose
     * the associated tags and build a list of definitions by tag the first time this method is called.
     *
     * It's important to build the map of tags to definitions here, to make sure we include tags that are added to
     * the definition after add() was called.
     *
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag) : bool
    {
        return false;
    }

    /**
     * @throws NotFoundException
     */
    public function getDefinition(string $id) : DefinitionInterface
    {
        $definition = $this->definitions[$id] ?? null;

        if (! $definition) {
            throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
        }

        $definition->setContainer($this->getContainer());

        return $definition;
    }
}
