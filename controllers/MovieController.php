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
     * Ritorna tutti i movies come array
     */
    public function actionList()
    {
        $movies = Movie::find()->asArray()->all();

        return [
            'status' => true,
            'count' => count($movies),
            'data' => $movies,
        ];
    }

    /**
     * Ritorna lo status dell'applicazione
     */
    public function actionHealth()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            Yii::$app->db->createCommand("SELECT 1")->execute();

            return ['status' => true, 'checks' => ['database' => true]];
        } catch (\Throwable $e) {

            Yii::$app->response->statusCode = 503;

            return [
                'status' => false,
                'checks' => ['database' => false],
                'message' => $e->getMessage()
            ];
        }
    }
}
