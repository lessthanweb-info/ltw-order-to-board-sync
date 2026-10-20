<?php
namespace LTW\Order_To_Board_Sync\Core;

/**
 * Contains code for Trello API
 *
 * @author		lessthanweb
 * @package 	LTW_Order_To_Board_Sync
 * @since		1.0.0
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Class
 */
class Helper {
	/**
	 * Constructor
	 *
	 * @param	void
	 * @return	void
	 * @since	1.0
	 *
	 */
	public function __construct() {
		
    }

    /**
     * Validates date. Date input should be in format "1 hour"/"1 week" etc..
     *
     * @param	string   $date
     * @return	bool
     * @since	1.0.0
     *
     */
    public static function validate_date( string $date ): bool {
        if ( empty( $date ) ) {
            return false;
        }

        //  Let's try to create a date
        $result = new \DateTime( 'now' );

        return $result->modify( '+' . $date ) === false ? false : true;
    }

    /**
     * Return array of colors that are allowed by Trello.
     *
     * @param	void
     * @return	array
     * @since	1.0.0
     *
     */
    public static function trello_colors(): array {
        return [ 'pink', 'yellow', 'lime', 'blue', 'black', 'orange', 'red', 'purple', 'sky', 'green' ];
    }

    /**
     * Format the date from "1 hour" to Trello required format which is yyyy-MM-ddTHH:mm
     *
     * @param	string  $date
     * @return	string
     * @since	1.0.0
     *
     */
    public static function get_trello_date_format( string $date ): string {
        $result = new \DateTime();

        $result->modify( '+' . $date );

        return $result->format( 'Y-m-dTH:i' );
    }

    /**
     * Returns set number of \n while default is one
     * 
     * @param	integer  $number    Default is 1 aka one new line
     * @return	string
     * @since	1.0.0
     *
     */
    public static function new_line( int $number = 1 ): string {
        $new_lines = '';

        for( $i = 0; $i <= $number; $i++ ) {
            $new_lines .= "\n";
        }

        return $new_lines;
    }
    
    /**
     * Returns formatted price without any HTML, just currency symbol and number
     * 
     * @param	float  $price
     * @return	string
     * @since	1.0.0
     *
     */
    public static function wc_price( float $price ): string {
        return wc_price( $price, [ 'in_span' => false ] );
    }

    /**
     * Builds the HTML for the card history that is then rendered in the metabox.
     * 
     * It returns an array with formatted HTML and history item count.
     * 
     * @param	array  $history
     * @return	array
     * @since	1.0.0
     *
     */
    public static function get_trello_card_history_data( array $history ): array {
        if ( empty( $history ) ) {
            return [
                'html' => '',
                'total_records' => 0
            ];
        }

        // Let's reverse the items so that the last added is the first one.
        $history = array_reverse( $history );

        $html = '<ul class="ltw-order-to-board-sync-card-history">';

        //  Build final HTML
        foreach ( $history as $key => $line ) {
            $note_text = sprintf( 
                __( 'Created a card in board <strong>%s</strong>, list <strong>%s</strong> with status <strong>%s</strong>', 'ltw-order-to-board-sync' ),
                $line['board_name'],
                $line['list_name'],
                wc_get_order_status_name( $line['status'] )
            );

			$note_text = apply_filters( Hooks::FILTER_CARD_HISTORY_NOTE, $note_text, $line );

            $html .= '<li class="content">';
                $html .= '<div class="note">' . wp_kses_post( $note_text ) . '</div>';
                $html .= '<div class="date">' . esc_html( date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $line['date'] ) ) . '</div>';
            $html .= '</li>';
        }
                
        $html .= '</ul>';

        return [
            'html' => $html,
            'total_records' => count( $history )
        ];
    }
}