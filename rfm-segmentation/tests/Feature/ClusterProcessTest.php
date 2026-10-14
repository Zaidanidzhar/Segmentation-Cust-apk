<?php

namespace Tests\Feature;

use App\Models\ClusterSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClusterProcessTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_saves_cluster_settings_from_request(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user);

        $response = $this->post('/admin/process-cluster', [
            'n_clusters' => 3,
            'cluster_configs' => [
                0 => [
                    'label' => 0,
                    'segment_name' => 'Champions',
                    'color' => '#10b981',
                    'icon' => '🏆',
                ],
                1 => [
                    'label' => 1,
                    'segment_name' => 'Loyal',
                    'color' => '#3b82f6',
                    'icon' => '💫',
                ],
                2 => [
                    'label' => 2,
                    'segment_name' => 'At Risk',
                    'color' => '#f59e0b',
                    'icon' => '⚠️',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('cluster_settings', [
            'cluster_label' => 0,
            'segment_name' => 'Champions',
        ]);

        $this->assertDatabaseHas('cluster_settings', [
            'cluster_label' => 1,
            'segment_name' => 'Loyal',
        ]);

        $this->assertDatabaseHas('cluster_settings', [
            'cluster_label' => 2,
            'segment_name' => 'At Risk',
        ]);
    }
}
