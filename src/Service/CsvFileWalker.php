<?php
declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use LogicException;
use RuntimeException;

class CsvFileWalker
{
    public function walk(string $filename, callable $func, array $mandatoryColumns = null): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('"%s" file is not exists', $filename));
        }

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open "%s" file', $filename));
        }

        try {
            $columns = fgetcsv($handle);
            $count = count($columns);

            $index = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $index++;

                if (!isset($row[0]) || [null] === $row) {
                    continue;
                }

                $rowCount = count($row);

                if ($count !== $rowCount) {
                    throw new LogicException(sprintf('Row #%d has wrong number of columns. Should have %d columns, got %d', $index, $count, $rowCount));
                }

                $data = array_combine($columns, $row);

                if ($mandatoryColumns !== null) {
                    foreach ($mandatoryColumns as $mandatoryColumn) {
                        if (!array_key_exists($mandatoryColumn, $data)) {
                            throw new LogicException(sprintf('Row #%d has not "%s" column', $index, $mandatoryColumn));
                        }
                    }
                }

                $func($data);
            }
        } finally {
            fclose($handle);
        }
    }
}