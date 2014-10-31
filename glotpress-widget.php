<?php

class GlotPress_Widget_Container {

	public function __construct() {
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	public function register_widget() {
		register_widget( 'GlotPress_Widget' );
	}

	public static function get_data_from_user( $url, $username ) {
		$url = trailingslashit( $url ) . 'api/profile/' . $username;

		$response = wp_remote_get( $url );

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );

			return $data;
		}

		return false;
	}

}

new GlotPress_Widget_Container;


class GlotPress_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'glotpress_widget', // Base ID
			__( 'GlotPress Widget', 'glotpress_widget' ), // Name
			array( 'description' => __( 'A widget for sites like translate.wordpress.org', 'glotpress_widget' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		if ( ! $instance['url'] || ! $instance['username'] ) {
			return false;
		}

		$data = GlotPress_Widget_Container::get_data_from_user( $instance['url'], $instance['username'] );

		if ( ! $data ) {
			return false;
		}


		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		echo '<p>';
		vprintf(
			_n(
				'%s is a polyglot who contributes to %s',
				'%s is a polyglot who knows %s but also knows %s.',
				count( (array) $data->locales ),
				'glotpress_widget'
			),
			array_merge(
				array( $instance['username'] ),
				array_keys( (array) $data->locales )
			)
		);
		echo '</p>';

		echo '<p>';
		echo _e( 'Recent projects', 'glotpress_widget' );
		echo '<ul>';

		foreach ( $data->recent_projects as $project ) {
			$url = esc_url( $instance['url'] . $project->project_url );
			echo '<li><a href="' . $url . '">' . $project->set_name . '</a></li>';
		}

		echo '</ul>';

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title    = $instance[ 'title' ];
			$username = $instance[ 'username' ];
			$url      = $instance[ 'url' ];
		}
		else {
			$title    = __( 'New title', 'glotpress_widget' );
			$username = '';
			$url      = 'https://translate.wordpress.org';
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'glotpress_widget' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Username:', 'glotpress_widget' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo esc_attr( $username ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php _e( 'URL:', 'glotpress_widget' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php echo esc_url( $url ); ?>">
		</p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']    = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['username'] = ( ! empty( $new_instance['username'] ) ) ? strip_tags( $new_instance['username'] ) : '';
		$instance['url']      = ( ! empty( $new_instance['url'] ) ) ? esc_url_raw( $new_instance['url'] ) : 'https://translate.wordpress.org';

		return $instance;
	}

}