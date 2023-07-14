<?php

namespace app\modules\sitemap\models;

use Yii;

use yii\helpers\Url;

class SitemapData
{
    const ROUTE = '/site/search';

    /**
     * @var array
     */
    private $links = [];


    /**
     * Sitemap constructor.
     */
    public function __construct()
    {
        return $this
            ->generateCrissCrossDataLinks()
            ->generateSubjectsLinks()
            ->generateSuburbsLinks();
    }


    /**
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }


    /**
     * @return $this
     */
    private function generateCrissCrossDataLinks()
    {
        foreach ($this->getSitemapCrissCrossData() as $item) {
            $this->links[] = Url::to([
                self::ROUTE,
                'subject_name' => $item['subjectName'],
                'suburb_name'  => to_url_format($item['suburbName']) . '-' . $item['suburbPostcode'],
            ]);
        }

        return $this;
    }


    /**
     * @return $this
     */
    private function generateSubjectsLinks()
    {
        foreach ($this->getSitemapSubjects() as $item) {
            $this->links[] = Url::to([
                self::ROUTE,
                'subject_name' => $item['subjectName'],
            ]);
        }

        return $this;
    }


    /**
     * @return $this
     */
    private function generateSuburbsLinks()
    {
        foreach ($this->getSitemapSuburbs() as $item) {
            $this->links[] = Url::to([
                self::ROUTE,
                'suburb_name'  => to_url_format($item['suburbName']) . '-' . $item['suburbPostcode'],
            ]);
        }

        return $this;
    }


    /**
     * @return array[]
     */
    private function getSitemapCrissCrossData()
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT 
              s.id AS subjectId,
              aus.id AS suburbId,
              s.link AS subjectName,
              aus.suburb AS suburbName,
              aus.postcode AS suburbPostcode
            FROM User u
              INNER JOIN TutorSubject ts ON ts.tutorId = u.id
              INNER JOIN Subject s ON s.id = ts.subjectId
              INNER JOIN UserAdress ua ON ua.userId = u.id
              INNER JOIN AustralianSuburb aus
                ON ROUND(
                      6371 * acos(
                          cos(
                              radians(aus.latitude)
                          ) * cos(
                              radians(ua.latitude)
                          ) * cos(
                              radians(ua.longitude) - radians(aus.longitude)
                          ) + sin(
                              radians(aus.latitude)
                          ) * sin(
                              radians(ua.latitude)
                          )
                      ),
                      2
                  ) <= 10
                  AND aus.type NOT IN ('Post Office Boxes', 'LVR')
            WHERE u.userTypeId = 3 and u.isShownOnTutorNetwork = 1
            GROUP BY s.id, aus.id;
        ");

        return $command->queryAll();
    }


    /**
     * @return array[]
     */
    private function getSitemapSubjects()
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT
              s.id as subjectId,
              s.link as subjectName
            FROM User u
              INNER JOIN TutorSubject ts ON ts.tutorId = u.id
              INNER JOIN Subject s ON s.id = ts.subjectId
            WHERE u.userTypeId = 3 and u.isShownOnTutorNetwork = 1
            GROUP BY s.id;
        ");

        return $command->queryAll();
    }


    /**
     * @return array[]
     */
    private function getSitemapSuburbs()
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT
              aus.id AS suburbId,
              aus.suburb AS suburbName,
              aus.postcode AS suburbPostcode
            FROM User u
              INNER JOIN UserAdress ua ON ua.userId = u.id
              INNER JOIN AustralianSuburb aus
                ON ROUND(
                        6371 * acos(
                            cos(
                                radians(aus.latitude)
                            ) * cos(
                                radians(ua.latitude)
                            ) * cos(
                                radians(ua.longitude) - radians(aus.longitude)
                            ) + sin(
                                radians(aus.latitude)
                            ) * sin(
                                radians(ua.latitude)
                            )
                        ),
                        2
                    ) <= 10
                    AND aus.type NOT IN ('Post Office Boxes', 'LVR')
            WHERE u.userTypeId = 3 and u.isShownOnTutorNetwork = 1
            GROUP BY aus.id;
        ");

        return $command->queryAll();
    }
}
