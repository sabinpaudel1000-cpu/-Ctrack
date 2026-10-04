<?php

namespace App\Services\Predictions\Contracts;

/**
 * Both statistical predictors use this shape.
 * A later machine-learning class can implement it and be bound in AppServiceProvider.
 */
interface PredictorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function predict(array $input): array;
}
