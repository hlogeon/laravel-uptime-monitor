<?php

namespace Spatie\UptimeMonitor\Test;

use Artisan;
use Carbon\Carbon;
use Event;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\UptimeMonitor\UptimeMonitorServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var \Spatie\UptimeMonitor\Test\Server */
    protected $server;

    public function setUp()
    {
        $this->server = new Server();

        Carbon::setTestNow(Carbon::create(2016, 1, 1, 00, 00, 00));

        parent::setUp();

        $this->withFactories(__DIR__ . '/factories');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            UptimeMonitorServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('mail.driver', 'log');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'prefix' => '',
            'database' => ':memory:',
        ]);

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../database/migrations/create_sites_table.php.stub';

        (new \CreateSitesTable())->up();
    }

    public function progressMinutes(int $minutes)
    {
        $newNow = Carbon::now()->addMinutes($minutes);

        Carbon::setTestNow($newNow);
    }

    public function bringTestServerUp()
    {
        $this->server->up();
    }

    public function bringTestServerDown()
    {
        $this->server->down();
    }

    /**
     * @param string|array $searchStrings
     */
    protected function seeInConsoleOutput($searchStrings)
    {
        if (!is_array($searchStrings)) {
            $searchStrings = [$searchStrings];
        }

        $output = Artisan::output();

        foreach ($searchStrings as $searchString) {
            $this->assertContains((string)$searchString, $output);
        }

    }

    /**
     * @param string|array $searchStrings
     */
    protected function dontSeeInConsoleOutput($searchStrings)
    {
        if (!is_array($searchStrings)) {
            $searchStrings = [$searchStrings];
        }

        $output = Artisan::output();

        foreach ($searchStrings as $searchString) {
            $this->assertNotContains((string)$searchString, $output);
        }
    }

    public function skipIfNotConnectedToTheInternet()
    {
        try {
            file_get_contents('https://google.com');
        } catch (\ErrorException $e) {
            $this->markTestSkipped('No internet connection available.');
        }
    }

    protected function resetEventAssertions()
    {
        Event::fake();
    }
}
