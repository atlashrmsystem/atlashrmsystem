<?php

namespace App\Support;

class DepartmentAssignmentPolicy
{
    /**
     * Canonical departments currently used by HR.
     *
     * @return array<int, string>
     */
    public static function departments(): array
    {
        return [
            'Operations',
            'Food & Beverage',
            'Digital Marketing',
            'Finance',
            'HR',
            'Warehouse',
        ];
    }

    public static function normalize(?string $department): string
    {
        if ($department === null) {
            return '';
        }

        $normalized = strtolower(trim($department));
        $normalized = str_replace('&', ' and ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    /**
     * Policy format:
     * - show_brand: display brand selector
     * - show_store: display store selector
     * - requires_brand: brand selection mandatory
     * - requires_store: store selection mandatory
     * - note: helper text for UI
     *
     * @return array{show_brand: bool, show_store: bool, requires_brand: bool, requires_store: bool, note: string}
     */
    public static function forDepartment(?string $department): array
    {
        $key = self::normalize($department);

        $requiredAssignment = [
            'show_brand' => true,
            'show_store' => true,
            'requires_brand' => true,
            'requires_store' => true,
            'note' => 'Brand and store are required for this department.',
        ];

        $optionalAssignment = [
            'show_brand' => false,
            'show_store' => false,
            'requires_brand' => false,
            'requires_store' => false,
            'note' => 'Brand and store are not required for this department.',
        ];

        return match ($key) {
            'food beverage', 'f b', 'f and b', 'food and beverage' => $requiredAssignment,
            'operations', 'operation', 'warehouse', 'marketing', 'digital marketing', 'finance', 'hr', 'human resources' => $optionalAssignment,
            default => $optionalAssignment,
        };
    }

    /**
     * @return array<int, array{name: string, policy: array{show_brand: bool, show_store: bool, requires_brand: bool, requires_store: bool, note: string}}>
     */
    public static function options(): array
    {
        return collect(self::departments())
            ->map(fn (string $name) => [
                'name' => $name,
                'policy' => self::forDepartment($name),
            ])
            ->values()
            ->all();
    }
}
