<?php

namespace Utopia\Tests\Unit\Log;

use PHPUnit\Framework\TestCase;
use Utopia\Logger\Log\User;

class UserTest extends TestCase
{
    public function testLogUser(): void
    {
        $user = new User();

        self::assertSame(null, $user->getEmail());
        self::assertSame(null, $user->getUsername());
        self::assertSame(null, $user->getId());

        $user = new User('618e291cd8949');
        self::assertSame('618e291cd8949', $user->getId());

        $user = new User(null, 'matej@appwrite.io');
        self::assertSame('matej@appwrite.io', $user->getEmail());

        $user = new User(null, null, 'Meldiron');
        self::assertSame('Meldiron', $user->getUsername());
    }
}
