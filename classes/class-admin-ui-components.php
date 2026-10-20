<?php
namespace LTW\Order_To_Board_Sync\Admin\UI;

/**
 * Methods for displaying the UI in the admin area
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LTW\Order_To_Board_Sync\Core\Helper;

/**
 * UI Class
 */
class Components {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0.0
	 *
	 */
	public function __construct() {
        
    }

	/**
	 * Create HTML for the switch field: input type = checkbox
	 *
	 * @param	array	$args	{
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$note
	 *		@type	boolean	$checked
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function btn_save( array $args = [] ): string {
		$defaults = [
			'id' => 'btn_save',
			'name' => 'btn_save',
			'value' => __( 'Save', 'ltw-order-to-board-sync' ),
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		return '<input type="submit" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $args['value'] ) . '">';
    }

    /**
	 * Create HTML for the switch field: input type = checkbox
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$note
	 *		@type	boolean	$checked
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function switch( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'note' => '',
			'checked' => false,
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		// Let's check if switch is checked or not
		$switch_checked = ! empty( $checked ) ? 'checked' : '';

		$switch = '<div>';

		$switch .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';

		if ( $note ) {
			$switch .= '<p class="switch-note">' . esc_html( $note ) . '</p>';
		}

        $switch .= '<input type="checkbox" id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" role="switch" ' . $switch_checked . '>';

		$switch .= '</div>';

        return $switch;
    }

	/**
	 * Create HTML for the text field: input type = text
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$value
	 *		@type	string	$note
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function text( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'value' => '',
			'note' => '',
			'type' => 'text',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$text = '<div>';

		$text .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>
                <input type="' . esc_attr( $type ) . '" ' . ( $type === 'password' ? 'autocomplete="current-password"' : '' ) . ' id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" value="' . esc_attr( $value ) . '" aria-label="' . esc_attr( $label ) . '">';
        
		if ( $note ) {
			$text .= '<small>' . esc_html( $note ) . '</small>';
		}

		$text .= '</div>';

        return $text;
    }

	/**
	 * Create HTML for accordion field
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$label
	 *		@type	string	$text
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function accordion( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'label' => '',
			'text' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$accordion = '';

		$accordion .= '<details name="' . esc_attr( $section . '-' . $id ) . '">
				<summary>' . esc_html( $label ) . '</summary>
				' . wp_kses_post( $text ) . '
				</details>';

        return $accordion;
    }

	/**
	 * Create HTML for dropdown field.
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	array	$options
	 *		@type	boolean	$selected
	 *		@type	string	$label
	 *		@type	string	$note
	 *		@type	string	$classes
	 *		@type	boolean	$loader
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function select( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'options' => [],
			'selected' => '',
			'label' => '',
			'note' => '',
			'classes' => '',
			'loader' => false,
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$select = '<div class="' . $id . '">';

		// Label is sometimes needed :)
		if ( $label ) {
			// Show loader right after the field label so it depends on label being set.
			$loader_html = ! empty( $loader ) ? '<span class="loader" aria-busy="true" data-parent="' . $id . '">' . __( 'Loading data..', 'ltw-order-to-board-sync' ) . '</span>' : '';

			$select .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong>' . $loader_html . '</label>';
		}

		// If $classes is not empty then add them to the select field
		$css_classes = ! empty( $classes ) ? 'class="' . esc_attr( $classes ) . '"' : '';

		// Build the select field
		$select .= '<select id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" ' . $css_classes . ' data-selected="' . esc_attr( $selected ) . '" aria-label="' . esc_attr( $label ) . '">';

		foreach ( $options as $key => $option ) {
			// Check if this option should be selected
			$is_selected = '';
			
			// First check if option has its own selected property
			if ( isset( $option['selected'] ) && $option['selected'] ) {
				$is_selected = 'selected';
			} elseif ( ! empty( $selected ) && $selected === $option['value'] ) {
				// Otherwise check if this option matches the main $selected value
				$is_selected = 'selected';
			}

			$disabled = isset( $option['disabled'] ) && $option['disabled'] ? 'disabled' : '';

			$select .= '<option ' . esc_attr( $is_selected ) . esc_attr( $disabled ) . ' value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['text'] ) . '</option>';
		}

		$select .= '</select>';

		// Adds note below the select field
		if ( $note ) {
			$select .= '<small>' . esc_html( $note ) . '</small>';
		}

		$select .= '</div>';

		return $select;
    }

	/**
	 * Create HTML for color selection field.
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$summary
	 *		@type	string	$note
	 *		@type	string	$classes
	 *		@type	string	$color
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function color( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'summary' => __( 'Default - No color', 'ltw-order-to-board-sync' ),
			'note' => '',
			'classes' => '',
			'selected' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$select = '<div class="' . $id . '">';

		// Label is sometimes needed :)
		if ( $label ) {
			$select .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		}

		// If $classes is not empty then add them to the select field
		$css_classes = ! empty( $classes ) ? esc_attr( $classes ) : '';

		// If item is selected, show that else the default one
		$summary = $selected ? ucfirst( $selected ) : $summary;

		// Build the color picker field
		$select .= '<details class="dropdown ' . $css_classes . '">';
			$select .= '<input name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" type="hidden" value="' . $selected . '">';
			$select .= '<summary id="ltw-order-to-board-sync-' . esc_attr( $id ) . '">' . esc_html( $summary ) . '</summary>';
			$select .= '<ul>';
				$select .= '<li data-color="">' . __( 'Default - No color', 'ltw-order-to-board-sync' ) . '</li>';

				foreach ( Helper::trello_colors() as $color ) {
					$selected_color = $selected == $color ? ' selected' : '';

					$select .= '<li class="' . esc_attr( $color . $selected_color ) . '" data-color="' . esc_attr( $color ) . '">' . esc_html( ucfirst( $color ) ) . '</li>';
				}

			$select .= '</ul>';
		$select .= '</details>';

		// Adds note below the select field
		if ( $note ) {
			$select .= '<small>' . esc_html( $note ) . '</small>';
		}

		$select .= '</div>';

		return $select;
    }

	/**
	 * Create HTML for date selection field.
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$note
	 *		@type	string	$classes
	 *		@type	string	$date
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function date( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'note' => '',
			'classes' => '',
			'date' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$select = '<div class="' . $id . '">';

		// Label for date field
		if ( $label ) {
			$select .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		}

		// If $classes is not empty then add them to the select field
		$css_classes = ! empty( $classes ) ? 'class="' . esc_attr( $classes ) . '"' : '';

		// Date field HTML
		$select .= '<input id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" ' . $css_classes . ' type="date" value="' . esc_attr( $date ) . '" aria-label="' . __( 'Date', 'ltw-order-to-board-sync' ) . '">';

		// Adds note below the select field
		if ( $note ) {
			$select .= '<small>' . esc_html( $note ) . '</small>';
		}

		$select .= '</div>';

		return $select;
    }

	/**
	 * Create HTML for time selection field.
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$note
	 *		@type	string	$classes
	 *		@type	string	$time
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function time( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'note' => '',
			'classes' => '',
			'time' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$select = '<div class="' . $id . '">';

		// Label for time field
		if ( $label ) {
			$select .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		}

		// If $classes is not empty then add them to the select field
		$css_classes = ! empty( $classes ) ? 'class="' . esc_attr( $classes ) . '"' : '';

		// Build the color picker field
		$select .= '<input id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" ' . $css_classes . ' type="time" value="' . esc_attr( $time ) . '" aria-label="' . __( 'Time', 'ltw-order-to-board-sync' ) . '">';

		// Adds note below the select field
		if ( $note ) {
			$select .= '<small>' . esc_html( $note ) . '</small>';
		}

		$select .= '</div>';

		return $select;
    }

	/**
	 * Create HTML for logs. This is a special one.
	 * 
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$header
	 *		@type	string	$body
	 *		@type	string	$type
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function log( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'header' => '',
			'body' => '',
			'type' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		// $header contains type of error which we can also use for a type since its just: error, info,...
		$log = '<article id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" class="ltw-wc-log log-type-' . esc_attr( strtolower( $type ) ) . '">';

		// Add delete button
		$log .= '<button class="ltw-wc-log-delete" data-log-id="' . esc_attr( $id ) . '" aria-label="' . esc_attr( __( 'Delete log', 'ltw-order-to-board-sync' ) ) . '">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</button>';

		// Add header if set
		if ( $header ) {
			$log .= '<header>' . esc_html( $header ) . '</header>';
		}

		// This is the main body
		if ( $body ) {
			$log .= wp_kses_post( $body );
		}

		// Add footer if set
		/*if ( $footer ) {
			$log .= '<footer>' . esc_html( $footer ) . '</footer>';
		}*/

		$log .= '</article>';

		return $log;
    }

	/**
	 * Create a row container for multiple fields
	 *
	 * @param	array	$args	{
	 *		@type	array	$fields		Array of field HTML strings to display in the row
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function row( array $args = [] ): string {
		$defaults = [
			'fields' => [],
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		if ( empty( $fields ) ) {
			return '';
		}

		$html = '<div class="grid">';
		
		foreach ( $fields as $field ) {
			$html .= $field;
		}
		
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Create HTML for the textarea field
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$text
	 *		@type	string	$note
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function textarea( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'text' => '',
			'note' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$textarea = '<div>';

		$textarea .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>
                <textarea rows="6" id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" aria-label="' . esc_attr( $label ) . '">' . esc_html( $text ) . '</textarea>';
        
		if ( $note ) {
			$textarea .= '<small>' . esc_html( $note ) . '</small>';
		}

		$textarea .= '</div>';

        return $textarea;
    }

	/**
	 * Create HTML for the number field with select field after it.
	 *
	 * @param	array	$args	{
	 *		@type	string	$section
	 *		@type	string	$id
	 *		@type	string	$name
	 *		@type	string	$label
	 *		@type	string	$value	// For number field
	 *		@type	array	$options	// For select field
	 *		@type	string	$note
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
    public function number_with_select( array $args = [] ): string {
		$defaults = [
			'section' => '',
			'id' => '',
			'name' => '',
			'label' => '',
			'value' => '0 hour',
			'options' => [],
			'note' => '',
		];
		
		$args = array_merge( $defaults, $args );
		extract( $args );

		$text = '<div>';

		$text .= '<label for="ltw-order-to-board-sync-' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label>';

		// Container for input and select field
		$text .= '<fieldset role="group" class="ltw-order-to-board-sync-due-date">';
        
			// This holds the combined values of the two fields
			$text .= '<input type="hidden" name="ltw_order_to_board_sync' . esc_attr( $section ) . '[' . esc_attr( $name ) . ']" value="' . esc_attr( $value ) . '">';
			
			// Now we need to split the $value as it contains data for both fields. A simple split by space should be enough.
			$field_data = explode( ' ', $value );

			if ( empty( $field_data ) || ! isset( $field_data[1] ) ) {
				$field_data = [ '0', 'hour' ];	// Default values if none is set
			}

			// Number field
			$text .= '<input type="number" id="ltw-order-to-board-sync-' . esc_attr( $id ) . '" min="0" value="' . esc_attr( $field_data[0] ) . '" aria-label="' . esc_attr( $label ) . '">';
			
			// Select box
			$text .= '<select id="ltw-order-to-board-sync-' . esc_attr( $id ) . '-period" data-selected="' . esc_attr( $selected ) . '" aria-label="' . esc_attr( $label ) . '">';

			// Fill the select field with options and values
			foreach ( $options as $key => $option ) {
				// Check if this option should be selected
				$is_selected = '';

				if ( $option['value'] == ( $field_data[1] ?? 'hour' ) ) {
					$is_selected = 'selected';
				}

				$disabled = isset( $option['disabled'] ) && $option['disabled'] ? 'disabled' : '';

				$text .= '<option ' . esc_attr( $is_selected ) . esc_attr( $disabled ) . ' value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['text'] ) . '</option>';
			}

			$text .= '</select>';

		$text .= '</fieldset>';

		if ( $note ) {
			$text .= '<small>' . esc_html( $note ) . '</small>';
		}

		$text .= '</div>';

        return $text;
    }

	/**
	 * Echos a separator HR element.
	 *
	 * @param	array	$args	{
	 *		@type	string	$id
	 * }
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function separator( array $args = [] ): string {
		$defaults = [
			'id' => '',
		];

		$args = array_merge( $defaults, $args );
		extract( $args );

		return '<hr id="' . esc_attr( $id ) . '" class="ltw-order-to-board-sync-separator">';
	}

	/**
	 * Render a paragraph containing allowed inline HTML.
	 *
	 * @param	array	$args	Paragraph content.
	 * @return	string
	 * @since	1.0.0
	 *
	 */
	public function paragraph( array $args = [] ): string {
		$text = $args['text'] ?? '';

		// Preserve safe inline markup, such as the link configured for the Help tab.
		return '<p>' . wp_kses_post( $text ) . '</p>';
	}
}