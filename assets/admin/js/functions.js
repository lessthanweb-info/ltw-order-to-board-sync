jQuery( document ).ready( function( $ ) {
    const ltw_order_to_board_sync_top_tab_links = $( '.ltw-order-to-board-sync-top-tabs .top-tab' );
    const ltw_order_to_board_sync_top_tab_contents = $('.ltw-order-to-board-sync-top-content');
    let ltw_order_to_board_sync_current_top_tab_id = 'cards';

    $( '.ltw-order-to-board-sync-top-tabs' ).on( 'click', '.top-tab', function ( e ) {
        e.preventDefault();

        const tab_id = $( this ).data( 'top-id' );

        history.replaceState( null, null, '#' + tab_id );

        ltw_order_to_board_sync_current_top_tab_id = tab_id;

        ltw_order_to_board_sync_set_active_tab();
    } );

    $( '.ltw-order-to-board-sync-container' ).on( 'click', '.tab-link', function ( e ) {
        e.preventDefault();

        const tab_id = $( this ).data( 'tab-id' );

        $( '.ltw-order-to-board-sync-top-content-' + ltw_order_to_board_sync_current_top_tab_id + ' .ltw-order-to-board-sync-container .tab-link' ).removeClass( 'active' );
        $( '.ltw-order-to-board-sync-top-content-' + ltw_order_to_board_sync_current_top_tab_id + ' .ltw-order-to-board-sync-container .tab-content' ).removeClass( 'active' );

        $( this ).addClass( 'active' );

        $( this ).parents( '.ltw-order-to-board-sync-container' ).children( '.ltw-order-to-board-sync-content' ).children( '#' + tab_id ).addClass( 'active' );
    } );

    //  Set active tab based on hash from URL if there is any
    let ltw_order_to_board_sync_set_active_tab = function() {
        let hash = window.location.hash.substring( 1 );

        if ( hash ) {
            //  Hide all tabs
            ltw_order_to_board_sync_top_tab_links.removeClass( 'active' );
            ltw_order_to_board_sync_top_tab_contents.removeClass( 'active' );

            //  Find and activate the tab with matching data-top-id
            const active_tab = $( '.ltw-order-to-board-sync-top-tabs' ).children( '.top-tab[data-top-id="' + hash + '"]' );

            if ( active_tab.length ) {
                active_tab.addClass( 'active' );

                ltw_order_to_board_sync_current_top_tab_id = hash;

                // Update the forms URL as well
                $( '#ltw-order-to-board-sync-current-page' ).val( $( '#ltw-order-to-board-sync-page-url' ).val() + '#' + hash );

                $( '.ltw-order-to-board-sync-main-content' ).children( '.ltw-order-to-board-sync-top-content-' + hash ).addClass( 'active' );
            }
        }
    }

    ltw_order_to_board_sync_set_active_tab();

    //  Clicking the back/forward buttons in browser changes the tab as well
    $( window ).on( 'hashchange', ltw_order_to_board_sync_set_active_tab );

    //  Delete log handler
    $( '.ltw-logs-container' ).on( 'click', '.ltw-wc-log-delete', function( e ) {
        e.preventDefault();

        const log_id = $( this ).data( 'log-id' );
        const log_element = $( '#ltw-order-to-board-sync-' + log_id );

        $.ajax( {
            type: 'post',
            url: ltw_order_to_board_sync.ajax_url,
            data: {
                'action': 'ltw_trello_delete_log',
                'nonce': ltw_order_to_board_sync.nonce,
                'log_id': log_id
            },
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    //  Remove the log element from the list
                    log_element.fadeOut( 200, function() {
                        $( this ).remove();

                        //  Check if there are any logs left
                        if ( $( '.ltw-logs-container .ltw-wc-log' ).length === 0 ) {
                            //  If no logs remain, show "no logs" message
                            $( '#ltw-logs' ).html( '<h2>' + ltw_order_to_board_sync.texts.logs + '</h2><p>' + ltw_order_to_board_sync.texts.no_logs_available + '</p>' );
                        }
                    } );
                } else {
                    console.error( ltw_order_to_board_sync.texts.error, response.data.message );
                }
            },
            error: function() {
                console.error( 'AJAX error when deleting log' );
            }
        } );
    } );

    //  Delete all logs handler
    $( '.ltw-order-to-board-sync-content' ).on( 'click', '#ltw-order-to-board-sync-delete-all-logs', function( e ) {
        e.preventDefault();

        //  Ask for confirmation as this is a destructive, irreversible action
        if ( ! window.confirm( ltw_order_to_board_sync.texts.confirm_delete_all_logs ) ) {
            return;
        }

        $.ajax( {
            type: 'post',
            url: ltw_order_to_board_sync.ajax_url,
            data: {
                'action': 'ltw_trello_delete_all_logs',
                'nonce': ltw_order_to_board_sync.nonce
            },
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    //  Show "no logs" message and remove the logs list + delete-all button
                    $( '#ltw-logs' ).html( '<h2>' + ltw_order_to_board_sync.texts.logs + '</h2><p>' + ltw_order_to_board_sync.texts.no_logs_available + '</p>' );
                } else {
                    console.error( ltw_order_to_board_sync.texts.error, response.data.message );
                }
            },
            error: function() {
                console.error( 'AJAX error when deleting all logs' );
            }
        } );
    } );

    //  Color picker field
    $( '.ltw-order-to-board-sync .ltw-order-to-board-sync-color-field' ).on( 'click', 'li', function( e ) {
        e.preventDefault();

        let selected_color = $( this ).data( 'color' );

        //  Remove selected
        $( this ).parent().children( 'li' ).removeClass( 'selected' );
        //  Add selected class to the current item
        $( this ).addClass( 'selected' );

        //  Add currently selected item to the input field
        $( this ).closest( 'details' ).children( 'input[type=hidden]' ).val( selected_color );

        //  Change summary
        $( this ).closest( '.ltw-order-to-board-sync-color-field' ).children( 'summary' ).html( $( this ).text() );

        //  Close dropdown
        $( this ).closest( '.ltw-order-to-board-sync-color-field' ).attr( 'open', false );
    } );

    //  Due date field
    $( '.ltw-order-to-board-sync .ltw-order-to-board-sync-due-date' ).on( 'change', 'input[type="number"], select', function( e ) {
        e.preventDefault();

        let hidden_field = $( this ).parent().children( 'input[type=hidden]' );
        let input_field = $( this ).parent().children( 'input[type=number]' );
        let select_field = $( this ).parent().children( 'select' );
        let due_date = input_field.val() + ' ' + select_field.val();

        //  Save the result to hidden field
        hidden_field.val( due_date );
    } );
} );