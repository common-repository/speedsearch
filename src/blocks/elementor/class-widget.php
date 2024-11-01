<?php
/**
 * Elementor widget class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Elementor widget.
 */
class Widget extends Widget_Base {

    /**
     * Shortcode name.
     *
     * @var string
     */
    protected $shortcode_name;

    /**
     * Shortcode data.
     *
     * @var array
     */
    protected $shortcode_data;

    /**
     * Sets up a new instance.
     *
     * @throws Exception Exception.
     *
     * @param array      $data           Widget data. Default is an empty array.
     * @param array|null $args           Optional. Widget default arguments. Default is null.
     * @param string     $shortcode_name Shortcode name.
     */
    public function __construct( $data = [], $args = null, $shortcode_name = false ) {
        if ( false === $shortcode_name ) {
            $shortcode_name = preg_replace( '@^speedsearch_@', '', $data['widgetType'] );
        }

        $this->shortcode_name = $shortcode_name;
        $this->shortcode_data = SpeedSearch::$shortcodes->data[ $this->shortcode_name ];

        $this->full_name  = 'speedsearch_' . $this->shortcode_name;
        $this->full_title = 'SpeedSearch ' . $this->shortcode_data['title'];

        parent::__construct( $data, $args );
    }

    /**
     * Get widget name.
     *
     * Retrieve counter widget name.
     *
     * @return string Widget name.
     */
    public function get_name() {
        return $this->full_name;
    }

    /**
     * Get widget title.
     *
     * Retrieve counter widget title.
     *
     * @return string Widget title.
     */
    public function get_title() {
        return $this->full_title;
    }

    /**
     * Get widget icon.
     *
     * Retrieve counter widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-site-search';
    }

    /**
     * Retrieve the list of scripts the counter widget depended on.
     *
     * Used to set scripts dependencies required to run the widget.
     *
     * @return array Widget scripts dependencies.
     */
    public function get_script_depends() {
        return [];
    }

    /**
     * Get widget keywords.
     *
     * Retrieve the list of keywords the widget belongs to.
     *
     * @return array Widget keywords.
     */
    public function get_keywords() {
        return [ 'speedsearch' ];
    }

    /**
     * Register counter widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'section_' . $this->full_name,
            [
                'label' => $this->full_title,
            ]
        );

        foreach ( $this->shortcode_data['arguments'] as $argument_name => $argument_data ) {
            switch ( $argument_data['type'] ) {
                case 'string':
                    $this->add_control(
                        $argument_name,
                        [
                            'label'   => $argument_data['label'],
                            'type'    => Controls_Manager::TEXT,
                            'default' => $argument_data['default'],
                        ]
                    );

                    break;
                case 'bool':
                    $this->add_control(
                        $argument_name,
                        [
                            'label'   => $argument_data['label'],
                            'type'    => Controls_Manager::SWITCHER,
                            'default' => $argument_data['default'] ? 'yes' : '',
                        ]
                    );

                    break;
                case 'number':
                    $this->add_control(
                        $argument_name,
                        [
                            'label'   => $argument_data['label'],
                            'type'    => Controls_Manager::NUMBER,
                            'default' => $argument_data['default'],
                            'min'     => $argument_data['min'],
                            'max'     => $argument_data['max'],
                            'step'    => 1,
                        ]
                    );

                    break;
                case 'select':
                    $this->add_control(
                        $argument_name,
                        [
                            'label'   => $argument_data['label'],
                            'type'    => Controls_Manager::SELECT,
                            'default' => $argument_data['default'],
                            'options' => $argument_data['options'],
                        ]
                    );

                    break;
            }
        }

        $this->end_controls_section();
    }

    /**
     * Render counter widget output in the editor.
     *
     * Written as a Backbone JavaScript template and used to generate the live preview.
     */
    protected function content_template() {}

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return [ 'speedsearch' ];
    }

    /**
     * Render counter widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        echo sanitize_user_field(
            'speedsearch',
            $this->shortcode_data['callback']( $settings ),
            0,
            'display'
        );
    }
}
