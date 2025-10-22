<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\ContentNegotiator;
use app\models\Movie;


class MovieController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * ritorna tutti i movies come array
     */
    public function actionMovies()
    {
        $movies = Movie::find()->asArray()->all();

        return [
            'status' => true,
            'count' => count($movies),
            'data' => $movies,
        ];
    }

    public function actionHealth()
    {
        $result = [
            'status' => true,
            'checks' => [],
        ];

        // 1) Check DB (open/close)
        try {
            Yii::$app->db->open();
            Yii::$app->db->close();
            $result['checks']['database'] = ['status' => true];
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 500;
            $result['checks']['database'] = [
                'status' => false,
                'message' => $e->getMessage(),
            ];
            $result['status'] = false;
            return $result;
        }

        // 2) Optional: check external URL (useful per "lanciare qualche api")
        $external = getenv('TEST_EXTERNAL_URL') ?: null;
        if ($external) {
            $externalCheck = $this->checkUrl($external);
            $result['checks']['external'] = $externalCheck;
            if ($externalCheck['status'] !== true) {
                Yii::$app->response->statusCode = 500;
                $result['status'] = false;
            }
        }

        return $result;
    }

    /**
     * Restituisce array con status / http_code / error.
     */
    protected function checkUrl(string $url): array
    {
        // usa cURL se disponibile (più robusto)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                return ['status' => 'error', 'error' => $err];
            }
            return ['status' => 'ok', 'http_code' => $code];
        }

        // fallback a file_get_contents
        $opts = ['http' => ['method' => 'GET', 'timeout' => 3]];
        $context = stream_context_create($opts);
        set_error_handler(function(){});
        $content = @file_get_contents($url, false, $context);
        restore_error_handler();
        // proviamo a leggere codice dalla $http_response_header
        $httpCode = null;
        if (!empty($http_response_header)) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $m)) {
                $httpCode = (int)$m[1];
            }
        }
        if ($content === false) {
            return ['status' => 'error', 'http_code' => $httpCode, 'error' => 'no response'];
        }
        return ['status' => 'ok', 'http_code' => $httpCode];
    }
}
