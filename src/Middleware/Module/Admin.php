<?php

namespace App\Middleware\Module;

use App\Entity\Repository\SettingsRepository;
use App\Event;
use App\EventDispatcher;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /admin pages.
 */
class Admin
{
    public function __construct(
        protected EventDispatcher $dispatcher,
        protected SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->settingsRepo->readSettings();

        $event = new Event\BuildAdminMenu($request, $settings);
        $this->dispatcher->dispatch($event);

        $view = $request->getView();

        $active_tab = null;
        $routeContext = RouteContext::fromRequest($request);
        $current_route = $routeContext->getRoute();

        if ($current_route instanceof RouteInterface) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->addData(
            [
                'admin_panels' => $event->getFilteredMenu(),
            ]
        );

        // These two intentionally separated (the sidebar needs admin_panels).
        $view->addData(
            [
                'sidebar' => $view->render(
                    'admin/sidebar',
                    [
                        'active_tab' => $active_tab,
                    ]
                ),
            ]
        );

        return $handler->handle($request);
    }
}
