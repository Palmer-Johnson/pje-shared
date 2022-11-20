<?php

namespace astuteo\pjeShared\services;
use Craft;
use SKAgarwal\GoogleApi\Exceptions\GooglePlacesApiException;
use SKAgarwal\GoogleApi\PlacesApi;
use craft\helpers\App;

class GooglePlaces
{
    /**
     * @throws GooglePlacesApiException
     */
    public static function getInfo($placeid = 'ChIJA15YpmP2BogRUhyvDzZdOoo', $options = [], $cacheDuration = 172800) {
        $fields = $options['fields'] ?? 'opening_hours';
        $cache = Craft::$app->getCache();
        $duration = $cacheDuration;
        // let's see if we already have this cached
        $locationInfo = $cache->get($placeid);
        $key =  App::env('GOOGLE_PLACES') ?: '';

        if($locationInfo) {
            return $locationInfo;
        }
            $googlePlaces = new PlacesApi($key);
            $response = $googlePlaces->placeDetails($placeid, ['output' => 'json', 'fields' => $fields]);
            if($response['status'] == 'OK') {
                $locationInfo = $cache->set($placeid, $response['result'], $duration);
            } else {
                $locationInfo = null;
            }
        return $cache->get($placeid);
    }
}
