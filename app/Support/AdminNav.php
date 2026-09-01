<?php

namespace App\Support;

/**
 * Command Center information architecture (directive §6).
 *
 * Single source of truth for the Super Admin sidebar, topbar title, command
 * palette and mobile bottom navigation. Items whose route exists in this
 * rebuild are `enabled`; the rest are marked `soon` (Phase 9 stages 3–7) and
 * render as clearly-disabled entries with a "Phase 9" chip — never dead links.
 *
 * Every item may carry a `permission` string; the sidebar only shows items the
 * authenticated user is allowed to see (Gate is wired to `role_permissions` in
 * AppServiceProvider). Frontend visibility is never the security boundary —
 * routes keep their server-side middleware.
 */
class AdminNav
{
    /** @return array<int, array{label:string, items:array}> */
    public static function groups(): array
    {
        return [
            [
                'label' => 'Command Center',
                'items' => [
                    ['label' => 'Overview', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2', 'key' => 'o'],
                    ['label' => 'Live Operations', 'soon' => true, 'icon' => 'bolt'],
                    ['label' => 'Executive Intelligence', 'soon' => true, 'icon' => 'chart-bar'],
                ],
            ],
            [
                'label' => 'Financial',
                'items' => [
                    ['label' => 'Financial Overview', 'soon' => true, 'icon' => 'banknotes'],
                    ['label' => 'Transactions', 'route' => 'admin.transactions', 'icon' => 'arrow-right-left', 'key' => 'f'],
                    ['label' => 'Revenue', 'route' => 'admin.revenue-ledger', 'icon' => 'trending-up'],
                    ['label' => 'Commissions', 'soon' => true, 'icon' => 'percent'],
                    ['label' => 'Treasury & Wallets', 'route' => 'admin.treasury', 'icon' => 'wallet'],
                    ['label' => 'Liquidity', 'route' => 'admin.liquidity-wallets', 'icon' => 'droplet'],
                    ['label' => 'Settlement', 'route' => 'admin.settlements', 'icon' => 'scale'],
                    ['label' => 'Reconciliation', 'soon' => true, 'icon' => 'check-badge'],
                    ['label' => 'Master Ledger', 'route' => 'admin.master-ledger', 'icon' => 'book-open'],
                ],
            ],
            [
                'label' => 'Network',
                'items' => [
                    ['label' => 'Customers', 'soon' => true, 'icon' => 'users'],
                    ['label' => 'Agents', 'route' => 'admin.directory', 'icon' => 'user-group', 'key' => 'a'],
                    ['label' => 'Aggregators', 'soon' => true, 'icon' => 'building'],
                    ['label' => 'Agent Locations', 'soon' => true, 'icon' => 'map'],
                    ['label' => 'Network Performance', 'route' => 'admin.network', 'icon' => 'network'],
                    ['label' => 'Geographic Intelligence', 'soon' => true, 'icon' => 'map'],
                ],
            ],
            [
                'label' => 'Risk & Compliance',
                'items' => [
                    [
                        'label' => 'Risk Center', 'soon' => true, 'icon' => 'shield-exclamation',
                        'children' => [
                            ['label' => 'Fraud Alerts', 'soon' => true],
                            ['label' => 'AML Alerts', 'soon' => true],
                            ['label' => 'High-Risk Agents', 'soon' => true],
                            ['label' => 'High-Risk Customers', 'soon' => true],
                            ['label' => 'Held Transactions', 'soon' => true],
                            ['label' => 'Investigation Cases', 'soon' => true],
                        ],
                    ],
                    ['label' => 'Fraud Monitoring', 'soon' => true, 'icon' => 'fingerprint'],
                    ['label' => 'AML Monitoring', 'soon' => true, 'icon' => 'shield-check'],
                    [
                        'label' => 'KYC / KYB',
                        'icon' => 'identification',
                        'children' => [
                            ['label' => 'KYC Hub', 'route' => 'admin.kyc-hub'],
                            ['label' => 'KYC Queue', 'route' => 'admin.kyc-queue'],
                        ],
                    ],
                    ['label' => 'Transaction Holds', 'soon' => true, 'icon' => 'lock'],
                    ['label' => 'Disputes', 'soon' => true, 'icon' => 'scale'],
                    ['label' => 'Security Events', 'soon' => true, 'icon' => 'shield'],
                ],
            ],
            [
                'label' => 'Payments',
                'items' => [
                    ['label' => 'Payment Rails', 'soon' => true, 'icon' => 'credit-card'],
                    ['label' => 'Providers', 'soon' => true, 'icon' => 'server-stack'],
                    ['label' => 'Provider Performance', 'soon' => true, 'icon' => 'chart-bar'],
                    ['label' => 'Webhooks', 'soon' => true, 'icon' => 'globe'],
                    ['label' => 'Failed Payments', 'soon' => true, 'icon' => 'x-circle'],
                    ['label' => 'Payment Routing', 'soon' => true, 'icon' => 'arrow-right-left'],
                ],
            ],
            [
                'label' => 'Operations',
                'items' => [
                    ['label' => 'Operations Queue', 'soon' => true, 'icon' => 'queue-list'],
                    ['label' => 'Approvals', 'soon' => true, 'icon' => 'check-circle'],
                    ['label' => 'Maker / Checker', 'soon' => true, 'icon' => 'user-check'],
                    ['label' => 'Support', 'soon' => true, 'icon' => 'chat'],
                    ['label' => 'Incidents', 'soon' => true, 'icon' => 'exclamation-triangle'],
                    ['label' => 'System Alerts', 'soon' => true, 'icon' => 'bell'],
                ],
            ],
            [
                'label' => 'Analytics',
                'items' => [
                    ['label' => 'Business Intelligence', 'soon' => true, 'icon' => 'sparkles'],
                    ['label' => 'Customer Analytics', 'soon' => true, 'icon' => 'users'],
                    ['label' => 'Agent Analytics', 'soon' => true, 'icon' => 'user-group'],
                    ['label' => 'Aggregator Analytics', 'soon' => true, 'icon' => 'building'],
                    ['label' => 'Liquidity Intelligence', 'soon' => true, 'icon' => 'droplet'],
                    ['label' => 'Revenue Intelligence', 'route' => 'admin.revenue-analytics', 'icon' => 'trending-up'],
                    ['label' => 'Predictive Analytics', 'soon' => true, 'icon' => 'chart-pie'],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['label' => 'Users', 'soon' => true, 'icon' => 'user-check'],
                    ['label' => 'Roles & Permissions', 'soon' => true, 'icon' => 'key'],
                    ['label' => 'Organizations', 'soon' => true, 'icon' => 'building-office'],
                    ['label' => 'Countries', 'soon' => true, 'icon' => 'flag'],
                    ['label' => 'Currencies', 'soon' => true, 'icon' => 'banknotes'],
                    ['label' => 'FX Rates', 'route' => 'admin.fx-rates', 'icon' => 'arrows'],
                    ['label' => 'Fees', 'soon' => true, 'icon' => 'tag'],
                    ['label' => 'Commission Rules', 'soon' => true, 'icon' => 'percent'],
                    ['label' => 'Transaction Limits', 'soon' => true, 'icon' => 'gauge'],
                    ['label' => 'System Configuration', 'route' => 'admin.settings', 'icon' => 'cog'],
                    ['label' => 'API Management', 'soon' => true, 'icon' => 'code'],
                    ['label' => 'Audit Logs', 'route' => 'admin.audit-logs', 'icon' => 'clipboard'],
                    ['label' => 'Security', 'route' => 'admin.security', 'icon' => 'lock'],
                ],
            ],
            [
                'label' => 'Infrastructure',
                'items' => [
                    ['label' => 'System Health', 'soon' => true, 'icon' => 'heart-pulse'],
                    ['label' => 'API Monitoring', 'soon' => true, 'icon' => 'code'],
                    ['label' => 'Database', 'soon' => true, 'icon' => 'database'],
                    ['label' => 'Queues', 'soon' => true, 'icon' => 'queue-list'],
                    ['label' => 'Redis', 'soon' => true, 'icon' => 'cube'],
                    ['label' => 'Storage', 'soon' => true, 'icon' => 'archive'],
                    ['label' => 'Provider Health', 'soon' => true, 'icon' => 'server-stack'],
                    ['label' => 'Webhooks', 'soon' => true, 'icon' => 'globe'],
                    ['label' => 'Nodes', 'route' => 'admin.nodes', 'icon' => 'server'],
                ],
            ],
        ];
    }

    /** Flatten groups into palette actions (only enabled destinations). */
    public static function actions(): array
    {
        $actions = [];
        foreach (static::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (isset($item['route'])) {
                    $actions[] = [
                        'label' => $item['label'],
                        'group' => $group['label'],
                        'route' => $item['route'],
                        'icon' => $item['icon'] ?? 'squares-2x2',
                    ];
                }
                foreach ($item['children'] ?? [] as $child) {
                    if (isset($child['route'])) {
                        $actions[] = [
                            'label' => $child['label'],
                            'group' => $group['label'],
                            'route' => $child['route'],
                            'icon' => $child['icon'] ?? 'squares-2x2',
                        ];
                    }
                }
            }
        }

        return $actions;
    }

    /** Page label for a route name (topbar title). */
    public static function labelFor(string $route): string
    {
        foreach (static::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (($item['route'] ?? null) === $route) {
                    return $item['label'];
                }
                foreach ($item['children'] ?? [] as $child) {
                    if (($child['route'] ?? null) === $route) {
                        return $child['label'];
                    }
                }
            }
        }

        return 'Command Center';
    }
}
