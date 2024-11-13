<?php declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors;

use Composer\Package\CompletePackageInterface as PackageContract;
use PHPUnit\Framework\TestCase;

final class ByPackageLicenseInspectorTest extends TestCase
{
    /** @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageLicenseInspector::<public> */
    public function testComponent()
    {
        $mock = $this->createMock(PackageContract::class);
        $mock->expects($this->atLeastOnce())->method('getLicense')->willReturnOnConsecutiveCalls(
            [''], [], ['MIT'], ['mit', 'apache'], ['mit', 'proprietary', 'apache']
        );

        $component = new ByPackageLicenseInspector(['accept-license:mit', 'accept-license:apache']);

        $this->assertFalse($component->canUse($mock));
        $this->assertFalse($component->canUse($mock));
        $this->assertTrue($component->canUse($mock));
        $this->assertTrue($component->canUse($mock));
        $this->assertFalse($component->canUse($mock));
    }
}