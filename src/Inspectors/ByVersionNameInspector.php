<?php

declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors;

use Composer\Package\CompletePackageInterface;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\InspectorInterface as InspectorContract;

final class ByVersionNameInspector implements InspectorContract
{
    private $envVar;

    public function __construct(array $settings = [])
    {
        $this->envVar = array_filter(explode(',', array_values(array_map(
            static function (string $setting): string {
                return str_replace('check-version-environment:', '', $setting);
            },
            array_filter(
                array_map('trim', $settings),
                static function (string $setting): bool {
                    return strncmp($setting, 'check-version-environment:', 26) === 0;
                }
            )
        ))[0] ?? ''));
    }

    public function canUse(CompletePackageInterface $package): bool
    {
        if (isset($this->envVar[1]) && isset($_ENV[$this->envVar[0]]) && $_ENV[$this->envVar[0]] !== $this->envVar[1]) {
            return true;
        }

        return !$package->isDev();
    }
}