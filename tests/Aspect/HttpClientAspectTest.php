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
namespace HyperfTest\Tracer\Aspect;

use Hyperf\Tracer\Aspect\HttpClientAspect;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenTracing\Tracer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class HttpClientAspectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testClearUriNumbers(): void
    {
        $aspect = new HttpClientAspect(
            Mockery::spy(Tracer::class),
            Mockery::spy(SwitchManager::class),
            Mockery::spy(SpanTagManager::class),
        );

        self::assertSame('/v1/test', $aspect->clearUri('/v1/test'));
        self::assertSame('/v2/test/<NUMBER>', $aspect->clearUri('/v2/test/123'));
        self::assertSame('/v3/test/<NUMBER>/bar', $aspect->clearUri('/v3/test/123/bar'));
        self::assertSame('/v4/test/<NUMBER>/bar/<NUMBER>/', $aspect->clearUri('/v4/test/123/bar/456/'));
        self::assertSame('/v5/test/<NUMBER>/<NUMBER>', $aspect->clearUri('/v5/test/123/456'));
        self::assertSame('/v6/test/<NUMBER>/<NUMBER>/', $aspect->clearUri('/v6/test/123/456/'));
        self::assertSame('/v7/test/<NUMBER>/<NUMBER>/<NUMBER>', $aspect->clearUri('/v7/test/123/456/789'));
        self::assertSame('/v8/test/<NUMBER>/<NUMBER>/<NUMBER>/', $aspect->clearUri('/v8/test/123/456/789/'));
    }

    public function testClearUriUuids(): void
    {
        $aspect = new HttpClientAspect(
            Mockery::spy(Tracer::class),
            Mockery::spy(SwitchManager::class),
            Mockery::spy(SpanTagManager::class),
        );

        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        self::assertSame('/v1/test', $aspect->clearUri('/v1/test'));
        self::assertSame('/v2/test/<UUID>', $aspect->clearUri("/v2/test/{$uuid}"));
        self::assertSame('/v3/test/<UUID>/bar', $aspect->clearUri("/v3/test/{$uuid}/bar"));
        self::assertSame('/v4/test/<UUID>/bar/<UUID>/', $aspect->clearUri("/v4/test/{$uuid}/bar/{$uuid}/"));
        self::assertSame('/v5/test/<UUID>/<UUID>', $aspect->clearUri("/v5/test/{$uuid}/{$uuid}"));
        self::assertSame('/v6/test/<UUID>/<UUID>/', $aspect->clearUri("/v6/test/{$uuid}/{$uuid}/"));
        self::assertSame('/v7/test/<UUID>/<UUID>/<UUID>', $aspect->clearUri("/v7/test/{$uuid}/{$uuid}/{$uuid}"));
        self::assertSame('/v8/test/<UUID>/<UUID>/<UUID>/', $aspect->clearUri("/v8/test/{$uuid}/{$uuid}/{$uuid}/"));
    }
}
