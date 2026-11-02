<?php

try {
    if (DBSYSTEM === 'postgres') {
        preg_match('/[^:]+:\/\/[^:]+:([^@]+)@(.+)/', $_ENV['DATABASE_URL'] ?? '', $matches);
        $pwd = $matches[1] ?? null;
        $connect = $matches[2] ?? null;

        $db = $_ENV['DB_DATABASE'];
        if (!$pwd) {
            throw new Exception('Cannot to connect to the database server');
        }
        $params = ['host' => $connect, 'port' => 5432, 'database' => $db, 'user' => 'recorded_user', 'password' => $pwd, 'sslmode' => 'require'];
        //$params = ['host' => 'ep-soft-wave-abyjtquu-pooler.eu-west-2.aws.neon.tech', 'port' => 5432, 'database' => 'neondb', 'user' => 'neondb_owner', 'password' => 'npg_YBnC6DQXyjr0', 'sslmode' => 'require'];
   
        $db = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password'],
            $params['sslmode']
        );

        $pdo = new PDO($db);
        //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //$pdo->exec('SET search_path TO recorded');
    } else {
        $pwd = $_ENV['DB_PASSWORD'];
        $user = $_ENV['DB_USERNAME'];
        $dbname = $_ENV['DB_DATABASE'];
        $host = $_ENV['DB_HOST'];

        $pwd = 'covid19krauq';
        $user = 'root';
        $dbname = 'recordings';
        $host = 'localhost';

        $pdo = new \PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pwd
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('SET NAMES "utf8"');
    }
} catch (PDOException $e) {
    //$vars for pagehead,pageheader,pagenav
    $output = 'Unable to connect to the database server: ' . $e->getMessage();
    $error = $output;
    $TITLE = 'damn';
    $fork = BADMINTON;
    $id = 'admin';
    $myId = 'id="admin"';
    $nav = [];
    $klas = 'connect';
    include TEMPLATE . 'output.html.php';
    exit();
}
