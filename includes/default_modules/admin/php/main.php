<?php
namespace SIM\ADMIN;
use SIM;

if ( ! defined( 'ABSPATH' ) ) exit;


add_action( 'admin_menu', function(){
    new AdminMenu();
} );
