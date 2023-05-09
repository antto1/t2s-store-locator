<?php
/*
Plugin Name: T2s Store locator
Description: A store location plugin that queries the name of the store
Author: Theme 2 site
Author URI: http://theme2site.com/
Version: 1.0.0
*/
define('ASL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASL_PLUGIN_NAME', trim(dirname(ASL_PLUGIN_BASENAME), '/'));
define('ASL_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . ASL_PLUGIN_NAME);
define('ASL_PLUGIN_URL', WP_PLUGIN_URL . '/' . ASL_PLUGIN_NAME);
define('ASL_OPTIONS_PREFIX', 'asl_');
define('ASL_PLUGIN_VERSION', '1.0.0');

// <step1>
function asl_setup()
{
    // Registers custom Post Type.
    $labels = array(
        'name' => 'Stores',
        'singular_name' => 'Store',
        'name_admin_bar' => 'Store',
        'add_new' => __( 'Add' ).'Store',
        'add_new_item' => __( 'Add' ).'Store',
    );
    $args = array(
        'labels' => apply_filters( 't2s_stores_labels', $labels ),
        'description' => '',
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => '',
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => true,
        'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ), /*'author','thumbnail','excerpt','page-attributes', 'comments'*/
        'menu_position' => 20,
        'menu_icon' => 'dashicons-admin-post',
    );
    register_post_type( 't2s_stores', apply_filters( 't2s_stores_register_args', $args, 't2s_stores' ) );

    // Register new taxonomy
    $labels = array(
        'name' => 'Store Categories',
        'singular_name' => 'Store Category',
        'menu_name' => 'Store Categories',
    );
    $args = array(
        'label' => 'Store Categories',
        'labels' => apply_filters( 't2s_store_categories_labels', $labels ),
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'meta_box_cb' => null,
        'show_admin_column' => true,
        'update_count_callback' => '',
        'query_var' => 't2s_store_categories',
        'rewrite' => true,
        'sort' => '',
    );
    register_taxonomy( 't2s_store_categories', 't2s_stores', apply_filters( 't2s_store_categories_register_args', $args, 't2s_store_categories', 't2s_stores' ) );
}
add_action( 'init', 'asl_setup' );
// </step1>

// <step2>
function asl_deactivation() {
    unregister_post_type( 't2s_stores' );
    unregister_taxonomy( 't2s_store_categories' );
}
register_deactivation_hook( __FILE__, 'asl_deactivation' );
// </step2>

// <step3>
function t2s_admin_theme_style() {
    wp_enqueue_style('t2s-bootstrap', plugins_url('assets/css/bootstrap.min.css', __FILE__));
}
add_action('admin_enqueue_scripts', 't2s_admin_theme_style');
add_action('login_enqueue_scripts', 't2s_admin_theme_style');

add_action( 'add_meta_boxes', 'cd_meta_box_add' );
function cd_meta_box_add()
{
    add_meta_box( 'store_map_meta_box', 'STORE MAP', 'cd_meta_box_cb', 't2s_stores', 'normal', 'high' );
}

function cd_meta_box_cb()
{
    global $post;
    $values = get_post_custom( $post->ID );
    $address = isset( $values['store_map_meta_box_address'] ) ? esc_attr( $values['store_map_meta_box_address'][0] ) : '';
    $longitude = isset( $values['store_map_meta_box_longitude'] ) ? esc_attr( $values['store_map_meta_box_longitude'][0] ) : '';
    $latitude = isset( $values['store_map_meta_box_latitude'] ) ? esc_attr( $values['store_map_meta_box_latitude'][0] ) : '';
?>
    <div class="row mt-3">
        <div class="col-12 form-group">
            <label class="form-label" for="store_map_meta_box_address">Address</label>
            <input class="form-control" type="text" name="store_map_meta_box_address" id="store_map_meta_box_address" value="<?php echo $address; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="store_map_meta_box_latitude">Latitude</label>
            <input class="form-control" type="text" name="store_map_meta_box_latitude" id="store_map_meta_box_latitude" value="<?php echo $latitude; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="store_map_meta_box_longitude">Longitude</label>
            <input class="form-control" type="text" name="store_map_meta_box_longitude" id="store_map_meta_box_longitude" value="<?php echo $longitude; ?>" />
        </div>
    </div>
    <input
        id="pac-input"
        class="map-search-controls"
        type="text"
        placeholder="Search"
        style="
            margin: 10px 0;
            width: calc(100% - 256px);
            height: 40px;
            border: 0;
            background: none padding-box rgb(255, 255, 255);
            box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 4px -1px;
            border-radius: 2px;
        "
    />
    <div id="map" style="height: 500px;width: 100%;"></div>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('t2s_google_map_api'); ?>&callback=initAutocomplete&libraries=places&v=weekly" async defer></script>
    <script>
        function displayCoordinates(latLng, address) {
            document.getElementById("store_map_meta_box_latitude").value = latLng.lat().toFixed(6);
            document.getElementById("store_map_meta_box_longitude").value = latLng.lng().toFixed(6);
            document.getElementById("store_map_meta_box_address").value = address ? address : '';
        }

        function initAutocomplete() {
            const mapCenter = { lat: -33.8688, lng: 151.2195 };
            <?php if($latitude && $longitude){ ?>
                mapCenter.lat = <?php echo $latitude; ?>;
                mapCenter.lng = <?php echo $longitude; ?>;
            <?php } ?>
            const map = new google.maps.Map(document.getElementById("map"), {
                center: mapCenter,
                zoom: 13,
                mapTypeId: "roadmap",
            });
            const marker = new google.maps.Marker({
                position: mapCenter,
                map: map,
                draggable: true,
            });
            google.maps.event.addListener(map, "click", (event) => {
                const latLng = event.latLng;
                marker.setPosition(latLng);
                displayCoordinates(latLng);
            });
            google.maps.event.addListener(marker, "dragend", (event) => {
                const latLng = event.latLng;
                displayCoordinates(latLng);
            });

            // Create the search box and link it to the UI element.
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            // Bias the SearchBox results towards current map's viewport.
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });
            let markers = [];

            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                console.log(places)
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];

                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();

                marker.setPosition(places[0].geometry.location, places[0].formatted_address);
                displayCoordinates(places[0].geometry.location, places[0].formatted_address);

                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }

                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };

                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );
                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }
        window.initAutocomplete = initAutocomplete;
    </script>
<?php
}

add_action( 'save_post', 'cd_meta_box_save' );
function cd_meta_box_save( $post_id )
{
    // Bail if we're doing an auto save
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post', $post_id ) ) return;
    // now we can actually save the data
    if( isset( $_POST['store_map_meta_box_address'] ) )
        update_post_meta( $post_id, 'store_map_meta_box_address', esc_attr( $_POST['store_map_meta_box_address'] ) );

    if( isset( $_POST['store_map_meta_box_longitude'] ) )
        update_post_meta( $post_id, 'store_map_meta_box_longitude', esc_attr( $_POST['store_map_meta_box_longitude'] ) );

    if( isset( $_POST['store_map_meta_box_latitude'] ) )
        update_post_meta( $post_id, 'store_map_meta_box_latitude', esc_attr( $_POST['store_map_meta_box_latitude'] ) );
}
// </step3>

// <step4>
function register_store_map_shortcode() {
    require_once(ASL_PLUGIN_DIR . '/template/base.php');
    require_once(ASL_PLUGIN_DIR . '/template/default.php');
}
add_shortcode('t2s_store', 'register_store_map_shortcode');
// </step4>

// <step5>
/**
 * Store map
 *
 * @return void
 */
function get_stores()
{
    if (isset($_POST['action']) && $_POST['action'] == 'get_stores') {
        $storesSearchInput = $_POST['storesSearchInput'];
        $query_args = array(
            'post_type' => 't2s_stores',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'post_parent' => 0
        );
        $the_query = new WP_Query($query_args);
        $data1 = '';
        $data2 = '';
        if($the_query->have_posts()){
            while ($the_query->have_posts()) : $the_query->the_post();
                global $post;
                $address = get_post_meta($post->ID, 'store_map_meta_box_address') ? get_post_meta($post->ID, 'store_map_meta_box_address')[0] : '';
                $lng = get_post_meta($post->ID, 'store_map_meta_box_longitude') ? get_post_meta($post->ID, 'store_map_meta_box_longitude')[0] : '';
                $lat = get_post_meta($post->ID, 'store_map_meta_box_latitude') ? get_post_meta($post->ID, 'store_map_meta_box_latitude')[0] : '';
                $location  =  [
                    'title'   => get_the_title(),
                    'link'    => get_the_permalink(),
                    'address' => $address,
                    'lng'     => $lng,
                    'lat'     => $lat
                ];
                $data1 .= '<div class="stores-search-item">';
                $data1 .= '<div class="stores-search-left">';
                $data1 .= '<h4 class="stores-search-title"><a href="'.get_the_permalink().'">'.get_the_title().'</a></h4>';
                $data1 .= '<div class="stores-search-address" data-lat="'.$location['lat'].'" data-lng="'.$location['lng'].'">'.$location['address'].'</div>';
                $data1 .= '</div>';
                $data1 .= '<div class="stores-search-right">';
                $data1 .= '<a href="'.get_the_permalink().'">';
                $data1 .= '<img src="'.get_the_post_thumbnail_url().'">';
                $data1 .= '</a>';
                $data1 .= '</div>';
                $data1 .= '</div>';
                $data2 .= '<div class="marker" data-lat="'.esc_attr($location['lat']).'" data-lng="'.esc_attr($location['lng']).'">';
                $data2 .= '<h3><a class="marker-title-link" href="'.get_the_permalink().'">'.get_the_title().'</a></h3>';
                $data2 .= '<p><em>'.esc_html( $location['address'] ).'</em></p>';
                $data2 .= '</div>';
                //合成数组
                $result = array('top'=>$data1, 'bottom'=>$data2);
                //输出
            endwhile;
        }else{
            $result = array('top'=>'<p>No Result</p>', 'bottom'=>'');
        }
    }else{
        $result = array('top'=>'<p>No Result</p>', 'bottom'=>'');
    }
    $result = json_encode($result);
    echo $result;
    wp_reset_query();
    die();
}
add_action("wp_ajax_get_stores", "get_stores");
add_action("wp_ajax_nopriv_get_stores", "get_stores");
// </step5>

// Panel Admin
function t2s_store_locator_admin()
{
    global $wpdb, $my_plugin_hook;
?>
    <div class="wrap">
    </div>
<?php
}

// add_action('admin_menu', 'asl_add_menu');
function asl_add_menu()
{
    global $my_plugin_hook;
    $my_plugin_hook = add_options_page('T2s Store locator', 'T2s Store locator', 'manage_options', 't2s_store_locator', 't2s_store_locator_admin');
}

// Add options
function t2s_register_options() {
    register_setting( 'general', 't2s_google_map_api' );
    add_settings_field( 't2s_google_map_api', '<label for="t2s_google_map_api">Google map api</label>', 't2s_google_map_api_function', 'general' );
}

function t2s_google_map_api_function() {
    $value = get_option( 't2s_google_map_api', '' );
    echo '<input type="text" name="t2s_google_map_api" id="t2s_google_map_api" class="regular-text" value="'.$value.'" />';
}
add_filter( 'admin_init' , 't2s_register_options' );