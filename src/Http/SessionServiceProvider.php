<?php

declare(strict_types=1);

namespace Engelsystem\Http;

use Engelsystem\Config\Config;
use Engelsystem\Container\ServiceProvider;
use Engelsystem\Http\SessionHandlers\DatabaseHandler;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $sessionStorage = $this->getSessionStorage();
        $this->app->instance('session.storage', $sessionStorage);
        $this->app->bind(SessionStorageInterface::class, 'session.storage');

        $session = $this->app->make(Session::class);
        $this->app->instance(Session::class, $session);
        $this->app->instance('session', $session);
        $this->app->bind(SessionInterface::class, Session::class);

        if (!$session->has('_token')) {
            $session->set('_token', Str::random(42));
        }

        /** @var Request $request */
        $request = $this->app->get('request');
        $request->setSession($session);

        $session->start();
    }

    /**
     * Returns the session storage
     */
    protected function getSessionStorage(): SessionStorageInterface
    {
        if ($this->isCli()) {
            return $this->app->make(MockArraySessionStorage::class);
        }

        /** @var Config $config */
        $config = $this->app->get('config');
        $sessionConfig = $config->get('session');

        $handler = match ($sessionConfig['driver']) {
            'pdo'   => $this->app->make(DatabaseHandler::class),
            default => null,
        };

        return $this->app->make(NativeSessionStorage::class, [
            'options' => [
                'cookie_secure'   => true,
                'cookie_httponly' => true,
                'name'            => $sessionConfig['name'],
                'cookie_lifetime' => (int) ($sessionConfig['lifetime'] * 24 * 60 * 60),
            ],
            'handler' => $handler,
        ]);
    }

    /**
     * Test if is called from cli
     */
    protected function isCli(): bool
    {
        return PHP_SAPI == 'cli' || PHP_SAPI == 'phpdbg';
    }
}
