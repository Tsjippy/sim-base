<?php
namespace SIM\ADMIN;
use SIM;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminMenu{
    public $tab;
    public $tabLinkButtonsWrapper;
    public $mainDiv;
    public $dom;
    public $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->tab      = 'settings';
        if(isset($_GET['tab'])){
            $this->tab  = sanitize_key($_GET['tab']);
        }

        $this->dom		= new \DOMDocument();

        // Register a custom menu page.
        add_menu_page("SIM Plugin Settings", "SIM Settings", 'edit_others_posts', "sim", [$this, "mainMenu"]);

        foreach(wp_get_active_and_valid_plugins() as $plugin){
            if(
                strpos($plugin, 'tsjippy-') !== false &&                    // Only add submenu for tsjippy plugins
                strpos($plugin, 'tsjippy-shared-functionality') === false   // But not for the shared functionality plugin
            ){
                $slug = str_replace('tsjippy-', '', basename($plugin, '.php'));
                $name = ucwords(str_replace('-', ' ', $slug));
    
                add_submenu_page(
                    'sim', 
                    $name, 
                    $name, 
                    "edit_others_posts", 
                    "sim_$slug", 
                    function() use ( $name, $slug ){
                        $this->buildSubMenu($name, $slug);
                    }
                );
            }
        }
    }
    
    public function mainMenu(){
        do_action('sim_plugin_actions');
    
        ?>
        <div class="wrap">
            <h1>SIM Plugin Settings</h1>
            <p>Welcome to the SIM Plugin Settings page!</p>
        </div>
        <?php
    }
    
    /**
     * Tablink button for the submenu
     * 
     * @param   string  $slug   The slug one of settings, emails, data or functions
     * 
     * @return DOMElement       The DOm Document node
     */
    public function tabLinkButton($slug){
        $classString		= 'tablink';
        
        if($this->tab == $slug){
            $classString	.= ' active';
        }
        
        $attributes				= [
            'class' 		=> $classString, 
            'id' 			=> "show-$slug", 
            'data-target'	=> $slug
        ];

        if($slug == 'settings'){
            $position   = 'afterBegin';
        }else{
            $position   = 'beforeEnd';   
        }
        return addElement('button', $this->tabLinkButtonsWrapper, $attributes, ucfirst($slug), $position);
    }

    /**
    * Build the submenu container and tablink button
    * 
    * @param    string $slug    The slug of the submenu, used for the id and data-target of the button
    * @param    string $name    The name of the submenu
    *
    * @return   DOMElement      The domcontent node
    */
    public function mainNode($slug, $name){
        /**
         * Main container for the submenu
         */
        $attributes				= [
            'id'	=> $slug, 
            'class' => 'tabcontent'
        ];
        if($this->tab != $slug){
            $attributes['class'] .= ' hidden';
        }

        $node    = addElement('div', $this->mainDiv, $attributes);
        addElement('h2', $node, [], $name);

        return $node;
    }

    /**
     * Builds the submenu for each plugin
     */
    public function buildSubMenu($name, $slug){
        if(empty($_GET['page'])){
            return '';
        }

        $this->settings	= get_option("sim_{$slug}_settings", []);

        $message	    = $this->handlePost();

        $this->mainDiv	= addElement('div', $this->dom, ['class' => 'plugin-settings']);
        addElement('h1', $this->mainDiv, [], "$name plugin settings");

        $this->tabLinkButtonsWrapper	= addElement('div', $this->mainDiv, ['class' => 'tablink-wrapper']);
        
        $className          = "SIM\\" . strtoupper($slug) . "\\AdminMenu";
        $subMenu            = new $className($this->settings, $name);
            
        $settingsTab        = $this->settingsTab($subMenu, $slug, $name);
        $emailSettingsTab   = $this->emailSettingsTab($subMenu, $slug, $name);
        $dataTab            = $this->dataTab($subMenu, $slug, $name);
        $functionsTab       = $this->functionsTab($subMenu, $slug, $name);

        // Only add a tablink button for the settings if there is at least on other tab
        if($emailSettingsTab || $dataTab || $functionsTab){
            $this->tabLinkButton('settings');
        }

        if($this->tab == 'settings'){
            $parent = $settingsTab;
        }elseif($this->tab == 'emails'){
            $parent = $emailSettingsTab;
        }elseif($this->tab == 'data'){
            $parent = $dataTab;
        }elseif($this->tab == 'functions'){
            $parent = $functionsTab;
        }

        if(!empty($message)){
            addRawHtml($message, $parent, 'afterBegin');
        }

        echo $this->dom->saveHtml();
    }

    public function settingsTab($subMenu, $slug, $name){
        $node   = $this->mainNode('settings', 'Settings');

        $form   = addElement('form', $node, ['method' => "post"]);
        addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);
        addElement('input', $form, ['type' => "hidden", 'class' => 'no-reset', 'name' => "nonce", 'value' => wp_create_nonce('plugin-settings')]);

        $wrapper    = addElement('div', $form, ['class' => 'options']);

        $hasSettings    = $subMenu->settings($wrapper);

        if($hasSettings){
            addElement('input', $form, ['type' => "submit", 'value' => "Save $name settings"]);
        }else{
            addElement('div', $wrapper, [], 'No special settings needed for this plugin');
        }

        return $node;
    }

    public function emailSettingsTab($subMenu, $slug, $name){
        $node    = $this->mainNode('emails', 'E-mail Settings');

        $form   = addElement('form', $node, ['method' => "post"]);
        addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);

        $hasEmails  = $subMenu->emails($form);

        if($hasEmails){
            addElement('input', $form, ['type' => "submit", 'value' => "Save $name e-mail settings"]);

            $this->tabLinkButton('emails');

            return $node;
        }
        
        $node->remove();

        return false;
    }

    public function dataTab($subMenu, $slug, $name){
        $node    = $this->mainNode('data', 'Data Settings');

        if(!$subMenu->data($node)){
            $node->remove();

            return false;
        }

        $this->tabLinkButton('data');

        return $node;
    }

    public function functionsTab($subMenu, $slug, $name){
        $node    = $this->mainNode('functions', 'Functions');

        if(!$subMenu->functions($node)){
            $node->remove();

            return false;
        }

        $this->tabLinkButton('functions');

        return $node;
    }

    public function handlePost(){
        $message	= apply_filters('sim-admin-settings-post', '', $this->settings);
        
        // do some checks
        if(
            !isset($_POST['plugin']) ||
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'plugin-settings' )
        ){
            return '';
        }

        if(isset($_POST['emails'])){
            $message	.= "<div class='success'>E-mail settings succesfully saved</div>";
            $this->saveEmails();
        }else{
            $message	.= "<div class='success'>Settings succesfully saved</div>";
            $this->saveSettings();
        }
        
        // Build the message
        $plugin	= SIM\getFromTransient('plugin');
        if(isset($plugin)){
            if(isset($plugin['installed'])){
                $name		 = ucfirst($plugin['installed']);
                $message	.= "<br><br>Dependend plugin '$name' succesfully installed and activated";
            }elseif(isset($plugin['activated'])){
                $name		 = ucfirst($plugin['activated']);
                $message	.= "<br><br>Dependend plugin '$name' succesfully activated";
            }
            SIM\deleteFromTransient('plugin');
        }
        
        return $message;
    }

    /**
    * Saves plugins settings from $_POST
    */
    public function saveSettings(){
        if(
            !isset($_POST['plugin']) ||
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'plugin-settings' )
        ){
            return '';
        }

        $slug	    = sanitize_key(wp_unslash($_POST['plugin']));
        $options	= $_POST;
        unset($options['plugin']);
        unset($options['nonce']);

        foreach($options as &$option){
            $option = SIM\deslash($option);
        }

        /**
         * Filters the settings of this sub-plugin
         * @param   array   $options    The options to save, after being sanitized
         * @param   array   $settings   The current saved settings, before saving the new ones
         * @return  array                The options to save, after being processed by the filter
         */
        $this->settings	= apply_filters("sim_plugin_{$slug}_after_save", $options, get_option("sim_{$slug}_settings", []));

        update_option("sim_{$slug}_settings", $this->settings);
    }

    public function saveEmails(){
        if(
            !isset($_POST['plugin']) ||
            !isset($_POST['nonce']) ||
            !isset($_POST['emails']) ||
            !wp_verify_nonce($_POST['nonce'], 'plugin-settings' )
        ){
            return '';
        }

        $slug	        = sanitize_text_field($_POST['plugin']);
        $emailSettings	= $_POST['emails'];
        unset($emailSettings['plugin']);

        foreach($emailSettings as &$emailSetting){
            $emailSetting = SIM\deslash($emailSetting);
        }

        update_option("sim_{$slug}_emails", $emailSettings);
    }
}