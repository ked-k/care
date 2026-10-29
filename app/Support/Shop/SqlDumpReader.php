<?php

namespace App\Support\Shop;

/**
 * Batch 40: reads rows out of a phpMyAdmin/mysqldump SQL file without
 * running it — enough to import data from another app's dump (see
 * ShopImportBotble). Handles multi-row INSERTs, quoted strings with
 * backslash escapes, NULLs and numbers.
 */
class SqlDumpReader
{
    public function __construct(private string $sql)
    {
    }

    /** @return array<int, array<string, ?string>> every row of $table as column => value */
    public function rows(string $table): array
    {
        $rows = [];
        $needle = 'INSERT INTO `'.$table.'` (';
        $offset = 0;

        while (($start = strpos($this->sql, $needle, $offset)) !== false) {
            $colsStart = $start + strlen($needle);
            $colsEnd = strpos($this->sql, ')', $colsStart);
            $columns = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', substr($this->sql, $colsStart, $colsEnd - $colsStart)));

            $pos = strpos($this->sql, 'VALUES', $colsEnd) + 6;
            [$tuples, $offset] = $this->parseValues($pos);
            foreach ($tuples as $values) {
                if (count($values) === count($columns)) {
                    $rows[] = array_combine($columns, $values);
                }
            }
        }

        return $rows;
    }

    /** @return array{0: array<int, array<int, ?string>>, 1: int} tuples and the offset after the statement */
    private function parseValues(int $pos): array
    {
        $sql = $this->sql;
        $len = strlen($sql);
        $tuples = [];
        $row = null;
        $cur = '';
        $inString = false;
        $wasQuoted = false;

        for ($i = $pos; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($inString) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $next = $sql[++$i];
                    $cur .= match ($next) { 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", default => $next };
                } elseif ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $cur .= "'";
                        $i++;
                    } else {
                        $inString = false;
                    }
                } else {
                    $cur .= $ch;
                }
                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $wasQuoted = true;
                $cur = ''; // drop the space between the comma and the opening quote
            } elseif ($ch === '(' && $row === null) {
                $row = [];
                $cur = '';
                $wasQuoted = false;
            } elseif (($ch === ',' || $ch === ')') && $row !== null) {
                $value = $wasQuoted ? $cur : trim($cur);
                $row[] = (! $wasQuoted && strtoupper($value) === 'NULL') ? null : $value;
                $cur = '';
                $wasQuoted = false;
                if ($ch === ')') {
                    $tuples[] = $row;
                    $row = null;
                }
            } elseif ($ch === ';' && $row === null) {
                return [$tuples, $i + 1];
            } elseif ($row !== null) {
                $cur .= $ch;
            }
        }

        return [$tuples, $len];
    }
}
