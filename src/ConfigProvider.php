<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf + PicPay.
 *
 * @link     https://github.com/PicPay/hyperf-tracer
 * @document https://github.com/PicPay/hyperf-tracer/wiki
 * @contact  @PicPay
 * @license  https://github.com/PicPay/hyperf-tracer/blob/main/LICENSE
 */
namespace Hyperf\Tracer;

use GuzzleHttp\Client;
use Hyperf\Tracer\Aspect\HttpClientAspect;
use Hyperf\Tracer\Aspect\MongoCollectionAspect;
use Hyperf\Tracer\Aspect\RedisAspect;
use Hyperf\Tracer\Listener\DbQueryExecutedListener;
use Hyperf\Tracer\Middleware\TraceMiddleware;
use Jaeger\SpanContext;
use Jaeger\ThriftUdpTransport;
use OpenTracing\Tracer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Tracer::class => TracerFactory::class,
                SwitchManager::class => SwitchManagerFactory::class,
                SpanTagManager::class => SpanTagManagerFactory::class,
                Client::class => Client::class,
            ],
            'aspects' => [
                HttpClientAspect::class,
                MongoCollectionAspect::class,
                RedisAspect::class,
            ],
            'listeners' => [
                DbQueryExecutedListener::class,
            ],
            'middlewares' => [
                TraceMiddleware::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => [
                        ThriftUdpTransport::class => __DIR__ . '/../class_map/ThriftUdpTransport.php',
                        SpanContext::class => __DIR__ . '/../class_map/SpanContext.php',
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for tracer.',
                    'source' => __DIR__ . '/../publish/opentracing.php',
                    'destination' => BASE_PATH . '/config/autoload/opentracing.php',
                ],
            ],
        ];
    }
}
