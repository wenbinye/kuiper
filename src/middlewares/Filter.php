<?php
namespace kuiper\web\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;
use LogicException;
use kuiper\annotations\ReaderInterface;
use kuiper\web\annotation\filter\FilterInterface;
use ReflectionClass;

class Filter
{
    /**
     * @var ReaderInterface
     */
    private $annotationReader;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $cache;
    
    public function __construct(ReaderInterface $annotationReader, ContainerInterface $container)
    {
        $this->annotationReader = $annotationReader;
        $this->container = $container;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $routeInfo = $request->getAttribute('routeInfo');
        if ($routeInfo === null) {
            throw new LogicException("Please add Filter middleware before dispatch");
        }
        if (isset($routeInfo['controller']) && isset($routeInfo['action'])) {
            $filters = $this->getFilters($routeInfo['controller'], strtolower($routeInfo['action']));
            return $this->callFilters($request, $response, $filters, $next);
        } else {
            return $next($request, $response);
        }
    }

    private function getFilters($controller, $action)
    {
        $className = is_string($controller) ? $controller : get_class($controller);
        if (isset($this->cache[$className][$action])) {
            return $this->cache[$className][$action];
        }
        $class = new ReflectionClass($className);
        $method = $class->getMethod($action);
        
        $annots = [];
        foreach ($this->annotationReader->getClassAnnotations($class) as $annot) {
            if ($annot instanceof FilterInterface) {
                $annots[get_class($annot)] = $annot;
            }
        }
        foreach ($this->annotationReader->getMethodAnnotations($method) as $annot) {
            if ($annot instanceof FilterInterface) {
                $annots[get_class($annot)] = $annot;
            }
        }
        usort($annots, function ($a, $b) {
            return $a->getPriority() - $b->getPriority();
        });
        $filters = [];
        foreach ($annots as $annot) {
            $filters[] = $annot->createMiddleware($this->container);
        }
        return $this->cache[$className][$action] = $filters;
    }

    private function callFilters($request, $response, $filters, $final, $i = 0)
    {
        if ($i < count($filters)) {
            return $filters[$i]($request, $response, function ($request, $response) use ($filters, $final, $i) {
                return $this->callFilters($request, $response, $filters, $final, $i+1);
            });
        } else {
            return $final($request, $response);
        }
    }
}
