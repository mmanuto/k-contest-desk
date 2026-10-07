<?php
declare(strict_types=1);

/**
 * Migra i dati dal MariaDB locale di XAMPP al database PostgreSQL già creato.
 *
 * Uso:
 *   php database/postgresql/migrate-data.php            (solo riepilogo)
 *   php database/postgresql/migrate-data.php --execute  (esegue la migrazione)
 */

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$configDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
if (file_exists($configDir . '.env')) {
    (new \josegonzalez\Dotenv\Loader([$configDir . '.env']))
        ->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

function environment(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function identifier(string $name): string
{
    return '"' . str_replace('"', '""', $name) . '"';
}

$tables = [
    'agecategories',
    'api_requests',
    'athletes',
    'athlete_inscriptions',
    'categorycodes',
    'clubs',
    'club_inscriptions',
    'competitions',
    'competition_club_rankings',
    'federations',
    'katas',
    'logs',
    'rest_api_phinxlog',
    'results_judged_panel',
    'results_match',
    'results_timed',
    'scores',
    'scores_old',
    'sessions',
    'statuses',
    'tatami_assignments',
    'teams',
    'users',
];

$identityColumns = [
    'athlete_inscriptions' => 'id',
    'club_inscriptions' => 'id',
    'competition_club_rankings' => 'id',
    'federations' => 'id',
    'katas' => 'id',
    'logs' => 'id',
    'results_judged_panel' => 'id',
    'results_match' => 'id',
    'results_timed' => 'id',
    'scores' => 'id',
    'scores_old' => 'id',
    'statuses' => 'id',
    'tatami_assignments' => 'id',
    'teams' => 'id',
];

$mysqlDsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    environment('MYSQL_SOURCE_HOST', '127.0.0.1'),
    environment('MYSQL_SOURCE_PORT', '3306'),
    environment('MYSQL_SOURCE_DATABASE', 'kcontest-desk')
);

$postgresDsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    environment('DB_HOST', '127.0.0.1'),
    environment('DB_PORT', '5432'),
    environment('DB_DATABASE', 'kcontest_desk')
);

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $mysql = new PDO(
        $mysqlDsn,
        environment('MYSQL_SOURCE_USERNAME', 'root'),
        environment('MYSQL_SOURCE_PASSWORD', ''),
        $pdoOptions
    );
    $postgres = new PDO(
        $postgresDsn,
        environment('DB_USERNAME', 'kcontest_app'),
        environment('DB_PASSWORD', ''),
        $pdoOptions
    );

    echo "Origine MariaDB: " . environment('MYSQL_SOURCE_DATABASE', 'kcontest-desk') . PHP_EOL;
    echo "Destinazione PostgreSQL: " . environment('DB_DATABASE', 'kcontest_desk') . PHP_EOL . PHP_EOL;

    $sourceCounts = [];
    foreach ($tables as $table) {
        $sourceCounts[$table] = (int)$mysql->query(
            'SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`'
        )->fetchColumn();
        printf("%-32s %8d righe\n", $table, $sourceCounts[$table]);
    }

    if (!in_array('--execute', $argv, true)) {
        echo PHP_EOL;
        echo "Simulazione completata: nessun dato modificato." . PHP_EOL;
        echo "Controlla il riepilogo e rilancia con --execute per eseguire la migrazione." . PHP_EOL;
        exit(0);
    }

    echo PHP_EOL . "Migrazione in corso..." . PHP_EOL;
    $postgres->beginTransaction();

    $truncate = implode(', ', array_map('identifier', $tables));
    $postgres->exec('TRUNCATE TABLE ' . $truncate . ' RESTART IDENTITY CASCADE');

    foreach ($tables as $table) {
        $targetColumnQuery = $postgres->prepare(
            "SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = :table
             ORDER BY ordinal_position"
        );
        $targetColumnQuery->execute(['table' => $table]);
        $targetColumns = $targetColumnQuery->fetchAll(PDO::FETCH_COLUMN);

        $sourceColumnQuery = $mysql->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $sourceColumns = $sourceColumnQuery->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_values(array_intersect($sourceColumns, $targetColumns));

        if ($columns === []) {
            throw new RuntimeException("Nessuna colonna compatibile per la tabella {$table}");
        }

        $quotedColumns = implode(', ', array_map('identifier', $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = $postgres->prepare(
            'INSERT INTO ' . identifier($table) . " ({$quotedColumns}) VALUES ({$placeholders})"
        );

        $select = $mysql->query(
            'SELECT ' . implode(', ', array_map(
                static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
                $columns
            )) . ' FROM `' . str_replace('`', '``', $table) . '`'
        );

        $migrated = 0;
        while ($row = $select->fetch()) {
            $insert->execute(array_values($row));
            $migrated++;
        }

        printf("%-32s %8d righe importate\n", $table, $migrated);
    }

    foreach ($identityColumns as $table => $column) {
        $sequenceStatement = $postgres->prepare('SELECT pg_get_serial_sequence(:table, :column)');
        $sequenceStatement->execute(['table' => $table, 'column' => $column]);
        $sequence = $sequenceStatement->fetchColumn();
        if (!$sequence) {
            continue;
        }

        $maximum = $postgres->query(
            'SELECT MAX(' . identifier($column) . ') FROM ' . identifier($table)
        )->fetchColumn();

        $setSequence = $postgres->prepare('SELECT setval(CAST(:sequence AS regclass), :value, :called)');
        $setSequence->bindValue(':sequence', $sequence);
        $setSequence->bindValue(':value', $maximum === null ? 1 : (int)$maximum, PDO::PARAM_INT);
        $setSequence->bindValue(':called', $maximum !== null, PDO::PARAM_BOOL);
        $setSequence->execute();
    }

    foreach ($tables as $table) {
        $targetCount = (int)$postgres->query(
            'SELECT COUNT(*) FROM ' . identifier($table)
        )->fetchColumn();
        if ($targetCount !== $sourceCounts[$table]) {
            throw new RuntimeException(
                "Conteggio non valido per {$table}: MariaDB {$sourceCounts[$table]}, PostgreSQL {$targetCount}"
            );
        }
    }

    $postgres->commit();
    echo PHP_EOL . "Migrazione completata e verificata con successo." . PHP_EOL;
} catch (Throwable $error) {
    if (isset($postgres) && $postgres->inTransaction()) {
        $postgres->rollBack();
    }
    fwrite(STDERR, PHP_EOL . 'ERRORE: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
