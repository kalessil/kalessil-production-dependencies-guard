<?php

declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors;

use Composer\Package\CompletePackage;
use PHPUnit\Framework\TestCase;

class ByVersionNameInspectorTest extends TestCase
{
    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByVersionNameInspector::canUse
     */
    public function testCanUse()
    {
        $sut = new ByVersionNameInspector();

        $package = new CompletePackage('test', 'dev-abc as 1.2.3', 'dev-abc as 1.2.3');
        $this->assertFalse($sut->canUse($package));

        $package = new CompletePackage('test', '1.2.3', '1.2.3');
        $this->assertTrue($sut->canUse($package));
    }

    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByVersionNameInspector::__construct
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByVersionNameInspector::canUse
     */
    public function testEnvVarCheck()
    {
        $_ENV['ENV'] = 'dev';
        $sut = new ByVersionNameInspector([
            'check-version:ENV,prod'
        ]);

        $package = new CompletePackage('test', 'dev-abc as 1.2.3', 'dev-abc as 1.2.3');
        $this->assertTrue($sut->canUse($package));

        $_ENV['ENV'] = 'prod';
        $this->assertFalse($sut->canUse($package));
    }
}
