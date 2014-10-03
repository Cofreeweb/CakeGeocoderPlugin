<?php
App::uses( 'GeocoderAppController', 'Geocoder.Controller');
/**
 * Geoservices Controller
 *
 */
class GeoservicesController extends GeocoderAppController 
{
  public $components = array(
      'RequestHandler'
  );

  public function get()
  {
    $response = $this->Geoservice->geocode( false, $this->request->data, array(
        'language' => Configure::read( 'Config.language'),
    ));

    $return = array();

    if( isset( $response [0])) {
      $return = (array)$response [0];
    } 

    $this->set( array(
        'response' => $return,
        '_serialize' => 'response'
    ));
  }

}
