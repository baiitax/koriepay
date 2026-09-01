<?php

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

class InvalidWebhookSignatureException extends RuntimeException
{
}
