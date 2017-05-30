<?php

$elrError = function ($message, $subtitle = '', $title = '') {
    $title = $title ?: __('ELR &rsaquo; Error', 'elr');
    $footer = '';
    $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
    wp_die($message, $title);
};

// Ensure dependencies are loaded

if (!file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    $elrError(
        __('You must run <code>composer install</code> from the ELR directory.', 'elr'),
        __('Autoloader not found.', 'elr')
    );
}

require_once $composer;

// define Timber
$timber = new \Timber\Timber();

if (! class_exists('Timber')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' .
        esc_url(admin_url('plugins.php#timber')) . '">' . esc_url(admin_url('plugins.php')) . '</a></p></div>';
    });
    return;
}

// Define Constants

define('THEMEROOT', get_stylesheet_directory_uri());
define('IMAGES', THEMEROOT . '/assets/images/compressed');
define('SCRIPTS', THEMEROOT . '/assets/js');
define('STYLES', THEMEROOT . '/assets/css');

// Set Up Content Width Value

if (!isset($content_width)) {
    $content_width = 1300;
}

// Make theme available for translation
$lang_dir = THEMEROOT . '/languages';
load_theme_textdomain('elr', $lang_dir);

update_option('uploads_use_yearmonth_folders', 0);

$timber::$dirname = ['views'];

class Site extends \TimberSite
{
    private $fonts;
    public function __construct()
    {
        $fonts = 'https://fonts.googleapis.com/css?family='.
            'Roboto:700,500,400,300, 200|Raleway:300italic,400,300|Roboto+Slab:300,400,500';

        $admin = new \WpAdmin\Admin;
        $setup = new \WpSetup\Setup;
        $security = new \WpSecurity\Security;
        $builder = new \WpCustomPosts\CptBuilder;
        $shortcodes = New \WpShortcodes\Shortcodes;

        $setup->registerMenus([
            'main-nav',
            'footer-nav',
            'social-nav'
        ]);

        $shortcodes->addShortcodes();

        // $builder->createPostType([
        //     'singular_name' => 'project',
        //     'taxonomies' => [],
        //     'custom_taxonomies' => [
        //         [
        //             'singular_name' => 'technology',
        //             'plural_name' => 'technologies',
        //             'hierarchical' => false
        //         ]
        //     ],
        //     'fields' => [
        //         [
        //             'id' => '_project_date',
        //             'label' => 'Date',
        //         ]
        //     ]
        // ]);

        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('menus');
        add_filter('timber_context', [$this, 'addToContext']);
        add_filter('get_twig', [$this, 'addToTwig']);
        add_filter('manage_posts_columns', [$admin, 'thumbnailColumn'], 5);
        add_filter('user_can_richedit', [$this, 'disableVisualEditor'], 50);
        add_filter('the_generator', [$security, 'removeWpVersion']);
        add_action('wp_enqueue_scripts', [$this, 'loadScripts']);
        add_action('wp_print_scripts', [$this, 'themeQueueJs']);
        add_action('after_setup_theme', [$setup, 'themeSlugSetup']);
        add_action('manage_posts_custom_column', [$admin, 'thumbnailCustomColumn'], 5, 2);
        add_action('dashboard_glance_items', [$admin, 'dashboardCpts']);
        add_action('admin_menu', [$this, 'themeMenu']);
        add_action('widgets_init', function () use ($setup) {
            $setup->registerSidebars(['sidebar']);
        });
        parent::__construct();
    }

    public function addToContext($context)
    {
        $context['main_nav'] = new \TimberMenu('main-nav');
        $context['social_nav'] = new \TimberMenu('social-nav');
        $context['footer_nav'] = new \TimberMenu('footer-nav');
        $context['site'] = $this;
        $context['sidebar'] = Timber::get_sidebar('sidebar.php');
        return $context;
    }

    public function addToTwig($twig)
    {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension(new \Twig_Extension_StringLoader());
        $twig->addFilter(new Twig_SimpleFilter('human_diff', function($string) {
            return human_time_diff(strtotime('01/01/' . $string), current_time('timestamp'));
        }));

        return $twig;
    }

    public function loadScripts()
    {
        wp_register_script('main', SCRIPTS . '/main.min.js', ['jquery'], null, true);
        wp_register_script('font-awesome', 'https://use.fontawesome.com/185c4dbad0.js', [], null);
        wp_register_style('style', STYLES . '/custom.css', [], null, 'screen');
        wp_register_style('fonts', $this->fonts, [], null, 'screen');

        wp_enqueue_script('main');
        wp_enqueue_script('font-awesome');
        wp_enqueue_style('fonts');
        wp_enqueue_style('style');
    }

    public function themeQueueJs()
    {
        if ((!is_admin()) && is_single() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }

    public function disableVisualEditor()
    {
        # add logic here if you want to permit it selectively
        return false;
    }

    public function breadcrumbs()
    {
        if (function_exists('yoast_breadcrumb')) {
            yoast_breadcrumb('<p id="breadcrumbs" class="breadcrumbs">', '</p>');
        }

        return;
    }

    public function themeMenu()
    {
        $settings = new \WpThemeOptions\ThemeOptions;
        $options = [
            'title' => 'Theme Settings',
            'subpages' => [
                [
                    'id' => 'general_options',
                    'title' => 'General Options',
                    'description' => 'These are some general options for your theme',
                    'fields' => [
                        [
                            'id' => 'name',
                            'instructions' => 'Your first name and last name'
                        ],
                        [
                            'id' => 'email',
                            'input_type' => 'email',
                            'instructions' => 'your preferred email'
                        ],
                        [
                            'id' => 'phone',
                            'input_type' => 'tel',
                            'instructions' => 'your preferred phone'
                        ]
                    ]
                ]
            ]
        ];

        $settings->initializeThemeSettings($options);
    }
}

new Site();
