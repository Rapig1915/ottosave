<?php

namespace App\Traits;

trait CSVResponseTrait
{
    private function makeCSVResponse(array $list, string $filename, array $column_headers = null)
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        if (isset($list) && isset($list[0])) {
            $column_headers = ($column_headers) ? $column_headers : array_keys($list[0]);
            array_unshift($list, $column_headers);

            $callback = function () use ($list) {
                $FH = fopen('php://output', 'w');
                foreach ($list as $row) {
                    fputcsv($FH, $row);
                }
                fclose($FH);
            };

            return response()->stream($callback, 200, $headers);
        } else {
            return response()->json(
                [
                    'message' => 'No results to return.',
                ]
            );
        }
    }
}
