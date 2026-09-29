<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cria a aplicação já forçando um ambiente de teste isolado.
     *
     * O container define DB_CONNECTION=pgsql e CACHE_STORE=redis como variáveis
     * de ambiente reais, que o Laravel prioriza sobre o phpunit.xml. Forçamos
     * aqui SQLite em memória e cache em array para que os testes nunca toquem
     * o banco/Redis reais e fiquem isolados entre si.
     */
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');

        return $app;
    }
}
