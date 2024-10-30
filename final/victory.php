<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>勝利！</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <h1>勝利しました！</h1>
    <?php if (!isset($_GET['final'])): ?>
        <a href="battle.php">次の戦いへ進む</a>
    <?php else: ?>
        <a href="ending.html">全ての敵を倒しました！</a>
    <?php endif; ?>
    <audio id="victory-sound" src="sound/victory-sound.mp3"></audio>
    
     <script>
    // 勝利時の効果音を再生し、音量を50%に設定
        var victorySound = document.getElementById('victory-sound');
        victorySound.volume = 0.03; 
        victorySound.play();
    </script>
</body>
</html>