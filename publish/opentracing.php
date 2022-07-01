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
return [
    'default' => env('TRACER_DRIVER', 'jaeger'),
    'enable' => [
        'guzzle' => env('TRACER_ENABLE_GUZZLE', true),
        'redis' => env('TRACER_ENABLE_REDIS', true),
        'db' => env('TRACER_ENABLE_DB', true),
        'method' => env('TRACER_ENABLE_METHOD', false), // Experimental
        'exception' => env('TRACER_ENABLE_EXCEPTION', true),
    ],
    'tracer' => [
        'jaeger' => [
            'driver' => Hyperf\Tracer\Adapter\JaegerTracerFactory::class,
            'name' => env('APP_NAME', 'skeleton'),
            'options' => [
                'sampler' => [
                    'type' => Jaeger\SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                'logging' => true,
                'dispatch_mode' => Jaeger\Config::JAEGER_OVER_BINARY_UDP,
                'local_agent' => [
                    'reporting_host' => env('JAEGER_REPORTING_HOST', 'localhost'),
                    'reporting_port' => env('JAEGER_REPORTING_PORT', 6832),
                ],
            ],
        ],
        'noop' => [
            'driver' => Hyperf\Tracer\Adapter\NoopTracerFactory::class,
        ],
    ],
    'tags' => [
        'http' => [
            'url' => 'http.url',
            'host' => 'http.host',
            'method' => 'http.method',
            'target' => 'http.target',
            'route' => 'http.route',
            'scheme' => 'http.scheme',
            'server_name' => 'http.server_name',
            'status_code' => 'http.status_code',
            'request.header' => 'http.request.header',
            'response.header' => 'http.response.header',
        ],
        'redis' => [
            'arguments' => 'arguments',
            'result' => 'result',
        ],
        'db' => [
            'db.query' => 'db.query',
            'db.statement' => 'db.statement',
            'db.query_time' => 'db.query_time',
        ],
        'exception' => [
            'class' => 'exception.class',
            'code' => 'exception.code',
            'message' => 'exception.message',
            'stack_trace' => 'exception.stack_trace',
        ],
        'coroutine' => [
            'id' => 'coroutine.id',
        ],
        'service' => [
            'name' => 'service.name',
            'namespace' => 'service.namespace',
            'instance.id' => 'service.instance.id',
            'version' => 'service.version',
        ],
        'net' => [
            'host.port' => 'net.host.port',
        ],
    ],
];
