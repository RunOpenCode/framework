<?php
/*
 * Go! AOP framework
 *
 * @copyright Copyright 2011, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Go\PhpUnit;

use Go\TestUtils\AdvisorIdentifiersExtractor;
use PHPUnit_Framework_Constraint as Constraint;
use ReflectionClass;
use Go\Instrument\PathResolver;

/**
 * Asserts that class member is woven for given class.
 */
class ClassMemberWovenConstraint extends Constraint
{
    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration)
    {
        parent::__construct();
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function matches($other)
    {
        if (!$other instanceof ClassAdvisorIdentifier) {
            throw new \InvalidArgumentException(sprintf('Expected instance of "%s", got "%s".', ClassAdvisorIdentifier::class, is_object($other) ? get_class($other) : gettype($other)));
        }

        $joinPoints = AdvisorIdentifiersExtractor::extract($this->getPathToProxy($other->getClass()));

        $access = $other->getTarget();

        if (!isset($joinPoints[$access])) {
            return false;
        }

        if (!isset($joinPoints[$access][$other->getSubject()])) {
            return false;
        }

        if (null === $other->getAdvisorIdentifier()) {
            return true; // if advisor identifier is not specified, that means that any matches, so weaving exists.
        }

        $index        = $other->getIndex();
        $advisorIndex = array_search($other->getAdvisorIdentifier(), $joinPoints[$access][$other->getSubject()], true);
        $isIndexValid = ($index === null) || ($advisorIndex === $index);

        return $advisorIndex !== false && $isIndexValid;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'join point exists.';
    }

    /**
     * Get path to proxied class.
     *
     * @param string $class Full qualified class name which is subject of weaving
     *
     * @return string Path to proxy class.
     */
    private function getPathToProxy($class)
    {
        $filename = (new ReflectionClass($class))->getFileName();
        $suffix   = substr($filename, strlen(PathResolver::realpath($this->configuration['appDir'])));

        return $this->configuration['cacheDir'] . '/_proxies' . $suffix;
    }
}
