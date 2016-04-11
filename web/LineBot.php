<?php

use Monolog\Logger;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;

class LineBot
{
    const API_BASE_URL = 'https://trialbot-api.line.me/v1/';
    const TO_CHANNEL = 1383378250;
    const EVENT_TYPE = '138311608800106203';
    public $logger;
    public $api;
    public $channel_id;
    public $channel_secret;
    public $channel_mid;
    public function __construct($channel_id = null, $channel_secret = null, $channel_mid = null, $endpoint = null)
    {
        if (!$channel_id) {
            $channel_id = getenv('LINE_CHANNEL_ID');
        }
        if (!$channel_secret) {
            $channel_secret = getenv('LINE_CHANNEL_SECRET');
        }
        if (!$channel_mid) {
            $channel_mid = getenv('LINE_CHANNEL_MID');
        }
        if (!$endpoint) {
            if (getenv('LINE_BOT_ENDPOINT')) {
                $endpoint = getenv('LINE_BOT_ENDPOINT');
            } else {
                $endpoint = self::API_BASE_URL;
            }
        }
        $this->channel_id = $channel_id;
        $this->channel_secret = $channel_secret;
        $this->channel_mid = $channel_mid;
        $this->logger = new Logger('linebot');
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Line-ChannelID' => $this->channel_id,
            'X-Line-ChannelSecret' => $this->channel_secret,
            'X-Line-Trusted-User-With-ACL' => $this->channel_mid,
        ];
        $this->api = new HttpClient(['base_uri' => $endpoint, 'headers' => $headers]);
        // for heroku. set addon 'fixie:tricycle'
        if (getenv('FIXIE_URL')) {
            $this->api = new HttpClient(['base_uri' => $endpoint, 'headers' => $headers, 'proxy' => ['https' => getenv('FIXIE_URL')]]);
        }
    }
    public function isValid($signature, $request_body)
    {
        $is_valid = false;
        $hash = hash_hmac("sha256", $request_body, $this->channel_secret, true);
        $this->logger->info(base64_encode($hash));
        if (base64_encode($hash) === $signature) {
            $is_valid = true;
        }
        return $is_valid;
    }
    public function postEvents($events)
    {
        $path = 'events';
        $response = $this->api->request('POST', $path, ['json' => $events]);
        $result = json_decode((string) $response->getBody(), true);
        return $result;
    }
    public function sendText($to, $text)
    {
        $event = [
            "to" => [$to],
            "toChannel" => self::TO_CHANNEL,
            "eventType" => self::EVENT_TYPE,
            "content" => [
                "contentType" => 1,
                "toType" => 1,
                "text" => $text,
            ],
        ];
        return $this->postEvents($event);
    }
    public function sendImage($to, $originalImage, $previewImage)
    {
        $event = [
            "to" => [$to],
            "toChannel" => self::TO_CHANNEL,
            "eventType" => self::EVENT_TYPE,
            "content" => [
                "contentType" => 2,
                "toType" => 1,
                "originalContentUrl" => $originalImage,
                "previewImageUrl" => $previewImage,
            ],
        ];
        return $this->postEvents($event);
    }
    public function sendVideo($to, $originalVideo, $previewVideo)
    {
        $event = [
            "to" => [$to],
            "toChannel" => self::TO_CHANNEL,
            "eventType" => self::EVENT_TYPE,
            "content" => [
                "contentType" => 3,
                "toType" => 1,
                "originalContentUrl" => $originalVideo,
                "previewImageUrl" => $previewVideo,
            ],
        ];
        return $this->postEvents($event);
    }
    public function sendAudio($to, $originalAudio, $audioLength)
    {
        $event = [
            "to" => [$to],
            "toChannel" => self::TO_CHANNEL,
            "eventType" => self::EVENT_TYPE,
            "content" => [
                "contentType" => 4,
                "toType" => 1,
                "originalContentUrl" => $originalAudio,
                "contentMetadata" => [
                    "AUDLEN" => $audioLength
                ],
            ],
        ];
        return $this->postEvents($event);
    }
    public function sendSticker($to, $stkid, $stkpkgid, $stkver)
    {
        $event = [
            "to" => [$to],
            "toChannel" => self::TO_CHANNEL,
            "eventType" => self::EVENT_TYPE,
            "content" => [
                "contentType" => 8,
                "toType" => 1,
                "contentMetadata" => [
                    "STKID" => $stkid,
                    "STKPKGID" => $stkpkgid,
                    "STKVER" => $stkver,
                ],
            ],
        ];
        return $this->postEvents($event);
    }
}