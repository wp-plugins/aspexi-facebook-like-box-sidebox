<?php
/*
Plugin Name: Aspexi Facebook Like Box Sidebox
Plugin URI:  http://aspexi.com/downloads/aspexi-facebook-like-box-sidebox-hd/?src=free_plugin
Description: Plugin adds fancy Facebook Like Box Sidebox.
Author: Aspexi
Version: 1.0.1
Author URI: http://aspexi.com/
License: GPLv2 or later

    © Copyright 2014 Aspexi
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined('ABSPATH') or exit();

if ( !class_exists( 'AspexiFBsidebox' ) ) {

    define('ASPEXIFBSIDEBOX_VERSION', '1.0.1');
    define('ASPEXIFBSIDEBOX_URL', plugins_url() . '/aspexi-facebook-side-box/');

    class AspexiFBsidebox {

        public $cf          = array(); // config array
        private $messages   = array(); // admin messages
        private $errors     = array(); // admin errors

        public function __construct() {

            /* Configuration */
            $this->settings();

            add_action( 'admin_menu',           array( &$this, 'admin_menu'));
            add_action( 'init',                 array( &$this, 'init' ), 10 );
            add_action( 'wp_footer',            array( &$this, 'get_html' ), 21 );
            add_action( 'admin_enqueue_scripts',array( &$this, 'admin_scripts') );
            add_action( 'wp_enqueue_scripts',   array( &$this, 'init_scripts') );
            add_filter( 'plugin_action_links',  array( &$this, 'settings_link' ), 10, 2);
            
            register_uninstall_hook( __FILE__, array( 'AspexiFBsidebox', 'uninstall' ) );
        }

        /* WP init action */
        public function init() {

            /* Internationalization */
            load_plugin_textdomain( 'aspexifbsidebox', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

            /* Exras */
            $this->extras_init();
        }

        public function settings() {

            /* Defaults */
            $cf_default = array(
                'aspexifbsidebox_version' => ASPEXIFBSIDEBOX_VERSION,
                'url' => '',
                'locale' => 'en_GB',
                'status' => 'enabled',
            );

            /* Install default options */
            if ( is_multisite() ) {
                if ( !get_site_option( 'aspexifbsidebox_options' ) ) {
                    add_site_option( 'aspexifbsidebox_options', $cf_default, '', 'yes' );
                }
            } else {
                if ( !get_option( 'aspexifbsidebox_options' ) )
                    add_option( 'aspexifbsidebox_options', $cf_default, '', 'yes' );
            }

            /* Get options from the database */
            if ( is_multisite() )
                $this->cf = get_site_option( 'aspexifbsidebox_options' );
            else
                $this->cf = get_option( 'aspexifbsidebox_options' );

            /* Upgrade */
            if( $this->cf['aspexifbsidebox_version'] != ASPEXIFBSIDEBOX_VERSION ) {
                switch( $this->cf['aspexifbsidebox_version'] ) {
                   default:
                        $this->cf = array_merge( $cf_default, (array)$this->cf );
                        $this->cf['aspexifbsidebox_version'] = ASPEXIFBSIDEBOX_VERSION;
                        if ( is_multisite() )
                            update_site_option( 'aspexifbsidebox_options',  $this->cf, '', 'yes' );
                        else
                            update_option( 'aspexifbsidebox_options',  $this->cf, '', 'yes' );
               }
            }
        }

        public function settings_link( $action_links, $plugin_file ){
            if( $plugin_file == plugin_basename(__FILE__) ) {
                $settings_link = '<a href="themes.php?page=' . dirname( plugin_basename( __FILE__ ) ).'.php">' . __("Settings") . '</a>';
                array_unshift( $action_links, $settings_link );
            }
            return $action_links;
        }

        private function add_message( $message ) {
            $message = trim( $message );

            if( strlen( $message ) )
                $this->messages[] = $message;
        }

        private function add_error( $error ) {
            $error = trim( $error );

            if( strlen( $error ) )
                $this->errors[] = $error;
        }

        public function has_errors() {
            return count( $this->errors );
        }

        public function display_admin_notices( $echo = false ) {
            $ret = '';

            foreach( (array)$this->errors as $error ) {
                $ret .= '<div class="error fade"><p><strong>'.$error.'</strong></p></div>';
            }

            foreach( (array)$this->messages as $message ) {
                $ret .= '<div class="updated fade"><p><strong>'.$message.'</strong></p></div>';
            }

            if( $echo )
                echo $ret;
            else
                return $ret;
        }

        public function admin_menu() {
            add_submenu_page( 'themes.php', __( 'Aspexi Facebook Like Box Sidebox', 'aspexifbsidebox' ), __( 'Facebook Like Box Sidebox', 'aspexifbsidebox' ), 'manage_options', 'aspexi-facebook-side-box.php', array( &$this, 'admin_page') );
        }

        public function admin_page() {

            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            $preview = false;

            // request action
            if ( isset( $_REQUEST['afbsb_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'afbsb_nonce_name' ) ) {

                if( !in_array( $_REQUEST['afbsb_status'], array('enabled','disabled') ) )
                    $this->add_error( __( 'Wrong or missing status. Available statuses: enabled and disabled. Settings not saved.', 'aspexifbsidebox' ) );

                if( !$this->has_errors() ) {
                    $aspexifbsidebox_request_options = array();

                    $aspexifbsidebox_request_options['url']     = isset( $_REQUEST['afbsb_url'] ) ? trim( $_REQUEST['afbsb_url'] ) : '';
                    $aspexifbsidebox_request_options['locale']  = isset( $_REQUEST['afbsb_locale'] ) ? $_REQUEST['afbsb_locale'] : '';
                    $aspexifbsidebox_request_options['status']  = isset( $_REQUEST['afbsb_status'] ) ? $_REQUEST['afbsb_status'] : '';
                    $this->cf = array_merge( (array)$this->cf, $aspexifbsidebox_request_options );

                    if ( is_multisite() ) 
                        update_site_option( 'aspexifbsidebox_options',  $this->cf, '', 'yes' );
                    else
                        update_option( 'aspexifbsidebox_options',  $this->cf, '', 'yes' );

                    $this->add_message( __( 'Settings saved.', 'aspexifbsidebox' ) );

                    // Preview maybe
                    if( @$_REQUEST['preview'] )
                        $preview = true;
                    else
                        $preview = false;  
                }   
            }

            // Locale
            $locales = array(
                'Afrikaans' => 'af_ZA',
                'Albanian' => 'sq_AL',
                'Arabic' => 'ar_AR',
                'Armenian' => 'hy_AM',
                'Aymara' => 'ay_BO',
                'Azeri' => 'az_AZ',
                'Basque' => 'eu_ES',
                'Belarusian' => 'be_BY',
                'Bengali' => 'bn_IN',
                'Bosnian' => 'bs_BA',
                'Bulgarian' => 'bg_BG',
                'Catalan' => 'ca_ES',
                'Cherokee' => 'ck_US',
                'Croatian' => 'hr_HR',
                'Czech' => 'cs_CZ',
                'Danish' => 'da_DK',
                'Dutch' => 'nl_NL',
                'Dutch (Belgium)' => 'nl_BE',
                'English (Pirate)' => 'en_PI',
                'English (UK)' => 'en_GB',
                'English (Upside Down)' => 'en_UD',
                'English (US)' => 'en_US',
                'Esperanto' => 'eo_EO',
                'Estonian' => 'et_EE',
                'Faroese' => 'fo_FO',
                'Filipino' => 'tl_PH',
                'Finnish' => 'fi_FI',
                'Finnish (test)' => 'fb_FI',
                'French (Canada)' => 'fr_CA',
                'French (France)' => 'fr_FR',
                'Galician' => 'gl_ES',
                'Georgian' => 'ka_GE',
                'German' => 'de_DE',
                'Greek' => 'el_GR',
                'Guaran' => 'gn_PY',
                'Gujarati' => 'gu_IN',
                'Hebrew' => 'he_IL',
                'Hindi' => 'hi_IN',
                'Hungarian' => 'hu_HU',
                'Icelandic' => 'is_IS',
                'Indonesian' => 'id_ID',
                'Irish' => 'ga_IE',
                'Italian' => 'it_IT',
                'Japanese' => 'ja_JP',
                'Javanese' => 'jv_ID',
                'Kannada' => 'kn_IN',
                'Kazakh' => 'kk_KZ',
                'Khmer' => 'km_KH',
                'Klingon' => 'tl_ST',
                'Korean' => 'ko_KR',
                'Kurdish' => 'ku_TR',
                'Latin' => 'la_VA',
                'Latvian' => 'lv_LV',
                'Leet Speak' => 'fb_LT',
                'Limburgish' => 'li_NL',
                'Lithuanian' => 'lt_LT',
                'Macedonian' => 'mk_MK',
                'Malagasy' => 'mg_MG',
                'Malay' => 'ms_MY',
                'Malayalam' => 'ml_IN',
                'Maltese' => 'mt_MT',
                'Marathi' => 'mr_IN',
                'Mongolian' => 'mn_MN',
                'Nepali' => 'ne_NP',
                'Northern Sami' => 'se_NO',
                'Norwegian (bokmal)' => 'nb_NO',
                'Norwegian (nynorsk)' => 'nn_NO',
                'Pashto' => 'ps_AF',
                'Persian' => 'fa_IR',
                'Polish' => 'pl_PL',
                'Portuguese (Brazil)' => 'pt_BR',
                'Portuguese (Portugal)' => 'pt_PT',
                'Punjabi' => 'pa_IN',
                'Quechua' => 'qu_PE',
                'Romanian' => 'ro_RO',
                'Romansh' => 'rm_CH',
                'Russian' => 'ru_RU',
                'Sanskrit' => 'sa_IN',
                'Serbian' => 'sr_RS',
                'Simplified Chinese (China)' => 'zh_CN',
                'Slovak' => 'sk_SK',
                'Slovenian' => 'sl_SI',
                'Somali' => 'so_SO',
                'Spanish' => 'es_LA',
                'Spanish (Chile)' => 'es_CL',
                'Spanish (Colombia)' => 'es_CO',
                'Spanish (Mexico)' => 'es_MX',
                'Spanish (Spain)' => 'es_ES',
                'Spanish (Venezuela)' => 'es_VE',
                'Swahili' => 'sw_KE',
                'Swedish' => 'sv_SE',
                'Syriac' => 'sy_SY',
                'Tajik' => 'tg_TJ',
                'Tamil' => 'ta_IN',
                'Tatar' => 'tt_RU',
                'Telugu' => 'te_IN',
                'Thai' => 'th_TH',
                'Traditional Chinese (Hong Kong)' => 'zh_HK',
                'Traditional Chinese (Taiwan)' => 'zh_TW',
                'Turkish' => 'tr_TR',
                'Ukrainian' => 'uk_UA',
                'Urdu' => 'ur_PK',
                'Uzbek' => 'uz_UZ',
                'Vietnamese' => 'vi_VN',
                'Welsh' => 'cy_GB',
                'Xhosa' => 'xh_ZA',
                'Yiddish' => 'yi_DE',
                'Zulu' => 'zu_ZA'
            );

            $locales_input = '<select name="afbsb_locale">';

            foreach( $locales as $k => $v ) {
                $locales_input .= '<option value="'.$v.'"'.( ( $this->cf['locale'] == $v ) ? ' selected="selected"' : '' ).'>'.$k.'</option>';
            }

            $locales_input .= '</select>';

            // show form
            ?>
            <div class="wrap">
                <div id="icon-link" class="icon32"></div><h2><?php _e( 'Aspexi Facebook Like Box Sidebox Settings', 'aspexifbsidebox' ); ?></h2>
                <?php $this->display_admin_notices( true ); ?>
                <div id="poststuff" class="metabox-holder">
                    <div id="post-body">
                        <div id="post-body-content">
                            <form method="post" action="themes.php?page=aspexi-facebook-side-box.php">

                                <input type="hidden" name="afbsb_form_submit" value="submit" />

                                <div class="postbox">
                                    <h3><span><?php _e('Settings', 'aspexifbsidebox'); ?></span></h3>
                                    <div class="inside">
                                    <table class="form-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Like Box', 'aspexifbsidebox'); ?></th>
                                                <td><select name="afbsb_status">
                                                    <option value="enabled"<?php if( 'enabled' == $this->cf['status'] ) echo ' selected="selected"'; ?>><?php _e('enabled', 'aspexifbsidebox'); ?></option>
                                                    <option value="disabled"<?php if( 'disabled' == $this->cf['status'] ) echo ' selected="selected"'; ?>><?php _e('disabled', 'aspexifbsidebox'); ?></option>
                                                    </select></td>
                                            </tr>                                        
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Facebook Page URL', 'aspexifbsidebox'); ?></th>
                                                <td>http://www.facebook.com/&nbsp;<input type="text" name="afbsb_url" value="<?php echo $this->cf['url']; ?>" />
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Like Box Height', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_height" value="258" size="3" disabled readonly />&nbsp;px<?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Like Box Width', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_width" value="296" size="3" disabled readonly />&nbsp;px<?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Show Friends\' Faces', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_faces" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Number of Connections', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('For auto generated number of connection set 0', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="text" name="afbsb_faces_count" value="0" size="3" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Show Posts (Stream)', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_stream" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Force Wall', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('For "place" Pages (Pages that have a physical location that can be used with check-ins), this specifies whether the stream contains posts by the Page or just check-ins from friends.', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="checkbox" value="on" name="afbsb_wall" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Header', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Specifies whether to display the Facebook header at the top of the plugin.', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="checkbox" value="on" name="afbsb_header" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Localization', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Change might not be visible immediately due to Facebook / browser cache', 'aspexifbsidebox'); ?></span></th>
                                                <td><?php echo $locales_input; ?></td>
                                            </tr>
                                            <?php
                                            echo apply_filters('aspexifbsidebox_admin_settings', '');
                                            ?>                                           
                                        </tbody>
                                    </table>
                                                                           
                                    </div>
                                </div>

                                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save all settings', 'aspexifbsidebox'); ?>" id="submitbutton" />
                                <input class="button-secondary" type="submit" name="preview" value="<?php _e('Save and preview', 'aspexifbsidebox'); ?>" id="previewbutton" /></p>
                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'afbsb_nonce_name' ); ?>

                                <div class="postbox">
                                    <h3><span><?php _e('Button Settings', 'aspexifbsidebox'); ?></span></h3>
                                    <div class="inside">
                                    <table class="form-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Placement', 'aspexifbsidebox'); ?></th>
                                                <td><select name="afbsb_placement" disabled readonly>
                                                    <option value="left"><?php _e('left', 'aspexifbsidebox'); ?></option>
                                                    <option value="right" selected="selected"><?php _e('right', 'aspexifbsidebox'); ?></option>
                                                    </select><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Button Space', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Space between button and left/right page edge', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="text" name="afbsb_btspace" value="0" size="3" disabled readonly />&nbsp;px<?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Button Placement', 'aspexifbsidebox'); ?></th>
                                                <td><input type="radio" name="afbsb_btvertical" value="top" disabled readonly />&nbsp;<?php _e('top of like box','aspexifbsidebox'); ?><br />
                                                    <input type="radio" name="afbsb_btvertical" value="middle" checked disabled readonly />&nbsp;<?php _e('middle of like box','aspexifbsidebox'); ?><br />
                                                    <input type="radio" name="afbsb_btvertical" value="bottom" disabled readonly />&nbsp;<?php _e('bottom of like box','aspexifbsidebox'); ?><br />
                                                    <input type="radio" name="afbsb_btvertical" value="fixed" disabled readonly />&nbsp;<?php _e('fixed','aspexifbsidebox'); ?>
                                                    <input type="text" name="afbsb_btvertical_val" value="" size="3" disabled readonly />&nbsp;px <?php _e('from like box top','aspexifbsidebox'); ?><?php echo $this->get_pro_link(); ?>
                                                    </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Button Image', 'aspexifbsidebox'); ?></th>
                                                <td><span><input type="radio" name="afbsb_btimage" value="fb1-right" checked disabled readonly />&nbsp;<img src="<?php echo ASPEXIFBSIDEBOX_URL.'images/fb1-right.png'; ?>" alt="" style="cursor:pointer;" /></span>
                                                <span><input type="radio" name="afbsb_btimage" value="" disabled readonly />&nbsp;<img src="<?php echo ASPEXIFBSIDEBOX_URL.'images/preview-buttons.jpg'; ?>" alt="" style="cursor:pointer;" /></span><?php echo $this->get_pro_link(); ?>
                                                </td>
                                            </tr>   
                                            <tr valign="top">
                                                <th scope="row"><?php _e('High Resolution', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Use SVG high quality images instead of PNG if possible. Recommended for Retina displays (iPhone, iPad, MacBook Pro).', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="checkbox" value="on" name="afbsb_bthq" disabled readonly />&nbsp;<img src="<?php echo ASPEXIFBSIDEBOX_URL.'images/svgonoff.png'; ?>" alt="" style="cursor:pointer;" /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>                              
                                        </tbody>
                                    </table>
                                    </div>
                                </div>

                                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save all settings', 'aspexifbsidebox'); ?>" id="submitbutton" />
                                <input class="button-secondary" type="submit" name="preview" value="<?php _e('Save and preview', 'aspexifbsidebox'); ?>" id="previewbutton" /></p>

                                <div class="postbox">
                                    <h3><span><?php _e('Advanced Look and Feel', 'aspexifbsidebox'); ?></span></h3>
                                    <div class="inside">
                                    <table class="form-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Vertical placement', 'aspexifbsidebox'); ?></th>
                                                <td><input type="radio" name="afbsb_vertical" value="middle" checked disabled readonly />&nbsp;<?php _e('middle','aspexifbsidebox'); ?><br />
                                                    <input type="radio" name="afbsb_vertical" value="fixed" disabled readonly />&nbsp;<?php _e('fixed','aspexifbsidebox'); ?>
                                                    <input type="text" name="afbsb_vertical_val" value="" size="3" disabled readonly />&nbsp;px <?php _e('from page top','aspexifbsidebox'); ?><?php echo $this->get_pro_link(); ?><br />
                                                    <input type="radio" name="afbsb_vertical" value="fixed2" disabled readonly />&nbsp;<?php _e('fixed','aspexifbsidebox'); ?>
                                                    <input type="text" name="afbsb_vertical_val2" value="" size="3" disabled readonly />&nbsp;px <?php _e('from page bottom','aspexifbsidebox'); ?><?php echo $this->get_pro_link(); ?>
                                                </td>
                                            </tr>    
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Color Scheme', 'aspexifbsidebox'); ?></th>
                                                <td><select name="afbsb_colorscheme" disabled readonly>
                                                    <option value="light" selected="selected"><?php _e('light', 'aspexifbsidebox'); ?></option>
                                                    <option value="dark"><?php _e('dark', 'aspexifbsidebox'); ?></option>
                                                    </select><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Border Color', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_bordercolor" class="bordercolor-field" value="#3B5998" size="6" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>    
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Border Width', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_borderwidth" value="2" size="3" disabled readonly />&nbsp;px<?php echo $this->get_pro_link(); ?></td>
                                            </tr>   
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Background Color', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_bgcolor" class="bgcolor-field" value="#FFFFFF" size="6" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Slide on mouse...', 'aspexifbsidebox'); ?></th>
                                                <td><select name="afbsb_slideon" disabled readonly>
                                                    <option value="hover" selected="selected"><?php _e('hover', 'aspexifbsidebox'); ?></option>
                                                    <option value="click"><?php _e('click', 'aspexifbsidebox'); ?></option>
                                                    </select><?php echo $this->get_pro_link(); ?></td>
                                            </tr>   
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Slide Time', 'aspexifbsidebox'); ?></th>
                                                <td><input type="text" name="afbsb_slidetime" value="400" size="3" disabled readonly />&nbsp;<?php _e('milliseconds', 'aspexifbsidebox'); ?><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Delay FB content load', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Checking this box will prevent from loading the facebook content while loading the whole page. With this box checked the page will load faster, but facebook content may appear a bit later while opening the box for the first time.', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="checkbox" value="on" name="afbsb_async" disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Disable on GET', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Example: set Parameter=iframe and Value=true. Like Box will be disabled on all URLs like yourwebsite.com/?iframe=true.', 'aspexifbsidebox'); ?></span></th>
                                                <td><?php _e('Parameter', 'aspexifbsidebox'); ?>:&nbsp;<input type="text" name="afbsb_disableparam" value="" size="6" disabled readonly /><br />
                                                    <?php _e('Value', 'aspexifbsidebox'); ?>:&nbsp;<input type="text" name="afbsb_disableval" value="" size="6" disabled readonly /><?php echo $this->get_pro_link(); ?>
                                                </td>
                                            </tr>  
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Disable on Small Screens', 'aspexifbsidebox'); ?><br /><span style="font-size: 10px"><?php _e('Dynamically hide the plugin if screen size is smaller than like box size (CSS media query)', 'aspexifbsidebox'); ?></span></th>
                                                <td><input type="checkbox" value="on" name="afbsb_smallscreens" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>                       
                                        </tbody>
                                    </table>
                                    </div>
                                </div>

                                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save all settings', 'aspexifbsidebox'); ?>" id="submitbutton" />
                                <input class="button-secondary" type="submit" name="preview" value="<?php _e('Save and preview', 'aspexifbsidebox'); ?>" id="previewbutton" /></p>

                                <div class="postbox">
                                    <h3><span><?php _e('Enable on Mobile', 'aspexifbsidebox'); ?></span></h3>
                                    <div class="inside">
                                    <table class="form-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('iPad & iPod', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_edipad" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                             <tr valign="top">
                                                <th scope="row"><?php _e('iPhone', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_ediphone" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Android', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_edandroid" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Other Mobile Devices', 'aspexifbsidebox'); ?></th>
                                                <td><input type="checkbox" value="on" name="afbsb_edothers" checked disabled readonly /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>                         
                                        </tbody>
                                    </table>
                                    </div>
                                </div>

                                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save all settings', 'aspexifbsidebox'); ?>" id="submitbutton" />
                                <input class="button-secondary" type="submit" name="preview" value="<?php _e('Save and preview', 'aspexifbsidebox'); ?>" id="previewbutton" /></p>
                            </form>
                            <div class="postbox">
                                <h3><span>Made by</span></h3>   
                                <div class="inside">
                                    <div style="width: 170px; margin: 0 auto;">
                                        <a href="<?php echo $this->get_pro_url(); ?>" target="_blank"><img src="<?php echo ASPEXIFBSIDEBOX_URL.'images/aspexi300.png'; ?>" alt="" border="0" width="150" /></a>
                                    </div>
                                </div>
                            </div>   
                        </div>                                             
                    </div>
                </div>
            </div>
            <?php
            // Preview
            if( $preview ) {
                $this->init_scripts();
                echo $this->get_html($preview);
            }
        }

        public function get_pro_url() {
            return 'http://aspexi.com/downloads/aspexi-facebook-like-box-sidebox-hd/?src=free_plugin';
        }

        public function get_pro_link() {
            $ret = '';

            $ret .= '&nbsp;&nbsp;&nbsp;<a href="'.$this->get_pro_url().'" target="_blank">'.__( 'Get PRO version', 'aspexifbsidebox' ).'</a>';

            return $ret;
        }

        public function get_html( $preview = false ) {

            $url            = apply_filters( 'aspexifbsidebox_url', $this->cf['url'] );
            $status         = apply_filters( 'aspexifbsidebox_status', $this->cf['status'] );

            // Disable maybe
            if( ( !strlen( $url ) || 'enabled' != $status ) && !$preview )
                return;

            // Options
            $locale         = apply_filters( 'aspexifbsidebox_locale', $this->cf['locale'] );
            $height         = 258;
            $width          = 296;
            $placement      = 'right';
            $btspace        = 0;
            $btimage        = 'fb1-right.png';
            $bordercolor    = '#3B5998';
            $borderwidth    = 2;
            $bgcolor        = '#ffffff';
  
            $css_placement = array();
            if( 'left' == $placement ) {
                $css_placement[0] = 'right';
                $css_placement[1] = '0 '.(51+$btspace).'px 0 5px';
            } else {
                $css_placement[0] = 'left';
                $css_placement[1] = '0 5px 0 '.(51+$btspace).'px';
            }

            $css_placement[2] = '50%;margin-top:-'.floor($height/2).'px';

			$smallscreenscss = '';
            if( $width > 0 ) {
                $widthmax = (int)($width + 2 * $borderwidth + 48 + 30);
                $smallscreenscss = '@media (max-width: '.$widthmax.'px) { #aspexifbsidebox { display: none; } }';
            }

            $stream     = 'false';
            $header     = 'false';

            // Facebook button image (check in THEME CHILD -> THEME PARENT -> PLUGIN DIR)
            // TODO: move this to admin page
            $users_button_custom    = '/plugins/aspexi-facebook-side-box/images/aspexi-fb-custom.png';
            $users_button_template  = get_template_directory() . $users_button_custom;
            $users_button_child     = get_stylesheet_directory() . $users_button_custom;
            $button_uri             = '';

            if( file_exists( $users_button_child ) )
                $button_uri = get_stylesheet_directory_uri() . $users_button_custom;
            elseif( file_exists( $users_button_template ) )
                $button_uri = get_template_directory_uri() . $users_button_custom;
            elseif( file_exists( plugin_dir_path( __FILE__ ).'images/'.$btimage ) )
                $button_uri = ASPEXIFBSIDEBOX_URL.'images/'.$btimage;

            if( '' == $button_uri ) {
                $button_uri = ASPEXIFBSIDEBOX_URL.'images/fb1-right.png';
            }

            $button_uri  = apply_filters( 'aspexifbsidebox_button_uri', $button_uri );
            
            $output = '';

            $output .= '<style type="text/css">'.$smallscreenscss.'
#aspexifbsidebox{box-sizing: content-box;-webkit-box-sizing: content-box;-moz-box-sizing: content-box;
        bottom: 180px;
    right: 0;
    margin: 0;
    padding: 0;
    position: fixed;
    list-style: none;
    z-index: 99999;}

.aspexifbfacebook {
    background: none repeat scroll 0 0 rgba(0, 0, 0, 0);
    height: 155px;
    padding: 0;
    position: relative;
    width: 51px;
    box-sizing: content-box;
    -webkit-box-sizing: content-box;
    -moz-box-sizing: content-box;
} 

.aspexifbfacebook.active {
    padding-top: 250px;
    width: 51px;
}

.aspexifbfacebook.active .afbbox {
    display: block;
    width:0; 
    opacity:0;
}

.aspexifbfacebook img { display: block; padding-left:3px; }  

.aspexifbfacebook .afbbox {
    border: 3px solid #4464a9;
    position: absolute;
    bottom: 0;
    right: 51px;
    background: #fff;
    opacity: 0;
    display: none;
}
.afbbox {
    overflow: hidden;
}
.aspexifbfacebook .afbbox iframe {
    display: block;
    max-width: none;
    margin-bottom: 0px;
}

.aspexifbfacebook .arrow {
    display: none;
    width:4px; height:7px; 
    background: none; position: absolute; bottom: 15px; right: 44px;
}
            </style><ul id="aspexifbsidebox">
    <li class="aspexifbfacebook">
        <img alt="facebook" src="'.$button_uri.'">
        <span class="arrow"></span>
        <div class="afbbox" data-width="'.$width.'">
            <iframe frameborder="0" allowtransparency="true" style="border:none; overflow:hidden; width:'.$width.'px; height:'.$height.'px;" scrolling="no" src="http://www.facebook.com/plugins/likebox.php?locale='.$locale.'&amp;href=http%3A%2F%2Fwww.facebook.com%2F'.$url.'&amp;width=300&amp;height='.$height.'&amp;colorscheme=light&amp;show_faces=true&amp;border_color=%23ffffff&amp;stream=false&amp;header=false"></iframe>
            <em></em>
        </div>
    </li>
</ul>';

            $output = apply_filters( 'aspexifbsidebox_output', $output );

            echo $output;
        }

        public function init_scripts() {
            $width      = 296;
            $placement  = 'right';
            $slideon    = 'hover';

            wp_enqueue_script( 'aspexi-facebook-side-box', ASPEXIFBSIDEBOX_URL . 'js/afsb.js', array( 'jquery' ), false, true );
            wp_localize_script( 'aspexi-facebook-side-box', 'afsb', array(
                    'slideon'   => $slideon,
                    'placement' => $placement,
                    'width'     => (int)$width
                ) );
        }

        public static function uninstall() {

            if ( !is_multisite() ) 
                delete_option( 'aspexifbsidebox_options' );
            else {
                global $wpdb;
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                $original_blog_id = get_current_blog_id();
                foreach ( $blog_ids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    delete_option( 'aspexifbsidebox_options' );  
                }
                switch_to_blog( $original_blog_id );

                delete_site_option( 'aspexifbsidebox_options' );  
            }
        }

        public function admin_scripts() {
            // premium only
            return;
        }
        
        public function extras_init() {
            /* qTranslate */
            add_filter( 'aspexifbsidebox_admin_settings', array( &$this, 'extras_qtranslate_admin' ) );
            add_filter( 'aspexifbsidebox_admin_settings', array( &$this, 'extras_polylang_admin' ) );
        }
        
        public function extras_qtranslate_detect() {
            global $q_config;
            return (isset($q_config) && !empty($q_config));
        }
        
        public function extras_qtranslate_admin( $extra_admin_content ) {
            $qtranslate_locale = $this->extras_qtranslate_detect();

            if( $qtranslate_locale ) {
                $extra_admin_content .= '<tr valign="top">
    <th scope="row">'.__('qTranslate/mqTranslate', 'aspexifbsidebox').'<br /><span style="font-size: 10px">'.__('Try to detect qTranslate/mqTranslate language and force it instead of language set in Localization.', 'aspexifbsidebox').'</span></th>
    <td><input type="checkbox" value="on" name="afbsb_qtranslate" disabled readonly />'.$this->get_pro_link().'</td>
</tr>';
            }

            return $extra_admin_content;
        }
        
        public function extras_polylang_admin( $extra_admin_content ) {
            
            if(function_exists('pll_current_language')) {
                $extra_admin_content .= '<tr valign="top">
    <th scope="row">'.__('Polylang', 'aspexifbsidebox').'<br /><span style="font-size: 10px">'.__('Try to detect Polylang language and force it instead of language set in Localization.', 'aspexifbsidebox').'</span></th>
    <td><input type="checkbox" value="on" name="afbsb_polylang" disabled readonly />'.$this->get_pro_link().'</td>
</tr>';
            }

            return $extra_admin_content;
        }
    }

    /* Let's start the show */
    global $aspexifbsidebox;

    $aspexifbsidebox = new AspexiFBsidebox();
}