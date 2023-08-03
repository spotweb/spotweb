<?php

declare(strict_types=1);

namespace Laminas\Xml2Json\Exception;

use RuntimeException as PhpRuntimeException;

class RuntimeException extends PhpRuntimeException implements ExceptionInterface
{
}
