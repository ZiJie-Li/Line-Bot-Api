<?php
require_once('../vendor/autoload.php');
require_once('./LineBot.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$bot = new LineBot();

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->before(function (Request $request) use($bot) {
    // Signature validation
    $request_body = $request->getContent();
    $signature = $request->headers->get('X-LINE-CHANNELSIGNATURE');
    if (!$bot->isValid($signature, $request_body)) {
        return new Response('Signature validation failed.', 400);
    }
});

$app->post('/callback', function (Request $request) use ($app, $bot) {
   
    $body = json_decode($request->getContent(), true);

    foreach ($body['result'] as $obj) {
        $app['monolog']->addInfo(sprintf('obj: %s', json_encode($obj)));

        $from = $obj['content']['from']; //取得使用者mid
        $content = $obj['content']; //取得訊息資料

        // Here you can hack
        if ($content['text']) {
            switch ($content['text']) {
                case 'text':
                    $bot->sendText($from, sprintf('%s', '你好'));
                    break;
                case 'image':
                    $bot->sendImage($from, "http://www.example.com/larger.jpg", "http://www.example.com/thumbnail.jpg");
                    break;
                case 'video':
                    $bot->sendVideo($from, "http://www.example.com/movie.mp4", "http://www.example.com/preview.jpg");
                    break;
                case 'sticker':
                    $bot->sendSticker($from, "120", "1", "100");
                    break;
                default:
                   $bot->sendText($from, sprintf('你是說：「 %s 」 嗎？', $content['text']));
            }
        }
    }

    return 0;
});

$app->run();