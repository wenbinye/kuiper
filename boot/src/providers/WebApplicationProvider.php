<?php

namespace kuiper\boot\providers;

use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use kuiper\web\ApplicationInterface;
use kuiper\web\ErrorHandler;
use kuiper\web\ErrorHandlerInterface;
use kuiper\web\FastRouteUrlResolver;
use kuiper\web\MicroApplication;
use kuiper\web\RouteRegistarInterface;
use kuiper\web\security\Auth;
use kuiper\web\security\AuthInterface;
use kuiper\web\security\PermissionChecker;
use kuiper\web\security\PermissionCheckerInterface;
use kuiper\web\session\CacheSessionHandler;
use kuiper\web\session\FlashInterface;
use kuiper\web\session\FlashSession;
use kuiper\web\session\Session;
use kuiper\web\session\SessionInterface;
use kuiper\web\UrlResolverInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\GenericEvent as Event;
use Zend\Diactoros\ServerRequestFactory;

class WebApplicationProvider extends Provider
{
    public function register()
    {
        $settings = $this->settings;
        $this->services->addDefinitions([
            ApplicationInterface::class => di\factory([$this, 'provideWebApplication']),
            ErrorHandlerInterface::class => di\object(ErrorHandler::class),
            RouteRegistarInterface::class => di\get(ApplicationInterface::class),
            UrlResolverInterface::class => di\object(FastRouteUrlResolver::class)
            ->constructor(di\params([
                'baseUri' => $settings['app.base_uri'],
            ])),
            ServerRequestInterface::class => di\factory([ServerRequestFactory::class, 'fromGlobals']),
            FlashInterface::class => di\object(FlashSession::class),
            AuthInterface::class => di\object(Auth::class)
            ->method('initialize'),
            SessionInterface::class => di\factory([$this, 'provideSession']),
            PermissionCheckerInterface::class => di\object(PermissionChecker::class),
        ]);
    }

    public function provideSession()
    {
        $session = new Session($conf = $this->settings['app.session']);
        $cache = $this->app->get(CacheItemPoolInterface::class);
        session_set_save_handler(new CacheSessionHandler($cache, $conf), true);
        $session->start();

        return $session;
    }

    public function provideWebApplication()
    {
        $app = new MicroApplication($container = $this->app->getContainer());
        foreach ($this->app->getModules() as $module) {
            if ($module->getBasePath()) {
                $file = $module->getBasePath().'/routes/web.php';
                if (file_exists($file)) {
                    if ($namespace = $module->getNamespace()) {
                        $app->group([
                            'namespace' => $namespace.'\\controllers',
                        ], function ($app) use ($container, $file) {
                            require $file;
                        });
                    } else {
                        require $file;
                    }
                }
            }
        }
        $this->app->getEventDispatcher()->dispatch(Events::BOOT_WEB_APPLICATION, new Event($app));

        return $app;
    }
}
