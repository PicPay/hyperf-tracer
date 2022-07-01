<?php
/**
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);
/**
 * This file is part of Hyperf + PicPay.
 *
 * @link     https://github.com/PicPay/hyperf-tracer
 * @document https://github.com/PicPay/hyperf-tracer/wiki
 * @contact  @PicPay
 * @license  https://github.com/PicPay/hyperf-tracer/blob/main/LICENSE
 */
namespace Hyperf\Tracer\Middleware;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Tracer\ExceptionAppender;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

class TraceMiddleware implements MiddlewareInterface
{
    use SpanStarter;
    use ExceptionAppender;

    private SwitchManager $switchManager;

    private SpanTagManager $spanTagManager;

    private Tracer $tracer;

    public function __construct(Tracer $tracer, SwitchManager $switchManager, SpanTagManager $spanTagManager)
    {
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
        $this->spanTagManager = $spanTagManager;
    }

    /**
     * Process an incoming server request.
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $span = $this->buildSpan($request);

        defer(function () {
            try {
                $this->tracer->flush();
            } catch (Throwable $exception) {
                if (ApplicationContext::hasContainer() && ApplicationContext::getContainer()->has(StdoutLoggerInterface::class)) {
                    ApplicationContext::getContainer()
                        ->get(StdoutLoggerInterface::class)
                        ->error($exception->getMessage());
                }
            }
        });
        try {
            $response = $handler->handle($request);
            $span->setTag($this->spanTagManager->get('http', 'status_code'), $response->getStatusCode());
            $span->setTag('otel.status_code', 'OK');
        } catch (Throwable $exception) {
            $this->switchManager->isEnabled('exception') && $this->appendExceptionToSpan($span, $exception);
            if ($exception instanceof HttpException) {
                $span->setTag($this->spanTagManager->get('http', 'status_code'), $exception->getStatusCode());
            }
            throw $exception;
        } finally {
            $span->finish();
        }

        return $response;
    }

    protected function buildSpan(ServerRequestInterface $request): Span
    {
        $uri = $request->getUri();
        $target = sprintf('%s?%s', $uri->getPath(), $uri->getQuery());
        $host = !is_null($uri->getPort()) ? $uri->getHost() . ':' . $uri->getPort() : $uri->getHost();
        $route = $this->getRoute($request);
        $method = $request->getMethod();

        $span = $this->startSpan(sprintf('%s %s', $method, $route));

        $span->setTag('span.kind', SPAN_KIND_RPC_SERVER);
        $span->setTag('kind', SPAN_KIND_RPC_SERVER);
        $span->setTag($this->spanTagManager->get('http', 'server_name'), $host);
        $span->setTag($this->spanTagManager->get('http', 'target'), $target);
        $span->setTag($this->spanTagManager->get('http', 'method'), $method);
        $span->setTag($this->spanTagManager->get('http', 'route'), $route);
        $span->setTag($this->spanTagManager->get('http', 'scheme'), $uri->getScheme());
        $span->setTag($this->spanTagManager->get('http', 'host'), $host);
        $span->setTag($this->spanTagManager->get('net', 'host.port'), $uri->getPort());
        $span->setTag($this->spanTagManager->get('coroutine', 'id'), (string) Coroutine::id());
        $span->setTag($this->spanTagManager->get('service', 'instance.id'), env('HOSTNAME', $host));

        foreach ($request->getHeaders() as $key => $value) {
            if (!in_array(strtolower($key), $this->headersBlacklist())) {
                $span->setTag($this->spanTagManager->get('http', 'request.header') . '.' . $key, implode(', ', $value));
            }
        }
        return $span;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getRoute(ServerRequestInterface $request): string
    {
        $dispatched = $request->getAttribute('Hyperf\HttpServer\Router\Dispatched');

        if (!$dispatched) {
            return $request->getUri()->getPath();
        }

        if (!$dispatched->handler) {
            return 'not_found';
        }

        return $dispatched->handler->route;
    }

    /**
     * @return string[]
     */
    protected function headersBlacklist(): array
    {
        return [
            'x-authentication'
        ];
    }
}
