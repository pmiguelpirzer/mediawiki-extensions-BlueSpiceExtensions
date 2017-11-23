/**
 * ShoutBox Extension
 *
 * Inspiration by
 * Adrian "yEnS" Mato Gondelle & Ivan Guardado Castro
 * www.yensdesign.com
 * yensamg@gmail.com
 *
 * @author     Markus Glaser <glaser@hallowelt.com>
 * @author     Karl Waldmanstetter
 * @package    Bluespice_Extensions
 * @subpackage ShoutBox
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

/* Changelog
 * v1.1.0
 * - Reworked code
 * v0.1
 * - initial commit
 */

//Last Code review RBV (30.06.2011)

/**
 * Base class for all ShoutBox related methods and properties
 */
BsShoutBox = {
	/**
	 * Reference to input field where current message is stored
	 * @var jQuery text input field
	 */
	textField: null,
	/**
	 * Default value of message field, taken from message input box
	 * @var string
	 */
	defaultMessage: '',
	/**
	 * Reference to the message list div
	 * @var jQuery
	 */
	msgList: null,
	/**
	 * Reference to the send button
	 * @var jQuery
	 */
	btnSend: null,
	/**
	 * Reference to the ajax loader gif
	 * @var jQuery
	 */
	ajaxLoader: null,
	/**
	 * Reference to the caracter counter
	 * @var jQuery
	 */
	characterCounter: null,
	/**
	 * Reference to the jQuery Tab
	 * @var jQuery
	 */
	shoutboxTab: null,
	/**
	 * Sup Element for the ShoutboxTab
	 * @var jQuery
	 */
	shoutboxTabCounter: null,
	/**
	 * Load and display a current list of shouts from server
	 * @param sblimit int Maximum number of shouts before more link is displayed
	 */
	updateShoutbox: function( sblimit ) {
		if( typeof sblimit === 'undefined' ) {
			sblimit = 0;
		}
		$( document ).trigger( "onBsShoutboxBeforeUpdated", [ BsShoutBox ] );
		BsShoutBox.ajaxLoader.fadeIn();

		$.ajax({
			dataType: "json",
			type: 'post',
			url: mw.util.wikiScript( 'api' ),
			data: {
				action: 'bs-shoutbox-tasks',
				task: 'getShouts',
				format: 'json',
				token: mw.user.tokens.get('editToken', ''),
				taskData: JSON.stringify({
					articleId: mw.config.get( "wgArticleId" ),
					limit: sblimit
				})
			},
			success: function( oData, oTextStatus ) {
				if( oData.success ) {
					BsShoutBox.msgList.html( oData.payload.html );
					BsShoutBox.msgList.slideDown();
					BsShoutBox.btnSend.blur().removeAttr( 'disabled' ); //reactivate the send button
					BsShoutBox.textField.val( BsShoutBox.defaultMessage );
					BsShoutBox.textField.blur().removeAttr( 'disabled' );
					BsShoutBox.ajaxLoader.fadeOut();
					BsShoutBox.characterCounter.text( mw.message( 'bs-shoutbox-charactersleft', BsShoutBox.textField.attr( 'maxlength' ) ).text() );
					BsShoutBox.shoutboxTabCounter.text( $( "#bs-sb-count-all" ).text() );
					//statebar element
					if ($('#sb-Shoutbox-text a').length !== 0) {
						var nshoutsmsg = mw.message( 'bs-shoutbox-n-shouts', $( "#bs-sb-count-all" ).text() ).text();
						$('#sb-Shoutbox-text a').text( nshoutsmsg );
						$('#sb-Shoutbox-text a').attr( 'title', nshoutsmsg );
					}
					$( document ).trigger( "onBsShoutboxAfterUpdated", [ BsShoutBox ] );
				}
			}
		});
	},
	archiveEntry: function( iShoutID ) {
		$( document ).trigger( "onBsShoutboxBeforeArchived", [ BsShoutBox ] );
		BsShoutBox.ajaxLoader.fadeIn();
		$.ajax({
			dataType: "json",
			type: 'post',
			url: mw.util.wikiScript( 'api' ),
			data: {
				action: 'bs-shoutbox-tasks',
				task: 'archiveShout',
				format: 'json',
				token: mw.user.tokens.get('editToken', ''),
				taskData: JSON.stringify({
					shoutId: iShoutID
				})
			},
			success: function( oData, oTextStatus ) {
				if( oData.success !== true ) {
					mw.notify(
						oData.message,
						{ title: mw.msg( 'bs-extjs-title-success' ) }
					);
				}
				BsShoutBox.updateShoutbox();
				$( document ).trigger( "onBsShoutboxAfterArchived", [ BsShoutBox ] );
			}
		});
	}
};

mw.loader.using( 'ext.bluespice', function() {
	BsShoutBox.textField = $( "#bs-sb-message" );
	BsShoutBox.btnSend = $( "#bs-sb-send" );
	BsShoutBox.msgList = $( "#bs-sb-content" );
	BsShoutBox.ajaxLoader = $( "#bs-sb-loading" );
	BsShoutBox.defaultMessage = BsShoutBox.textField.val();
	BsShoutBox.characterCounter = $( '#bs-sb-charactercounter' );
	BsShoutBox.shoutboxTab = $( "#bs-data-after-content-tabs a[href='#bs-shoutbox']" );
	BsShoutBox.shoutboxTabCounter = $( "<sup class='bs-sb-tab-counter'>" );
	if ( typeof ( BsShoutBox.shoutboxTab ) !== "undefined" )
		BsShoutBox.shoutboxTab.after( BsShoutBox.shoutboxTabCounter );
	BsShoutBox.updateShoutbox();

	//HTML5 like placeholder effect.

	BsShoutBox.textField
			.focus( function() {
				if ( $( this ).val() == BsShoutBox.defaultMessage )
					$( this ).val( '' );
			}
			).blur( function() {
		if ( $( this ).val() == '' ) {
			$( this ).val( BsShoutBox.defaultMessage );
		}
	} );

	BsShoutBox.textField.bind( "input propertychange", function( e ) {
		var currCharLen = $( this ).attr( 'maxlength' ) - $( this ).val().length;

		BsShoutBox.characterCounter.text( mw.message( 'bs-shoutbox-charactersleft', currCharLen ).text() );
	} );

	$( "#bs-sb-form" ).submit( function() {
		var sMessage = BsShoutBox.textField.val();
		if ( sMessage === '' || sMessage === BsShoutBox.defaultMessage ) {
			bs.util.alert(
					'bs-shoutbox-alert',
					{
						textMsg: 'bs-shoutbox-entermessage'
					}
			);
			return false;
		}

		//we deactivate submit button while sending
		BsShoutBox.btnSend.blur().attr( 'disabled', 'disabled' );
		BsShoutBox.textField.blur().attr( 'disabled', 'disabled' );

		$.ajax({
			dataType: "json",
			type: 'post',
			url: mw.util.wikiScript( 'api' ),
			data: {
				action: 'bs-shoutbox-tasks',
				task: 'insertShout',
				format: 'json',
				token: mw.user.tokens.get('editToken', ''),
				taskData: JSON.stringify({
					articleId: mw.config.get( "wgArticleId" ),
					message: sMessage
				})
			},
			success: function( oData, oTextStatus ) {
				if ( oData.success !== true ) {
					mw.notify(
						oData.message,
						{ title: mw.msg( 'bs-extjs-title-success' ) }
					);
				}
				BsShoutBox.updateShoutbox();
			}
		});

		//we prevent the refresh of the page after submitting the form
		return false;
	});

	$( document ).on( 'click', '.bs-sb-archive', function() {
		var iShoutID = $( this ).parent().attr( 'id' );
		bs.util.confirm(
				'bs-shoutbox-confirm',
				{
					titleMsg: 'bs-shoutbox-confirm-title',
					textMsg: 'bs-shoutbox-confirm-text'
				},
		{
			ok: function() {
				BsShoutBox.archiveEntry( iShoutID.replace( /bs-sb-/, "" ) );
			}
		} );
	} );

	$('#sb-Shoutbox-link').click( function() {
		$("#bs-data-after-content").tabs('select','#bs-shoutbox');
	});
} );