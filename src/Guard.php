<?php declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface as EventSubscriberContract;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Plugin\PluginInterface as ComposerPluginContract;
use Composer\Script\ScriptEvents;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageAbandonedInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageDescriptionInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageLicenseInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageNameInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByPackageTypeInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\ByVersionNameInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\InspectorInterface;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\StubInspector;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Suppliers\FromComposerLockSupplier;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Suppliers\FromComposerManifestSupplier;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Suppliers\SupplierInterface as SupplierContract;

use RuntimeException;

use function in_array;

final class Guard implements ComposerPluginContract, EventSubscriberContract
{
    const CHECK_LOCK_FILE    = 'check-lock-file';
    const CHECK_DESCRIPTION  = 'check-description';
    const CHECK_LICENSE      = 'check-license';
    const CHECK_ABANDONED    = 'check-abandoned';
    const CHECK_VERSION    = 'check-version';

    /** @var bool */
    private $useLockFile;
    /** @var Composer */
    private $composer;
    /** @var array<string,InspectorInterface> */
    private $inspectors;
    /** @var Whitelist */
    private $whitelist;
    /** @var SupplierContract */
    private $supplier;

    public function activate(Composer $composer, IOInterface $io)
    {
        $manifest = json_decode(file_get_contents(Factory::getComposerFile()), true);
        $settings = $manifest['extra']['production-dependencies-guard'] ?? [];

        $checkLicense     = in_array(self::CHECK_LICENSE,     $settings, true);
        $checkAbandoned   = in_array(self::CHECK_ABANDONED,   $settings, true);
        $checkDescription = in_array(self::CHECK_DESCRIPTION, $settings, true);
        $checkVersion     = in_array(self::CHECK_VERSION, $settings, true);
        $this->inspectors = [
            'dev-package-name'     => new ByPackageNameInspector(),
            'dev-package-type'     => new ByPackageTypeInspector(),
            'license'              => $checkLicense     ? new ByPackageLicenseInspector($settings) : new StubInspector(),
            'abandoned'            => $checkAbandoned   ? new ByPackageAbandonedInspector()        : new StubInspector(),
            'description-keywords' => $checkDescription ? new ByPackageDescriptionInspector()      : new StubInspector(),
            'dev-version'          => $checkVersion     ? new ByVersionNameInspector()             : new StubInspector(),
        ];

        $this->composer    = $composer;
        $this->whitelist   = new Whitelist($settings);
        $this->useLockFile = in_array(self::CHECK_LOCK_FILE, $settings, true);
        $this->supplier    = $this->useLockFile ? new FromComposerLockSupplier() : new FromComposerManifestSupplier();
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => ['checkGeneric'],
            ScriptEvents::POST_UPDATE_CMD  => ['checkGeneric'],
        );
    }

    private function check(SupplierContract $supplier, CompletePackageInterface... $packages)
    {
        $violations = [];
        foreach ($packages as $package) {
            $packageName            = strtolower($package->getName());
            $packageId              = sprintf('%s (via %s)', $packageName, implode(', ', $supplier->why($packageName)));
            $violations[$packageId] = [];
            foreach ($this->inspectors as $rule => $inspector) {
                if (! $inspector->canUse($package)) {
                    $violations[$packageId] []= $rule;
                }
            }
        }
        if (($violations = array_filter($violations)) !== []) {
            $message = sprintf(
                'Dependencies guard has found violations in require-dependencies (source: %s):',
                $this->useLockFile ? 'lock-file' : 'manifest'
            );
            foreach ($violations as $packageName => $rules) {
                $message .= PHP_EOL . ' - ' . $packageName . ': ' . implode(', ', $rules);
            }
            throw new RuntimeException($message);
        }
    }

    /** @return array<int, CompletePackageInterface> */
    private function find(string... $packages): array {
        return array_filter(
            array_filter(
                $this->composer->getRepositoryManager()->getLocalRepository()->getPackages(),
                static function (CompletePackageInterface $package) use ($packages): bool { return in_array(strtolower($package->getName()), $packages, true); }
            ),
            function (CompletePackageInterface $package): bool { return ! $this->whitelist->canUse($package); }
        );
    }

    public function checkGeneric()
    {
        $this->check($this->supplier, ...$this->find(...$this->supplier->packages()));
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}