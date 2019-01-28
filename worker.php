<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;
use app\components\FightHelper;

define('GAME_SERVER', 'server_1');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/config/console.php');
new yii\console\Application($config);

// для запуска использовать: php worker.php start


// listen port 2021 for socket.io client
// ssl context
$context = array(
    'ssl' => array(
        'local_cert'  => 'path_to_cert',
        'local_pk'    => 'path_to_key',
        'verify_peer' => false,
    )
);
$io = new SocketIO('8120', $context);

// connection to SocketIO
$io->on('connection', function($client)use($io){
    echo 'connecting...'."\n";
    // New player
    $client->on('new-player', function($data)use($io, $client){
        $client->username = $data['name'];
        $client->room = GAME_SERVER;
        $client->join(GAME_SERVER);
        $client->broadcast->to(GAME_SERVER)->emit('adduser', 2);
    });

    // Load player in location
    $client->on('load_location', function($data)use($io){
        $io->emit('check_status', ['msg' => 'Персонаж '. $data['pers_id'] . ' загрузился. Локацию найти не удалось!']);
    });

    // Chat
    $client->on('chat_message', function($data)use($io, $client){
        $io->emit('chat_message', $data);
        $client->broadcast->to(GAME_SERVER)->emit('chat_message', $data);
    });

    // Stop server
    $client->on('stop_server', function($data)use($io, $client){
        Worker::stopAll();
    });

    // Join player with arena
    $client->on('join_arena', function($data)use($io, $client){
        $fightHelper = Yii::$app->fightHelper;
        $fight = $fightHelper->joinFight($data['pers_id']);
        $room_fight = 'fight_'.$fight['id'];
        $client->join($room_fight);
        $io->emit('join_arena', $fight);
        $client->broadcast->to($room_fight)->emit('join_arena', $fight);
    });

    // Attack player with arena
    $client->on('attack', function($data)use($io, $client){
        $fightHelper = Yii::$app->fightHelper;
        $res = $fightHelper->attackArea($data['fight_id'], $data['pers_id'], $data['enemy'], $data['attack_area'], $data['defence_area']);
        $room_fight = 'fight_'.$data['fight_id'];
        $client->emit('attack_area', $res);
        $client->broadcast->to($room_fight)->emit('attack_area', $res);
    });
});

Worker::runAll();
