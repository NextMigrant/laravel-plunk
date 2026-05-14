<?php

namespace NextMigrant\Plunk;

use Illuminate\Support\Facades\Facade;
use NextMigrant\Plunk\Data\EmailVerification;
use NextMigrant\Plunk\Resources\Contacts;
use NextMigrant\Plunk\Resources\Events;
use NextMigrant\Plunk\Resources\Transactional;

/**
 * @method static Contacts contacts()
 * @method static Transactional transactional()
 * @method static Events events()
 * @method static EmailVerification verifyEmail(string $email)
 * @method static \NextMigrant\Plunk\PlunkClient getClient()
 *
 * @see PlunkManager
 */
class Plunk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PlunkManager::class;
    }
}
