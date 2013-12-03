<?php

/**
 * undocumented class
 *
 * @link https://developers.google.com/maps/documentation/javascript/reference
 * @package geocoder.view.helper
 * @author Alfonso Etxeberria
 */

class MapsHelper extends AppHelper 
{
  public $helpers = array( 'Html', 'Form', 'Js');

  public $map = false;
  
  public $markers = array();
  
  public $language = null;
  
  public function beforeRender() 
  {
    if( !$this->request->is( 'ajax'))
    {
      $this->Html->script( 'http://maps.google.com/maps/api/js?sensor=false&language='. $this->language, array(
          'inline' => false,
          'once' => true
      ));
    }
  }
  
  public function create( $options = array())
  {
    $_options = array(
        'width' => "100%",
        'height' => "500px",
        'objectName' => 'map',
        'id' => 'mapindex',
        'zoom' => 10
    );
    
    $options = array_merge( $_options, $options);
    
    $this->map = $options;
    $this->markers = array();
  }
  
  public function addMarker( $options)
  {
    $this->markers [] = $options;
  }
  
  public function setLanguage( $language)
  {
    $this->language = $language;
  }
  
  public function render()
  {
    $mapjs = <<<EOF
      var latLng = new google.maps.LatLng( {$this->map ['lat']}, {$this->map ['lng']});
      {$this->map ['objectName']}.map = new google.maps.Map(document.getElementById('{$this->map ['id']}'), {
        zoom: {$this->map ['zoom']},
        center: latLng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      });
      {$this->map ['objectName']}.infoWindow = new google.maps.InfoWindow();
EOF;

    foreach( $this->markers as $key => $marker)
    {
      $mapjs .= <<<EOF
      var latLng = new google.maps.LatLng( {$marker ['lat']}, {$marker ['lng']});
      var {$marker ['makerObject']} = new google.maps.Marker({
        position: latLng,
        title: '{$marker ['title']}',
        map: {$this->map ['objectName']}.map
      });
EOF;
      
      if( isset( $marker ['content']))
      {
        $mapjs .= <<<EOF
          google.maps.event.addListener( {$marker ['makerObject']}, 'click', function() {
              {$this->map ['objectName']}.infoWindow.setContent( '{$marker ['content']}')
              {$this->map ['objectName']}.infoWindow.open( {$this->map ['objectName']}.map, {$marker ['makerObject']});
          });          
EOF;
      }
      elseif( isset( $marker ['element']))
      {
        $mapjs .= <<<EOF
          $('{$marker ['element']}').data( 'marker', {$marker ['makerObject']});
          google.maps.event.addListener( {$marker ['makerObject']}, 'click', function() {
              {$this->map ['objectName']}.infoWindow.setContent( $('{$marker ['element']}').html())
              {$this->map ['objectName']}.infoWindow.open( {$this->map ['objectName']}.map, {$marker ['makerObject']});
          });          
EOF;
      }
    }
    
    $js_block = $this->Html->scriptBlock( 'var '. $this->map ['objectName'] .' = {map: null, infoWindow: null};');
    $js = $mapjs;
    // $js = 'google.maps.event.addDomListener(window, "load", function(){'. $mapjs .'});';
    $this->Js->buffer( $js, true);
    
    return $this->Html->tag( 'div', '', array(
        'id' => $this->map ['id'],
        'style' => 'width: '. $this->map ['width'] .'; height: '. $this->map ['height']
    )) . $js_block;
  }

  /**
   * After render callback.  afterRender is called after the view file is rendered
   * but before the layout has been rendered.
   *
   * @access public
   */
  function afterRender() {
  }

  /**
   * Before layout callback.  beforeLayout is called before the layout is rendered.
   *
   * @access public
   */
  function beforeLayout() {
  }

  /**
   * After layout callback.  afterLayout is called after the layout has rendered.
   *
   * @access public
   */
  function afterLayout() {
  }
}