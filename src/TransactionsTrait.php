<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox;

use NestboxPHP\Nestbox\Exception\TransactionBeginFailedException;
use NestboxPHP\Nestbox\Exception\TransactionCommitFailedException;
use NestboxPHP\Nestbox\Exception\TransactionException;
use NestboxPHP\Nestbox\Exception\TransactionInProgressException;
use NestboxPHP\Nestbox\Exception\TransactionRollbackFailedException;

trait TransactionsTrait
{
}