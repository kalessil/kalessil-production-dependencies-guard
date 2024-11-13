<?php

declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors;

use Composer\Package\CompletePackageInterface;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\InspectorInterface as InspectorContract;

final class ByVersionNameInspector implements InspectorContract
{
    public function canUse(CompletePackageInterface $package): bool
    {
        return !$package->isDev();
    }
}