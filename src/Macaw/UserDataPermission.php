<?php

declare(strict_types = 1);

namespace NestboxPHP\Nestbox\Macaw;

class UserDataPermission
{
    private string $permission;

    public function __construct(bool $isPublic = false)
    {
        $this->permission = $isPublic ? "Public" : "Private";
    }

    public function __invoke(): string
    {
        return $this->permission;
    }
}