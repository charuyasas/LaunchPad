<?php

namespace App\Providers;

use App\Dashboard\Modules\Sites\IO\GitHub\GitHubDeployPipeline;
use App\Dashboard\Modules\Sites\IO\Http\HttpVersionReader;
use App\Dashboard\Modules\Sites\IO\Http\LaravelHttpTransport;
use App\Dashboard\Modules\Sites\IO\Screenshots\PlaywrightScreenshotter;
use App\Dashboard\Modules\Sites\IO\Screenshots\SiteScreenshotter;
use App\Dashboard\Modules\Sites\IO\Storage\JsonSiteRepository;
use App\Dashboard\Modules\Sites\UseCases\DeployPipeline;
use App\Dashboard\Modules\Sites\UseCases\RetryingSiteChecker;
use App\Dashboard\Modules\Sites\UseCases\SiteChecker;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Dashboard\Modules\Sites\UseCases\SiteStatusClassifier;
use App\Dashboard\Modules\Sites\UseCases\UsleepSleeper;
use App\Dashboard\Modules\Sites\UseCases\VersionParser;
use App\Dashboard\Modules\Sites\UseCases\VersionReader;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteRepository::class, function () {
            return new JsonSiteRepository((string) config('launchpad.sites_path'));
        });

        $this->app->bind(SiteChecker::class, function () {
            return new RetryingSiteChecker(
                transport: new LaravelHttpTransport,
                classifier: new SiteStatusClassifier((int) config('launchpad.slow_threshold_ms')),
                sleeper: new UsleepSleeper,
                attempts: (int) config('launchpad.retries'),
                timeoutMs: (int) config('launchpad.timeout_ms'),
                backoffMs: config('launchpad.backoff_ms'),
                samples: max(1, (int) config('launchpad.samples')),
            );
        });

        $this->app->bind(VersionReader::class, function () {
            return new HttpVersionReader(
                transport: new LaravelHttpTransport,
                parser: new VersionParser,
                timeoutMs: (int) config('launchpad.timeout_ms'),
            );
        });

        $this->app->bind(SiteScreenshotter::class, PlaywrightScreenshotter::class);

        $this->app->bind(DeployPipeline::class, function () {
            return new GitHubDeployPipeline(
                repository: config('launchpad.deploy.repository'),
                workflow: (string) config('launchpad.deploy.workflow', 'deploy-on-main.yml'),
                token: config('launchpad.deploy.token'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
