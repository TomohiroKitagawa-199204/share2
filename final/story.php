<?php
session_start();

// 初回アクセス時の初期化
if (!isset($_SESSION['player']) || !isset($_SESSION['monster'])) {
    initializeGame();
}

// ゲームの初期化
function initializeGame() {
    $_SESSION['player'] = [
        'name' => '勇者',
        'hp' => 100,
        'attack' => 20,
    ];

    $_SESSION['monster'] = [
        'name' => 'スライム',
        'hp' => 100,
        'attack' => 10,
    ];
}

// プレイヤーとモンスターの情報を取得
$player = &$_SESSION['player'];
$monster = &$_SESSION['monster'];

// 戦闘の進行
$attackResult = '';
$result = '';

if (isset($_POST['attack'])) {
    $attackResult = playerAttack();
    if ($monster['hp'] > 0) {
        $attackResult .= "<br>" . monsterAttack();
    }
}

// プレイヤーの攻撃
function playerAttack() {
    global $player, $monster;
    $damageToMonster = rand($player['attack'] - 5, $player['attack'] + 5);
    $monster['hp'] -= $damageToMonster;
    return "{$player['name']} の攻撃！ {$monster['name']} に {$damageToMonster} のダメージ！";
}

// モンスターの攻撃
function monsterAttack() {
    global $player, $monster;
    $damageToPlayer = rand($monster['attack'] - 3, $monster['attack'] + 3);
    $player['hp'] -= $damageToPlayer;
    return "{$monster['name']} の攻撃！ {$player['name']} に {$damageToPlayer} のダメージ！";
}

// 戦闘結果
if ($player['hp'] <= 0) {
    $result = "{$player['name']} は倒れてしまった...";
    session_destroy(); // ゲームオーバー時にセッションを破棄
} elseif ($monster['hp'] <= 0) {
    $result = "{$monster['name']} を倒した！ {$player['name']} の勝利！";
    session_destroy(); // モンスターを倒した後にセッションを破棄
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ドラクエ1風 RPGゲーム</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            text-align: center;
        }
        .battle-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin-top: 20px;
        }
        .result {
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ドラクエ1風 RPGゲーム - 戦闘シーン</h1>
        
        <div class="battle-container">
            <h2>戦闘中</h2>
            <p><?php echo isset($attackResult) ? $attackResult : ''; ?></p>
            <p><?php echo isset($result) ? $result : ''; ?></p>
            <p><?php echo "{$player['name']} のHP: {$player['hp']}, {$monster['name']} のHP: {$monster['hp']}"; ?></p>
            <form method="post" action="">
                <input type="submit" name="attack" value="攻撃する">
            </form>
        </div>
    </div>
</body>
</html>