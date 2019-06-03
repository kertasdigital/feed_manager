<?php
namespace KertasDigital\FeedManager;

use Illuminate\Support\ServiceProvider;
use KertasDigital\FeedManager\Commands\FeedFetchCommand;
use KertasDigital\FeedManager\FeedManagerFactory;

class FeedManagerServiceProvider extends ServiceProvider {

	public function boot(){
		//$this->loadRoutesFrom(__DIR__.'/routes/web.php');
		//$this->loadViewsFrom(__DIR__.'/views','feed-manager');
		$this->loadMigrationsFrom(__DIR__.'/database/migrations');
		$this->mergeConfigFrom(
			__DIR__.'/config/feedmanager.php', 
			'feed-manager'
		);
		$this->publishes([
				__DIR__ . '/config/feedmanager.php' => config_path('feedmanager.php'),
		]);
		if ($this->app->runningInConsole()) {
			$this->commands([
				FeedFetchCommand::class				
			]);
    }
	}
	
	public function register()
	{
		$this->app->singleton('FeedManagerFactory', function () {
				$config = config('feedmanager');

				if (! $config) {
						throw new \RunTimeException('Feedmanager configuration not found. Please run `php artisan vendor:publish`');
				}

				return new FeedManagerFactory($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
     return ['FeedManagerFactory'];
	}
}
