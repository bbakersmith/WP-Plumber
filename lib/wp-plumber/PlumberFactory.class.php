<?php

class PlumberFactory {


  protected $_class_to_create;

  
  function __construct($class) {
    // assign the class that is to be created by this factory
    global $_class;
    $this->_class_to_create = $class;
  }  


}

?>
