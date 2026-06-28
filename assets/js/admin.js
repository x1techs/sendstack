/**
 * SendStack Admin JS
 *
 * @package SendStack
 * @since   1.0.0
 */
( function ( $ ) {
	'use strict';

	var SendStackAdmin = {
		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			// Toggle password visibility in SMTP settings.
			$( document ).on( 'click', '.sstk-toggle-password', function ( e ) {
				e.preventDefault();
				var $input = $( this ).prev( 'input' );
				if ( 'password' === $input.attr( 'type' ) ) {
					$input.attr( 'type', 'text' );
					$( this ).text( sendstackAdmin.i18n.hide || 'Hide' );
				} else {
					$input.attr( 'type', 'password' );
					$( this ).text( sendstackAdmin.i18n.show || 'Show' );
				}
			} );

			// Confirm bulk delete.
			$( document ).on( 'click', '#doaction, #doaction2', function ( e ) {
				var action = $( this ).prev( 'select' ).val();
				if ( 'bulk_delete' === action ) {
					if ( ! confirm( sendstackAdmin.i18n.confirm_delete || 'Are you sure?' ) ) { // eslint-disable-line no-alert
						e.preventDefault();
					}
				}
			} );
		}
	};

	$( document ).ready( function () {
		SendStackAdmin.init();
	} );
} )( jQuery );
