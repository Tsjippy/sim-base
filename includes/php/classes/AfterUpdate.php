<?php
namespace TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

class AfterUpdate extends AfterPluginUpdate {

    public function afterPluginUpdate($oldVersion){
        global $wpdb;

        printArray('Running update actions');

        if(version_compare('10.0.0', $oldVersion)){
            $github = new GITHUB\Github();

            /**
             * transfer module settings to option er plugin
             */
            $modules     = get_option('sim_modules', []);

            foreach($modules as $module => $settings){
                if(isset($settings['emails'])){
                    update_option("tsjippy_{$module}_emails", $settings);

                    unset($settings['emails']);
                }
                
                update_option("tsjippy_{$module}_settings", $settings);

                /**
                 * Download the the module as plugin
                 */
                $github->downloadFromGithub('Tsjippy', TSJIPPY\PLUGINNAME, WP_PLUGIN_DIR."\tsjippy-$module");
            }

            /**
             * Rename tables to tsjippy_
             */
            $tables = $wpdb->get_col("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'local' and TABLE_TYPE = 'BASE TABLE' and TABLE_NAME like '%_tsjippy_%'");
            
            foreach($tables as $table){
                $newName    = str_replace('_sim_', '_tsjippy_', $table);
                $wpdb->query("ALTER TABLE $table RENAME TO $newName");
            }

            
        }

        
    }
}
