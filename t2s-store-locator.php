<?php
/*
Plugin Name: T2S Store locator
Description: A store location plugin that queries the name of the store
Author: Theme 2 site
Author URI: http://theme2site.com/
Version: 1.0.0
*/
define('T2S_STORE_LOCATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('T2S_STORE_LOCATOR_PLUGIN_NAME', trim(dirname(T2S_STORE_LOCATOR_PLUGIN_BASENAME), '/'));
define('T2S_STORE_LOCATOR_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . T2S_STORE_LOCATOR_PLUGIN_NAME);
define('T2S_STORE_LOCATOR_PLUGIN_URL', WP_PLUGIN_URL . '/' . T2S_STORE_LOCATOR_PLUGIN_NAME);
define('T2S_STORE_LOCATOR_OPTIONS_PREFIX', 'T2SStoreLocator_');
define('T2S_STORE_LOCATOR_PLUGIN_VERSION', '1.0.0');

// <step1>
function T2SStoreLocator_setup()
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
add_action( 'init', 'T2SStoreLocator_setup' );
// </step1>

// <step2>
function T2SStoreLocator_deactivation() {
    unregister_post_type( 't2s_stores' );
    unregister_taxonomy( 't2s_store_categories' );
}
register_deactivation_hook( __FILE__, 'T2SStoreLocator_deactivation' );
// </step2>

// <step3>
function T2SStoreLocator_style() {
    wp_enqueue_style('t2s-bootstrap', plugins_url('assets/css/bootstrap.min.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'T2SStoreLocator_style');
add_action('login_enqueue_scripts', 'T2SStoreLocator_style');

add_action( 'add_meta_boxes', 'T2SStoreLocator_add_meta_box' );
function T2SStoreLocator_add_meta_box()
{
    add_meta_box( 'T2SStoreLocator_meta', 'STORE MAP', 'T2SStoreLocator_meta_box_cb', 't2s_stores', 'normal', 'high' );
}

function T2SStoreLocator_meta_box_cb()
{
    global $post;
    $values = get_post_custom( $post->ID );
    $address = isset( $values['T2SStoreLocator_meta_address'] ) ? esc_attr( $values['T2SStoreLocator_meta_address'][0] ) : '';
    $longitude = isset( $values['T2SStoreLocator_meta_longitude'] ) ? esc_attr( $values['T2SStoreLocator_meta_longitude'][0] ) : '';
    $latitude = isset( $values['T2SStoreLocator_meta_latitude'] ) ? esc_attr( $values['T2SStoreLocator_meta_latitude'][0] ) : '';
?>
    <div class="row mt-3">
        <div class="col-12 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_address">Address</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_address" id="T2SStoreLocator_meta_address" value="<?php echo $address; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_latitude">Latitude</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_latitude" id="T2SStoreLocator_meta_latitude" value="<?php echo $latitude; ?>" />
        </div>
        <div class="col-6 form-group">
            <label class="form-label" for="T2SStoreLocator_meta_longitude">Longitude</label>
            <input class="form-control" type="text" name="T2SStoreLocator_meta_longitude" id="T2SStoreLocator_meta_longitude" value="<?php echo $longitude; ?>" />
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
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('T2SStoreLocator_google_map_api'); ?>&callback=initAutocomplete&libraries=places&v=weekly" async defer></script>
    <script>
        function displayCoordinates(latLng, address) {
            document.getElementById("T2SStoreLocator_meta_latitude").value = latLng.lat().toFixed(6);
            document.getElementById("T2SStoreLocator_meta_longitude").value = latLng.lng().toFixed(6);
            document.getElementById("T2SStoreLocator_meta_address").value = address ? address : '';
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

add_action( 'save_post', 'T2SStoreLocator_meta_box_save' );
function T2SStoreLocator_meta_box_save( $post_id )
{
    // Bail if we're doing an auto save
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post', $post_id ) ) return;
    // now we can actually save the data
    if( isset( $_POST['T2SStoreLocator_meta_address'] ) )
        update_post_meta( $post_id, 'T2SStoreLocator_meta_address', esc_attr( $_POST['T2SStoreLocator_meta_address'] ) );

    if( isset( $_POST['T2SStoreLocator_meta_longitude'] ) )
        update_post_meta( $post_id, 'T2SStoreLocator_meta_longitude', esc_attr( $_POST['T2SStoreLocator_meta_longitude'] ) );

    if( isset( $_POST['T2SStoreLocator_meta_latitude'] ) )
        update_post_meta( $post_id, 'T2SStoreLocator_meta_latitude', esc_attr( $_POST['T2SStoreLocator_meta_latitude'] ) );
}
// </step3>

// <step4>
function T2SStoreLocator_register_shortcode() {
    require_once(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/base.php');
    require_once(T2S_STORE_LOCATOR_PLUGIN_DIR . '/template/default.php');
}
add_shortcode('T2S_STORE', 'T2SStoreLocator_register_shortcode');
// </step4>

// <step5>
/**
 * Store map
 *
 * @return void
 */
function T2SStoreLocator_get_stores()
{
    if (isset($_POST['action']) && $_POST['action'] == 'T2SStoreLocator_get_stores') {
        $storesSearchInput = $_POST['storesSearchInput'];
        $query_args = array(
            'post_type' => 't2s_stores',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'post_parent' => 0,
            's' => $storesSearchInput
        );
        $the_query = new WP_Query($query_args);
        $data1 = '';
        $data2 = '';
        if($the_query->have_posts()){
            while ($the_query->have_posts()) : $the_query->the_post();
                global $post;
                $address = get_post_meta($post->ID, 'T2SStoreLocator_meta_address') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_address')[0] : '';
                $lng = get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_longitude')[0] : '';
                $lat = get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude') ? get_post_meta($post->ID, 'T2SStoreLocator_meta_latitude')[0] : '';
                $location  =  [
                    'title'   => get_the_title(),
                    'link'    => get_the_permalink(),
                    'address' => $address,
                    'lng'     => $lng,
                    'lat'     => $lat
                ];
                $data1 .= '<div class="t2s-stores-search-item">';
                $data1 .= '<div class="t2s-stores-search-left">';
                $data1 .= '<h4 class="t2s-stores-search-title"><a href="'.get_the_permalink().'">'.get_the_title().'</a></h4>';
                $data1 .= '<div class="t2s-stores-search-address" data-lat="'.$location['lat'].'" data-lng="'.$location['lng'].'">'.$location['address'].'</div>';
                $data1 .= '</div>';
                $data1 .= '<a class="t2s-stores-search-right" href="'.get_the_permalink().'" style="background-image: url('.get_the_post_thumbnail_url().');"></a>';
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
add_action("wp_ajax_T2SStoreLocator_get_stores", "T2SStoreLocator_get_stores");
add_action("wp_ajax_nopriv_T2SStoreLocator_get_stores", "T2SStoreLocator_get_stores");
// </step5>

// Panel Admin
function T2SStoreLocator_setting_form()
{
?>
    <div class="wrap">
    <h1>Setting</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'T2SStoreLocator_options' ); ?>
        <?php do_settings_sections( 'T2SStoreLocator_options' ); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="blogname">Google map api</label>
                    </th>
                    <td>
                        <input class="regular-text" type="text" name="T2SStoreLocator_google_map_api" id="T2SStoreLocator_google_map_api" value="<?php echo esc_attr( get_option('T2SStoreLocator_google_map_api') ); ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
    </div>
<?php
}

add_action('admin_menu', 'T2SStoreLocator_add_menu');
function T2SStoreLocator_add_menu()
{
    global $my_plugin_hook;
    $my_plugin_hook = add_options_page('T2S Store locator', 'T2S Store locator', 'manage_options', 'T2SStoreLocator_setting', 'T2SStoreLocator_setting_form');
}

// Add options
function T2SStoreLocator_add_options() {
    register_setting( 'T2SStoreLocator_options', 'T2SStoreLocator_google_map_api' );
}
add_filter( 'admin_init' , 'T2SStoreLocator_add_options' );