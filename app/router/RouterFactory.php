<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
        $router = new RouteList();

        $router[] = new Route('<presenter>[/<id [0-9]+>][/<action>]', [
            'action' => 'default',
        ]);

		return $router;
	}
}
