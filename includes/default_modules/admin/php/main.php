<?php
namespace SIM\ADMIN;
use SIM;

if(PLUGINVERSION < '7.0.0') {
    return;
}

add_action( 'admin_menu', function(){
    new AdminMenu();
} );
