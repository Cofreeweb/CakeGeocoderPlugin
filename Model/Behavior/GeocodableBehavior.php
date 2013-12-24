<?php
App::uses('HttpSocket', 'Network/Http');

class GeocodableBehavior extends ModelBehavior 
{
    public function setup(Model $Model, $settings = array()) {
      if (!isset($this->settings[$Model->alias])) {
          $this->settings[$Model->alias] = array(
              'addressColumn' => 'address',
              'latitudeColumn' => 'latitude',
              'longitudeColumn' => 'longitude',
              'parameters' => array()
          );
      }
      $this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);
  }
  
  /**
   * Before save callback.
   *
   * @param Model $model
   * @return boolean
   * @todo Throw exception or something if address column is not either an array or a string
   */
  // public function beforeSave(Model $Model, $options = array()) {
  //     
  //     $addressColumn = $this->settings[$Model->alias]['addressColumn'];
  //     $latitudeColumn = $this->settings[$Model->alias]['latitudeColumn'];
  //     $longitudeColumn = $this->settings[$Model->alias]['longitudeColumn'];
  //     $parameters = (array) $this->settings[$Model->alias]['parameters'];
  //     
  //     if (is_array($addressColumn)) {
  //         $address = array();
  //         foreach ($addressColumn as $column) {
  //             if (!empty($Model->data[$Model->alias][$column])) {
  //                 $address[] = $Model->data[$Model->alias][$column]; 
  //             }
  //         }
  //         $address = implode(', ', $address);
  //     }
  //     else {
  //         $address = $Model->data[$Model->alias][$addressColumn];
  //     }
  //     
  //     $parameters['address'] = $address;
  //     $parameters['sensor'] = 'false';
  //     
  //     $http = new HttpSocket();
  //     
  //     $response = $http->get('http://maps.googleapis.com/maps/api/geocode/json', $parameters);
  //     $response = json_decode($response);
  //     
  //     if ($response->status == 'OK') {
  //         $Model->data[$Model->alias][$latitudeColumn] = floatval($response->results[0]->geometry->location->lat);
  //         $Model->data[$Model->alias][$longitudeColumn] = floatval($response->results[0]->geometry->location->lng);
  //         return true;
  //     }
  //     return false;
  // }
  
  public function addressFormat( Model $Model, $response, $params)
  {
    $return = array();
    
    if( !is_array( $params))
    {
      $params = array( $params);
    }
    
    if( empty( $response))
    {
      return false;
    }
    
    foreach( $params as $param)
    {
      foreach( $response [0]->address_components as $address)
      {
        if( isset( $address->types [0]) && $address->types [0] == $param)
        {
          $return [] = $address->long_name;
        }
      }
    }
    
    return implode( ", ", $return);
  }
  
  public function geocode( Model $Model, $address, $parameters = array()) 
  {
    if( $address)
    {
      $parameters['address'] = $address;
    }

    $parameters['sensor'] = 'false';

    $url = 'http://maps.googleapis.com/maps/api/geocode/json';

    $http = new HttpSocket();

    $response = $http->get($url, $parameters);
    $response = json_decode($response);

    return $response->results;
  }
}