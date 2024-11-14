<?php

declare(strict_types=1);

namespace Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors;

use Composer\Package\CompletePackageInterface;
use Kalessil\Composer\Plugins\ProductionDependenciesGuard\Inspectors\InspectorInterface as InspectorContract;

final class ByPackageLicenseInspector implements InspectorContract
{
    /** @var array<int,string> */
    private $allowed;

    public function __construct(array $settings)
    {
        $this->allowed = array_map(
            static function (string $setting): string {
                return str_replace('accept-license:', '', $setting);
            },
            array_filter(
                array_map('strtolower', array_map('trim', $settings)),
                static function (string $setting): bool {
                    return strncmp($setting, 'accept-license:', 15) === 0;
                }
            )
        );
    }

    public function canUse(CompletePackageInterface $package): bool
    {
        $license = $package->getLicense();
        $hasLicense = ! empty($license);
        if ($hasLicense && $this->allowed !== []) {
            $unfit = array_diff(array_map('strtolower', array_map('trim', $license)), $this->allowed);
            return $unfit === [];
        }

        return $hasLicense;
    }
}