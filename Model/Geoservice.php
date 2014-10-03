<?php
App::uses( 'GeoserviceAppModel', 'Geocoder.Model');
/**
 * Geoservice Model
 *
 */
class Geoservice extends GeocoderAppModel 
{

  public $useTable = false;

  public $actsAs = array(
      'Geocoder.Geocodable'
  );


  

}
