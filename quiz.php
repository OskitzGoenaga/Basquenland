<?php
session_start();

$quizData = [
    ["question" => "Where is the Basque Country located?", "options" => ["Southern Spain and Portugal", "Northern Spain and Southwestern France", "Eastern Spain and Andorra", "Northern France"], "correct" => 1],
    ["question" => "What is the Basque Coast Geopark famous for?", "options" => ["Golden sand dunes", "Volcanic craters", "Rock formations revealing geological history", "Ancient Roman ruins"], "correct" => 2],
    ["question" => "Why is La Concha Bay in San Sebastian named so?", "options" => ["Because of its shell shape", "Because of the seashells found there", "It was named by a famous explorer", "Because of an old legend"], "correct" => 0],
    ["question" => "What is unique about the Basque language (Euskera)?", "options" => ["It is a dialect of Spanish", "It is similar to French", "It is derived from Latin", "It is unrelated to any other language in the world"], "correct" => 3],
    ["question" => "What are 'Pintxos'?", "options" => ["Large grilled steaks", "Small snacks usually served on bread", "Traditional Basque desserts", "Fish cheeks cooked in sauce"], "correct" => 1],
    ["question" => "Which of the following is a traditional Basque dessert?", "options" => ["Pantxineta", "Txuleton", "Marmitako", "Talo"], "correct" => 0],
    ["question" => "What is a 'Txapela'?", "options" => ["A traditional black beret", "A type of guitar", "A wooden shoe", "A traditional skirt"], "correct" => 0],
    ["question" => "What is 'Txalaparta'?", "options" => ["A type of flute", "A horn instrument", "A percussion instrument played by two people", "A traditional dance"], "correct" => 2],
    ["question" => "Who is Olentzero?", "options" => ["A famous Basque king", "The Basque equivalent of Santa Claus", "A legendary dragon", "A famous chef"], "correct" => 1],
    ["question" => "Which Basque city is home to the Guggenheim Museum?", "options" => ["San Sebastian", "Vitoria-Gasteiz", "Bilbao", "Zumaia"], "correct" => 2],
    ["question" => "What is the political capital of the Basque Country?", "options" => ["Bilbao", "San Sebastian", "Vitoria-Gasteiz", "Biarritz"], "correct" => 2],
    ["question" => "Which place was a famous filming location for 'Game of Thrones'?", "options" => ["Zumaia Coast", "La Concha Bay", "San Juan de Gaztelugatxe", "Guggenheim Museum"], "correct" => 2],
    ["question" => "What is 'Harrijasotzaile'?", "options" => ["Wood chopping competition", "Traditional Basque stone lifting", "Coastal rowing race", "A traditional song"], "correct" => 1],
    ["question" => "Where is Tilburg located?", "options" => ["North Holland", "South Holland", "North Brabant", "Friesland"], "correct" => 2],
    ["question" => "What historical industry made Tilburg known as the 'Wool City'?", "options" => ["Shipbuilding", "Textile production", "Diamond cutting", "Cheese making"], "correct" => 1],
    ["question" => "What is 'Spoorzone' known for today?", "options" => ["A modern train manufacturing plant", "A busy airport terminal", "An old railway zone transformed into a cultural hub", "A historic castle"], "correct" => 2],
    ["question" => "What is 013 Poppodium?", "options" => ["A traditional dance", "The largest pop music venue in the Netherlands", "A famous library", "A theme park"], "correct" => 1],
    ["question" => "When does the Tilburgse Kermis (largest Benelux funfair) take place?", "options" => ["January", "April", "July", "December"], "correct" => 2]
];

$totalQuestions = count($quizData);

define('LEADERBOARD_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'leaderboard.csv');

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
            $score = (int) $row[1];
            $total = (int) $row[2];
            $date = trim((string) $row[3]);

            if ($name === '') {
                continue;
            }

            $ranking[] = [
                'name' => $name,
                'score' => $score,
                'total' => $total,
                'date' => $date
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

function saveRankingToCsv($ranking)
{
    if (!ensureLeaderboardStorage() || !is_writable(LEADERBOARD_FILE)) {
        return;
    }

    $fp = @fopen(LEADERBOARD_FILE, 'wb');
    if ($fp === false) {
        return;
    }

    if (flock($fp, LOCK_EX)) {
        fputcsv($fp, ['name', 'score', 'total', 'date']);
        foreach ($ranking as $entry) {
            fputcsv($fp, [
                (string) ($entry['name'] ?? ''),
                (int) ($entry['score'] ?? 0),
                (int) ($entry['total'] ?? 0),
                (string) ($entry['date'] ?? '')
            ]);
        }
        fflush($fp);
        flock($fp, LOCK_UN);
    }

    fclose($fp);
}

function upsertRanking($name, $score, $total)
{
    $ranking = getRankingFromCsv();
    $foundIndex = -1;

    foreach ($ranking as $index => $entry) {
        $entryName = isset($entry['name']) ? strtolower((string) $entry['name']) : '';
        if ($entryName === strtolower($name)) {
            $foundIndex = $index;
            break;
        }
    }

    $today = date('n/j/Y');

    if ($foundIndex !== -1) {
        $existingScore = isset($ranking[$foundIndex]['score']) ? (int) $ranking[$foundIndex]['score'] : 0;
        if ($score > $existingScore) {
            $ranking[$foundIndex]['score'] = $score;
            $ranking[$foundIndex]['date'] = $today;
            $ranking[$foundIndex]['total'] = $total;
        }
    } else {
        $ranking[] = [
            'name' => $name,
            'score' => $score,
            'total' => $total,
            'date' => $today
        ];
    }

    usort($ranking, static function ($a, $b) {
        return ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
    });

    saveRankingToCsv($ranking);
}

function renderLeaderboard($totalQuestions)
{
    $ranking = getRankingFromCsv();

    if (count($ranking) === 0) {
        echo '<h3 style="margin-top:0;">Top 10 Leaderboard</h3>';
        echo '<p style="color:#646d9e;font-style:italic;">No scores recorded yet. Be the first to play!</p>';
        return;
    }

    echo '<h3 style="margin-top:0;">Top 10 Leaderboard</h3>';
    echo '<div style="overflow-x:auto;">';
    echo '<table class="ranking-table">';
    echo '<thead><tr><th style="width:10%;">Rank</th><th>Player</th><th>Score</th><th>Date</th></tr></thead>';
    echo '<tbody>';

    foreach (array_slice($ranking, 0, 10) as $i => $entry) {
        $weight = $i < 3 ? '700' : '600';
        $color = '#1e2768';

        if ($i === 0) {
            $color = '#d4af37';
        } elseif ($i === 1) {
            $color = '#a8a8a8';
        } elseif ($i === 2) {
            $color = '#cd7f32';
        }

        $name = h($entry['name'] ?? '');
        $score = (int) ($entry['score'] ?? 0);
        $total = (int) ($entry['total'] ?? $totalQuestions);
        $date = h($entry['date'] ?? '');

        echo '<tr>';
        echo '<td style="font-weight:' . $weight . ';color:' . $color . '">#' . ($i + 1) . '</td>';
        echo '<td>' . $name . '</td>';
        echo '<td>' . $score . ' / ' . $total . '</td>';
        echo '<td style="color:#646d9e;font-size:0.9rem;">' . $date . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

$defaultName = $_COOKIE['lastQuizPlayer'] ?? '';

if (!isset($_SESSION['quizState']) || !is_array($_SESSION['quizState'])) {
    $_SESSION['quizState'] = [
        'phase' => 'welcome',
        'index' => 0,
        'score' => 0,
        'name' => $defaultName,
        'answered' => false,
        'selected' => null,
        'error' => ''
    ];
}

$state = &$_SESSION['quizState'];
$state['error'] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'start') {
        $name = trim((string) ($_POST['player_name'] ?? ''));

        if ($name === '') {
            $state['phase'] = 'welcome';
            $state['error'] = 'Please enter your name to start the quiz.';
        } else {
            $state['phase'] = 'question';
            $state['index'] = 0;
            $state['score'] = 0;
            $state['name'] = $name;
            $state['answered'] = false;
            $state['selected'] = null;

            setcookie('lastQuizPlayer', $name, [
                'expires' => time() + (60 * 60 * 24 * 365),
                'path' => '/',
                'samesite' => 'Lax'
            ]);

            $_COOKIE['lastQuizPlayer'] = $name;
        }
    } elseif ($action === 'answer' && $state['phase'] === 'question' && $state['answered'] === false) {
        $selected = filter_input(INPUT_POST, 'selected', FILTER_VALIDATE_INT);
        $currentIndex = (int) $state['index'];

        if ($selected !== false && $selected !== null && isset($quizData[$currentIndex]['options'][$selected])) {
            $state['selected'] = $selected;
            $state['answered'] = true;

            if ($selected === (int) $quizData[$currentIndex]['correct']) {
                $state['score'] = (int) $state['score'] + 1;
            }
        }
    } elseif ($action === 'next' && $state['phase'] === 'question' && $state['answered'] === true) {
        $state['index'] = (int) $state['index'] + 1;
        $state['answered'] = false;
        $state['selected'] = null;

        if ((int) $state['index'] >= $totalQuestions) {
            $state['phase'] = 'result';
            upsertRanking((string) $state['name'], (int) $state['score'], $totalQuestions);
        }
    } elseif ($action === 'restart') {
        $state['phase'] = 'welcome';
        $state['index'] = 0;
        $state['score'] = 0;
        $state['answered'] = false;
        $state['selected'] = null;
        $state['error'] = '';
    }
}

$currentPhase = $state['phase'];
$currentIndex = (int) $state['index'];
$currentScore = (int) $state['score'];
$playerName = (string) $state['name'];
$answered = (bool) $state['answered'];
$selectedIndex = $state['selected'];
$errorMessage = (string) ($state['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basque Country Quiz</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @font-face {
            font-family: "PoppinsLocal";
            src: url("font/Poppins-Regular.ttf") format("truetype");
            font-weight: 400; font-style: normal;
        }
        @font-face {
            font-family: "PoppinsLocal";
            src: url("font/Poppins-SemiBold.ttf") format("truetype");
            font-weight: 600; font-style: normal;
        }
        @font-face {
            font-family: "PoppinsLocal";
            src: url("font/Poppins-Bold.ttf") format("truetype");
            font-weight: 700; font-style: normal;
        }

        body {
            font-family: "PoppinsLocal", sans-serif;
            font-weight: 600;
            background: #f0f2f4;
            color: #1e2768;
            line-height: 1.35;
        }

        .site-kop {
            min-height: 88px;
            padding: 22px 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }
        .site-kop > .site-titel {
            font-family: "PoppinsLocal", sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            letter-spacing: -0.02em;
            text-decoration: none;
            color: #1e2768;
        }
        .site-kop > .site-titel:visited,
        .site-kop > .site-titel:hover,
        .site-kop > .site-titel:active {
            text-decoration: none;
            color: #1e2768;
        }
        .site-kop > .navigatie-links { display: flex; gap: 38px; }
        .site-kop > .navigatie-links > a {
            text-decoration: none; color: #1e1e1e;
            font-size: 0.95rem; font-weight: 600;
        }
        .site-kop > .navigatie-links > a:hover { color: #21b451; }
        .site-kop > .kop-knoppen { display: flex; gap: 12px; }

        .main-knop {
            border: none; border-radius: 999px;
            background: #31d866; color: #0c2744;
            font-size: 1rem; font-family: "PoppinsLocal", sans-serif;
            font-weight: 600; padding: 12px 32px;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .main-knop:hover { transform: translateY(-2px); background: #21b451; }
        .main-knop-licht { background: #f2f4f8; }

        .article-wrap { max-width: 960px; margin: 0 auto; padding: 72px 84px 96px; }
        .section-block { margin-bottom: 80px; }

        .site-voet {
            min-height: 290px; background: #090a0f; color: #ffffff;
            padding: 58px 84px; display: flex; justify-content: space-between; gap: 64px;
        }
        .site-voet > .voet-links { max-width: 430px; }
        .site-voet > .voet-links > h3 { font-size: 1.35rem; margin-bottom: 14px; }
        .site-voet > .voet-links > p { color: #c8c8cf; font-size: 0.98rem; margin-bottom: 36px; }
        .site-voet > .voet-links > h4 { font-size: 2rem; font-weight: 600; }
        .site-voet > .voet-rechts { display: flex; gap: 54px; }
        .site-voet > .voet-rechts > .voet-kolom { min-width: 140px; display: flex; flex-direction: column; gap: 11px; }
        .site-voet > .voet-rechts > .voet-kolom > h3 { margin-bottom: 8px; }
        .site-voet > .voet-rechts > .voet-kolom > a { text-decoration: none; color: #dbdbea; font-weight: 600; }
        .site-voet > .voet-rechts > .voet-kolom > a:hover { color: #31d866; }

        .section-label {
            font-size: 0.75rem; letter-spacing: 0.42em;
            color: #646d9e; font-weight: 400;
            margin-bottom: 10px; text-transform: uppercase;
        }
        .section-block h2 {
            font-size: clamp(1.8rem, 4vw, 2.8rem); line-height: 1.1;
            letter-spacing: -0.03em; margin-bottom: 24px; color: #1e2768;
        }
        .section-block h3 { font-size: 1.25rem; margin-top: 36px; margin-bottom: 12px; color: #1e2768; }
        .section-block p { color: #4a548f; line-height: 1.75; margin-bottom: 16px; font-weight: 600; }

        .quiz-input {
            width: 100%; max-width: 400px;
            padding: 14px 20px; border: 2px solid #dde1f0; border-radius: 12px;
            font-family: "PoppinsLocal", sans-serif; font-size: 1rem;
            color: #1e2768; background: #f8f9fc; outline: none;
            display: block; margin-bottom: 20px;
            transition: border-color 0.2s ease;
        }
        .quiz-input:focus { border-color: #31d866; background: #ffffff; }

        .quiz-progress {
            width: 100%; height: 6px; background: #dde1f0;
            border-radius: 999px; margin-bottom: 36px; overflow: hidden;
        }
        .quiz-progress-bar {
            height: 100%; background: #31d866;
            border-radius: 999px; width: 0%;
            transition: width 0.4s ease;
        }

        .quiz-option {
            display: block; width: 100%; text-align: left;
            padding: 16px 22px; margin-bottom: 12px;
            border: 2px solid #dde1f0; border-radius: 12px;
            background: #f8f9fc; font-family: "PoppinsLocal", sans-serif;
            font-size: 1rem; color: #1e2768; cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, transform 0.1s ease;
        }
        .quiz-option:hover:not(:disabled) {
            border-color: #1e2768; background: #eef0f9; transform: translateX(4px);
        }
        .quiz-option.selected-correct {
            border-color: #31d866; background: #e6faf0; color: #0c6e31; font-weight: 600;
        }
        .quiz-option.selected-wrong {
            border-color: #e53e3e; background: #fff0f0; color: #c53030; font-weight: 600;
        }

        .ranking-table { width: 100%; border-collapse: collapse; font-family: "PoppinsLocal", sans-serif; font-size: 0.95rem; }
        .ranking-table th {
            text-align: left; padding: 10px 14px; font-size: 0.75rem;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            color: #646d9e; border-bottom: 2px solid #eef0f9;
        }
        .ranking-table td { padding: 12px 14px; color: #1e2768; border-bottom: 1px solid #eef0f9; }
        .ranking-table tr:last-child td { border-bottom: none; }
        .ranking-table tr:hover td { background: #f8f9fc; }

        .inline-form { display: inline-block; }

        @media (max-width: 1000px) {
            .site-kop { flex-wrap: wrap; gap: 18px; padding: 20px; }
            .article-wrap { padding: 48px 20px 72px; }
            .site-voet { flex-direction: column; padding: 44px 20px; }
        }
        @media (max-width: 620px) {
            .site-kop > .navigatie-links { width: 100%; justify-content: space-between; gap: 12px; }
            .site-kop > .kop-knoppen { width: 100%; }
            .site-kop > .kop-knoppen > .main-knop { flex: 1; text-align: center; }
            .site-voet > .voet-rechts { flex-direction: column; gap: 28px; }
        }
    </style>
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
            <div class="section-block" id="quiz-app" style="background:#ffffff;padding:40px;border-radius:16px;box-shadow:0 2px 16px rgba(30,39,104,0.08);">
                <?php if ($currentPhase === 'welcome'): ?>
                    <p class="section-label">01 &mdash; Basque Knowledge Test</p>
                    <h2>Welcome to the Quiz!</h2>
                    <p style="margin-bottom:24px;">Test your knowledge about the Basque Country and Tilburg. Enter your name below to start and see if you can make it to the leaderboard.</p>

                    <?php if ($errorMessage !== ''): ?>
                        <p style="color:#c53030;margin-bottom:12px;"><?php echo h($errorMessage); ?></p>
                    <?php endif; ?>

                    <form method="post" action="quiz.php">
                        <input type="hidden" name="action" value="start">
                        <label for="player-name" style="display:block;font-size:0.85rem;color:#646d9e;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;">Your Name</label>
                        <input type="text" id="player-name" name="player_name" class="quiz-input" placeholder="e.g. Amaia" value="<?php echo h($playerName); ?>">
                        <button id="start-btn" class="main-knop" type="submit">Start The Quiz</button>
                    </form>

                    <div style="margin-top:60px;"><?php renderLeaderboard($totalQuestions); ?></div>
                <?php elseif ($currentPhase === 'question' && isset($quizData[$currentIndex])): ?>
                    <?php
                    $question = $quizData[$currentIndex];
                    $correctIndex = (int) $question['correct'];
                    $progress = ($currentIndex / $totalQuestions) * 100;
                    ?>
                    <div class="quiz-progress"><div class="quiz-progress-bar" style="width:<?php echo h($progress); ?>%;"></div></div>
                    <div id="question-header">
                        <p class="section-label">Question <?php echo h($currentIndex + 1); ?> of <?php echo h($totalQuestions); ?></p>
                        <h2 style="margin-bottom:24px;"><?php echo h($question['question']); ?></h2>
                    </div>

                    <div id="options-container" style="margin-bottom:30px;">
                        <form method="post" action="quiz.php">
                            <input type="hidden" name="action" value="answer">
                            <?php foreach ($question['options'] as $optionIndex => $optionText): ?>
                                <?php
                                $className = 'quiz-option';
                                if ($answered) {
                                    if ($optionIndex === $correctIndex) {
                                        $className .= ' selected-correct';
                                    } elseif ($selectedIndex === $optionIndex && $optionIndex !== $correctIndex) {
                                        $className .= ' selected-wrong';
                                    }
                                }
                                ?>
                                <?php if (!$answered): ?>
                                    <button class="<?php echo h($className); ?>" type="submit" name="selected" value="<?php echo h($optionIndex); ?>"><?php echo h($optionText); ?></button>
                                <?php else: ?>
                                    <button class="<?php echo h($className); ?>" type="button" disabled style="cursor:not-allowed;"><?php echo h($optionText); ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </form>
                    </div>

                    <?php if ($answered): ?>
                        <form method="post" action="quiz.php">
                            <input type="hidden" name="action" value="next">
                            <button id="next-btn" class="main-knop" type="submit"><?php echo $currentIndex === ($totalQuestions - 1) ? 'Finish Quiz' : 'Next Question'; ?></button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                    $percentage = $totalQuestions > 0 ? ($currentScore / $totalQuestions) : 0;
                    if ($percentage === 1.0) {
                        $message = "Perfect! You're a true Basque Country expert!";
                    } elseif ($percentage >= 0.7) {
                        $message = "Great job! You know a lot about the Basque Country.";
                    } elseif ($percentage >= 0.4) {
                        $message = "Good effort! You've learned some interesting facts.";
                    } else {
                        $message = "Time to read the article again to learn more about this beautiful region!";
                    }
                    ?>
                    <div class="quiz-progress"><div class="quiz-progress-bar" style="width:100%;"></div></div>
                    <p class="section-label">Quiz Completed</p>
                    <h2 style="font-size:clamp(2.5rem,6vw,4rem);color:#31d866;margin:15px 0;line-height:1;">
                        <?php echo h($currentScore); ?> <span style="font-size:1.5rem;color:#1e2768;">/ <?php echo h($totalQuestions); ?></span>
                    </h2>
                    <p style="font-size:1.1rem;color:#4a548f;line-height:1.6;margin-bottom:30px;font-weight:600;"><?php echo h($message); ?></p>

                    <div style="display:flex;gap:16px;margin-bottom:40px;flex-wrap:wrap;">
                        <form method="post" action="quiz.php" class="inline-form">
                            <input type="hidden" name="action" value="restart">
                            <button class="main-knop" type="submit">Play Again</button>
                        </form>
                        <a class="main-knop main-knop-licht" href="leaderboard.php">Full Leaderboard</a>
                        <a class="main-knop main-knop-licht" href="index.php">Return Home</a>
                    </div>

                    <?php renderLeaderboard($totalQuestions); ?>
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
