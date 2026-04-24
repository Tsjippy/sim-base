<?php
namespace SIM\ADMIN;
use SIM;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class SubAdminMenu{

    public $settings;
    public $name;

    public function __construct($settings, $name){
        $this->settings	= $settings;
        $this->name		= $name;
    }

    /**
     * @param   object  $node   The DOM Document node to add html to
     * 
     * @return  bool            True if something was printed to screen false otherwise
     */
    abstract function settings($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     * 
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function emails($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     * 
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function data($node);

    /**
     * @param   object  $node   The DOM Document node to add html to
     * 
     * @return  bool    True if something was printed to screen false otherwise
     */
    abstract function functions($node);

}