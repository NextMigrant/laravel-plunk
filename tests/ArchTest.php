<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('exceptions extend PlunkException')
    ->expect('NextMigrant\Plunk\Exceptions')
    ->toExtend('NextMigrant\Plunk\Exceptions\PlunkException')
    ->ignoring('NextMigrant\Plunk\Exceptions\PlunkException');

arch('DTOs are final-like readonly classes')
    ->expect('NextMigrant\Plunk\Data')
    ->toHaveConstructor();

arch('resources depend on PlunkClient')
    ->expect('NextMigrant\Plunk\Resources')
    ->toOnlyBeUsedIn([
        'NextMigrant\Plunk\PlunkManager',
        'NextMigrant\Plunk\Plunk',
        'NextMigrant\Plunk\Tests',
    ]);
