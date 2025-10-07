<?php

/**
 * @package Ni
 */
        
function addResource($cmsid, $ResourceName, $Contact, $ResourceType, $Url) {
    // Validate inputs
    $cmsid = htmlspecialchars($cmsid);
    $ResourceName = htmlspecialchars($ResourceName);
    $Contact = htmlspecialchars($Contact);
    $ResourceType = htmlspecialchars($ResourceType);
    $Url = filter_var($Url, FILTER_VALIDATE_URL);

    if (!$Url) {
        echo '<p class="redmsg"><i class="fa fa-thumbs-down"></i>&nbsp;Invalid URL provided</p>';
        return;
    }

    $curl = curl_init();

    $payload = json_encode([
        "EntityName" => "API_RESOURCES_NEW",
        "Type" => "Add",
        "Url" => $Url,
        "CmsId" => $cmsid,
        "ResourceName" => $ResourceName,
        "ContactRid" => $Contact,
        "ResourceType" => $ResourceType
    ]);

    curl_setopt_array($curl, [
        CURLOPT_URL => apiurl . 'api/oomi/InsertAPI',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'AccessID: ' . accessid,
            'Signature: ' . encodedSignature,
            'CurrentDateTime: ' . datex,
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    
    // Error handling for cURL errors
    if (curl_errno($curl)) {
        echo '<p class="redmsg"><i class="fa fa-thumbs-down"></i>&nbsp;Curl Error: ' . curl_error($curl) . '</p>';
        curl_close($curl);
        return;
    }

    $response2 = json_decode($response, true);
    curl_close($curl);

    if (isset($response2['Error']['ErrorCode']) && $response2['Error']['ErrorCode'] == '200') {
        echo '<p class="msg"><i class="fa fa-thumbs-up"></i>&nbsp;Added to your resources successfully</p>';
    } else {
        echo '<p class="redmsg"><i class="fa fa-thumbs-down"></i>&nbsp;Something went wrong with the API call</p>';
    }
}

function generateForm($cmsid, $resourceName, $contact, $resourceType, $url) {
    return '<form class="elementor-form" action="' . $url . '" method="post" name="addresource">
        <input type="hidden" name="cmsid" value="' . $cmsid . '">
        <input type="hidden" name="ResourceName" value="' . $resourceName . '">
        <input type="hidden" name="Contact" value="' . $contact . '">
        <input type="hidden" name="ResourceType" value="' . $resourceType . '">
        <input type="hidden" name="Url" value="' . $url . '"> 
        <div class="buttonform1"><button type="submit" class="btnworkout1"><i class="fas fa-box"></i>&nbsp;&nbsp;Save resource</button></div>
    </form>';
}

function addResourcess() {
    if (isset($_POST['cmsid']) && isset($_SESSION['username'])) {
        // Handle POST request
        if ($_POST['ResourceType'] === 'post') {
            $category_details = get_the_category(intval($_POST['cmsid']));
            foreach ($category_details as $category) {
                if ($category->slug === 'newsupdates') {
                    $_POST['ResourceType'] = 'News';
                    break;
                }
            }
        }
        addResource($_POST['cmsid'], $_POST['ResourceName'], $_POST['Contact'], $_POST['ResourceType'], $_POST['Url']);
    } else {
        // Handle GET request (display form)
        $post_type = get_post_type();
        $backlink = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        if (in_array($post_type, ['podcasts', 'resources', 'jobs', 'publication', 'policies_guidance', 'post'])) {
            if (isset($_SESSION['username'])) {
                return generateForm(get_the_ID(), get_the_title(), decryptssorid($_COOKIE['liGA']), $post_type, get_the_permalink());
            } else {
                return '<a href="'.ssourl.'/cms-sso/?ReturnUrl=' . $backlink . '" class="btnworkout1"><i class="fas fa-key"></i>&nbsp;&nbsp;Login / Join us</a>';
            }
        }
    }
}
//add_shortcode('addResourcesc', 'addResourcess');

function usernameget(){ 
  session_start();
  if (isset($_SESSION['name'])) { return $_SESSION['name']; } 
  if(is_user_logged_in()) { $current_user = wp_get_current_user();
        return esc_html( $current_user->display_name );}
} add_shortcode('usernamegetsc', 'usernameget');

// Function to check if user has access based on ACF field visibility and memstatus session value
function redirect_if_member_only_content() {
    $post_id = get_the_ID();
    $memberchecks = get_field("visibility");
    session_start();
    $user_level = isset($_SESSION['memlevel']) ? $_SESSION['memlevel'] : '';
    $user_logged_in = isset($_SESSION['name']);

    if (!empty($memberchecks)) {
        $requires_login = false;
        $allowed_levels = array();

        foreach ($memberchecks as $membercheck) {
            if ($membercheck == 'Public') {
                // If 'Public' is one of the options, everyone is allowed
                return;
            }
            if (in_array($membercheck, ['Full Member', 'Associate Member', 'Non Member'])) {
                $requires_login = true;
                $allowed_levels[] = $membercheck;
            }
        }

        // Check if login is required
        if ($requires_login && !$user_logged_in) {
            $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            echo "<script type='text/javascript'>window.location.href='" . home_url('/cms-sso?RedirectUrl=') . $url . "';</script>";
            exit;
        }

        // Check if user has the required membership level
        if (!in_array($user_level, $allowed_levels)) {
            $errorm = "Sorry, this resource is for " . implode(' or ', $allowed_levels) . " only.";
            wp_redirect(home_url('/restricted/?message=' . urlencode($errorm)));
            exit;
        }
    }
}
add_shortcode('redirect_if_member_content', 'redirect_if_member_only_content');

function getssotoken(){
    session_start();
    if(isset($_GET['RedirectUrl'])){ $_SESSION['url'] =  $_GET['RedirectUrl']; }
    require "OpenIDConnectClient.php5";
    //echo ssourl.' '.clientId.' '.clientSecret;
    $oidc = new OpenIDConnectClient(ssourl,clientId,clientSecret);
    //print_r($oidc);
    $oidc->addScope("openid, profile");
    $oidc->authenticate();
    $token = $oidc->token;
    $decoded_token = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));
       $_SESSION['rid'] = $decoded_token->sub;
       $_SESSION['name'] = $decoded_token->name;
       $_SESSION['memstatus'] = $decoded_token->membership_status;
       $_SESSION['memlevel'] = $decoded_token->membership_level;
       $_SESSION['email'] = $decoded_token->email;
       $_SESSION['username'] = $decoded_token->username;
    echo "<script type='text/javascript'>window.location.href='" . $_SESSION['url'] . "';</script>";
} 
add_shortcode('ssosc', 'getssotoken');

function dashboard(){
    session_start(); 
    if(isset($_SESSION['name'])){
      if((($_SESSION['memstatus']) == "Current") || (($_SESSION['memstatus']) == "Active")){
        if((($_SESSION['memlevel']) == "Full Member")){
            return do_shortcode('[elementor-template id="8903"]');
        }
        if((($_SESSION['memlevel']) == "Associate Member")){
            //return do_shortcode('[elementor-template id="8900"]');
            return do_shortcode('[elementor-template id="8903"]');
        }
        if((($_SESSION['memlevel']) == "")){
            return do_shortcode('[elementor-template id="8888"]');
        }
      } else {
        return do_shortcode('[elementor-template id="8888"]');
      }
    }
    if(is_user_logged_in()){
        return do_shortcode('[elementor-template id="8903"]');
    }
    else{ 
        $url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        echo "<script type='text/javascript'>window.location.href='" . home_url('/cms-sso?RedirectUrl=').$url . "';</script>";
    }
} add_shortcode('dashboardsc','dashboard');

function homeslider(){
  $args = array('post_type' => 'nislides', 'post_status' => 'publish',);
  $my_query = null;
  $my_query = new WP_query($args);
  $html2 =  '<!-- Swiper --><div class="swiper mySwiper"><div class="swiper-wrapper">';
  if ($my_query->have_posts()) :
    while ($my_query->have_posts()) : $my_query->the_post();
      $custom = get_post_custom(get_the_ID());
      if (has_post_thumbnail()) {
        if (isMobileDevice()) { $thumb_img[0] = get_field('mobile_banner'); } 
        else { $thumb_img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full', true); }
      }
      $link = get_the_permalink();
      $url = get_field("button_link");
      $html2 .=  '<div class="swiper-slide" ><div class="container"><div class="row slider-row"><div class="col-md-7 sliderdetails"><h2 class="card-text mb-auto slidercontent">
      ' . get_the_excerpt() . '</h2><p><a href="' . $url . '#" class="sliderlink">' . get_field("button_title") . '</a></p></div><div class="col-md-5 sliderimage">
      <img class="img-responsive" src="' . $thumb_img[0] . '" alt="' . get_the_title() . '"></div></div></div></div>';
      $index2++;
    endwhile;
    wp_reset_postdata();
  else :
    _e('Sorry, no posts matched your criteria.');
  endif;
  $html3 = '</div><div class="swiper-button-next"></div><div class="swiper-button-prev"></div><div class="swiper-pagination"></div></div>';
  return $html2 . $html3;
} add_shortcode('homeslides', 'homeslider');


function get_sibling_pages_with_active_shortcode() {
    global $post;

    if ( ! is_page() ) return '';

    $current_page_id = $post->ID;
    $parent_id = $post->post_parent ? $post->post_parent : $post->ID;

    // Try to get child pages first
    $child_pages = get_pages( array(
        'child_of'    => $current_page_id,
        'parent'      => $current_page_id,
        'sort_column' => 'menu_order',
        'sort_order'  => 'ASC'
    ) );

    // If no children, get siblings (pages with same parent)
    if ( empty( $child_pages ) ) {
        $child_pages = get_pages( array(
            'child_of'    => $parent_id,
            'parent'      => $parent_id,
            'sort_column' => 'menu_order',
            'sort_order'  => 'ASC'
        ) );
    }

    if ( empty( $child_pages ) ) return '';

    // Start output
    $output = '<h3 style="font-size: 24px; color: #1E2850; margin-bottom: 8px; text-align: right;">In this section</h3>';
    $output .= '<div style="height: 2px; background-color: #E5E6EB; margin-top: 20px; margin-bottom: 20px;"></div>';
    $output .= '<ul class="sibling-pages" style="list-style: none; padding: 0;">';

    foreach ( $child_pages as $page ) {
        $is_active = ( $page->ID == $current_page_id ) ? 'active-sibling' : '';
        $underline_style = ( $page->ID == $current_page_id ) ? '#E64E00' : 'transparent';

        $output .= '<li class="fv-sib-list ' . esc_attr($is_active) . '" style="margin-bottom: 18px; text-align: right !important; list-style:none;font-family: Myriad Pro, sans-serif;">';
        $output .= '<a href="' . get_permalink( $page->ID ) . '" style="color: #1E2850; text-align: right; font-size: 20px; font-weight: 400; border-bottom: 2px solid ' . $underline_style . ';">';
        $output .= esc_html( $page->post_title );
        $output .= '</a></li>';
    }

    $output .= '</ul>';
    $output .= '<div style="height: 2px; background-color: #E5E6EB; margin-bottom: 20px;"></div>';

    return $output;
}
add_shortcode( 'sibling_pages', 'get_sibling_pages_with_active_shortcode' );


function custom_add_to_cart_button() {
    global $post;
    if ( ! $post ) return '';

    $postname = $post->post_name;
    $cart_url = portalurl."product/Addtocart?productId=" . $postname;
    
    if ( get_field('purchase_via_amazon_link') ) {  $amazonlink = get_field('purchase_via_amazon_link');
        echo '<div class="elementor-element elementor-element-15d1f100 elementor-widget elementor-widget-button" data-id="151f100" data-element_type="widget" data-widget_type="button.default">
		<div class="elementor-widget-container">
			<div class="elementor-button-wrapper"><a class="elementor-button elementor-button-link elementor-size-sm" href="'.$amazonlink.'">
				<span class="elementor-button-content-wrapper"><span class="elementor-button-text">Buy via Amazon</span></span>
			</a></div>
		</div></div>';
    } else {
    
    echo '<div class="elementor-element elementor-element-feec051 elementor-align-left elementor-widget__width-auto elementor-widget elementor-widget-button" data-id="feec051" data-element_type="widget" data-widget_type="button.default">
        <div class="elementor-widget-container">
            <div class="elementor-button-wrapper">
                <a href="'.esc_url($cart_url).'" class="elementor-button elementor-size-sm" role="button">
                    <span class="elementor-button-content-wrapper"> 
                        <span class="elementor-button-text">Add to cart</span>
                    </span>
                </a>
            </div>
        </div>
    </div>';
    } 
}
//add_shortcode('addtocart_btn', 'custom_add_to_cart_button');

// === Product Gallery Shortcode (Thumbnails Right, Scrollable) ===
// Usage: [product_gallery]

function my_product_gallery_shortcode($atts) {
    global $post;

    if (!$post) return '';

    $atts = shortcode_atts([
        'id' => $post->ID,
    ], $atts, 'product_gallery');

    $post_id = intval($atts['id']);

    // Featured Image
    $featured_img = get_the_post_thumbnail_url($post_id, 'large');

    // ACF Images
    $acf_images = [];
    for ($i = 1; $i <= 5; $i++) {
        $img_url = get_field("PRODUCT_Image$i", $post_id);
        if ($img_url) {
            $acf_images[] = $img_url;
        }
    }

    if (!$featured_img && empty($acf_images)) {
        return '';
    }

    ob_start();
    ?>
    <div class="product-gallery">
      <!-- Main Image Left -->
      <div class="main-image">
        <img id="currentImage-<?php echo esc_attr($post_id); ?>" src="<?php echo esc_url($featured_img); ?>" alt="Product Image">
      </div>

      <!-- Thumbnails Right -->
      <div class="thumbnail-col">
        <img src="<?php echo esc_url($featured_img); ?>" class="thumb active" onclick="changeImage<?php echo $post_id; ?>(this)" />
        <?php foreach ($acf_images as $img): ?>
          <img src="<?php echo esc_url($img); ?>" class="thumb" onclick="changeImage<?php echo $post_id; ?>(this)" />
        <?php endforeach; ?>
      </div>
    </div>

    <script>
    function changeImage<?php echo $post_id; ?>(el) {
      document.getElementById("currentImage-<?php echo $post_id; ?>").src = el.src;
      document.querySelectorAll(".product-gallery .thumb").forEach(img => img.classList.remove("active"));
      el.classList.add("active");
    }

    document.addEventListener("DOMContentLoaded", function() {
      const mainImg = document.getElementById("currentImage-<?php echo $post_id; ?>");
      mainImg.addEventListener("mousemove", function(e) {
        const { left, top, width, height } = mainImg.getBoundingClientRect();
        const x = ((e.pageX - left) / width) * 100;
        const y = ((e.pageY - top) / height) * 100;
        mainImg.style.transformOrigin = `${x}% ${y}%`;
        mainImg.style.transform = "scale(2)";
      });
      mainImg.addEventListener("mouseleave", function() {
        mainImg.style.transform = "scale(1)";
      });
    });
    </script>

    <style>
    .product-gallery {
      display: flex;
      align-items: flex-start;
      gap: 15px;
      max-width: 700px;
      margin: 0 auto;
    }
    .thumbnail-col {
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-height: 565px; /* 3 thumbnails (70px + 10px gap) */
      overflow-y: auto;
      padding-right: 5px;
    }
    .thumbnail-col::-webkit-scrollbar {
      width: 6px;
    }
    .thumbnail-col::-webkit-scrollbar-thumb {
      background: #aaa;
      border-radius: 3px;
    }
    .thumbnail-col img {
          width: 102px;
    height: 134px;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid rgba(229, 230, 235, 1);
    transition: 0.2s;
    padding: 8px;
    border-radius: 5px;
    }
    .thumbnail-col img.active,
    .thumbnail-col img:hover {
      border: 2px solid #333;
    }
    .main-image {
      flex: 1;
    border: 1px solid rgba(229, 230, 235, 1);
    overflow: hidden;
    border-radius: 12px;
    padding: 20px;
    }
    .main-image img {
      width: 100%;
      transition: transform 0.2s ease;
      cursor: zoom-in;
    }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('product_gallery', 'my_product_gallery_shortcode');

// === Product Stock Shortcode ===
// Usage: [product_stock] or [product_stock id="123"]

function my_product_stock_shortcode($atts) {
    global $post;

    if (!$post) return '';

    $atts = shortcode_atts([
        'id' => $post->ID,
    ], $atts, 'product_stock');

    $post_id = intval($atts['id']);

    // Get ACF field value
    $qty = get_field('PRODUCT_QuantityInHand', $post_id);

    // Check stock
    if ($qty && intval($qty) >= 1) {
        return '<p class="x">Status: <span class="in-stock">In Stock</span></p>';
    } else {
        return '<p class="x">Status: <span class="out-of-stock">Out of Stock</span></p>';
    }
}
add_shortcode('product_stock', 'my_product_stock_shortcode');

// Booking Card Shortcode
function course_booking_card_shortcode() {
    ob_start();
    ?>
    <div style="width:450px; background:#EDF1F6; border-radius:12px; padding:25px; font-family: Myriad Pro;">
    <?php
       // Example SCF fields
    $start_date_raw = get_field('COURSE_SETUP_StartDate'); // e.g. "2025-02-26 00:00:00.000"
    $end_date_raw   = get_field('COURSE_SETUP_EndDate');   // e.g. "2025-02-28 00:00:00.000"
    $max_seats = get_field('COURSE_SETUP_MaximumSeats'); 
    $ava_seats   = get_field('COURSE_SETUP_SeatsAvailable');  
    $booked_seats   = get_field('COURSE_SETUP_TotalBooked');   
    $training_loc   = get_field('COURSE_SETUP_TrainingLocation');   
    $course_rate_json = get_field('COURSE_RATE');
    
    // Convert to DateTime (ignore microseconds if present)
    $start = DateTime::createFromFormat('Y-m-d H:i:s.u', $start_date_raw);
    if (!$start) {
        $start = DateTime::createFromFormat('Y-m-d H:i:s', $start_date_raw);
    }

    $end = DateTime::createFromFormat('Y-m-d H:i:s.u', $end_date_raw);
    if (!$end) {
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $end_date_raw);
    }

    // Format: 26-28 February 2025
    if ($start && $end) {
    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
        // Same exact date
        $formatted_date = $start->format('j M Y');
    } elseif ($start->format('M Y') === $end->format('M Y')) {
        // Same month + year
        $formatted_date = $start->format('j') . ' - ' . $end->format('j M Y');
    } elseif ($start->format('Y') === $end->format('Y')) {
        // Same year, different month
        $formatted_date = $start->format('j M') . ' - ' . $end->format('j M Y');
    } else {
        // Different years
        $formatted_date = $start->format('j M Y') . ' - ' . $end->format('j M Y');
    }
} else {
    $formatted_date = $start_date_raw . ' - ' . $end_date_raw; // fallback
}

    ?>

    <!-- Event Card -->
    <div style="display:flex; align-items:flex-start; margin-bottom:20px;">
        <div style="flex:0 0 32px; display:flex; justify-content:center; align-items:flex-start;">
            <!-- Calendar Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
<path d="M34.7027 11.2901C34.6844 9.86248 34.8129 7.22081 34.2868 5.97908C33.5637 4.2721 31.9881 3.05009 30.1616 2.73837C29.9909 2.70926 29.6073 2.63555 29.4601 2.63978C29.4024 2.64166 29.3878 2.65715 29.3546 2.70081V5.84622C29.3546 6.07907 28.9086 6.86401 28.744 7.06588C27.7302 8.30621 25.6889 8.28367 24.6667 7.06588C24.0092 6.28235 24.0369 5.60257 24.0069 4.62796C23.9862 3.96367 24.0261 3.29468 24.0087 2.63086H20.0228C20.0036 3.32614 20.0482 4.02658 20.0247 4.72185C19.9924 5.68989 20.0135 6.41427 19.3176 7.16165C17.817 8.77285 14.9031 7.89167 14.7244 5.65843L14.677 2.63086H10.6911L10.6461 5.66078C10.4323 7.6161 8.28599 8.58648 6.59738 7.59779C4.7573 6.52084 5.55588 4.4505 5.29844 2.74823C5.29141 2.70034 5.2839 2.65856 5.23045 2.6426C4.90782 2.70175 4.58051 2.72053 4.25835 2.78625C2.78779 3.08577 1.54794 4.09371 0.765763 5.35704C0.626959 5.57675 0.507382 5.79786 0.406093 6.02414C0.379364 6.08189 0.353104 6.1401 0.328251 6.19878V6.20911C0.110667 6.75792 0 7.34334 0 8.02171V30.656C0 31.3775 0.0689327 31.8573 0.328251 32.4883C0.92473 33.9216 2.07267 35.176 3.55121 35.7168C4.31931 35.9975 4.94674 35.9435 5.74205 36.0116L21.3827 36.0562L20.3206 33.3784C17.0559 33.3634 13.7898 33.4047 10.5251 33.3826C8.56454 33.3695 6.58612 33.2892 4.61755 33.3347C3.75285 33.0371 3.00819 32.3052 2.71839 31.4334L2.67243 13.3346H32.0275V20.0245L34.6999 20.7522C34.6685 17.5997 34.7426 14.443 34.7022 11.2906L34.7027 11.2901Z" fill="#003E7E"/>
<path d="M16.3092 26.7283C16.8925 26.6931 17.5898 26.6856 18.1732 26.7124C18.8494 26.7434 19.6649 26.7898 19.8974 27.5682C20.033 28.0208 20.0292 29.4752 19.8721 29.9198C19.7277 30.3291 19.248 30.616 18.8273 30.6578C18.0996 30.7296 16.7542 30.7122 16.0119 30.6601C15.1331 30.5986 14.8246 30.1498 14.7251 29.3193C14.6506 28.6954 14.6257 27.733 15.0285 27.2302C15.4117 26.7527 15.7348 26.7631 16.3092 26.7283Z" fill="#003E7E"/>
<path d="M8.24312 26.7278C9.0328 26.6767 10.1151 26.6739 10.8973 26.7663C11.3301 26.8175 11.7343 27.11 11.8731 27.5264C12.0119 27.9428 12.0152 29.4991 11.8736 29.8925C11.7193 30.3221 11.2522 30.6131 10.8082 30.6568C10.0855 30.7281 8.67922 30.7169 7.94629 30.6591C7.11675 30.5939 6.76599 30.0582 6.70597 29.2719C6.65673 28.6306 6.61828 27.7203 7.03469 27.2081C7.39764 26.7612 7.70479 26.7626 8.24312 26.7278Z" fill="#003E7E"/>
<path d="M16.59 21.3755C17.2611 21.3469 18.2078 21.3436 18.8681 21.4145C20.1464 21.552 20.0498 23.0421 19.9752 24.0134C19.9204 24.7289 19.5771 25.2336 18.8264 25.3045C18.0808 25.3744 16.6205 25.3711 15.8712 25.3068C15.3108 25.2589 14.9258 24.9068 14.7936 24.3669C14.6712 23.8656 14.6383 22.6215 14.8409 22.1595C15.1842 21.3779 15.8651 21.406 16.59 21.3755Z" fill="#003E7E"/>
<path d="M8.61776 21.3757C9.25879 21.3494 10.3186 21.332 10.9366 21.4212C11.5134 21.5043 11.9054 21.9977 11.9561 22.5602C11.9969 23.008 12.0123 24.3188 11.8257 24.6817C11.6513 25.0201 11.1753 25.2704 10.8072 25.3051C10.0743 25.3741 8.63511 25.3713 7.89889 25.3075C7.34884 25.26 6.92539 24.9413 6.78424 24.4047C6.66889 23.9653 6.64075 22.685 6.78846 22.2682C7.09983 21.3903 7.84074 21.4081 8.61729 21.3762L8.61776 21.3757Z" fill="#003E7E"/>
<path d="M23.7633 16.0703C24.3926 15.9778 26.191 15.9923 26.8456 16.0585C28.1431 16.1895 28.0653 17.6279 27.9968 18.6185C27.9448 19.3758 27.6039 19.8931 26.8006 19.956C26.273 19.9973 25.6817 19.9574 25.1593 19.9523C24.0775 19.9415 22.9727 20.1954 22.7481 18.7983C22.6065 17.9171 22.5962 16.2416 23.7628 16.0698L23.7633 16.0703Z" fill="#003E7E"/>
<path d="M15.7445 16.0682C16.3246 15.9701 18.1534 16.0016 18.7808 16.0579C19.5855 16.1302 19.9264 16.6278 19.9794 17.3931C20.0474 18.3761 20.1355 19.8432 18.83 19.9554C18.2772 20.0028 17.6441 19.9577 17.0955 19.9516C16.3124 19.9432 15.209 20.1554 14.8573 19.2348C14.6866 18.7874 14.6552 17.6048 14.7363 17.1245C14.8118 16.6767 15.2906 16.1452 15.7445 16.0682Z" fill="#003E7E"/>
<path d="M26.484 0.01408C26.9904 -0.0577479 27.4068 0.149286 27.7135 0.542696C28.0343 0.953947 28.0225 1.39055 28.0446 1.90132C28.0877 2.88861 28.0657 4.2524 27.9977 5.23827C27.9297 6.22415 27.1119 6.92271 26.1351 6.48893C25.2432 6.09317 25.4528 5.19696 25.4186 4.38948C25.3764 3.39704 25.2634 1.9985 25.4378 1.03235C25.5227 0.562883 26.0113 0.0812134 26.4835 0.01408H26.484Z" fill="#003E7E"/>
<path d="M17.1514 0.013726C17.8834 -0.0904949 18.599 0.511827 18.6651 1.24325C18.7584 2.27091 18.7204 3.95112 18.6651 5.00272C18.6294 5.6764 18.5671 6.1806 17.8899 6.48012C17.0205 6.86461 16.1783 6.35806 16.0902 5.41725C15.9851 4.2943 15.9715 2.75117 16.0353 1.61882C16.0761 0.895849 16.3251 0.131092 17.1514 0.013726Z" fill="#003E7E"/>
<path d="M32.0776 29.3403C32.8307 29.4338 33.5941 29.3488 34.3491 29.3901C34.9699 29.4239 35.5012 29.4666 35.8365 30.0605C36.2135 30.7285 35.8679 31.7905 35.0792 31.951C34.1915 32.1318 31.3475 32.098 30.4148 31.9679C29.8563 31.89 29.5369 31.4313 29.4549 30.9102C29.3048 29.9553 29.3146 27.6803 29.4033 26.6912C29.4849 25.7799 30.2595 25.1654 31.1636 25.4903C31.9299 25.7658 31.9913 26.3996 32.0279 27.1132C32.0433 27.4174 31.9838 29.2483 32.0776 29.3403Z" fill="#003E7E"/>
<path d="M30.7143 40C25.5945 40 21.4294 35.8302 21.4294 30.7046C21.4294 25.579 25.5945 21.4092 30.7143 21.4092C35.834 21.4092 39.9991 25.579 39.9991 30.7046C39.9991 35.8302 35.834 40 30.7143 40ZM30.7143 24.226C27.1462 24.226 24.243 27.1324 24.243 30.7046C24.243 34.2767 27.1462 37.1832 30.7143 37.1832C34.2823 37.1832 37.1855 34.2767 37.1855 30.7046C37.1855 27.1324 34.2823 24.226 30.7143 24.226Z" fill="#003E7E"/>
<path d="M7.72593 16.0682C8.306 15.9701 10.1348 16.0016 10.7623 16.0579C11.5669 16.1302 11.9078 16.6278 11.9608 17.3931C12.0288 18.3761 12.117 19.8432 10.8115 19.9554C10.2586 20.0028 9.62556 19.9577 9.07692 19.9516C8.2938 19.9432 7.19041 20.1554 6.83871 19.2348C6.66802 18.7874 6.6366 17.6048 6.71773 17.1245C6.79323 16.6767 7.272 16.1452 7.72593 16.0682Z" fill="#003E7E"/>
<path d="M7.82007 0.013726C8.55207 -0.0904949 9.26766 0.511827 9.33378 1.24325C9.42709 2.27091 9.38911 3.95112 9.33378 5.00272C9.29814 5.6764 9.23577 6.1806 8.55864 6.48012C7.68924 6.86461 6.84704 6.35806 6.75888 5.41725C6.65384 4.2943 6.64024 2.75117 6.70402 1.61882C6.74482 0.895849 6.99382 0.131092 7.82007 0.013726Z" fill="#003E7E"/>
</svg>
        </div>
        <div style="flex:1; margin-left:12px;">
            <div style="font-weight: 600;color: #003E7E;font-size: 24px;"><?php echo esc_html($formatted_date); ?></div>
            <div style="color: #1E2850; font-family: Myriad Pro; font-size: 20px; font-style: normal; font-weight: 400; line-height: 28px;"><?php if(get_field('COURSE_SETUP_StartTime')) { echo get_field('COURSE_SETUP_StartTime') . ' - ' . get_field('COURSE_SETUP_EndTime'). ' UTC'; }?></div>
            <a style="margin-top: 20px;" class="elementor-button elementor-button-link elementor-size-sm" href="#">Book now</a>
        </div>
    </div>

    <hr style="border:none; border-top:3px solid #E5E6EB; margin:20px 0;">

        <!-- Price -->
        <?php if ($course_rate_json) {
    $rates = json_decode($course_rate_json, true); // decode JSON into array
    //echo "<pre>";print_r ($rates);
    if (is_array($rates) && count($rates) > 0) {
        ?>
        <div style="display:flex; align-items:flex-start; margin-bottom:20px;">
            <div style="flex:0 0 32px; display:flex; justify-content:center; align-items:flex-start;">
                <!-- Price Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <path d="M21.4916 0.306769C21.1493 0.311215 20.8158 0.453485 20.5757 0.69801L0.640213 20.6469C-0.213404 21.5005 -0.213404 22.9232 0.640213 23.7724L16.2143 39.3598C17.0635 40.2134 18.5039 40.2134 19.3531 39.3598L39.2887 19.4065C39.5332 19.1664 39.6799 18.8329 39.6932 18.4906L40 3.84573C40 3.84573 40 3.82794 40 3.81905C40 2.91653 39.8399 1.85395 38.9997 1.01367C38.1549 0.173391 37.0924 0 36.1632 0C31.2726 0.102256 26.3821 0.204513 21.4916 0.306769ZM36.2076 2.66756C36.6344 2.67645 37.0612 2.85428 37.1101 2.90319C37.1591 2.9521 37.3236 3.34334 37.3324 3.79238C37.3324 3.80571 37.3324 3.80571 37.3324 3.81905L37.039 17.8949L17.7837 37.1502L2.84984 22.2163L22.0918 2.96099C26.7956 2.86762 31.5083 2.76092 36.2076 2.66756ZM31.9973 4.45926C31.0859 4.45926 30.1701 4.79715 29.4809 5.48627C28.1027 6.86451 28.1027 9.13638 29.4809 10.5146C30.8592 11.8929 33.131 11.8929 34.5093 10.5146C35.8875 9.13638 35.8875 6.86451 34.5093 5.48627C33.8202 4.79715 32.9087 4.45926 31.9929 4.45926H31.9973ZM31.9973 7.10014C32.2241 7.10014 32.4419 7.19795 32.6242 7.37579C32.9843 7.73591 32.9843 8.26942 32.6242 8.6251C32.2641 8.98077 31.7306 8.98522 31.3749 8.6251C31.0192 8.26498 31.0148 7.73147 31.3749 7.37579C31.5527 7.19795 31.775 7.10014 32.0018 7.10014H31.9973ZM19.1308 11.5727C18.9708 11.5727 18.8063 11.5816 18.6462 11.5994C16.0765 11.8795 13.5601 14.1069 13.7557 17.4191C13.7735 17.7126 13.8046 17.966 13.8268 18.2372C13.6179 18.2372 13.3867 18.2372 13.1866 18.2372C12.4886 18.2728 11.884 18.9441 11.924 19.6421C11.964 20.3401 12.6309 20.9448 13.3289 20.9047H14.0802C14.1292 21.3982 14.1647 21.8562 14.1647 22.2519C14.1603 23.3989 13.9735 24.1992 13.1066 25.4885C12.8354 25.8886 12.8043 26.4399 13.031 26.8667C13.2578 27.2935 13.7335 27.5781 14.2181 27.5736H23.1099C23.8124 27.5825 24.4615 26.9468 24.4615 26.2399C24.4615 25.533 23.8124 24.8972 23.1099 24.9061H16.3165C16.6322 24.008 16.8278 23.1322 16.8323 22.2652C16.8323 21.8117 16.8012 21.3671 16.7611 20.9047H19.9978C20.7002 20.9136 21.3493 20.2779 21.3493 19.571C21.3493 18.8641 20.7002 18.2283 19.9978 18.2372H16.4944C16.4677 17.9215 16.4455 17.6103 16.4233 17.2635C16.4233 17.2591 16.4233 17.2546 16.4233 17.2502C16.3121 15.2584 17.5525 14.4004 18.9396 14.2492C19.6332 14.1736 20.3179 14.3337 20.8158 14.6938C21.3138 15.0539 21.6783 15.5785 21.7761 16.57C21.8428 17.268 22.5364 17.8459 23.2344 17.7793C23.9324 17.7126 24.5104 17.019 24.4437 16.321C24.2836 14.676 23.4745 13.3422 22.3719 12.542C21.4071 11.8439 20.2645 11.5416 19.1353 11.5683L19.1308 11.5727Z" fill="#003E7E"/>
                </svg>
            </div>
            <div style="flex:1; margin-left:12px;">
                <div style="font-weight:600;color:#003E7E;font-size:24px; margin-bottom:6px;">Price</div>
                <div style="display:flex;align-items:center;flex-wrap:wrap;">
                    <?php 
                    $count = 0;
                    foreach ($rates as $rate) {
                        $count++;
                        $extraStyle = ($count > 2) ? 'margin-top:20px;' : '';
                        ?>
                        <div style="font-size:20px;color:#1E2850;display:flex;flex-direction:column;gap:5px;font-weight:400; <?php echo $extraStyle; ?>">
                            <span><?php echo esc_html($rate['ShortDescription']); ?></span> 
                            <span style="color:#1E2850;font-family:Myriad Pro;font-size:24px;font-style:normal;font-weight:600;line-height:normal;<?php echo ($rate['ShortDescription'] === 'Member'|| 
                            $rate['ShortDescription'] === 'Company Member') ? 'border-radius:8px;background:#003E7E;color:#FFF;padding:5px 20px;': ''; ?>">
                                £<?php echo esc_html($rate['NetAmount']); ?>
                            </span>
                        </div>
                        <?php if ($count % 2 == 1) { ?>
                        <div style="width:2px;height:70px;background:#E5E6EB;margin:0px 10px;"></div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    }
} ?>

        <!-- Duration -->
        <div style="display:flex; align-items:flex-start; margin-bottom:20px;">
            <div style="flex:0 0 32px; display:flex; justify-content:center; align-items:flex-start;">
                <!-- Clock Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
<path d="M16.5228 8.71359C16.3228 10.94 16.2928 16.1027 16.631 18.2609C16.7701 19.1436 17.2292 19.3363 17.491 19.3736C19.4683 19.649 26.0483 19.7372 27.9474 19.3518C28.3065 19.279 28.5146 18.9281 28.6065 18.7263C28.8183 18.2609 28.8265 17.6863 28.6246 17.329C28.3146 16.7809 27.9919 16.67 26.4628 16.5863C25.7828 16.549 25.0619 16.56 24.3637 16.57H24.3537C23.2646 16.5854 22.1383 16.6018 20.9846 16.459L20.4292 16.39L20.0301 15.9981C19.4101 15.3909 19.3783 14.8636 19.4156 11.0045V10.9872C19.421 10.4081 19.4274 9.8145 19.4183 9.64723C19.3246 7.83541 19.1428 7.53359 18.471 7.29178C18.2701 7.21996 18.0746 7.18359 17.8892 7.18359C17.6492 7.18359 17.431 7.24541 17.2401 7.36723C16.8374 7.6245 16.5756 8.11541 16.5219 8.7145L16.5228 8.71359Z" fill="#003E7E"/>
<path d="M18 36C8.07455 36 0 27.9255 0 18C0 8.07455 8.07455 0 18 0C27.9255 0 36 8.07455 36 18C36 27.9255 27.9255 36 18 36ZM18 2.63636C9.52818 2.63636 2.63636 9.52818 2.63636 18C2.63636 26.4718 9.52818 33.3636 18 33.3636C26.4718 33.3636 33.3636 26.4718 33.3636 18C33.3636 9.52818 26.4718 2.63636 18 2.63636Z" fill="#003E7E"/>
</svg>
            </div><div style="flex: 1;margin-left:12px;">
    <div style="font-weight: 600;color: #003E7E;font-size: 24px; margin-bottom:6px;">Duration</div>
            <div style="color: #1E2850; font-family: Myriad Pro; font-size: 20px; font-style: normal; font-weight: 400; line-height: 28px;"><?php
$start = get_field('COURSE_SETUP_StartTime'); 
$end   = get_field('COURSE_SETUP_EndTime');   

if ($start && $end) {
    $start_time = new DateTime($start);
    $end_time   = new DateTime($end);

    // Handle overnight
    if ($end_time < $start_time) {
        $end_time->modify('+1 day');
    }

    $duration = $end_time->getTimestamp() - $start_time->getTimestamp();
    $hours    = floor($duration / 3600);
    $minutes  = floor(($duration % 3600) / 60);

    echo $hours . 'h ' . $minutes . 'm';
}
?>
</div></div>
        </div>

        <!-- Seats -->
        <div style="display:flex; align-items:flex-start; margin-bottom:20px;">
            <div style="flex:0 0 32px; display:flex; justify-content:center; align-items:flex-start;">
                <!-- Clock Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="29" viewBox="0 0 40 29" fill="none">
<path d="M39.9984 22.8981C39.8838 19.4369 37.5913 14.5207 34.4944 12.9912C39.5745 7.58573 35.4328 -0.86385 27.9353 0.0756286C25.4319 0.389552 23.2665 2.41401 22.1827 4.59658C20.738 4.13944 19.2107 4.21392 17.7603 4.5771C15.7198 -0.0939355 9.67045 -1.53867 5.79682 1.88241C2.47199 4.81885 2.4548 9.86339 5.50696 12.9912C2.37919 14.5493 0.15079 19.4025 0.00414009 22.8981C-0.0267939 23.6405 0.0980879 24.5215 0.982572 24.6796H9.63265C9.47454 25.6718 8.6187 28.3344 9.86408 28.8592L29.9666 28.9165C31.5294 28.1706 30.4891 25.9938 30.3699 24.6796H39.02C39.8243 24.6796 40.019 23.5145 39.9984 22.8992V22.8981ZM34.1668 6.15932C35.2563 10.1956 31.4056 13.7748 27.5045 12.4206C27.5045 12.4206 27.6718 11.3849 27.5045 10.6448C27.4186 10.0662 27.0886 9.24127 26.9866 8.98578C26.4665 7.6728 25.5259 6.57292 24.426 5.70677C26.0701 1.05406 32.8916 1.43443 34.1668 6.15932ZM19.2279 6.72072C26.3175 5.81217 27.2158 16.3263 20.5592 16.9885C13.7606 17.6645 12.836 7.5399 19.2279 6.72072ZM6.70766 4.39723C9.19957 1.23279 14.1891 1.91563 15.5754 5.70677C14.4331 6.59813 13.5544 7.63041 13.0148 8.98578C12.8933 9.28939 12.5954 9.93098 12.4969 10.6448C12.3846 11.4525 12.4969 12.4206 12.4969 12.4206C7.78921 14.146 3.54437 8.41407 6.70766 4.39723ZM2.52813 22.2725C2.75154 19.941 4.24096 16.5956 6.15887 15.3055C6.47508 15.0924 7.42258 14.531 7.75942 14.5149C8.1375 14.4966 9.3783 15.0076 10.0291 15.0592C11.0029 15.1359 12.1612 15.0912 13.0675 14.7109C13.3688 15.6549 13.9703 16.4856 14.6715 17.1753C13.1442 18.2053 11.6067 20.5826 10.7784 22.2737H2.52813V22.2725ZM11.6938 26.6262C11.9962 24.0312 13.7297 20.4348 16.0131 19.202C16.2502 19.0737 16.7818 18.7598 17.0213 18.7678C17.2539 18.7758 18.1258 19.1826 18.5416 19.2616C20.0563 19.5492 21.5743 19.3888 22.979 18.7644C25.8111 19.8104 27.9593 23.6015 28.3065 26.6262H11.6938ZM29.2231 22.2725C28.3787 20.5929 26.8686 18.2007 25.33 17.1741C26.085 16.5371 26.4424 15.616 27.0084 14.8369C28.0166 15.0179 29.0489 15.1451 30.08 15.0523C30.685 14.9984 31.8719 14.4955 32.2408 14.5138C32.5868 14.531 33.587 15.1279 33.9101 15.3502C35.7604 16.6288 37.2762 20.0052 37.4721 22.2714H29.2231V22.2725Z" fill="#003E7E"/>
</svg>
            </div><div style="
    flex: 1;
    margin-left:12px;
">
    <div style="font-weight: 600;color: #003E7E;font-size: 24px; margin-bottom:6px;">Seats</div>
            <div style="color: #1E2850;font-family: Myriad Pro;font-size: 20px;font-style: normal;font-weight: 400;line-height: 28px;">Up to <?php echo esc_html($max_seats); ?></div></div>
        </div>

        <!-- Location -->
        <div style="display:flex; align-items:flex-start; margin-bottom:12px;">
            <div style="flex:0 0 32px; display:flex; justify-content:center; align-items:flex-start;">
                <!-- Clock Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="35" viewBox="0 0 24 35" fill="none">
<path d="M12 0C5.36571 0 0 5.33747 0 11.9368C0 20.8895 12 34.1053 12 34.1053C12 34.1053 24 20.8895 24 11.9368C24 5.33747 18.6343 0 12 0ZM12 16.2C9.63429 16.2 7.71429 14.2901 7.71429 11.9368C7.71429 9.58358 9.63429 7.67368 12 7.67368C14.3657 7.67368 16.2857 9.58358 16.2857 11.9368C16.2857 14.2901 14.3657 16.2 12 16.2Z" fill="#003E7E"/>
</svg>
            </div><div style="
    flex: 1;
    margin-left:12px;
">
    <div style="font-weight: 600;color: #003E7E;font-size: 24px; margin-bottom:6px;">Location</div>
            <div style="color: #1E2850;font-family: Myriad Pro;font-size: 20px;font-style: normal;font-weight: 400;line-height: 28px;"><?php echo esc_html($training_loc); ?></div></div>
        </div>

        
    </div>
    <div style="color: #1E2850;font-family: Myriad Pro;font-size: 18px;font-style: normal;font-weight: 400;line-height: normal;float: inline-end;margin-top: 10px;">*Subject to VAT</div>
    <?php
    return ob_get_clean();
}
add_shortcode('course_booking_card', 'course_booking_card_shortcode');

// Branch sidebar Boxes
function sidebar_boxes_shortcode( $atts ) {

    if ( have_rows('sidebar_boxes', get_the_ID()) ) {  
        ob_start();
        echo '<div class="boxes-wrapper" style="display:block;">';

        while ( have_rows('sidebar_boxes', get_the_ID()) ) {
            the_row();
            $title = get_sub_field('sidebar_item_title');
            $desc  = get_sub_field('sidebar_item_details');
            $color = '#EDF1F6'; // default color

            echo '<div class="box-item" style="flex:1 1 30%;background:'. esc_attr($color) .';padding:25px;border-radius:8px;margin-bottom:25px;">';
            if ( $title ) echo '<h3 style="font-family: Myriad Pro, Sans-serif;
    font-size: 26px;
    font-weight: 600;
    text-transform: none;
    font-style: normal;
    color: var(--e-global-color-text);">'. esc_html($title) .'</h3>';
            if ( $desc )  echo '<p>'. ($desc) .'</p>';
            echo '</div>';
        }

        echo '</div>';
        return ob_get_clean();
    }

    return ''; // nothing if no repeater rows
}
add_shortcode( 'sidebar_boxessc', 'sidebar_boxes_shortcode' );

// Branch Previous News
// Slider
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css' );
    wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], null, true );
} );

function upcoming_news_slider_shortcode( $atts ) {
    if ( have_rows('upcomingnews', get_the_ID()) ) {
        ob_start();
        ?>
        <div class="swiper upcoming-news-slider">
            <div class="swiper-wrapper">
                <?php while ( have_rows('upcomingnews', get_the_ID()) ) : the_row(); 
                    $title = get_sub_field('newstitle');
                    $datep = get_sub_field('newsdatepublished');
                    $desc  = get_sub_field('newsdetails');
                    $image = get_sub_field('newsimage');
                    $link  = get_sub_field('newslink');
                    
                    // Handle image (if set to return array in ACF)
                    $image_url = '';
                    if ( is_array($image) && isset($image['url']) ) {
                        $image_url = $image['url'];
                    } elseif ( is_string($image) ) {
                        $image_url = $image;
                    }
                ?>
                    <div class="swiper-slide">
                        <div class="fv-news-item" >
                            <?php if ( $image_url ) : ?>
                                <div class="fv-news-image">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                                </div>
                            <?php else : ?>
                                <div class="fv-news-image">
                                    <img src="<?php echo esc_url(home_url('/')).'wp-content/uploads/2025/08/Dynamic-Positioning-1.png'; ?>" alt="<?php echo esc_attr($title); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="fv-news-details">
                            <?php if ( $title ) : ?>
                                <h3 class="fv-news-title" style="margin:0 0 10px;"><?php echo esc_html($title); ?></h3>
                            <?php endif; ?>
                            
                            <?php if ( $datep ) : ?>
                                <p class="fv-news-date-published" ><i class="fa fa-calendar" style="color:rgba(230, 78, 0, 1);"></i>&nbsp;&nbsp;<?php echo esc_html($datep); ?></p>
                            <?php endif; ?>

                            <?php if ( $desc ) : ?>
                                <p class="fv-news-description" ><?php echo $desc; ?></p>
                            <?php endif; ?>

                            <?php if ( $link ) : ?>
                                <a href="<?php echo esc_url($link); ?>" class="fv-news-link">
                                    Read more
                                </a>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Navigation -->
            <!--div class="swiper-button-prev"></div-->
            
            <!-- Pagination -->
            <!--div class="swiper-pagination"></div-->
        </div>
        <div class="fv-rightsw swiper-button-next"></div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            new Swiper(".upcoming-news-slider", {
                loop: true,
                slidesPerView: 1,
                spaceBetween: 30,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                breakpoints: {
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    return ''; // nothing if no repeater rows
}
add_shortcode( 'branches_upcoming_news_slider', 'upcoming_news_slider_shortcode' );

// Branch Previous Events
// Slider
function upcoming_events_slider_shortcode( $atts ) {
    if ( have_rows('upcomingevents', get_the_ID()) ) {
        ob_start();
        ?>
        <div class="swiper upcoming-events-slider">
            <div class="swiper-wrapper">
                <?php while ( have_rows('upcomingevents', get_the_ID()) ) : the_row(); 
                    $title = get_sub_field('eventtitle');
                    $desc  = get_sub_field('eventdetails');
                    $image = get_sub_field('eventimage');
                    $link  = get_sub_field('eventlink');

                    // Handle image (if set to return array in ACF)
                    $image_url = '';
                    if ( is_array($image) && isset($image['url']) ) {
                        $image_url = $image['url'];
                    } elseif ( is_string($image) ) {
                        $image_url = $image;
                    }
                ?>
                    <div class="swiper-slide">
                        <div class="fv-event-item" >
                            <?php /*if ( $image_url ) : ?>
                                <div class="fv-event-image"> 
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>"> 
                                </div>
                            <?php else : ?>
                                <div class="fv-event-image">
                                    <img src="<?php echo esc_url(home_url('/')).'wp-content/uploads/2025/08/Dynamic-Positioning-1.png'; ?>" alt="<?php echo esc_attr($title); ?>">
                                </div>
                            <?php endif; */?>

                            <div class="fv-event-details">
                                <?php if ( $title ) : ?>
                                    <h3 class="fv-event-title"><?php echo esc_html($title); ?></h3>
                                <?php endif; ?>

                                <?php if ( $desc ) : ?>
                                    <p class="fv-event-description"><?php echo $desc; ?></p>
                                <?php endif; ?>

                                <?php if ( $link ) : ?>
                                    <a href="<?php echo esc_url($link); ?>" class="fv-event-link">
                                        Read more
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Navigation -->
            <!--div class="swiper-button-prev"></div-->
        </div>
        <div class="fv-rightsw swiper-button-next2"></div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            new Swiper(".upcoming-events-slider", {
                loop: true,
                slidesPerView: 1,
                spaceBetween: 30,
                navigation: {
                    nextEl: ".swiper-button-next2",
                    prevEl: ".swiper-button-prev",
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                breakpoints: {
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    return ''; // nothing if no repeater rows
}
add_shortcode( 'branches_upcoming_events_slider', 'upcoming_events_slider_shortcode' );

// Reusable cURL request function
function makeCurlRequest($url, $payload, $headers) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}
    
function bookoomievent() {
    session_start();

    // Get the event data
    $event = get_post(get_the_ID());
    if (!$event) {
        return '<p style="color:red;">Event not found.</p>';
    }

    $eventid = urldecode($event->post_name);
    $apiHeaders = [
        'AccessID: ' . accessid,
        'Signature: ' . encodedSignature,
        'CurrentDateTime: ' . datex,
        'Content-Type: application/json'
    ];
    
    //print_r($apiHeaders);

    // Fetch event details
    $eventPayload = json_encode([
        "Fields" => "EventId,SeatsAvailable,EventStatusName",
        "Criterias" => [
            ["Field" => "EventId", "Operator" => "3", "Rank" => "1", "Value" => $eventid]
        ],
        "Logic" => "1",
        "PageNo" => "1",
        "PageSize" => "20",
        "EntityName" => "event"
    ]);

    $eventResponse = makeCurlRequest(apiurl . '/api/oomi/GetEntity', $eventPayload, $apiHeaders);
    //print_r($eventResponse);
    $eventDetails = $eventResponse['Records'][0] ?? null;
    
    if (!$eventDetails) {
        return '<p style="color:red;">Failed to fetch event detailsd.</p>';
    }
    
    $disablebtn ='';
    
    if(!($eventDetails['EventStatusName'] == 'Open')){
      //  $disablebtn = 'pointer-events: none; cursor: not-allowed; opacity: 0.6; text-decoration: none;';
    } 

    $eventrid = $eventDetails['RecordId'];
    $SeatsAvailable = intval($eventDetails['SeatsAvailable']);

    // Fetch event rates
    $ratePayload = json_encode([
        "Fields" => "DelegateType,DelegateTypeName,RateTypeName,RateAmount",
        "Criterias" => [
            ["Field" => "ShowOnWeb", "Operator" => "3", "Rank" => "1", "Value" => "Y"],
            ["Field" => "Event", "Operator" => "3", "Rank" => "2", "Value" => $eventrid]
        ],
        "Logic" => "1 AND 2",
        "PageNo" => "1",
        "PageSize" => "20",
        "EntityName" => "event_rate"
    ]);

    $rateResponse = makeCurlRequest(apiurl . '/api/oomi/GetEntity', $ratePayload, $apiHeaders);
    
    $discountPayload = json_encode([
        "Fields" => "DateFrom,DateTo,DelegateType,DiscountByPercentage,DiscountPercentage,DiscountAmount",
        "Criterias" => [
            ["Field" => "Active", "Operator" => "3", "Rank" => "1", "Value" => "Y"],
            ["Field" => "Event", "Operator" => "3", "Rank" => "2", "Value" => $eventrid]
        ],
        "Logic" => "1 AND 2",
        "PageNo" => "1",
        "PageSize" => "20",
        "EntityName" => "Event_Discount"
    ]);
    
    $discountResponse = makeCurlRequest(apiurl . '/api/oomi/GetEntity', $discountPayload, $apiHeaders);
    
    $eventdiscounts = $discountResponse['Records'] ?? [];
    
    $eventRates = $rateResponse['Records'] ?? [];
    
    $pricewithdiscounts = [];

    // Generate rates HTML
    $ratesHtml = '<div class="prices-all">';
    if(($discountResponse['Error']["ErrorCode"]) == '200'){
        foreach ($eventRates as $item1) {
            foreach ($eventdiscounts as $item2) {
                if ($item1['DelegateType'] == $item2['DelegateType']) {
                    $pricewithdiscounts[] = array_merge($item1, $item2);
                }
            }
        }
        foreach ($pricewithdiscounts as $rate) {
            if($rate["DiscountInPercentage"] == 'Y'){
                $discountAmount = ((floatval($rate["RateAmount"])) * (floatval($rate["DiscountPercentage"]))) / 100;
                $price = (floatval($rate["RateAmount"])) - $discountAmount;
                $discountshow = '<sub>'.intval($rate["DiscountPercentage"]).'% Off</sub>';
            } else {
                if($rate["DiscountAmount"]!= '0.00'){
                    $price = (floatval($rate["RateAmount"])) - (floatval($rate["DiscountAmount"]));
                    $discountshow = '<sub>£'.$rate["DiscountAmount"].' Off</sub>';
                } else {
                    $price = $rate["RateAmount"];
                }
            }
            /*$ratesHtml .= $rate["DelegateTypeName"] . ': 
                <span style="color: #1e4164; float:right;"> 
                <strong>£' . $price . '</strong><sub>+VAT</sub> 
                <strong> 
                  &nbsp;<strike>£'.$rate["RateAmount"].'</strike> 
                </strong></span><br>';*/
            
        }
        foreach ($eventRates as $ratez) { if($ratez["RateTypeName"] == 'Member'){ $memberclass = 'MemberPrice'; $highlightit = 'highlightit'; }
            $ratesHtml .= '<div class="elementor-element price-box '.$memberclass.' e-con-full e-flex e-con e-child" data-id="1539b56" data-element_type="container">
				<div class="elementor-element elementor-element-c0052dd elementor-widget elementor-widget-text-editor" data-id="c0052dd" data-element_type="widget" data-widget_type="text-editor.default">
				<div class="elementor-widget-container">
									<p>'.$ratez["DelegateTypeName"].'</p>								</div>
				</div>
				<div class="elementor-element elementor-element-be30bf3 elementor-widget elementor-widget-heading" data-id="be30bf3" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h2 class="elementor-heading-title elementor-size-default '.$highlightit.'">£' . $price . ' &nbsp;<strike> £' . $ratez["RateAmount"] . '</strike> </h2>				</div>
				</div>
				</div>';
            //$ratez["DelegateTypeName"] . ': <span style="color: #1e4164; float:right;"><strong>£' . $ratez["RateAmount"] . '</strong>&nbsp;<sub>+VAT</sub></span><br>';
        }
    } else {
        foreach ($eventRates as $ratez) { if($ratez["RateTypeName"] == 'Member'){ $memberclass = 'MemberPrice'; $highlightit = 'highlightit'; }
            $ratesHtml .= '<div class="elementor-element price-box '.$memberclass.' e-con-full e-flex e-con e-child" data-id="1539b56" data-element_type="container">
				<div class="elementor-element elementor-element-c0052dd elementor-widget elementor-widget-text-editor" data-id="c0052dd" data-element_type="widget" data-widget_type="text-editor.default">
				<div class="elementor-widget-container">
									<p>'.$ratez["DelegateTypeName"].'</p>								</div>
				</div>
				<div class="elementor-element elementor-element-be30bf3 elementor-widget elementor-widget-heading" data-id="be30bf3" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h2 class="elementor-heading-title elementor-size-default '.$highlightit.'">£' . $ratez["RateAmount"] . '</h2>				</div>
				</div>
				</div>';
            //$ratez["DelegateTypeName"] . ': <span style="color: #1e4164; float:right;"><strong>£' . $ratez["RateAmount"] . '</strong>&nbsp;<sub>+VAT</sub></span><br>';
        }
    }
    $ratesHtml .= '</div>';

    // Generate booking button
    if ($SeatsAvailable > 0) {
        $bookNowButton = '<a class="elementor-button elementor-button-link elementor-size-sm buttonxx" 
        style="'.$disablebtn.'" href="' . portalurl . 'event/book/' . $eventid . '">
        <span class="elementor-button-content-wrapper"><span class="elementor-button-text">Book today</span>&nbsp;
        <span class="elementor-button-icon elementor-align-icon-right"><i aria-hidden="true" class="fas fa-arrow-right"></i></span></span></a>';
    } else {
        $bookNowButton = '<span style="color:red;">Sorry, this event is fully booked. Please contact the organizer for more information.</span>';
    }

    return $ratesHtml . $bookNowButton;
}

add_shortcode('bookoomieventsc', 'bookoomievent');
// Register the [sitemap_tree] shortcode
function custom_sitemap_tree_shortcode() {
    ob_start();
    echo '<div class="sitemap-tree">';

    // Show all pages as a tree
    echo '<h2>Pages</h2>';
    echo '<ul>';
    wp_list_pages(array(
        'title_li' => '',
        'sort_column' => 'menu_order, post_title'
    ));
    echo '</ul>';

    // Show posts by category
    echo '<h2>Blog Posts</h2>';
    $cats = get_categories();
    echo '<ul>';
    foreach ($cats as $cat) {
        echo '<li>' . $cat->name;
        echo '<ul>';
        $posts = get_posts(array(
            'category' => $cat->term_id,
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        foreach ($posts as $post) {
            echo '<li><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></li>';
        }
        echo '</ul>';
        echo '</li>';
    }
    echo '</ul>';

    // Show categories
    echo '<h2>Categories</h2>';
    echo '<ul>';
    wp_list_categories(array(
        'title_li' => ''
    ));
    echo '</ul>';

    echo '</div>';
    return ob_get_clean();
}
add_shortcode('sitemap_tree', 'custom_sitemap_tree_shortcode');



// === Enqueue Branches Interactive MAP Leaflet ===
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );
    wp_enqueue_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );
});

// === Shortcode to show map banner ===
add_shortcode('branch_map_banner', function ($atts = []) {
    $atts = shortcode_atts([
        'height' => '500px',
        'zoom'   => 9, // default world view
    ], $atts, 'branch_map_banner');

    // Query Branch posts
    $branches = new WP_Query([
        'post_type'      => 'branch',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    $markers = [];
    if ($branches->have_posts()) {
        while ($branches->have_posts()) {
            $branches->the_post();
            $id    = get_the_ID();
            $title = get_the_title();
            $link  = get_permalink();

            // Try stored coordinates first
            $lat = get_post_meta($id, 'branch_latitude', true);
            $lng = get_post_meta($id, 'branch_longitude', true);

            // If not set, geocode once and save
            if (empty($lat) || empty($lng)) {
                $response = wp_remote_get("https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($title), [
                    'headers' => ['User-Agent' => 'WordPress/BranchMap'],
                ]);

                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (!empty($data[0])) {
                        $lat = (float) $data[0]['lat'];
                        $lng = (float) $data[0]['lon'];
                        // Save for next time
                        update_post_meta($id, 'branch_latitude', $lat);
                        update_post_meta($id, 'branch_longitude', $lng);
                    }
                }
            }

            // Only add marker if we have coordinates
            if (!empty($lat) && !empty($lng)) {
                $markers[] = [
                    'title' => esc_js($title),
                    'link'  => esc_url($link),
                    'lat'   => (float) $lat,
                    'lng'   => (float) $lng,
                ];
            }
        }
        wp_reset_postdata();
    }

    // Map unique ID
    $map_id = 'branch-map-' . wp_generate_password(6, false);

    ob_start(); ?>
    <div class="branch-map-banner">
        <div id="<?php echo esc_attr($map_id); ?>"
             style="height: <?php echo esc_attr($atts['height']); ?>;"></div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof L === "undefined") return;
        var map = L.map("<?php echo esc_js($map_id); ?>")
                   .setView([20,0], <?php echo (int)$atts['zoom']; ?>);

        // English map tiles
        L.tileLayer('https://cartodb-basemaps-a.global.ssl.fastly.net/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.oomi.co.uk">oomi</a>',
    maxZoom: 19
}).addTo(map);


        var markers = <?php echo wp_json_encode($markers); ?>;
        if (markers.length) {
            var bounds = [];
            markers.forEach(function (m) {
                var marker = L.marker([m.lat, m.lng]).addTo(map);
                var popup = '<strong><a href="'+m.link+'" target="_blank">'+m.title+'</a></strong>';
                marker.bindPopup(popup);
                bounds.push([m.lat, m.lng]);
            });
            map.fitBounds(bounds, {padding: [40, 40]});
        }
    });
    </script>
    <?php
    return ob_get_clean();
});


// Auto-geocode branch title on save, store lat/lng
add_action('save_post_branch', function ($post_id, $post, $update) {
    // Prevent auto runs
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $title = get_the_title($post_id);

    // Skip if already has lat/lng
    $lat = get_post_meta($post_id, 'branch_latitude', true);
    $lng = get_post_meta($post_id, 'branch_longitude', true);
    if (!empty($lat) && !empty($lng)) {
        return;
    }

    // Call Nominatim once
    $response = wp_remote_get("https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($title), [
        'headers' => ['User-Agent' => 'WordPress/BranchMap'],
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!empty($data[0])) {
            update_post_meta($post_id, 'branch_latitude', $data[0]['lat']);
            update_post_meta($post_id, 'branch_longitude', $data[0]['lon']);
        }
    }
}, 10, 3);

// Shortcode to display "Featured" if EVENT_Featured is Y
function my_event_featured_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => get_the_ID(),
    ), $atts, 'event_featured');

    // Get the field value
    $value = get_field('EVENT_Featured', $atts['id']);

    if ($value === 'Y') {
        return '<span class="event-featured-label">Featured</span>';
    }

    return ''; // show nothing if N or empty
}
add_shortcode('event_featured', 'my_event_featured_shortcode');

// [course_discount]
function course_discount_shortcode($atts) {
    global $post;

    // get the JSON from your ACF field
    $json_data = get_field('COURSE_DISCOUNT', $post->ID);

    if (!$json_data) {
        return '';
    }

    // decode the JSON
    $discounts = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($discounts)) {
        return '';
    }

    // pick the first discount (or loop if you need multiple)
    $discount_percentage = $discounts[0]['DiscountPercentage'] ?? '';

    if ($discount_percentage) {
    return (int)$discount_percentage . '% off';
}

    return '';
}
add_shortcode('course_discount', 'course_discount_shortcode');


// 🔹 Shortcode: [member_price]
function member_price_shortcode($atts) {
    global $post;

    // Get JSON from ACF
    $json_data = get_field('COURSE_RATE', $post->ID);
    if (empty($json_data)) {
        return '';
    }

    // Decode JSON
    $rates = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($rates)) {
        return '';
    }

    // Find Member price
    foreach ($rates as $rate) {
        if (isset($rate['DelegateTypeName']) && strtolower($rate['DelegateTypeName']) === 'member') {
            $price = (int)$rate['NetAmount']; // strip decimals
            return 'From £' . $price;
        }
    }

    return ''; // No member price found
}
add_shortcode('member_price', 'member_price_shortcode');

