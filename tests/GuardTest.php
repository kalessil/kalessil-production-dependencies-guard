<?php
declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GuardTest extends TestCase
{
    /** @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<public> */
    public function testSubscribedEvents()
    {
        $this->assertCount(2, Guard::getSubscribedEvents());
    }

    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<public>
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<private>
     */
    public function testGenericBehaviourReporting()
    {
        $composer = $this->createMock(Composer::class);
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $localRepository = $this->createMock(InstalledRepositoryInterface::class);
        $composer->expects($this->atLeastOnce())->method('getRepositoryManager')->willReturn($repositoryManager);
        $repositoryManager->expects($this->atLeastOnce())->method('getLocalRepository')->willReturn($localRepository);
        $localRepository->expects($this->atLeastOnce())->method('getPackages')->willReturnCallback(function (): array {
            $pass = $this->createMock(CompletePackageInterface::class);
            $pass->expects($this->atLeastOnce())->method('getName')->willReturn('kalessil/kalessil');
            $pass->expects($this->atLeastOnce())->method('getType')->willReturn('library');

            $decline = $this->createMock(CompletePackageInterface::class);
            $decline->expects($this->atLeastOnce())->method('getName')->willReturn('phpunit/phpunit');
            $decline->expects($this->atLeastOnce())->method('getType')->willReturn('phpcodesniffer-standard');

            return [$pass, $decline];
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/violations.+ phpunit\/phpunit \(via manifest\): dev-package-name, dev-package-type\s*$/ims'
        );

        putenv(sprintf('COMPOSER=%s/data/activate-none-features.json', __DIR__));
        $component = new Guard();
        $component->activate($composer, $this->createMock(IOInterface::class));
        $component->checkGeneric();
    }

    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<public>
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<private>
     */
    public function testGenericBehaviourPassing()
    {
        $composer = $this->createMock(Composer::class);
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $localRepository = $this->createMock(InstalledRepositoryInterface::class);
        $composer->expects($this->atLeastOnce())->method('getRepositoryManager')->willReturn($repositoryManager);
        $repositoryManager->expects($this->atLeastOnce())->method('getLocalRepository')->willReturn($localRepository);
        $localRepository->expects($this->atLeastOnce())->method('getPackages')->willReturnCallback(function (): array {
            $pass = $this->createMock(CompletePackageInterface::class);
            $pass->expects($this->atLeastOnce())->method('getName')->willReturn('kalessil/kalessil');
            $pass->expects($this->atLeastOnce())->method('getType')->willReturn('library');

            return [$pass];
        });

        putenv(sprintf('COMPOSER=%s/data/activate-none-features.json', __DIR__));
        $component = new Guard();
        $component->activate($composer, $this->createMock(IOInterface::class));
        $component->checkGeneric();
    }

    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<public>
     */
    public function testDeactivate()
    {
        $composer = $this->getMockBuilder(Composer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $composer->expects($this->never())->method($this->anything());

        $component = new Guard();
        $component->deactivate($composer, $this->createMock(IOInterface::class));
    }

    /**
     * @covers \Kalessil\Composer\Plugins\ProductionDependenciesGuard\Guard::<public>
     */
    public function testUninstall()
    {
        $composer = $this->getMockBuilder(Composer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $composer->expects($this->never())->method($this->anything());

        $component = new Guard();
        $component->uninstall($composer, $this->createMock(IOInterface::class));
    }
}