<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClusterSetting;

class ClusterSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'cluster_label' => 0,
                'segment_name' => 'Champions',
                'color' => '#10b981',
                'icon' => '🏆',
            ],
            [
                'cluster_label' => 1,
                'segment_name' => 'Loyal Customers',
                'color' => '#3b82f6',
                'icon' => '💫',
            ],
            [
                'cluster_label' => 2,
                'segment_name' => 'At Risk',
                'color' => '#f59e0b',
                'icon' => '⚠️',
            ],
            [
                'cluster_label' => 3,
                'segment_name' => 'Hibernating',
                'color' => '#f43f5e',
                'icon' => '❄️',
            ],
        ];

        foreach ($defaults as $data) {
            ClusterSetting::updateOrCreate(
                ['cluster_label' => $data['cluster_label']],
                $data
            );
        }
    }
}
