<?php
/**
 * @package Ni
 */
//Ni-dev
define("baseurl", home_url("/"), true);
define("portalurl",    'https://staging-ni.oomi.co.uk/', true);
define("accessid",     '2143485778', true);
//echo define("datex", date("Y-m-d H:i:s"), true);
define("datex",        '2025-07-30 11:15:48', true);
$encodedSignature =    'UFENhnMXzH0uB23DFwYsHFL5THlmZCcefadfqNZV/EA=';
//$encodedSignature =   base64_encode(hash_hmac('sha256', datex, 'd1a85f741772ae215f06893b53b627e6'));
define("encodedSignature", $encodedSignature, true);
//define("apiurl",       'https://niapi.oomi.co.uk', true);
define("apiurl",       'https://devapi.oomi.co.uk', true);

//--------- SSO Settings -----------------------/

//define("ssourl",       'https://sso-staging-ni.oomi.co.uk', true);
/*define("clientId",     '69239681434', true);
 define("clientSecret", '2j58003f79i9llf993j951g9xh92h2fg', true); */

// Redirect author archive pages to the homepage
add_action("template_redirect", "redirect_author_archive");

function redirect_author_archive()
{
    if (is_author()) {
        wp_redirect(home_url());
        exit();
    }
}

function enqueue_custom_script()
{
    // Ensure jQuery is loaded (optional, if you need jQuery)
    wp_enqueue_script("jquery");

    // Register and enqueue your custom script
    wp_enqueue_script(
        "custom-script", // Handle for the script
        get_template_directory_uri() . "/assets/js/ni-custom.js", // Path to your script
        ["jquery"], // Dependencies (if any)
        null, // Version (null means no version check)
        true // Load script in footer (set to false to load in header)
    );
}
add_action("wp_enqueue_scripts", "enqueue_custom_script");

function li_child_enqueue_styles()
{
    wp_enqueue_style(
        "li-child-style",
        get_stylesheet_directory_uri() . "/style.css",
        [],
        wp_get_theme()->get("Version")
    );
}
add_action("wp_enqueue_scripts", "li_child_enqueue_styles");
add_filter("excerpt_length", function ($length) {
    return 100;
});

function wpb_noindex_author_archives()
{
    if (is_author()) {
        echo '<meta name="robots" content="noindex,follow" />';
    }
}
add_action("wp_head", "wpb_noindex_author_archives");

function nilogo()
{
    $custom_logo_id = get_theme_mod("custom_logo");
    $image = wp_get_attachment_image_src($custom_logo_id, "full");
    echo '<style type="text/css">
        #login h1 a,
        .login h1 a {
            background-image: url(' .
        ($image[0] != ""
            ? $image[0]
            : esc_url(get_stylesheet_directory_uri()) .
                "/assets/images/logo.png") .
        ');
            width: auto;
            max-width: 75%;
            background-size: contain;
            background-repeat: no-repeat;
            background-color: transparent;
        }
    </style>';
}
add_action("login_enqueue_scripts", "nilogo");

//add_filter('parse_request', 'custom_logout');
function custom_logout($request)
{
    if ($request->query_vars["pagename"] == "logout") {
        wp_logout();
        wp_set_current_user(0);
    }
}

add_filter("rest_endpoints", function ($endpoints) {
    if (isset($endpoints["/wp/v2/users"])) {
        unset($endpoints["/wp/v2/users"]);
    }

    if (isset($endpoints["/wp/v2/users/(?P<id>[\d]+)"])) {
        unset($endpoints["/wp/v2/users/(?P<id>[\d]+)"]);
    }

    return $endpoints;
});

function remove_footer_admincs()
{
    echo '<span id="footer-thankyou">Developed by <a href="https://oomi.co.uk" target="_blank">oomi</a> with <a href="https://wordpress.org" target="_blank">WordPress</a>.</span>';
}

add_filter("admin_footer_text", "remove_footer_admincs");

function clearwpadmin()
{
    echo '<style>li#menu-appearance ul li:last-child, li#menu-appearance ul li:nth-child(3) {display: none;} 
    .acf-readonly input, .acf-readonly textarea , .acf-readonly .acf-checkbox-list{
    background: #eee !important;
    pointer-events: none;
}</style>';
}

add_action("admin_head", "clearwpadmin");

function oomi_disable_comments_post_types_support()
{
    $post_types = get_post_types();

    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, "comments")) {
            remove_post_type_support($post_type, "comments");

            remove_post_type_support($post_type, "trackbacks");
        }
    }
}

add_action("admin_init", "oomi_disable_comments_post_types_support");

add_action("admin_init", function () {
    // Redirect any user trying to access comments page

    global $pagenow;

    if ($pagenow === "edit-comments.php") {
        wp_safe_redirect(admin_url());

        exit();
    }

    // Remove comments metabox from dashboard

    remove_meta_box("dashboard_recent_comments", "dashboard", "normal");

    // Disable support for comments and trackbacks in post types

    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, "comments")) {
            remove_post_type_support($post_type, "comments");

            remove_post_type_support($post_type, "trackbacks");
        }
    }
});

// Close comments on the front-end

add_filter("comments_open", "__return_false", 20, 2);

add_filter("pings_open", "__return_false", 20, 2);

// Hide existing comments

add_filter("comments_array", "__return_empty_array", 10, 2);

// Remove comments page in menu

add_action("admin_menu", function () {
    remove_menu_page("edit-comments.php");
});

// Remove comments links from admin bar

add_action("init", function () {
    if (is_admin_bar_showing()) {
        remove_action("admin_bar_menu", "wp_admin_bar_comments_menu", 60);
    }
});

function oomi_disable_comments_status()
{
    return false;
}

add_filter("comments_open", "oomi_disable_comments_status", 20, 2);

add_filter("pings_open", "oomi_disable_comments_status", 20, 2);

function oomi_disable_comments_hide_existing_comments($comments)
{
    $comments = [];

    return $comments;
}

add_filter(
    "comments_array",
    "oomi_disable_comments_hide_existing_comments",
    10,
    2
);

function isMobileDevice()
{
    return preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}

function generate_string($input, $strength = 16)
{
    $input_length = strlen($input);

    $random_string = "";

    for ($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];

        $random_string .= $random_character;
    }

    return $random_string;
}

function decryptssorid($plaintext)
{
    $encodedstr1 = explode("=", $plaintext);

    $encodedstr = $encodedstr1[1] . "==";

    $passwordcode = "7bf1GHjvb28";

    $ciphermethod = "AES-256-CBC";

    $password = substr(hash("sha256", $passwordcode, true), 0, 32);

    $iv =
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0) .
        chr(0x0);

    $decrypted = openssl_decrypt(
        base64_decode($encodedstr),
        $ciphermethod,
        $password,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $decrypted;
}

function checkgroupssologin($ssocookie)
{
    $encodedstr1 = explode("=", $ssocookie);

    return $grouplogincheck = $encodedstr1[4];
}

// Custom Post Cources

function create_posttype_courses()
{
    $supports = [
        "title", // post title

        "editor", // post content

        "author", // post author

        "thumbnail", // featured images

        "excerpt", // post excerpt

        "custom-fields", // custom fields

        "comments", // post comments

        "revisions", // post revisions

        "post-formats", // post formats
    ];

    $labels = [
        "name" => _x("Courses", "plural"),

        "singular_name" => _x("Course", "singular"),

        "menu_name" => _x("Courses", "admin menu"),

        "name_admin_bar" => _x("Courses", "admin bar"),

        "add_new" => _x("Add New", "add new"),

        "add_new_item" => __("Add New Course"),

        "new_item" => __("New Course"),

        "edit_item" => __("Edit Course Details"),

        "view_item" => __("View Course Details"),

        "all_items" => __("All Courses"),

        "search_items" => __("Search Course"),

        "not_found" => __("No Course found."),
    ];

    $args = [
        "supports" => $supports,

        "labels" => $labels,

        "public" => true,

        "query_var" => true,

        "menu_icon" => "dashicons-media-interactive",

        "rewrite" => ["slug" => "courses"],

        //'has_archive' => true,

        "hierarchical" => true,

        "show_in_rest" => true,

        "menu_position" => 10,

        // This is where we add taxonomies to our CPT

        //'taxonomies'          => array( 'category' ),
    ];

    register_post_type("courses", $args);

    register_taxonomy(
        "courses-category",
        ["courses"],
        [
            "label" => __("Courses Categories", "txtdomain"),

            "hierarchical" => true,

            "rewrite" => ["slug" => "courses-category"],

            "show_ui" => true,

            "show_in_menu" => true,

            "show_in_nav_menus" => true,

            "show_admin_column" => true,

            "show_in_rest" => true,

            "labels" => [
                "singular_name" => __("Courses categories", "txtdomain"),

                "all_items" => __("All Courses categories", "txtdomain"),

                "edit_item" => __("Edit Courses categories", "txtdomain"),

                "view_item" => __("View Courses categories", "txtdomain"),

                "update_item" => __("Update Courses categories", "txtdomain"),

                "add_new_item" => __("Add New Courses categories", "txtdomain"),

                "new_item_name" => __(
                    "New Courses categories Name",
                    "txtdomain"
                ),

                "search_items" => __("Search Courses categories", "txtdomain"),

                "parent_item" => __("Parent Courses categories", "txtdomain"),

                "parent_item_colon" => __(
                    "Parent Courses categories:",
                    "txtdomain"
                ),

                "not_found" => __("No Courses categories found", "txtdomain"),
            ],
        ]
    );

    register_taxonomy_for_object_type("courses-category", "courses");
}

// Hooking up our function to theme setup

//add_action('init', 'create_posttype_courses');

function newsletteruseradd($email, $firstname, $lastname)
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => apiurl . "api/oomi/GetEntity",

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_ENCODING => "",

        CURLOPT_MAXREDIRS => 10,

        CURLOPT_TIMEOUT => 0,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_CUSTOMREQUEST => "POST",

        CURLOPT_POSTFIELDS =>
            '{"Fields":"*","Criterias":[{"Field":"PrimaryEmail","Operator": "3","Rank": "1","Value":"' .
            $email .
            '"}],"Logic":"1","PageNo":"1","PageSize":"20","EntityName":"Contact"}',

        CURLOPT_HTTPHEADER => [
            "AccessID: " . accessID,

            "Signature: " . encodedSignature,

            "CurrentDateTime: " . datex,

            "Authorization: Basic NDM1MTk4NzY4OTpjODA0NjhjNTZmYjRmMTIxNDU5ZDAzMzVjODljMDNhMw==",

            "Content-Type: application/json",

            "Cookie: AWSALB=LfqjyNY9eyG/ZdBDY2Pk1Q0Q6PMx8szLs941LdyWeQyaXV9mfIsieJYTRjB/yjJqaePEQaB2/gsyB5FLjfVQhwQOOiw9VE2pJNGekLJCjQsr693R+oNq5MPBuOzj; AWSALBCORS=LfqjyNY9eyG/ZdBDY2Pk1Q0Q6PMx8szLs941LdyWeQyaXV9mfIsieJYTRjB/yjJqaePEQaB2/gsyB5FLjfVQhwQOOiw9VE2pJNGekLJCjQsr693R+oNq5MPBuOzj",
        ],
    ]);

    $json3 = curl_exec($curl);

    curl_close($curl);

    $checksubs = json_decode($json3);

    if ($checksubs->Error->ErrorCode == 200) {
        return "Thanks, You have already subscribed to Ni Newsletter.";
    } else {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => apiurl . "api/oomi/InsertAPI",

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_ENCODING => "",

            CURLOPT_MAXREDIRS => 10,

            CURLOPT_TIMEOUT => 0,

            CURLOPT_FOLLOWLOCATION => true,

            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

            CURLOPT_CUSTOMREQUEST => "POST",

            CURLOPT_POSTFIELDS =>
                '{ "EntityName": "API_CONTACT_REGISTRATION",

        "LoginUserName" : "' .
                $email .
                '", "FirstName" : "' .
                $firstname .
                '", "LastName" : "' .
                $lastname .
                '" }',

            CURLOPT_HTTPHEADER => [
                "AccessID: " . accessid,

                "Signature: " . encodedSignature,

                "CurrentDateTime: " . datex,

                "Content-Type: application/json",

                "Cookie: AWSALB=A9BEJahs4tS32Lcas1iX/YcChv7mmlaSZB5BD5XRCc9yI09Kt15d6CVqYspQ8oL3EpSK7qHF39oRaqMVlxk3n02Uu55oY1axPlz5GfFKsETgZc8OOCKGXVccirkQ; AWSALBCORS=A9BEJahs4tS32Lcas1iX/YcChv7mmlaSZB5BD5XRCc9yI09Kt15d6CVqYspQ8oL3EpSK7qHF39oRaqMVlxk3n02Uu55oY1axPlz5GfFKsETgZc8OOCKGXVccirkQ",
            ],
        ]);

        $json2 = curl_exec($curl);

        curl_close($curl);

        $addedresults = json_decode($json2);

        $reponse2 = json_decode($addedresults->Records);

        $recordid = "" . $reponse2->RecordId;

        if (isset($recordid)) {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => apiurl . "api/oomi/GetEntity",

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_ENCODING => "",

                CURLOPT_MAXREDIRS => 10,

                CURLOPT_TIMEOUT => 0,

                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

                CURLOPT_CUSTOMREQUEST => "POST",

                CURLOPT_POSTFIELDS => '{"Fields":"RecordId","Criterias":[{"Field":"Newsletter","Operator": "3","Rank": "1","Value":"Y"}],"Logic":"1","PageNo":"1","PageSize":"20","EntityName":"Mailing"}',

                CURLOPT_HTTPHEADER => [
                    "AccessID: " . accessid,

                    "Signature: " . encodedSignature,

                    "CurrentDateTime: " . datex,

                    "Content-Type: application/json",

                    "Cookie: AWSALB=LiaBNyuNYpi7oPsDpdbaPv/Wfqoim20smoeiabiRUd5QGztX80FmKNTOj4W4lGxP0+JOKg5Me+xPdonu83X3vvPBp0ZXJ+5wGUh/1dFk6l1ucVqzDDJLL18EOP1P; AWSALBCORS=LiaBNyuNYpi7oPsDpdbaPv/Wfqoim20smoeiabiRUd5QGztX80FmKNTOj4W4lGxP0+JOKg5Me+xPdonu83X3vvPBp0ZXJ+5wGUh/1dFk6l1ucVqzDDJLL18EOP1P",
                ],
            ]);

            $mailinglistsjson = curl_exec($curl);

            curl_close($curl);

            $mailinglists = json_decode($mailinglistsjson);

            if ($mailinglists->Error->ErrorCode == 200) {
                $maillistid = "" . $mailinglists->Records[0]->RecordId;

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => apiurl . "api/oomi/InsertAPI",

                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_ENCODING => "",

                    CURLOPT_MAXREDIRS => 10,

                    CURLOPT_TIMEOUT => 0,

                    CURLOPT_FOLLOWLOCATION => true,

                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

                    CURLOPT_CUSTOMREQUEST => "POST",

                    CURLOPT_POSTFIELDS =>
                        '{ "EntityName": "API_MAILING_PREFERENCE_UPDATE", "ContactRecordId": "' .
                        $recordid .
                        '", "MailingRecordId": "' .
                        $maillistid .
                        '", "OptIn": "Y" }',

                    CURLOPT_HTTPHEADER => [
                        "AccessID: " . accessid,

                        "Signature: " . encodedSignature,

                        "CurrentDateTime: " . datex,

                        "Authorization: Basic NDM1MTk4NzY4OTpjODA0NjhjNTZmYjRmMTIxNDU5ZDAzMzVjODljMDNhMw==",

                        "Content-Type: application/json",

                        "Cookie: AWSALB=KexQzU3Sj5yaZCB1SUTsOrDNneX/kx8K0vhTCkQnuuZ2LcHAy7eBBYJtY+W+L5yAXdODC0tmnoodAS6SUmvjluXzNFjDRkgA5PeXlOc+H78clxaG2PC2b7ZFQBgI; AWSALBCORS=KexQzU3Sj5yaZCB1SUTsOrDNneX/kx8K0vhTCkQnuuZ2LcHAy7eBBYJtY+W+L5yAXdODC0tmnoodAS6SUmvjluXzNFjDRkgA5PeXlOc+H78clxaG2PC2b7ZFQBgI",
                    ],
                ]);

                $maillistjson = curl_exec($curl);

                curl_close($curl);

                $addedintomaillist = json_decode($maillistjson);

                if ($addedintomaillist->Error->ErrorCode == 200) {
                    return "Thanks, you are now successfully subscribed to the Ni Newsletter.";
                }
            }
        }
    }
}

// Custom Breadcrumbs

function custom_breadcrumb_settings_page()
{
    add_options_page(
        "Breadcrumb Settings", // Page title

        "Breadcrumb Settings", // Menu title

        "manage_options", // Capability

        "breadcrumb-settings", // Menu slug

        "breadcrumb_settings_page_html" // Callback function
    );
}

add_action("admin_menu", "custom_breadcrumb_settings_page");

// Settings page HTML

function breadcrumb_settings_page_html()
{
    ?>

    <div class="wrap">

        <h1>Breadcrumb Settings</h1>

        <p>Here you can customize the appearance of your breadcrumbs. Choose the color, font, and font size that best fits your site’s design.</p>

        <p>To display the breadcrumbs on your site, use the following shortcode:</p>

        <pre><code>[custom_breadcrumb]</code></pre>

        <p>Simply add this shortcode to any page, post, or widget where you want the breadcrumbs to appear.</p>

        <form method="post" action="options.php">

            <?php
            settings_fields("breadcrumb_settings");

            do_settings_sections("breadcrumb-settings");

            submit_button();?>

        </form>

    </div>

<?php
}

// Register settings

function breadcrumb_settings_init()
{
    register_setting("breadcrumb_settings", "breadcrumb_color");

    register_setting("breadcrumb_settings", "breadcrumb_font");

    register_setting("breadcrumb_settings", "breadcrumb_font_size");

    register_setting("breadcrumb_settings", "breadcrumb_font_weight");

    register_setting("breadcrumb_settings", "breadcrumb_text_decoration"); // Add this line

    register_setting("breadcrumb_settings", "breadcrumb_seperator_color");

    register_setting("breadcrumb_settings", "breadcrumb_active_page_color");

    register_setting(
        "breadcrumb_settings",
        "breadcrumb_active_page_font_weight"
    );

    add_settings_section(
        "breadcrumb_settings_section",

        "Customize Breadcrumbs",

        null,

        "breadcrumb-settings"
    );

    add_settings_field(
        "breadcrumb_color",

        "Breadcrumb Color",

        "breadcrumb_color_field_html",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_font",

        "Breadcrumb Font",

        "breadcrumb_font_field_html",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_font_size",

        "Breadcrumb Font Size",

        "breadcrumb_font_size_field_html",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_font_weight",

        "Breadcrumb Font Weight",

        "breadcrumb_font_weight_field_html",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_text_decoration",

        "Breadcrumb Text Decoration",

        "breadcrumb_text_decoration_field_html",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_seperator_color",

        "Breadcrumb Seperator Color",

        "breadcrumb_seperator_color",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_active_page_color",

        "Breadcrumb Active Page Color",

        "breadcrumb_active_page_color",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );

    add_settings_field(
        "breadcrumb_active_page_font_weight",

        "Breadcrumb Active Page Font Weight",

        "breadcrumb_active_page_font_weight",

        "breadcrumb-settings",

        "breadcrumb_settings_section"
    );
}

add_action("admin_init", "breadcrumb_settings_init");

function breadcrumb_active_page_color()
{
    $breadcrumb_active_page_color = get_option(
        "breadcrumb_active_page_color",
        "#0073e6"
    );

    echo '<input type="text" name="breadcrumb_active_page_color" value="' .
        esc_attr($breadcrumb_active_page_color) .
        '" class="color-picker" data-default-color="#0073e6">';
}

function breadcrumb_color_field_html()
{
    $color = get_option("breadcrumb_color", "#0073e6");

    echo '<input type="text" name="breadcrumb_color" value="' .
        esc_attr($color) .
        '" class="color-picker" data-default-color="#0073e6">';
}

function breadcrumb_font_field_html()
{
    $font = get_option("breadcrumb_font", "Arial");

    echo '<input type="text" placeholder="Public  Sans" name="breadcrumb_font" value="' .
        esc_attr($font) .
        '">';
}

function breadcrumb_seperator_color()
{
    $breadcrumb_seperator_color = get_option(
        "breadcrumb_seperator_color",
        "#0073e6"
    );

    echo '<input type="text" name="breadcrumb_seperator_color" value="' .
        esc_attr($breadcrumb_seperator_color) .
        '" class="color-picker" data-default-color="#0073e6">';
}

function breadcrumb_text_decoration_field_html()
{
    $text_decoration = get_option("breadcrumb_text_decoration", "none");

    $decorations = [
        "none" => "None",

        "underline" => "Underline",

        "overline" => "Overline",

        "line-through" => "Line Through",
    ];

    echo '<select name="breadcrumb_text_decoration">';

    foreach ($decorations as $value => $label) {
        $selected = $value == $text_decoration ? "selected" : "";

        echo '<option value="' .
            esc_attr($value) .
            '" ' .
            $selected .
            ">" .
            esc_html($label) .
            "</option>";
    }

    echo "</select>";
}

function breadcrumb_active_page_font_weight()
{
    $font_weight = get_option("breadcrumb_active_page_font_weight", "500");

    $weights = [
        "100" => "100 Thin",

        "200" => "200 Extra Light",

        "300" => "300 Light",

        "400" => "400 Regular",

        "500" => "500 Medium",

        "600" => "600 Semi Bold",

        "700" => "700 Bold",

        "800" => "800 Extra Bold",

        "900" => "900 Heavy",
    ];

    echo '<select name="breadcrumb_active_page_font_weight">';

    foreach ($weights as $value => $label) {
        $selected = $value == $font_weight ? "selected" : "";

        echo '<option value="' .
            esc_attr($value) .
            '" ' .
            $selected .
            ">" .
            esc_html($label) .
            "</option>";
    }

    echo "</select>";
}

function breadcrumb_font_weight_field_html()
{
    $font_weight = get_option("breadcrumb_font_weight", "400");

    $weights = [
        "100" => "100 Thin",

        "200" => "200 Extra Light",

        "300" => "300 Light",

        "400" => "400 Regular",

        "500" => "500 Medium",

        "600" => "600 Semi Bold",

        "700" => "700 Bold",

        "800" => "800 Extra Bold",

        "900" => "900 Heavy",
    ];

    echo '<select name="breadcrumb_font_weight">';

    foreach ($weights as $value => $label) {
        $selected = $value == $font_weight ? "selected" : "";

        echo '<option value="' .
            esc_attr($value) .
            '" ' .
            $selected .
            ">" .
            esc_html($label) .
            "</option>";
    }

    echo "</select>";
}

function breadcrumb_font_size_field_html()
{
    $font_size = get_option("breadcrumb_font_size", "16px");

    echo '<input type="text" placeholder="16px" name="breadcrumb_font_size" value="' .
        esc_attr($font_size) .
        '">';
}

function enqueue_color_picker($hook_suffix)
{
    if ($hook_suffix !== "settings_page_breadcrumb-settings") {
        return;
    }

    wp_enqueue_style("wp-color-picker");

    wp_enqueue_script(
        "custom-script",
        get_template_directory_uri() . "/js/script.js",
        ["wp-color-picker"],
        false,
        true
    );
}

add_action("admin_enqueue_scripts", "enqueue_color_picker");

function custom_breadcrumb_shortcode()
{
    $separator =
        ' <i class="fa fa-chevron-right" aria-hidden="true" style="font-size: 12px;margin-top: 4px;padding: 0px 8px !important;"></i> ';

    $home = "Home"; // Text for the 'Home' link

    $breadcrumb = "";

    // Retrieve settings

    $color = get_option("breadcrumb_color", "#0F273B");

    $font = get_option("breadcrumb_font", "Public Sans");

    $font_size = get_option("breadcrumb_font_size", "16px");

    $font_weight = get_option("breadcrumb_font_weight", "400");

    $text_decoration = get_option("breadcrumb_text_decoration", "none");

    $breadcrumb_seperator_color = get_option(
        "breadcrumb_seperator_color",
        "#0F273B"
    );

    $breadcrumb_active_page_color = get_option(
        "breadcrumb_active_page_color",
        "#0F273B"
    );

    $breadcrumb_active_page_font_weight = get_option(
        "breadcrumb_active_page_font_weight",
        "500"
    ); // Apply only to links

    // Start breadcrumb

    if (!is_front_page()) {
        $breadcrumb .=
            '<nav class="custom-breadcrumb" style="color: ' .
            esc_attr($color) .
            "; font-family: " .
            esc_attr($font) .
            "; font-size: " .
            esc_attr($font_size) .
            "; font-weight: " .
            esc_attr($font_weight) .
            ';">';

        $breadcrumb .=
            '<a href="' .
            home_url() .
            '" style="color: ' .
            esc_attr($color) .
            "; text-decoration: " .
            esc_attr($text_decoration) .
            ';">' .
            $home .
            '</a><span style="color: ' .
            esc_attr($breadcrumb_seperator_color) .
            ';">' .
            $separator .
            "</span>";

        if (is_category() || is_single()) {
            // Category or Single Post

            if (is_single()) {
                //echo 'shhh';

                $post_type = get_post_type();

                //echo $post_type;

                $post_type_object = get_post_type_object($post_type);

                // print_r($post_type_object);

                $post_type = $post_type_object->name;

                $post_type_label = $post_type_object->label;

                //echo $post_type;

                $post = get_page_by_path($post_type, OBJECT, "events");

                // $permalink = get_permalink($post);

                echo $permalink;

                if (
                    $post_type == "resources" &&
                    !has_term(
                        "resources_information_type_institute_news",
                        "resources_information_type",
                        get_the_ID()
                    )
                ) {
                    //$post_type_child_page = 'Events calendar';

                    //$breadcrumb .= '<span>' . $post_type . '</span>';

                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        '/ni-resources/" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_label .
                        '</a><span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";

                    //$breadcrumb .= '<a href="' . home_url() .'/'. $post_type .'/'.'events-calendar' . '" style="color: ' . esc_attr($color) . '; text-decoration: ' . esc_attr($text_decoration) . ';">' . $post_type_child_page . '</a><span class="seperator-gap" style="color: ' . esc_attr($breadcrumb_seperator_color) . ';">' . $separator . '</span>';
                }

                if ($post_type == "products") {
                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        '/ni-bookshop/" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_label .
                        '</a><span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";
                }

                if ($post_type == "technical-resource") {
                    $post_type_child_page = "Events calendar";

                    //$breadcrumb .= '<span>' . $post_type . '</span>';

                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        '/li-technical-resource/" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_label .
                        '</a><span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";

                    //$breadcrumb .= '<a href="' . home_url() .'/'. $post_type .'/'.'events-calendar' . '" style="color: ' . esc_attr($color) . '; text-decoration: ' . esc_attr($text_decoration) . ';">' . $post_type_child_page . '</a><span class="seperator-gap" style="color: ' . esc_attr($breadcrumb_seperator_color) . ';">' . $separator . '</span>';
                } elseif (
                    $post_type == "events" ||
                    $post_type == "post" ||
                    ($post_type == "resources" &&
                        has_term(
                            "resources_information_type_institute_news",
                            "resources_information_type",
                            get_the_ID()
                        ))
                ) {
                    $post_type_child_page = "News & Events";

                    $post_type_child_page2 = "News";

                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        "/" .
                        "news-events" .
                        '" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_child_page .
                        '</a><span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";

                    //$breadcrumb .= '<a href="' . home_url() .'/'.'media-hub/news' . '" style="color: ' . esc_attr($color) . '; text-decoration: ' . esc_attr($text_decoration) . ';">News</a><span class="seperator-gap" style="color: ' . esc_attr($breadcrumb_seperator_color) . ';">' . $separator . '</span>';
                } elseif ($post_type == "magazine") {
                    $post_type_child_page = "Media hub";

                    $post_type_child_page2 = "Publications";

                    //$breadcrumb .= '<span>' . $post_type . '</span>';

                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        "/" .
                        "media-hub" .
                        '" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_child_page .
                        '</a>

                    <span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";

                    $breadcrumb .=
                        '<a href="' .
                        home_url() .
                        "/" .
                        "media-hub/publications" .
                        '" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        $post_type_child_page2 .
                        '</a>

                    <span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";
                }

                $breadcrumb .=
                    '<span style="color: ' .
                    esc_attr($breadcrumb_active_page_color) .
                    "; font-weight: " .
                    esc_attr($breadcrumb_active_page_font_weight) .
                    ';">' .
                    get_the_title() .
                    "</span>";
            } else {
                // Category Archive Page

                $breadcrumb .=
                    "<span>" . single_cat_title("", false) . "</span>";
            }
        } elseif (is_page()) {
            // Page

            global $post;

            $ancestors = get_post_ancestors($post->ID);

            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);

                foreach ($ancestors as $ancestor) {
                    $breadcrumb .=
                        '<a href="' .
                        get_permalink($ancestor) .
                        '" style="color: ' .
                        esc_attr($color) .
                        "; text-decoration: " .
                        esc_attr($text_decoration) .
                        ';">' .
                        get_the_title($ancestor) .
                        '</a><span class="seperator-gap" style="color: ' .
                        esc_attr($breadcrumb_seperator_color) .
                        ';">' .
                        $separator .
                        "</span>";
                }
            }

            $breadcrumb .=
                '<span style="color: ' .
                esc_attr($breadcrumb_active_page_color) .
                "; font-weight: " .
                esc_attr($breadcrumb_active_page_font_weight) .
                ';">' .
                get_the_title() .
                "</span>";
        } elseif (is_tag()) {
            // Tag Archive Page

            $breadcrumb .= "<span>" . single_tag_title("", false) . "</span>";
        } elseif (is_author()) {
            // Author Archive Page

            $breadcrumb .=
                "<span>" . get_the_author_meta("display_name") . "</span>";
        } elseif (is_search()) {
            // Search Results Page

            $breadcrumb .=
                '<span>Search Results for "' . get_search_query() . '"</span>';
        } elseif (is_404()) {
            // 404 Page

            $breadcrumb .= "<span>404 Not Found</span>";
        }

        // End breadcrumb

        $breadcrumb .= "</nav>";
    }

    return $breadcrumb;
}

add_shortcode("custom_breadcrumb", "custom_breadcrumb_shortcode");

// Draft Old Events

// Events oomi cron job to be from start date available - Schedule to run daily.

if (!wp_next_scheduled("auto_draft_old_events")) {
    wp_schedule_event(time(), "daily", "auto_draft_old_events");
}

add_action("auto_draft_old_events", "auto_draft_past_events");

function auto_draft_past_events()
{
    $today = current_time("Ymd");

    $args = [
        "post_type" => "events",

        "post_status" => "publish",

        "meta_query" => [
            [
                "key" => "EVENT_StartDate", // Your custom field name

                "value" => $today,

                "compare" => "<", // Find dates less than today

                "type" => "DATE",
            ],
        ],

        "posts_per_page" => -1, // Get all matching posts
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();

            // Change post status to draft

            wp_update_post([
                "ID" => $post_id,

                "post_status" => "draft",
            ]);
        }

        wp_reset_postdata();
    }
}

add_filter("views_edit-events", "events_filter");

function events_filter($views)
{
    $views["import"] =
        '<style>.notice.notice-error{display:none;} a.import{background: #2271b1; padding: 10px 15px; border: 1px solid; margin: 10px; color: #fff;}li.import{margin: 10px 5px;}</style>

    <a href="' .
        baseurl .
        'fetch-events-oomi.php" class="import" style="">Sync with oomi</a>';

    return $views;
}

add_filter("manage_events_posts_columns", "oomi_filter_posts_columns");

function oomi_filter_posts_columns($columns)
{
    $columns["startdate"] = __("Start date", "oomi");

    $columns["enddate"] = __("End date", "oomi");

    return $columns;
}

add_action("manage_events_posts_custom_column", "oomi_events_column", 10, 2);

function oomi_events_column($column, $post_id)
{
    if ("startdate" === $column) {
        $startdate = get_post_meta($post_id, "EVENT_StartDate", true);

        echo date("jS M, Y", strtotime($startdate));
    }

    if ("enddate" === $column) {
        $enddate = get_post_meta($post_id, "EVENT_EndDate", true);

        echo date("jS M, Y", strtotime($enddate));
    }
}

// Custom query for event posts based on start date

add_action("elementor/query/eventsquery", function ($query) {
    $meta_query = [
        "relation" => "AND",

        [
            "key" => "start_date", // Assuming the field is 'Start Date'

            "value" => date("Ymd"), // Format in 'YYYYMMDD'

            "compare" => ">=", // Only future or today’s events

            "type" => "DATE",
        ],
    ];

    // Set the query parameters

    $query->set("meta_key", "start_date");

    $query->set("orderby", "meta_value");

    $query->set("order", "ASC"); // Earliest events first

    $query->set("meta_query", $meta_query);
});

// Modify the rendered content of Elementor widgets

add_action(
    "elementor/widget/render_content",
    function ($content, $widget) {
        // Check if the widget is using the 'eventsquery'

        if ($widget->get_settings("query_id") === "eventsquery") {
            // Get the current post ID

            $post_id = get_the_ID();

            // Ensure that we have a post ID before proceeding

            if ($post_id) {
                // Get the featured image

                $featured_image = get_the_post_thumbnail_url($post_id);

                // If no featured image, use the custom field 'event_image'

                if (!$featured_image) {
                    $event_image_url = get_post_meta(
                        $post_id,
                        "event_image",
                        true
                    );

                    // Only replace the image if 'event_image' has a valid URL

                    if ($event_image_url) {
                        // Replace the content's image tag with the custom event image

                        $content = str_replace(
                            get_the_post_thumbnail($post_id),

                            '<img src="' .
                                esc_url($event_image_url) .
                                '" alt="' .
                                esc_attr(get_the_title($post_id)) .
                                '">',

                            $content
                        );
                    }
                }
            }
        }

        // Always return the content at the end of the function

        return $content;
    },
    10,
    2
);

function display_event_image_shortcode($atts)
{
    // Global post object

    global $post;

    // Default placeholder image URL

    $placeholder_image =
        baseurl .
        "/wp-content/uploads/2024/02/dashboard-directory-img.png.webp";

    // Check if the post type is 'events'

    if ("events" === get_post_type($post->ID)) {
        // Check if the post has a featured image

        if (has_post_thumbnail($post->ID)) {
            $image = get_the_post_thumbnail($post->ID, "full");
        }

        // Check if the ACF field 'event_image' is set
        elseif (
            function_exists("get_field") &&
            ($event_image = get_field("event_image", $post->ID))
        ) {
            $image =
                '<img src="' .
                esc_url($event_image) .
                '" class="eventimage" alt="Event Image">';
        }

        // Use placeholder image if neither is set
        else {
            $image =
                '<img src="' .
                esc_url($placeholder_image) .
                '" class="eventimage" alt="placeholder">';
        }

        return $image;
    }

    // If not the correct post type, return empty or alternative content

    return "";
}

add_shortcode("display_event_image", "display_event_image_shortcode");

function custom_replace_date_in_specific_repeater()
{
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
    // Select all repeaters
    const repeaters = document.querySelectorAll(
        ".elementor-icon-list-items.elementor-post-info"
    );

    repeaters.forEach(repeater => {
        const dates = repeater.querySelectorAll(
            ".elementor-post-info__item--type-custom"
        );

        if (dates.length === 1) {
            // ✅ Only one date → just show it cleaned
            const singleText = dates[0].textContent.trim();
            const normalize = str =>
                str.replace(/(\d+)(st|nd|rd|th)/, "$1").replace(",", "");
            const singleDate = new Date(normalize(singleText));

            dates[0].textContent = singleDate.toLocaleDateString("en-GB", {
                day: "numeric",
                month: "short",
                year: "numeric"
            });
        }

        if (dates.length >= 2) {
            const startText = dates[0].textContent.trim(); 
            const endText   = dates[1].textContent.trim();

            const normalize = str =>
                str.replace(/(\d+)(st|nd|rd|th)/, "$1").replace(",", "");

            const startDate = new Date(normalize(startText));
            const endDate   = new Date(normalize(endText));

            let output = "";

            // Case 1: Same exact day
            if (
                startDate.getDate() === endDate.getDate() &&
                startDate.getMonth() === endDate.getMonth() &&
                startDate.getFullYear() === endDate.getFullYear()
            ) {
                output = startDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "numeric"
                });
            }
            // Case 2: Same month & year
            else if (
                startDate.getMonth() === endDate.getMonth() &&
                startDate.getFullYear() === endDate.getFullYear()
            ) {
                const monthYear = endDate.toLocaleDateString("en-GB", {
                    month: "short",
                    year: "numeric"
                });
                output = `${startDate.getDate()} - ${endDate.getDate()} ${monthYear}`;
            }
            // Case 3: Same year, different month
            else if (startDate.getFullYear() === endDate.getFullYear()) {
                const startPart = startDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short"
                });
                const endPart = endDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short"
                });
                output = `${startPart} - ${endPart} ${endDate.getFullYear()}`;
            }
            // Case 4: Different years
            else {
                const startPart = startDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "numeric"
                });
                const endPart = endDate.toLocaleDateString("en-GB", {
                    day: "numeric",
                    month: "short",
                    year: "numeric"
                });
                output = `${startPart} - ${endPart}`;
            }

            // Replace with formatted date range
            dates[0].textContent = output;

            // Hide/remove second one
            dates[1].style.display = "none";
        }
    });
});

    </script>

<?php
}

add_action("wp_footer", "custom_replace_date_in_specific_repeater");

// Show taxonomy filters on the admin list page for "resources"

add_action("restrict_manage_posts", "filter_resources_by_taxonomies");

function filter_resources_by_taxonomies()
{
    global $typenow;

    // Only apply to 'resources' post type

    if ($typenow !== "resources") {
        return;
    }

    // Define the taxonomies

    $taxonomies = ["resources_information_type", "resources_topic"];

    foreach ($taxonomies as $taxonomy) {
        $taxonomy_obj = get_taxonomy($taxonomy);

        $taxonomy_name = $taxonomy_obj->name;

        $terms = get_terms([
            "taxonomy" => $taxonomy_name,

            "hide_empty" => false,
        ]);

        if (!empty($terms)) {
            $selected = isset($_GET[$taxonomy_name])
                ? $_GET[$taxonomy_name]
                : "";

            echo '<select name="' .
                esc_attr($taxonomy_name) .
                '" class="postform">';

            echo '<option value="">' .
                esc_html($taxonomy_obj->labels->all_items) .
                "</option>";

            foreach ($terms as $term) {
                printf(
                    '<option value="%s"%s>%s</option>',

                    esc_attr($term->slug),

                    selected($selected, $term->slug, false),

                    esc_html($term->name)
                );
            }

            echo "</select>";
        }
    }
}

// Modify the query based on selected taxonomy filters

add_filter("parse_query", "filter_resources_query_by_taxonomies");

function filter_resources_query_by_taxonomies($query)
{
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === "edit.php" &&
        isset($_GET["post_type"]) &&
        $_GET["post_type"] === "resources" &&
        $query->is_main_query()
    ) {
        $taxonomies = ["resources_information_type", "resources_topic"];

        foreach ($taxonomies as $taxonomy) {
            if (!empty($_GET[$taxonomy])) {
                $query->set($taxonomy, $_GET[$taxonomy]);
            }
        }
    }
}

add_action("wp_ajax_filter_navigator_children", "filter_navigator_children");
add_action(
    "wp_ajax_nopriv_filter_navigator_children",
    "filter_navigator_children"
);

function filter_navigator_children()
{
    $year = isset($_GET["year"]) ? intval($_GET["year"]) : date("Y");
    $parent_id = 29304;

    $query = new WP_Query([
        "post_type" => "page",
        "post_status" => "publish",
        "post_parent" => $parent_id,
        "posts_per_page" => -1,
        "orderby" => "menu_order",
        "order" => "DESC",
        "date_query" => [
            [
                "year" => $year,
            ],
        ],
    ]);

    if ($query->have_posts()) {
        echo "<ul>";
        while ($query->have_posts()) {
            $query->the_post();

            // Get ACF field
            $navigator_url = get_field("navigator_url"); // Returns URL or null if not set

            echo "<li>";
            echo '<a href="' .
                esc_url(get_permalink()) .
                '">' .
                esc_html(get_the_title()) .
                "</a>";

            if ($navigator_url) {
                echo ' <a href="' .
                    esc_url($navigator_url) .
                    '" target="_blank" rel="noopener"> (Download PDF)</a>';
            }

            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No pages found for " . esc_html($year) . ".</p>";
    }

    wp_reset_postdata();
    wp_die(); // Always call wp_die() in AJAX handlers
}

// First latest navigator (row 1)
add_action("elementor/query/latest_navigator_first", function ($query) {
    $parent_id = 29304; // Navigator parent page ID

    $query->set("post_parent", $parent_id);
    $query->set("post_type", "page");
    $query->set("posts_per_page", 1); // Only first latest
    $query->set("orderby", "meta_value_num");
    $query->set("meta_key", "navigator_number");
    $query->set("order", "DESC");
    $query->set("post__not_in", [$parent_id]);
});

// Next three latest navigators (row 2)
add_action("elementor/query/latest_navigator_next_three", function ($query) {
    $parent_id = 29304; // Navigator parent page ID

    $query->set("post_parent", $parent_id);
    $query->set("post_type", "page");
    $query->set("posts_per_page", 3); // Next three
    $query->set("offset", 1); // Skip the first one
    $query->set("orderby", "meta_value_num");
    $query->set("meta_key", "navigator_number");
    $query->set("order", "DESC");
    $query->set("post__not_in", [$parent_id]);
});

add_action("elementor/query/navigator_resources", function ($query) {
    if (is_page()) {
        $query->set("meta_key", "_navigator_page_id");
        $query->set("meta_value", get_the_ID());
        $query->set("post_type", "resources");
        $query->set("posts_per_page", -1);
    }
});

// Add meta box
function resources_navigator_link_metabox()
{
    add_meta_box(
        "resources_navigator_link",
        "Link to Navigator Page",
        "resources_navigator_link_callback",
        "resources",
        "side",
        "default"
    );
}
add_action("add_meta_boxes", "resources_navigator_link_metabox");

function resources_navigator_link_callback($post)
{
    $selected = get_post_meta($post->ID, "_navigator_page_id", true);
    $child_pages = get_pages([
        "child_of" => 29304,
        "sort_column" => "menu_order",
        "sort_order" => "ASC",
    ]);

    echo '<select name="navigator_page_id" style="width:100%">';
    echo '<option value="">— Select Navigator Page —</option>';
    foreach ($child_pages as $page) {
        printf(
            '<option value="%s" %s>%s</option>',
            $page->ID,
            selected($selected, $page->ID, false),
            esc_html($page->post_title)
        );
    }
    echo "</select>";
}

// Save meta
function resources_navigator_link_save($post_id)
{
    if (isset($_POST["navigator_page_id"])) {
        update_post_meta(
            $post_id,
            "_navigator_page_id",
            sanitize_text_field($_POST["navigator_page_id"])
        );
    }
}
add_action("save_post", "resources_navigator_link_save");

add_action("wp_ajax_filter_seaways_children", "filter_seaways_children");
add_action("wp_ajax_nopriv_filter_seaways_children", "filter_seaways_children");

function filter_seaways_children()
{
    $year = isset($_GET["year"]) ? intval($_GET["year"]) : date("Y");
    $parent_id = 29304;

    $query = new WP_Query([
        "post_type" => "page",
        "post_status" => "publish",
        "post_parent" => $parent_id,
        "posts_per_page" => -1,
        "orderby" => "menu_order",
        "order" => "DESC",
        "date_query" => [
            [
                "year" => $year,
            ],
        ],
    ]);

    if ($query->have_posts()) {
        echo "<ul>";
        while ($query->have_posts()) {
            $query->the_post();

            // Get ACF field
            $navigator_url = get_field("seaways_url"); // Returns URL or null if not set

            echo "<li>";
            echo '<a href="' .
                esc_url(get_permalink()) .
                '">' .
                esc_html(get_the_title()) .
                "</a>";

            //if ($navigator_url) {
            //    echo ' <a href="' . esc_url($navigator_url) . '" target="_blank" rel="noopener"> (Download PDF)</a>';
            // }

            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No pages found for " . esc_html($year) . ".</p>";
    }

    wp_reset_postdata();
    wp_die(); // Always call wp_die() in AJAX handlers
}

add_action("admin_footer-post.php", "toggle_acf_fields_by_template");
add_action("admin_footer-post-new.php", "toggle_acf_fields_by_template");
function toggle_acf_fields_by_template()
{
    ?>
    <script>
        (function($) {
            function toggleACFByTemplate(showAlert = false) {
                const templateVal = $('#page_template').val();
                console.log('Selected template:', templateVal);

                // Hide all first
                $('.seaways-fields, .navigator-fields').hide()
                    .find('input, select, textarea').removeAttr('required');

                // Show based on template
                if (templateVal === 'template-parts/seaways-single.php') {
                    $('.seaways-fields').show()
                        .find('input, select, textarea').attr('required', true);
                    if (showAlert) console.log('✅ Seaways fields visible');
                }

                if (templateVal === 'template-parts/navigator-single.php') {
                    $('.navigator-fields').show()
                        .find('input, select, textarea').attr('required', true);
                    if (showAlert) console.log('✅ Navigator fields visible');
                }
            }

            $(document).ready(function() {
                toggleACFByTemplate();
                $('#page_template').on('change', function() {
                    toggleACFByTemplate(true);
                });
            });
        })(jQuery);
    </script>
    <style>
        .seaways-fields,
        .navigator-fields {
            display: none;
        }
    </style>
<?php
}

// First latest Seaways (row 1)
add_action("elementor/query/latest_seaways_first", function ($query) {
    $parent_id = 91698; // Seaways parent page ID

    $query->set("post_parent", $parent_id);
    $query->set("post_type", "page");
    $query->set("posts_per_page", 1); // Only first latest
    $query->set("orderby", "meta_value_num");
    $query->set("meta_key", "seaways_number");
    $query->set("order", "DESC");
    $query->set("post__not_in", [$parent_id]);
});

// Next three latest Seaways (row 2)
add_action("elementor/query/latest_seaways_next_three", function ($query) {
    $parent_id = 91698; // Seaways parent page ID

    $query->set("post_parent", $parent_id);
    $query->set("post_type", "page");
    $query->set("posts_per_page", 3); // Next three
    $query->set("offset", 1); // Skip the first one
    $query->set("orderby", "meta_value_num");
    $query->set("meta_key", "seaways_number");
    $query->set("order", "DESC");
    $query->set("post__not_in", [$parent_id]);
});

/*add_action('init', function () {
    if ( ! current_user_can('manage_options') ) return;

    $attachments = get_posts([
        'post_type'      => 'attachment',
        'posts_per_page' => 500,
        'paged'          => isset($_GET['page_num']) ? intval($_GET['page_num']) : 1,
        'fields'         => 'ids',
    ]);

    require_once ABSPATH . 'wp-admin/includes/image.php';

    foreach ( $attachments as $id ) {
        $file = get_post_meta( $id, '_wp_attached_file', true );

        if ( filter_var( $file, FILTER_VALIDATE_URL ) ) {
            $uploads = wp_upload_dir();
            $relative_path = str_replace( trailingslashit( $uploads['baseurl'] ), '', $file );
            update_post_meta( $id, '_wp_attached_file', $relative_path );
            $file = $relative_path;
        }

        $full_path = get_attached_file( $id );
        if ( file_exists( $full_path ) ) {
            $metadata = wp_generate_attachment_metadata( $id, $full_path );
            wp_update_attachment_metadata( $id, $metadata );
        }
    }

    echo "Batch completed. Next: <a href='?page_num=" . ( isset($_GET['page_num']) ? intval($_GET['page_num']) + 1 : 2 ) . "'>Click here</a>";
    exit;
});*/




/* ---------------------------------------------------------------------------------------------------------------------------------------- */


// Create the Role
// 1. Register the Custom Role
function register_branch_editor_role()
{
    add_role("branch_editor", "Branch Editor", [
        "read" => true,
        "edit_branch" => true,
        "publish_branch" => true,
        "create_posts" => true,
    ]);
}
add_action("init", "register_branch_editor_role");

// 2. Add Admin Dropdown in User Profile to Assign a Post
function branch_editor_custom_field($user)
{
    if (!current_user_can("edit_users")) {
        return;
    }

    $selected_post = get_user_meta($user->ID, "allowed_branch_post", true);
    $posts = get_posts([
        "post_type" => "branch",
        "posts_per_page" => -1,
        "post_status" => "publish",
    ]);
    ?>
    <h3>Branch Edit Access</h3>
    <table class="form-table">
        <tr>
            <th><label for="allowed_branch_post">Assign a Branch</label></th>
            <td>
                <select name="allowed_branch_post" id="allowed_branch_post">
                    <option value="">— None —</option>
                    <?php foreach ($posts as $post): ?>
                        <option value="<?php echo esc_attr(
                            $post->ID
                        ); ?>" <?php selected($selected_post, $post->ID); ?>>
                            <?php echo esc_html($post->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Only this user can edit this post from the front end.</p>
            </td>
        </tr>
    </table>
<?php
}
add_action("show_user_profile", "branch_editor_custom_field");
add_action("edit_user_profile", "branch_editor_custom_field");

function save_branch_editor_custom_field($user_id)
{
    if (!current_user_can("edit_users")) {
        return;
    }
    update_user_meta(
        $user_id,
        "allowed_branch_post",
        intval($_POST["allowed_branch_post"])
    );
}
add_action("personal_options_update", "save_branch_editor_custom_field");
add_action("edit_user_profile_update", "save_branch_editor_custom_field");

// 3. Unified Front-End Form: Create or Edit
/*add_shortcode("branch_editor_form", function () {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access this form.</p><p><br><a class="wploginbtnoomi" href="' .wp_login_url(get_the_permalink()) .'">Log in here</a></p>';
    }
   
    $user = wp_get_current_user();
    
    print_r( $user->roles, true );
    if (!in_array("branch_editor", $user->roles)) {
        return "You do not have permission to access this form.";
    }

    $assigned_post_id = get_user_meta($user->ID, "allowed_branch_post", true);

    // Only allow access if a valid post is assigned
    if (!$assigned_post_id || !get_post_status($assigned_post_id)) {
        return "<p>You do not have any assigned post to edit.</p>";
    }

    $post = get_post($assigned_post_id);

    // Handle form submission
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        check_admin_referer("save_branch_post")
    ) {
        $title = sanitize_text_field($_POST["post_title"]);
        $content = wp_kses_post($_POST["post_content"]);

        wp_update_post([
            "ID" => $post->ID,
            "post_title" => esc_attr($post->post_title),
            "post_content" => $content,
        ]);

        echo '<div class="updated"><p>Post updated successfully!</p></div>';
    }

    $title_val = esc_attr($post->post_title);
    $content_val = esc_textarea($post->post_content);

    ob_start();
    ?>
    <form method="post">
        <?php wp_nonce_field("save_branch_post"); ?>
        <p><input disabled type="text" name="post_title" value="<?php echo $title_val; ?>" placeholder="Post Title" style="width: 100%;" required></p>
        <p><textarea id="post_content" name="post_content" rows="10" style="width: 100%;" placeholder="Post Content"><?php echo $content_val; ?></textarea></p>
        <p><input class="signinCSS oomi-join" type="submit" value="Update"></p>
        <p><a class="signinCSS oomi-join" href="<?php echo wp_logout_url(home_url()); ?>">Logout</a></p>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (!sessionStorage.getItem("pageRefreshed")) {
                sessionStorage.setItem("pageRefreshed", "true");
                window.location.reload(true);
            } else {
                sessionStorage.removeItem("pageRefreshed");
            }
        });
    </script>
    <?php return ob_get_clean();
});*/

add_shortcode("branch_editor_form", function () {
    //$postid = get_the_ID();
    if (!is_user_logged_in()) {
        return '<p>Please log in to access this form.</p><p><br><a class="wploginbtnoomi" href="' . wp_login_url(get_the_permalink()) . '">Log in here</a></p>';
    }
    $user = wp_get_current_user();
    if (!in_array("branch_editor", $user->roles)) {
        return "You do not have permission to access this form.";
    }
    $assigned_post_id = get_user_meta($user->ID, "allowed_branch_post", true);
    if (!$assigned_post_id || !get_post_status($assigned_post_id)) {
        return "<p>You do not have any assigned post to edit.</p>";
    }
    $post = get_post($assigned_post_id);
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && check_admin_referer("save_branch_post")) {
        $title   = sanitize_text_field($_POST["post_title"]);
        $content = wp_kses_post($_POST["post_content"]);
        // Update post
        wp_update_post([
            "ID"           => $assigned_post_id,
            "post_title"   => $title,
            "post_content" => $content,
        ]);
        // Save sidebar repeater
        if (isset($_POST["sidebar_boxes"]) && is_array($_POST["sidebar_boxes"])) {
            $clean_boxes = [];
            foreach ($_POST["sidebar_boxes"] as $box) {
                if (!empty($box["sidebar_item_title"]) || !empty($box["sidebar_item_details"])) {
                    $clean_boxes[] = [
                        "sidebar_item_title"   => sanitize_text_field($box["sidebar_item_title"]),
                        "sidebar_item_details" => wp_kses_post($box["sidebar_item_details"]), // allow HTML
                    ];
                }
            }
            // If using ACF repeater field
            if (function_exists('update_field')) {
                update_field('sidebar_boxes', $clean_boxes, $assigned_post_id);
            } else {
                // fallback if ACF is not available
                update_post_meta($assigned_post_id, "sidebar_boxes", $clean_boxes);
            }
        }
        echo '<div class="updated"><p>Post updated successfully!</p></div>';
    }
    $title_val   = esc_attr($post->post_title);
    $content_val = $post->post_content; // raw for TinyMCE
    $sidebar_boxes = get_post_meta($assigned_post_id, 'sidebar_boxes', true);
    if (!is_array($sidebar_boxes)) {
        $sidebar_boxes = [];
    }
    ob_start(); ?>
    <form method="post" class="branchsubmitform">
        <?php wp_nonce_field("save_branch_post"); ?>
        <!-- Title -->
        <p><input type="text" name="post_title" value="<?php echo $title_val; ?>" placeholder="Post Title" style="width: 100%;" required></p>
        <!-- Content -->
        <p><textarea id="post_content" name="post_content" rows="10" style="width: 100%;" placeholder="Post Content"><?php echo esc_textarea(wpautop($content_val)); ?></textarea></p>
        <!-- Sidebar Boxes Repeater -->
        <h3>Sidebar Boxes</h3>
        <div id="sidebar-repeater">
            <?php if (have_rows('sidebar_boxes', $assigned_post_id)) : ?>
                <?php $index = 0; ?>
                <?php while (have_rows('sidebar_boxes', $assigned_post_id)) : the_row(); ?>
                    <?php $title = get_sub_field('sidebar_item_title');
                    $details = get_sub_field('sidebar_item_details'); ?>
                    <div class="sidebar-box">
                        <!-- Title -->
                        <input type="text"
                            name="sidebar_boxes[<?php echo $index; ?>][sidebar_item_title]"
                            value="<?php echo esc_attr($title); ?>"
                            placeholder="Sidebar Item Title"
                            style="width:100%; margin-top:15px;" /><br>
                        <!-- Details -->
                        <textarea id="post_sidbarbox" name="sidebar_boxes[<?php echo $index; ?>][sidebar_item_details]"
                            placeholder="Sidebar Item Details"
                            style="width:100%; margin-top:15px;margin-bottom:15px; height:180px;"><?php echo esc_textarea($details); ?></textarea><br>
                        <!--button type="button" class="remove-box">Remove</button-->
                    </div>
                    <?php $index++; ?>
                <?php endwhile; ?>
            <?php else : ?>
                <!-- If no saved fields, show one empty repeater row -->
                <div class="sidebar-box">
                    <input type="text" name="sidebar_boxes[0][sidebar_item_title]" placeholder="Sidebar Item Title" style="width:100%; margin-top:15px;" /><br>
                    <textarea id="post_sidbarbox" name="sidebar_boxes[0][sidebar_item_details]" placeholder="Sidebar Item Details"
                        style="width:100%; margin-top:15px;margin-bottom:15px; height:180px;"></textarea><br>
                    <button type="button" class="remove-box">Remove</button>
                </div>
            <?php endif; ?>
        </div><br />
        <button type="button" id="add-sidebar-box">+ Add Sidebar Box</button><br />
        <!-- Submit -->
        <p><br><input class="signinCSS oomi-join" type="submit" value="Update"></p>
        <p><a class="signinCSS oomi-join" href="<?php echo wp_logout_url(home_url()); ?>">Logout</a></p>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let repeater = document.getElementById("sidebar-repeater");
            let addBtn = document.getElementById("add-sidebar-box");
            addBtn.addEventListener("click", function() {
                let count = repeater.querySelectorAll(".sidebar-box").length;
                if (count >= 6) {
                    alert("You can add a maximum of 6 sidebar boxes.");
                    return;
                }
                let index = count;
                let div = document.createElement("div");
                div.className = "sidebar-box";
                div.innerHTML = `<input type="text" name="sidebar_boxes[${index}][sidebar_item_title]" 
                placeholder="Sidebar Item Title" style="width:100%; margin-top:15px;" /><br>
                <textarea id="post_sidbarbox" name="sidebar_boxes[${index}][sidebar_item_details]" 
                placeholder="Sidebar Item Details" style="width:100%; margin-top:15px;margin-bottom:15px; height:180px;"></textarea><br>
                <button type="button" class="remove-box">Remove</button><br/>`;
                repeater.appendChild(div);
                div.querySelector(".remove-box").addEventListener("click", function() {
                    div.remove();
                });
            });
            document.querySelectorAll(".remove-box").forEach(btn => {
                btn.addEventListener("click", function() {
                    btn.parentElement.remove();
                });
            });
        });
    </script>
<?php return ob_get_clean();
});

function load_tinymce_for_branch_editor()
{
    if (is_page("branch-edit-page-form/")) {
        // <-- Change if needed
        wp_enqueue_script(
            "tinymce-js",
            "https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js",
            [],
            null,
            true
        );
        add_action("wp_footer", function () {
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof tinymce !== "undefined") {
                        tinymce.remove('#post_content'); // Clean up if already exists
                        tinymce.init({
                            selector: '#post_content',
                            height: 500,
                            menubar: true,
                            promotion: false, // Hide premium upgrade banner
                            plugins: [
                                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
                                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                'insertdatetime', 'media', 'table', 'help', 'wordcount'
                            ],
                            toolbar: 'undo redo | formatselect | bold italic backcolor | ' +
                                'alignleft aligncenter alignright alignjustify | ' +
                                'bullist numlist outdent indent | removeformat | help | ' +
                                'code fullscreen image link media table',
                            content_css: '//www.tiny.cloud/css/codepen.min.css'
                        });
                    }
                    if (typeof tinymce !== "undefined") {
                        tinymce.remove('#post_sidbarbox'); // Clean up if already exists
                        tinymce.init({
                            selector: '#post_sidbarbox',
                            height: 180,
                            menubar: false,
                            promotion: false, // Hide premium upgrade banner
                            plugins: [
                                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
                                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                'insertdatetime', 'media', 'table', 'help', 'wordcount'
                            ],
                            toolbar: 'undo redo | formatselect | bold italic backcolor | ' +
                                'alignleft aligncenter alignright alignjustify | ' +
                                'bullist numlist outdent indent | removeformat | help | ' +
                                'code fullscreen image link media table',
                            content_css: '//www.tiny.cloud/css/codepen.min.css'
                        });
                    }
                });
            </script><?php
        });
    }
}
add_action("wp_enqueue_scripts", "load_tinymce_for_branch_editor");

// Force redirect after login for branch_editor
add_action(
    "wp_login",
    function ($user_login, $user) {
        if (in_array("branch_editor", (array) $user->roles)) {
            wp_safe_redirect(home_url() . "/branch-edit-page-form/");
            exit();
        }
    },
    10,
    2
);

// Restrict backend access for branch_editor
add_action("admin_init", function () {
    if (current_user_can("branch_editor") && !defined("DOING_AJAX")) {
        wp_redirect(home_url() . "/branch-edit-page-form/"); // or change to same branch page
        exit();
    }
});

// Hide admin bar
add_action("after_setup_theme", function () {
    if (current_user_can("branch_editor")) {
        show_admin_bar(false);
    }
});

add_action('init', function() {
    // Only run for logged-in admin (avoid triggering on public visits)
    if ( ! current_user_can('manage_options') ) {
        return;
    }

    $page_id = 2230;
    $page = get_post($page_id);

    if ($page) {
        $old_content = $page->post_content;

        // Replace wrong /media/ URLs with correct uploads path
        $new_content = str_replace(
            'src="/media/',
            'src="https://ni.oomi-wp-staging.com/wp-content/uploads/media/',
            $old_content
        );

        // Only update if something actually changed
        if ($new_content !== $old_content) {
            wp_update_post([
                'ID'           => $page_id,
                'post_content' => $new_content,
            ]);
        }
    }
});

add_filter( 'the_excerpt', function( $excerpt ) {
    return wpautop( $excerpt ); // preserve <p> and <br>
}, 99 );
// Shortcode to display excerpt with line breaks and HTML
function ss_formatted_excerpt_shortcode() {
    global $post;
    if ( ! $post ) return '';

    $excerpt = $post->post_excerpt;

    if ( empty( $excerpt ) ) {
        $excerpt = wp_trim_words( $post->post_content, 55, '...' );
    }

    return wpautop( $excerpt ); // convert newlines into <p>/<br>
}
add_shortcode( 'formatted_excerpt', 'ss_formatted_excerpt_shortcode' );


// Custom query for Elementor Loop Grid (events)
add_action('elementor/query/event_loop_first', function($query) {
    $query->set('post_type', 'events');

    // Meta query for Featured + StartDate
    $meta_query = [
        'relation' => 'AND',
        'featured_clause' => [
            'key'     => 'Event_Featured',
            'compare' => 'EXISTS',
        ],
        'date_clause' => [
            'key'     => 'Event_StartDate',
            'compare' => 'EXISTS',
            'type'    => 'DATE',
        ],
    ];
    $query->set('meta_query', $meta_query);

    // Featured first, then by date
    $query->set('orderby', [
        'featured_clause' => 'DESC',
        'date_clause'     => 'DESC',
    ]);

    // Only 1 post
    $query->set('posts_per_page', 1);
});


add_action('elementor/query/event_loop_rest', function($query) {
    $query->set('post_type', 'events');

    $meta_query = [
        'relation' => 'AND',
        'featured_clause' => [
            'key'     => 'Event_Featured',
            'compare' => 'EXISTS',
        ],
        'date_clause' => [
            'key'     => 'Event_StartDate',
            'compare' => 'EXISTS',
            'type'    => 'DATE',
        ],
    ];
    $query->set('meta_query', $meta_query);

    $query->set('orderby', [
        'featured_clause' => 'DESC',
        'date_clause'     => 'DESC',
    ]);

    // Skip the first one
    $query->set('offset', 1);
    $query->set('posts_per_page', 2); // or set a limit if needed
});


add_action('admin_head', function() {
    global $typenow;

    // Only apply on "events", "courses", "products"
    if (isset($typenow) && in_array($typenow, ['events', 'courses', 'products'])) {
        ?>
        <style>
            /* ---------------- EXISTING LOCKS FOR EVENTS + COURSES ---------------- */
            [data-name="Event_AppClosingDate"].acf-field,
            [data-name="EVENT_Description"].acf-field,
            [data-name="COURSE_SETUP_BriefDescription"].acf-field,
            [data-name="Event_StartDate"].acf-field,
            [data-name="Event_StartTime"].acf-field,
            [data-name="Event_EndDate"].acf-field,
            [data-name="Event_EndTime"].acf-field,
            [data-name="event_rate"].acf-field,
            [data-name="Event_EventProvider"].acf-field,
            [data-name="Event_VenueName"].acf-field,
            [data-name="Event_Location"].acf-field,
            [data-name="Event_MainContactUserName"].acf-field,
            [data-name="Event_CoordinatorPhoneNumber"].acf-field,
            [data-name="Event_EventBanner"].acf-field,
            [data-name="Event_Overview"].acf-field,
            [data-name="Event_Featured"].acf-field,
            [data-name="EVENT_SpeakerName"].acf-field,
            [data-name="Event_MemberOnly"].acf-field,
            [data-name="EVENT_SPEAKER"].acf-field,
            [data-name="PRODUCT_PRICING"].acf-field {
                pointer-events: none !important;
                opacity: 0.6;
            }

            /* 🔒 Lock Events taxonomies */
            #product-categoriesdiv,
            #event-categoriesdiv,
            #event-typesdiv,
            #product-typesdiv,
            #event-regionsdiv,
            #product-regionsdiv,
            #product-branchesdiv,
            #event-branchesdiv,
            #pageparentdiv,
            #event-formatsdiv {
                pointer-events: none !important;
                opacity: 0.6;
            }

            /* 🔒 Lock Campus taxonomies */
            #course-categoriesdiv,
            #course-typesdiv,
            #postimagediv,
            #pageparentdiv {
                pointer-events: none !important;
                opacity: 0.6;
            }

            /* 🔒 Lock Featured Image box */
            #postimagediv {
                pointer-events: none !important;
                opacity: 0.6;
            }

            /* 🔒 Lock Title field */
            #titlewrap input#title {
                pointer-events: none !important;
                background: #f5f5f5 !important;
            }

            /* 🔒 Lock Slug editor */
            #edit-slug-box {
                pointer-events: none !important;
                opacity: 0.6;
            }

            /* 🚫 Hide Publish/Update */
            #submitdiv { display: none !important; }

            /* 🚫 Hide Excerpt */
            #postexcerpt { display: none !important; }

            /* 🚫 Hide Content editor */
            #postdivrich { display: none !important; }

            /* 🚫 Hide "Add New" buttons */
            .post-type-events .page-title-action,
            .post-type-courses .page-title-action {
                display: none !important;
            }

            /* 🚫 Hide row actions */
            .post-type-events .row-actions .inline,
            .post-type-courses .row-actions .inline,
            .post-type-events .row-actions .trash,
            .post-type-courses .row-actions .trash,
            .post-type-events .row-actions .clone,
            .post-type-courses .row-actions .clone,
            .post-type-events .row-actions .duplicate,
            .post-type-courses .row-actions .duplicate {
                display: none !important;
            }

            /* 🚫 Hide Event taxonomies in sidebar */
            #menu-posts-events ul li a[href*="taxonomy=event-regions"],
            #menu-posts-events ul li a[href*="taxonomy=event-types"],
            #menu-posts-events ul li a[href*="taxonomy=event-branches"] {
                display: none !important;
            }

            /* 🚫 Hide Course taxonomies in sidebar */
            #menu-posts-courses ul li a[href*="taxonomy=course-categories"],
            #menu-posts-courses ul li a[href*="taxonomy=course-types"],
            #menu-posts-courses ul li a[href*="taxonomy=event-branches"] {
                display: none !important;
            }

            /* 🚫 Hide Campus taxonomies in sidebar */
            #menu-posts-courses ul li a[href*="taxonomy=course-categories"],
            #menu-posts-campus ul li a[href*="taxonomy=course-types"] {
                display: none !important;
            }

            /* 🚫 Hide Add New links */
            #menu-posts-events .wp-submenu a[href*="post-new.php?post_type=events"],
            #menu-posts-courses .wp-submenu a[href*="post-new.php?post_type=courses"] {
                display: none !important;
            }

            /* List table: disable event/course taxonomy links */
            body.post-type-events td.column-taxonomy-event-categories a,
            body.post-type-events td.column-taxonomy-event-types a {
                pointer-events: none !important;
                text-decoration: none !important;
                opacity: .75;
                cursor: default;
            }

            body.post-type-courses td.column-taxonomy-course-categories a,
            body.post-type-courses td.column-taxonomy-course-types a {
                pointer-events: none !important;
                text-decoration: none !important;
                opacity: .75;
                cursor: default;
            }

            body.post-type-products td.column-taxonomy-product-categories,
            body.post-type-products td.column-taxonomy-product-types,
            body.post-type-events td.column-taxonomy-event-categories,
            body.post-type-events td.column-taxonomy-event-formats,
            body.post-type-courses td.column-taxonomy-course-categories,
            body.post-type-courses td.column-taxonomy-course-types {
                pointer-events: none !important;
            }

            /* Hide taxonomy fields from Quick/Bulk Edit */
            body.post-type-events .inline-edit-col .taxonomy-event-categories,
            body.post-type-events .inline-edit-col .taxonomy-event-formats,
            body.post-type-products .inline-edit-col .taxonomy-product-categories,
            body.post-type-products .inline-edit-col .taxonomy-product-types,
            body.post-type-courses .inline-edit-col .taxonomy-course-categories,
            body.post-type-courses .inline-edit-col .taxonomy-course-types {
                display: none !important;
            }

            /* ---------------- NEW: LOCKS FOR PRODUCTS ---------------- */

            /* 🚫 Hide Products taxonomies in sidebar */
            #menu-posts-products ul li a[href*="taxonomy=product-categories"],
            #menu-posts-products ul li a[href*="taxonomy=product-types"] {
                display: none !important;
            }

            /* 🚫 Hide Add New under Products */
            #menu-posts-products .wp-submenu a[href*="post-new.php?post_type=products"] {
                display: none !important;
            }

            /* List table: disable product taxonomy links */
            body.post-type-products td.column-taxonomy-product-categories a,
            body.post-type-products td.column-taxonomy-product-types a {
                pointer-events: none !important;
                text-decoration: none !important;
                opacity: .75;
                cursor: default;
            }

            body.post-type-products td.column-taxonomy-product-categories,
            body.post-type-products td.column-taxonomy-product-types {
                pointer-events: none !important;
            }

            /* Hide taxonomy fields from Quick/Bulk Edit */
            body.post-type-products .inline-edit-col .taxonomy-product-categories,
            body.post-type-products .inline-edit-col .taxonomy-product-types {
                display: none !important;
            }
        </style>
        <?php
    }
});
