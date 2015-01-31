<?php namespace JeffreyVdb\Recaptcha;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Recaptcha class
 *
 * @author     Greg Gilbert
 * @link       https://github.com/greggilbert
 */
class RecaptchaServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
//        $this->package('jeffreyvdb/recaptcha');
        // Add publish paths
        $this->loadViewsFrom(__DIR__ . '/views', 'recaptcha');
        $this->publishes([
            __DIR__ . '/views'             => base_path('resources/views/vendor/recaptcha')
        ]);

        // Merge config
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'recaptcha');

        $this->addValidator();
        $this->addFormMacro();
    }

    /**
     * Extends Validator to include a recaptcha type
     */
    public function addValidator()
    {
        $validator = $this->app['Validator'];

        $validator::extend('recaptcha', function ($attribute, $value, $parameters) {
            $captcha   = app('JeffreyVdb\Recaptcha\RecaptchaInterface');
            $challenge = app('Input')->get($captcha->getResponseKey());

            return $captcha->check($challenge, $value);
        });
    }

    /**
     * Extends Form to include a recaptcha macro
     */
    public function addFormMacro()
    {
        $this->app['form']->macro('captcha', function ($options = array()) {
            $configOptions = config('recaptcha.options', array());

            $mergedOptions = array_merge($configOptions, $options);

            $data = array(
                'public_key' => config('recaptcha.public_key'),
                'options'    => $mergedOptions,
            );

            if (array_key_exists('lang', $mergedOptions) && "" !== trim($mergedOptions['lang'])) {
                $data['lang'] = $mergedOptions['lang'];
            }

            $view = 'recaptcha::' . app('JeffreyVdb\Recaptcha\RecaptchaInterface')->getTemplate();

            $configTemplate = config('recaptcha.template', '');

            if (array_key_exists('template', $options)) {
                $view = $options['template'];
            }
            elseif ("" !== trim($configTemplate)) {
                $view = $configTemplate;
            }

            return app('view')->make($view, $data);
        });
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('JeffreyVdb\Recaptcha\RecaptchaInterface', function () {
            if (config('recaptcha.version', false) === 2 || config('recaptcha.v2', false)) {
                return new CheckRecaptchaV2;
            }

            return new CheckRecaptcha;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {

    }

}
