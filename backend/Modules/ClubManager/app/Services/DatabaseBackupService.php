<?php

namespace Modules\ClubManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;
use ZipArchive;

class DatabaseBackupService
{
    /**
     * Generate a database backup, compress it into a zip file, and return the zip file path.
     *
     * @return array Array containing 'path' and 'filename'
     * @throws Exception
     */
    public function generateBackupZip(): array
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0777, true, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $dbName = config('database.connections.mysql.database', 'database');
        $baseFilename = "backup_{$dbName}_{$timestamp}";
        $sqlPath = "{$backupDir}/{$baseFilename}.sql";
        $zipPath = "{$backupDir}/{$baseFilename}.zip";

        // Try mysqldump first
        $dumpSuccess = $this->dumpWithMysqldump($sqlPath);

        // Fallback to PHP dumper if mysqldump failed or produced empty file
        if (!$dumpSuccess || !File::exists($sqlPath) || File::size($sqlPath) === 0) {
            $this->dumpWithPhp($sqlPath);
        }

        if (!File::exists($sqlPath) || File::size($sqlPath) === 0) {
            throw new Exception(__('Failed to generate database dump file.'));
        }

        // Compress SQL file to ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($sqlPath, "{$baseFilename}.sql");
            $zip->close();
            @unlink($sqlPath);
        } else {
            @unlink($sqlPath);
            throw new Exception(__('Failed to create ZIP archive for database backup.'));
        }

        return [
            'path' => $zipPath,
            'filename' => "{$baseFilename}.zip",
        ];
    }

    /**
     * Export database using mysqldump binary
     */
    private function dumpWithMysqldump(string $sqlPath): bool
    {
        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $cmd = sprintf(
                'mysqldump --single-transaction --quick --routines --triggers --user=%s --host=%s --port=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($sqlPath)
            );

            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge(getenv(), ['MYSQL_PWD' => $password]);
            $process = proc_open($cmd, $descriptorspec, $pipes, null, $env);

            if (is_resource($process)) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnCode = proc_close($process);
                return $returnCode === 0;
            }
        } catch (Exception $e) {
            // Fallback will trigger
        }

        return false;
    }

    /**
     * Fallback PHP database dumper
     */
    private function dumpWithPhp(string $sqlPath): void
    {
        $handle = fopen($sqlPath, 'w');
        if (!$handle) {
            throw new Exception(__('Unable to open backup SQL file for writing.'));
        }

        fwrite($handle, "-- Database Backup (PHP Fallback)\n");
        fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $columnName = "Tables_in_{$dbName}";

        foreach ($tables as $tableObj) {
            $table = $tableObj->$columnName ?? current((array)$tableObj);
            if (!$table) continue;

            fwrite($handle, "-- Table structure for `$table` --\n");
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            
            $createTable = DB::select("SHOW CREATE TABLE `$table`");
            if (!empty($createTable)) {
                $createSql = ((array)$createTable[0])['Create Table'] ?? null;
                if ($createSql) {
                    fwrite($handle, $createSql . ";\n\n");
                }
            }

            fwrite($handle, "-- Data for `$table` --\n");
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArray = (array)$row;
                $values = array_map(function ($val) {
                    if (is_null($val)) return 'NULL';
                    return DB::getPdo()->quote($val);
                }, array_values($rowArray));

                $columns = array_map(function ($col) {
                    return "`$col`";
                }, array_keys($rowArray));

                if (!empty($columns)) {
                    $insertSql = sprintf(
                        "INSERT INTO `%s` (%s) VALUES (%s);\n",
                        $table,
                        implode(', ', $columns),
                        implode(', ', $values)
                    );
                    fwrite($handle, $insertSql);
                }
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }
}
