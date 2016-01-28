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
  
  private $__cache = array(); 
  
  public function beforeRender( $viewFile) 
  {
    if( !$this->request->is( 'ajax'))
    {
      $this->loadScripts();
    }
  }

/**
 * Añade los scripts a los bufferes
 * 
 * @return void
 */
  public function loadScripts()
  {
    $this->Html->script( 'http://maps.google.com/maps/api/js?libraries=places&language='. $this->language, array(
        'inline' => false,
        'once' => true
    ));
    
    $this->Html->script( '/geocoder/js/markerclusterer', array(
        'inline' => false,
        'once' => true
    ));
  }
  
/**
 * Cache the result of the callback into the class.
 *
 * @param string|array $key
 * @param callable $callback
 * @return mixed
 */
	private function cache($key, Closure $callback) {
		if (is_array($key)) {
			$key = implode('-', $key);
		}

		if( isset( $this->__cache[$key])) {
			return $this->__cache[$key];
		}

		$this->__cache[$key] = $callback();

		return $this->__cache[$key];
	}
	
	function jsonize( $foo)
  {
    $data = $this->jsonize_values( $foo);
    extract( $data);

    $json = json_encode( $foo);

    foreach( $value_arr as $value)
    {
      $json = str_replace( '"' . $value .'"', $value, $json);
    }


    return trim( $json);
  }
	
	function jsonize_values( $array)
  {
    $replace_keys = $value_arr = array();
    
    foreach( $array as $key => $value){
      // Look for values starting with 'function('
      if( !is_array( $value) && (strpos($value, 'function(') !== false || strpos($value, 'new') !== false)){
        // Store function string.
        $value_arr [] = $value;
        // Replace function string in $foo with a 'unique' special key.
        $value = '%' . $key . '%';
        // Later on, we'll look for the value, and replace it.
        $replace_keys [] = '"' . $value . '"';

      }
      elseif( is_array( $value))
      {
        $data = $this->jsonize_values( $value);
        $replace_keys = array_merge( $replace_keys, $data ['replace_keys']);
        $value_arr = array_merge( $value_arr, $data ['value_arr']);
      }
    }

    return compact( 'replace_keys', 'value_arr');
  }
	
  public function create( $options = array())
  {
    $_options = array(
        'objectName' => 'map',
        'id' => 'mapindex',
        'zoom' => 10,
        'cluster' => false,
        'scrollwheel' => 'true',
        
    );
    
    $options = array_merge( $_options, $options);
    
    $this->map = $options;
    $this->markers = array();
  }
  
/**
 * Añade un marker al mapa que será renderizado
 *
 * Ejemplo de options
 * 'lat' => 31.00000,
 * 'lng' => 31.0000,
 * 'title' => 'Un título',
 * 'element' => '#map-layer2', // Es el elemento de donde tomará la información para mostrar en la ventana
 * 'makerObject' => 'marker_2', // El nombre de objeto js del markder
 * 'icon' => array(
 *     'url' => WWW_ROOT .'img/marker.svg', // Si es tipo SVG, hay que dar la ruta absoluta del servidor, si es PNG, la ruta con el nombre del dominio
 *     'type' => 'svg',
 *     'options' => array(
 *         'fillColor' => '#990000',
 *         'fillOpacity' => 0.8,
 *         'strokeColor' => 'gold',
 *         'scale' => 0.5,
 *         'strokeWeight' => 2
  *    )
 * )
 *
 * @param array $options 
 * @return void
 * @link https://developers.google.com/maps/documentation/javascript/reference?hl=es#Symbol (las opciones del icono)
 */
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
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        scrollwheel: {$this->map ['scrollwheel']}
      });
      {$this->map ['objectName']}.infoWindow = new google.maps.InfoWindow();
      var markers = [];
EOF;

    foreach( $this->markers as $key => $marker)
    {
      if( isset( $marker ['icon']['type']) && $marker ['icon']['type'] == 'svg')
      {
        $path = $this->cache( 'marker_'. $marker ['icon']['url'], function(){
          App::uses( 'Util', 'Xml');
          $xml = Xml::build( WWW_ROOT .'img/marker.svg');
          $svg = Xml::toArray( $xml);
          $path = $svg ['svg']['path']['@d'];
          return $path;
        });
        
        $marker ['icon']['options']['path'] = $path;
        $icon_options = $this->jsonize( $marker ['icon']['options']);
      }
      else
      {
        $icon_options = $marker ['icon'];
      }
      
      $mapjs .= <<<EOF
      var latLng = new google.maps.LatLng( {$marker ['lat']}, {$marker ['lng']});
      var {$marker ['makerObject']} = new google.maps.Marker({
        position: latLng,
        title: '{$marker ['title']}',
        map: {$this->map ['objectName']}.map
EOF;
      if( !empty( $icon_options))
      {
        $mapjs .= <<<EOF
        ,icon: $icon_options
EOF;
      }
      $mapjs .= <<<EOF
      });
      markers.push( {$marker ['makerObject']});
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
        $width = isset( $marker ['width']) ? $marker ['width'] : '250px';
        $height = isset( $marker ['height']) ? $marker ['height'] : '250px';
        $mapjs .= <<<EOF
          $('{$marker ['element']}').data( 'marker', {$marker ['makerObject']});
          google.maps.event.addListener( {$marker ['makerObject']}, 'click', function() {
              {$this->map ['objectName']}.infoWindow.setContent( '<div style="width: $width; height: $height">' + $('{$marker ['element']}').html() + '</div>')
              {$this->map ['objectName']}.infoWindow.open( {$this->map ['objectName']}.map, {$marker ['makerObject']});
          });          
EOF;
      }
    }
    
    if( $this->map ['cluster'])
    {
      $cluster_options = json_encode( $this->map ['cluster']);
      $mapjs .= <<<EOF
        var mc = new MarkerClusterer(map.map, markers, $cluster_options);
EOF;
    }
    
    
    $js_block = $this->Html->scriptBlock( $this->map ['objectName'] .' = {map: null, infoWindow: null};');
    $this->Js->buffer( $mapjs, true);
    
    $attributes = array(
        'id' => $this->map ['id'],
        
    );
    
    if( isset( $this->map ['class']))
    {
      $attributes ['class'] = $this->map ['class'];
    }
    
    if( isset( $this->map ['width']) || isset( $this->map ['height']))
    {
      $style = '';
      
      if( isset( $this->map ['width']))
      {
        $style = 'width: '. $this->map ['width'];
      }
      
      if( isset( $this->map ['height']))
      {
        $style .= '; height: '. $this->map ['height'];
      }
      
      $attributes ['style'] = $style;
    }
    
    return $this->Html->tag( 'div', '', $attributes) . $js_block;
  }

}