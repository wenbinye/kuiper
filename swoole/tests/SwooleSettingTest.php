<?php

declare(strict_types=1);

namespace kuiper\swoole;

use PHPUnit\Framework\TestCase;

class SwooleSettingTest extends TestCase
{
    public function testEverySettingHasType()
    {
        foreach (SwooleSetting::instances() as $setting) {
            $this->assertNotNull($setting->type);
        }
    }
}