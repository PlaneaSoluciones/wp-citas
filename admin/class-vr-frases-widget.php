<?php
/**
 * VR-Frases Sidebar Widget Class Implementation
 *
 * WordPress Widget API implementation for displaying random quotes
 * in sidebar areas with administrative configuration.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_Widget' ) ) {
	return;
}

/**
 * WordPress widget class for dynamic quote display.
 *
 * Displays random quotes in sidebar areas with administrative
 * configuration and security features.
 *
 * @since 4.1.0
 * @package VR_Frases
 * @author Vicente Ruiz Gálvez
 */
class VR_Frases_Widget extends WP_Widget {
	/**
	 * Initialize widget with WordPress Widget API.
	 *
	 * Sets up widget ID, name, and description for admin and frontend.
	 *
	 * @since 4.1.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'VR_Frases_Widget',
			__( 'VR-frases', 'vr-frases' ),
			array(
				'description' => __( 'This widget displays a random phrase on every page reload.', 'vr-frases' ),
			)
		);
	}

	/**
	 * Render widget content for frontend display.
	 *
	 * Outputs widget with wrapper elements, title, and random quote.
	 *
	 * @since 4.1.0
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $instance['title'] ) ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . wp_kses_post( $args['after_title'] );
		}

		echo '<fieldset id="box" style="height: auto; border: solid 1px; padding: 10px;">';
		echo '<ul><li>' . wp_kses_post( vr_frases_random_frase() ) . '</li></ul>';
		echo '</fieldset>';

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Generate administrative configuration form.
	 *
	 * Creates widget configuration interface with title input
	 * and security nonce protection.
	 *
	 * @since 4.1.0
	 * @param array $instance Previously saved widget configuration.
	 * @return void
	 */
	public function form( $instance ) {
		$title       = ! empty( $instance['title'] ) ? esc_html( $instance['title'] ) : '';
		$nonce_field = esc_attr( $this->get_field_id( '_nonce' ) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vr-frases' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<input type="hidden" name="<?php echo esc_attr( $nonce_field ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'vr_frases_widget_nonce' ) ); ?>">
		<?php
	}

	/**
	 * Process and validate widget configuration updates.
	 *
	 * Handles widget settings with nonce verification and sanitization.
	 *
	 * @since 4.1.0
	 * @param array $new_instance New widget settings from admin form.
	 * @param array $old_instance Previous widget settings from database.
	 * @return array Validated and sanitized widget settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$nonce_field = $this->get_field_id( '_nonce' );
		if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), 'vr_frases_widget_nonce' ) ) {
			return $old_instance;
		}

		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
