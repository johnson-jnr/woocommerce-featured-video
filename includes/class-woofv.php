<?php

/**
 * The core plugin class.
 *
 *
 * @since      1.0.0
 * @package    Woofv
 * @subpackage Woofv/includes
 * @author     David Towoju (Figarts) <hello@figarts.co>
 */
class WooCommerce_Featured_Video {

  /**
   * The unique identifier of this plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $plugin_name    The string used to uniquely identify this plugin.
   */
  protected $plugin_name;

  /**
   * The current version of the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $version    The current version of the plugin.
   */
  protected $version;

  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the admin area and
   * the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function __construct() {
    
    if ( defined( 'WOOFV_VERSION' ) ) {
      $this->version = WOOFV_VERSION;
    } else {
      $this->version = '1.0.0';
    }
    $this->plugin_name = 'woofv';

    $this->load_dependencies();
    $this->set_locale();
    $this->initialize();
  }


  /**
   * Load the required dependencies for this plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies() {

    /**
     * The class responsible for defining internationalization functionality
     * of the plugin.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woofv-i18n.php';

  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Woofv_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function set_locale() {

    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woofv-i18n.php';

    $plugin_i18n = new Woofv_i18n();
    add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain' ) );

  }

  /**
   * Hooks the functions in the class
   *
   * @since    1.0.0
   * @access   private
   */
  private function initialize() {
    add_action( 'wp_enqueue_scripts', array($this,'enqueue_scripts') );
    add_action( 'add_meta_boxes', array($this, 'video_box') );
    add_action( 'save_post', array($this, 'woofv_save_video_box') );
    add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'replace_featured_image'), 10, 2);
  }


  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts() {
    // enqueue if fitvids is absent
    if ( ! class_exists( 'KplResponsiveVideoEmbeds' ) ) {
      wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/js/woofv-public.js', array( 'jquery' ), $this->version, false );
      wp_enqueue_script( 'fitvids', plugin_dir_url( __FILE__ ) . '../assets/js/jquery.fitvids.js', array( 'jquery' ), $this->version, false );
    }
  }


  /**
   * Create Featured Video metabox
   *
   * @since    1.0.0
   * @access   public
   */
  public function video_box( $post_type ) {
    add_meta_box(
      'some_meta_box_name',
      esc_html__( 'Featured Video', 'woofv' ),
      array($this, 'render_video_box'),
      'product',
      'side',
      'low'
    );
  }


  /**
   * Render Meta Box content.
   *
   * @param WP_Post $post The post object.
   */
  public function render_video_box( $post ) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'woofv_video_box', 'woofv_video_box_nonce' );

    // Use get_post_meta to retrieve an existing value from the database.
    $woofv_video_embed = maybe_unserialize( get_post_meta( $post->ID, '_woofv_video_embed', true ) );

    $url = isset($woofv_video_embed['url']) ? $woofv_video_embed['url'] : '';
    $position = isset($woofv_video_embed['position']) ? $woofv_video_embed['position'] : 'replace';
    $type = isset($woofv_video_embed['type']) ? $woofv_video_embed['type'] : 'youtube';
    $source = isset($woofv_video_embed['source']) ? $woofv_video_embed['source'] : '';
    
    // dump($source);

    // Display the form, using the current value.
    ?>
    <p style="display:none"><?php esc_html_e( 'Video URL', 'woofv' ); ?></p>
    <br/>
    <textarea id="woofv_video_embed_url" name="woofv_video_embed[url]" style="width: 98%" placeholder="Video URL"><?php echo esc_attr( $url ); ?></textarea>
   
    <br>
    <select name="woofv_video_embed[source]" style="margin-bottom: 10px; margin-top: 4px">
      <option value="local" <?php echo ($source == "local") ? ' selected="selected"' : '' ?>> Local media URL </option>
      <option value="external" <?php echo ($source == "external") ? ' selected="selected"' : '' ?>> External URL </option>
    </select> <br>
    <?php
    
    $url = 'https://figarts.co/donate/?utm_source=woofv&utm_medium=wp&utm_campaign=donate';
    $link = sprintf( wp_kses( __( 'Replaces featured image with video.', 'woofv' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
    echo $link;    
    
    ?>

    <?php      
  }
  
  /**
   * Save the meta when the post is saved.
   *
   * @param int $post_id The ID of the post being saved.
   */
  public function woofv_save_video_box( $post_id ) {
    /*
    * We need to verify this came from the our screen and with proper authorization,
    * because save_post can be triggered at other times.
    */

    // Check if our nonce is set.
    if ( ! isset( $_POST['woofv_video_box_nonce'] ) ) {
      return $post_id;
    }

    $nonce = $_POST['woofv_video_box_nonce'];

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce, 'woofv_video_box' ) ) {
      return $post_id;
    }

    /*
      * If this is an autosave, our form has not been submitted,
      * so we don't want to do anything.
      */
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    // Check the user's permissions.
    if ( 'product' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return $post_id;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Sanitize the user input.
    $woofv_data = array_map('sanitize_text_field', $_POST['woofv_video_embed'] );

    // Update the meta field.
    update_post_meta( $post_id, '_woofv_video_embed', $woofv_data );
  }

  /**
   * Replaces featured image with video
   *
   * @param int $post_id The ID of the post being saved.
   */
  public function replace_featured_image($html, $get_post_thumbnail_id){

    if ( !is_product() ) 
      return $html;

    $post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
    $video_url = esc_url( $this->get_product_video() ); 
    $source = esc_html( $this->get_product_video_source() );

    // Return if video is empty
    if ( !$video_url ) 
      return $html;

      if ($get_post_thumbnail_id == $post_thumbnail_id) {
        $html  = '<div data-thumb="" class="woocommerce-product-gallery__image woofv_video">';

        if ($source == "external") {
          global $wp_embed;
          $html  .= '<a href="">';
          $html .= $wp_embed->run_shortcode( '[embed]' . $video_url . '[/embed]' );
          $html .= '</a>';
        }

        else {
          $attr = array('src' => $video_url);
          $html .= wp_video_shortcode( $attr );
        }

        $html .= '</div>';


    }
    return $html;
  }

  /**
   * Replaces featured image with video
   *
   * @param int $post_id The ID of the post being saved.
   */
  private function get_product_video(){
    $meta = get_post_meta( get_the_ID(), '_woofv_video_embed', true );
    $url = isset($meta['url']) ? $meta['url'] : '';
    return $url;
  }

  private function get_product_video_source(){
    $meta = get_post_meta( get_the_ID(), '_woofv_video_embed', true );
    $source = isset($meta['source']) ? $meta['source'] : '';
  
    return $source;
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @since     1.0.0
   * @return    string    The name of the plugin.
   */
  public function get_plugin_name() {
    return $this->plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @since     1.0.0
   * @return    Woofv_Loader    Orchestrates the hooks of the plugin.
   */
  public function get_loader() {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @since     1.0.0
   * @return    string    The version number of the plugin.
   */
  public function get_version() {
    return $this->version;
  } 
 
}
