<?php

namespace Tests\Unit;

use App\Helpers\ModelMetricsHelper;
use Tests\TestCase;

class ModelMetricsHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = storage_path('app/model_metrics.json');
        if (file_exists($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_for_view_includes_euclidean_distance_experiment_by_k(): void
    {
        $path = storage_path('app/model_metrics.json');
        $payload = [
            'chosen_k' => 4,
            'euclidean_distance_by_k' => [
                ['k' => 2, 'average_euclidean_distance' => 6.42, 'max_euclidean_distance' => 11.28],
                ['k' => 3, 'average_euclidean_distance' => 5.13, 'max_euclidean_distance' => 9.88],
                ['k' => 4, 'average_euclidean_distance' => 4.07, 'max_euclidean_distance' => 8.35],
            ],
        ];

        file_put_contents($path, json_encode($payload));

        $metrics = ModelMetricsHelper::forView();

        $this->assertSame(4, $metrics['chosen_k']);
        $this->assertSame(
            4.07,
            round((float) $metrics['euclidean_distance_by_k'][2]['average_euclidean_distance'], 2)
        );
        $this->assertSame(8.35, round((float) $metrics['euclidean_distance_by_k'][2]['max_euclidean_distance'], 2));
    }
}
