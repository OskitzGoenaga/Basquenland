<?php

$leaderboardFileFromEnv = getenv('LEADERBOARD_FILE');
if (!is_string($leaderboardFileFromEnv) || trim($leaderboardFileFromEnv) === '') {
    $leaderboardFileFromEnv = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'leaderboard.csv';
}

define('LEADERBOARD_FILE', $leaderboardFileFromEnv);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ensureLeaderboardStorage()
{
    $dir = dirname(LEADERBOARD_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    if (!is_file(LEADERBOARD_FILE)) {
        $fp = @fopen(LEADERBOARD_FILE, 'wb');
        if ($fp !== false) {
            fputcsv($fp, ['name', 'score', 'total', 'date']);
            fclose($fp);
            @chmod(LEADERBOARD_FILE, 0664);
        } else {
            return false;
        }
    }

    return is_readable(LEADERBOARD_FILE);
}

function getRankingFromCsv()
{
    if (!ensureLeaderboardStorage()) {
        return [];
    }

    $ranking = [];
    $fp = @fopen(LEADERBOARD_FILE, 'rb');
    if ($fp === false) {
        return $ranking;
    }

    if (flock($fp, LOCK_SH)) {
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            if (strtolower((string) $row[0]) === 'name' && strtolower((string) $row[1]) === 'score') {
                continue;
            }

            $name = trim((string) $row[0]);
            if ($name === '') {
                continue;
            }

            $ranking[] = [
                'name' => $name,
                'score' => (int) $row[1],
                'total' => (int) $row[2],
                'date' => trim((string) $row[3])
            ];
        }

        flock($fp, LOCK_UN);
    }

    fclose($fp);

    usort($ranking, static function ($a, $b) {
        return ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
    });

    return $ranking;
}

$ranking = getRankingFromCsv();
$totalPlayers = count($ranking);
$bestScore = $totalPlayers > 0 ? (int) $ranking[0]['score'] : 0;
$bestTotal = $totalPlayers > 0 ? (int) $ranking[0]['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Leaderboard</title>
    <link rel="stylesheet" href="leaderboard.css">
</head>
<body>

    <header class="site-kop">
        <a class="site-titel" href="index.php">Euskal Herria x Tillie project</a>
        <div class="navigatie-links">
            <a href="basque_info.html">Basqueland</a>
            <a href="tilburg_info.html">Tilburg</a>
            <a href="about_us.html">About Us</a>
        </div>
        <div class="kop-knoppen">
            <a class="main-knop main-knop-licht" href="music.html">Music</a>
            <a class="main-knop main-knop-licht" href="quiz.php">Quiz</a>
            <a class="main-knop" href="leaderboard.php">Leaderboard</a>
        </div>
    </header>

    <main>
        <div class="article-wrap">
            <div class="panel">
                <p class="section-label">Shared Ranking</p>
                <h2>Global Quiz Leaderboard</h2>
                <p class="subtitle">here you can see all the people that have played the quiz</p>

                <div class="meta-row">
                    <span class="meta-pill">Players: <?php echo h($totalPlayers); ?></span>
                    <span class="meta-pill">Best score: <?php echo h($bestScore); ?> / <?php echo h($bestTotal); ?></span>
                </div>

                <?php if ($totalPlayers === 0): ?>
                    <p class="empty-state">No scores yet. Be the first person to complete the quiz.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th style="width:12%;">Rank</th>
                                    <th>Player</th>
                                    <th style="width:18%;">Score</th>
                                    <th style="width:20%;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranking as $i => $entry): ?>
                                    <?php
                                    $rankClass = '';
                                    if ($i === 0) {
                                        $rankClass = 'rank-1';
                                    } elseif ($i === 1) {
                                        $rankClass = 'rank-2';
                                    } elseif ($i === 2) {
                                        $rankClass = 'rank-3';
                                    }
                                    ?>
                                    <tr>
                                        <td class="rank-cell <?php echo h($rankClass); ?>">#<?php echo h($i + 1); ?></td>
                                        <td><?php echo h($entry['name'] ?? ''); ?></td>
                                        <td><?php echo h((int) ($entry['score'] ?? 0)); ?> / <?php echo h((int) ($entry['total'] ?? 0)); ?></td>
                                        <td><?php echo h($entry['date'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer id="over-ons" class="site-voet">
        <div class="voet-links">
            <h3>basqueland x Tillie</h3>
            <p>Our exchange project between Tilburg and Basqueland helped students share cultures, work together, learn new ideas, and build friendships.</p>
            <h4>&copy; 2026</h4>
        </div>
        <div class="voet-rechts">
            <div class="voet-kolom">
                <h3>pages</h3>
                <a href="tilburg_info.html">tilburg</a>
                <a href="basque_info.html">basqueland</a>
                <a href="quiz.php">quiz</a>
                <a href="leaderboard.php">leaderboard</a>
                <a href="about_us.html">about us</a>
            </div>
        </div>
    </footer>
</body>
</html>
