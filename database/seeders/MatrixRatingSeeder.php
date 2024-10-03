<?php

namespace Database\Seeders;

use App\Models\MatrixRating;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatrixRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['rating', 2024, 3.75, 4, 'A', 'A', '{"A": 0.1, "B": 0.2, "C": 0.5, "D": 0.15, "E": 0.05}'],
            ['rating', 2024, 3.50, 3.74, 'B', 'B', '{"A": 0.1, "B": 0.2, "C": 0.5, "D": 0.15, "E": 0.05}'],
            ['rating', 2024, 2.75, 3.49, 'C', 'C', '{"A": 0.1, "B": 0.2, "C": 0.5, "D": 0.15, "E": 0.05}'],
            ['rating', 2024, 2.00, 2.74, 'D', 'D', '{"A": 0.1, "B": 0.2, "C": 0.5, "D": 0.15, "E": 0.05}'],
            ['rating', 2024, 0.00, 1.99, 'E', 'E', '{"A": 0.1, "B": 0.2, "C": 0.5, "D": 0.15, "E": 0.05}'],
        ];

        foreach ($data as $item) {
            MatrixRating::insert([
                'name' => $item[0],
                'periode' => $item[1],
                'min_value' => $item[2],
                'max_value' => $item[3],
                'grade' => $item[4],
                'variable' => $item[5],
                'percentage' => $item[6],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
