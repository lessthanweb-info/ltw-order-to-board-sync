jQuery( document ).ready( function( $ ) {
    let ltw_order_to_board_sync_loading = false;

    const ltw_wc_loading = function( loading, container ) {
        let loader = $( container ).find( '.loader' );

        if ( loading ) {
            loader.addClass( 'is-active' ).show();
        } else {
            loader.removeClass( 'is-active' ).hide();
        }
    }

    //  Call Trello API and get a list of boards.
    const ltw_order_to_board_sync_get_boards = function() {
        let select = $( '.ltw-order-to-board-sync' ).find( '.ltw-order-to-board-sync-boards' );

        // Skip the API request on screens where card destination controls are not rendered.
        if ( select.length === 0 ) {
            return;
        }
        
        $.ajax( {
            type: 'post',
            url: ltw_order_to_board_sync.ajax_url,
            data: {
                'action': 'ltw_trello_get_boards',
                'nonce': ltw_order_to_board_sync.nonce,
            },
            dataType: 'json',
            beforeSend: function() {
                ltw_wc_loading( true, select.parent() );
            },
            success: function( response ) {
                if ( response.success ) {
                    //  Save all board IDs
                    let board_ids = [];
                    let selected_boards = {}; //    Store selected board for each select field
                    let selected_lists = {}; // Store selected list for each select field

                    //  Loop over all board select fields and add boards and select the choosen one :)
                    select.each( function ( i_select, select_field ) {
                        //  Get currently selected board ID, if any.
                        let selected_board_id = $( select_field ).data( 'selected' ) || $( select_field ).val() || '';
 
                        $( select_field ).empty();

                        //  Let's fill the select boxes with boards
                        $.each( response.data.boards, function( i_board, board ) {
                            let is_selected = board.id === selected_board_id;
                                
                            $( select_field ).append( $( '<option>', {
                                value: board.id,
                                text: board.name,
                                selected: is_selected
                            } ) );

                            //  Add board IDs to array for later use (only once)
                            if ( i_select === 0 ) {
                                board_ids.push( board.id );
                            }
                        } );

                        //  Store selected board ID for this field
                        selected_boards[ i_select ] = selected_board_id;
                    } );

                    //  If we have found some available boards, let try to get the lists as well
                    if ( board_ids.length > 0 ) {
                        ltw_order_to_board_sync_get_all_lists_batch( board_ids, selected_boards );
                    }
                    
                    ltw_wc_loading( false, select.parent() );
                } else {
                    console.error( ltw_order_to_board_sync.texts.error, response.data.message );
                }
            },
            error: function() {
                ltw_wc_loading( false, select.parent() );
            }
        } );
    }

    ltw_order_to_board_sync_get_boards();

    const ltw_order_to_board_sync_get_all_lists_batch = function( board_ids, selected_boards ) {
        let board_select = $( '.ltw-order-to-board-sync' ).find( '.ltw-order-to-board-sync-boards' );
        let list_select = $( '.ltw-order-to-board-sync' ).find( '.ltw-order-to-board-sync-lists' );

        $.ajax( {
            type: 'post',
            url: ltw_order_to_board_sync.ajax_url,
            data: {
                'action': 'ltw_trello_get_lists_batch',
                'nonce': ltw_order_to_board_sync.nonce,
                'board_ids': board_ids
            },
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    //  Store lists globally or in a data attribute for later use
                    window.ltw_order_to_board_sync_lists = response.data.lists;

                    //  Populate each board/list pair with the correct saved board and list ID
                    board_select.each( function( i, board_field ) {
                        let parent = $( board_field ).closest( '.ltw-order-to-board-sync-board-list-row' );

                        if ( parent.length === 0 ) {
                            parent = $( board_field ).closest( '.tab-content' );
                        }

                        if ( parent.length === 0 ) {
                            parent = $( board_field ).closest( '.ltw-order-to-board-sync-edit-order' );
                        }

                        let list_field = parent.find( '.ltw-order-to-board-sync-lists' );
                        let selected_board_id = selected_boards[ i ] || $( board_field ).val();

                        if ( ! selected_board_id ) {
                            return;
                        }

                        ltw_order_to_board_sync_populate_lists_for_board( selected_board_id, $( list_field ).data( 'selected' ), list_field );
                    } );

                    //  Add change handler to update lists when board selection changes
                    board_select.off( 'change' ).on( 'change', function() {
                        let selected_board_id = $( this ).val();

                        //  Find the corresponding lists select in the same parent
                        let parent = $( this ).closest( '.ltw-order-to-board-sync-board-list-row' );
                        if ( parent.length === 0 ) {
                            parent = $( this ).closest( '.tab-content.active, .ltw-order-to-board-sync-edit-order' );
                        }
                        let corresponding_list_select = parent.find( '.ltw-order-to-board-sync-lists' ).first();

                        ltw_order_to_board_sync_populate_lists_for_board( selected_board_id, null, corresponding_list_select );
                    } );

                    //  Trigger custom event
                    $( document ).trigger( 'ltw_trello_lists_loaded', [ response.data ] );

                    ltw_wc_loading( false, list_select.parent() );
                } else {
                    console.error( ltw_order_to_board_sync.texts.error, response.data.message );
                }
            },
            error: function() {
                ltw_wc_loading( false, list_select.parent() );
            }
        } );
    };

    //  Helper function to populate lists select for a specific board
    const ltw_order_to_board_sync_populate_lists_for_board = function( board_id, selected_list_id, list_field ) {
        //  Clear existing lists
        $( list_field ).empty();

        //  Get lists for the selected board
        if ( window.ltw_order_to_board_sync_lists && window.ltw_order_to_board_sync_lists[ board_id ] ) {
            let lists = window.ltw_order_to_board_sync_lists[ board_id ];

            $.each( lists, function( i, list ) {
                let is_selected = list.id == selected_list_id ? true : false;

                $( list_field ).append( $('<option>', {
                    value: list.id,
                    text: list.name,
                    selected: is_selected
                } ) );
            } );
        } else {
            console.log( ltw_order_to_board_sync.texts.no_lists_found_for_board, board_id );
        }
    };

    // Create card button handler
    $( document ).on( 'click', '#ltw-order-to-board-sync-create-card, #ltw-order-to-board-sync-retry-card', function( e ) {
        e.preventDefault();

        let btn = $( this );
        let container = btn.closest( '.ltw-order-to-board-sync-order-metabox' );
        let board = container.find( '.ltw-order-to-board-sync-boards' ).val() || '';
        let list = container.find( '.ltw-order-to-board-sync-lists' ).val() || '';
        let order_id = btn.data( 'order-id' ) || container.data( 'order-id' );
        let result_container = container.find( '.ltw-order-to-board-sync-result' );
        let history_container = container.children( '.ltw-order-to-board-sync-card-history-container' );
        let retry = btn.attr( 'id' ) === 'ltw-order-to-board-sync-retry-card' ? 1 : 0;

        // Clear previous result
        result_container.empty();

        if ( ! order_id ) {
            result_container.html( '<div class="notice notice-error inline">' + ( ltw_order_to_board_sync.texts.error || 'Error:' ) + ' Missing order ID</div>' );

            return;
        }

        if ( ! board || ! list ) {
            result_container.html( '<div class="notice notice-error inline">' + ( ltw_order_to_board_sync.texts.error || 'Error:' ) + ' Please select a board and a list.</div>' );
            
			return;
        }

        // Disable UI and show loader
        btn.prop( 'disabled', true );
        container.find( 'select' ).prop( 'disabled', true );
        container.find( 'input[type="text"]' ).prop( 'disabled', true );
        ltw_wc_loading( true, '.ltw-order-to-board-sync-actions' );

        $.ajax( {
            type: 'post',
            url: ltw_order_to_board_sync.ajax_url,
            data: {
                action: 'ltw_trello_create_card_from_order',
                nonce: ltw_order_to_board_sync.nonce,
                order_id: order_id,
                board_id: board,
                list_id: list,
                retry: retry,
            },
            dataType: 'json',
            success: function( response ) {
                if ( response.success ) {
                    //  Update the history list
					let history_list = response.data && response.data.html ? response.data.html : '';
                    
                    history_container.html( history_list );

                    $( '#ltw-order-to-board-sync-create-card' ).show();

                    $( '#ltw-order-to-board-sync-retry-card' ).hide();

                    //  Show a success message
                    let msg = response.data && response.data.message ? response.data.message : 'Card created successfully.';
                    
                    result_container.html( '<div class="notice notice-success inline">' + msg + '</div>' );
                } else {
                    let msg = response.data && response.data.message ? response.data.message : ( ltw_order_to_board_sync.texts.error || 'Error:' );
                    
                    result_container.html( '<div class="notice notice-error inline">' + msg + '</div>' );
                }
            },
            error: function( xhr, status, err ) {
                result_container.html( '<div class="notice notice-error inline">' + ( ltw_order_to_board_sync.texts.error || 'Error:' ) + ' ' + err + '</div>' );
            },
            complete: function() {
                // Enable UI and hide loader
                ltw_wc_loading( false, container );
                btn.prop( 'disabled', false );
                container.find( 'select' ).prop( 'disabled', false );
                container.find( 'input[type="text"]' ).prop( 'disabled', false );
            }
        } );
    } );

} );