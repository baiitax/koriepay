<?php

namespace App\Domain\Aggregator\Exceptions;

use DomainException;

/**
 * Thrown when an aggregator-role user has no `aggregators` record — the
 * console must not fabricate a network identity.
 */
class AggregatorNotProvisionedException extends DomainException
{
}
