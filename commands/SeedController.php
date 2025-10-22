<?php


namespace app\commands;

use Yii;
use yii\console\Controller;

class SeedController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionMovies()
    {
        $rows = [
            ['Inception', 'Thriller sci-fi diretto da Christopher Nolan', 2010],
            ['The Matrix', 'Action / Sci-fi che ha rivoluzionato gli effetti speciali', 1999],
            ['The Godfather', 'Crime drama classico di Francis Ford Coppola', 1972],
            ['Parasite', 'Satira sociale e thriller (Bong Joon-ho)', 2019],
            ['Rocky', 'Il riscatto di un uomo attraverso il pugilato', 1976],
        ];

        $columns = ['title', 'description', 'release_year'];

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            // batchInsert: veloce per molte righe
            $db->createCommand()->batchInsert('{{%movies}}', $columns, $rows)->execute();
            $transaction->commit();
            $this->stdout("Seeded " . count($rows) . " movie(s).\n");
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("Errore durante il seeding: " . $e->getMessage() . "\n");
            return 1;
        }

        return 0;
    }

    public function actionClearMovies()
    {
        $db = Yii::$app->db;
        try {
            $db->createCommand()->truncateTable('{{%movies}}')->execute();
            $this->stdout("Tabella movies svuotata.\n");
        } catch (\Throwable $e) {
            $this->stderr("Errore: " . $e->getMessage() . "\n");
            return 1;
        }
        return 0;
    }

}
