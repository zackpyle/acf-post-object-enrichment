/* global acfPoeData, jQuery, acf */
( function ( $ ) {
	'use strict';

	// Per-page cache: JSON.stringify(postTypes) → fields array
	var cache = {};

	// ── Bootstrap ──────────────────────────────────────────────────────────

	$( function () {
		initAll( document );
	} );

	// Handle fields added via "Add Field" button or duplication
	if ( typeof acf !== 'undefined' ) {
		acf.addAction( 'append', function ( $el ) {
			initAll( $el[ 0 ] || $el );
		} );
	}

	function initAll( context ) {
		$( context ).find( 'input[name*="[acf_poe_fields]"]' ).each( function () {
			initInput( $( this ) );
		} );
	}

	// ── Initialise one text input ──────────────────────────────────────────

	function initInput( $input ) {
		if ( $input.data( 'acf-poe-init' ) ) return;
		$input.data( 'acf-poe-init', true );

		var $ui = buildUI( parseCSV( $input.val() ) );
		$input.after( $ui ).hide();

		// In ACF 6, field-type-specific settings (post_type, our setting, etc.) are
		// siblings inside .acf-field-type-settings. Fall back to the nearest li[data-key]
		// for older ACF versions where the structure differs.
		var $container = $input.closest( '.acf-field-type-settings' );
		if ( ! $container.length ) {
			$container = $input.closest( 'li[data-key]' );
		}

		var $ptSelect     = $container.find( 'select[name$="[post_type][]"]' );
		var $returnFormat = $container.find( 'input[type="radio"][name$="[return_format]"]' );
		var $settingRow   = $input.closest( '.acf-poe-setting' );

		function syncVisibility() {
			var format = $container.find( 'input[type="radio"][name$="[return_format]"]:checked' ).val();
			$settingRow.toggle( format !== 'id' );
		}

		$returnFormat.on( 'change', syncVisibility );
		syncVisibility();

		// Invalidate AJAX cache when the post type selection changes
		$ptSelect.on( 'change', function () {
			delete cache[ cacheKey( $ptSelect ) ];
		} );

		$ui.data( { $input: $input, $ptSelect: $ptSelect } );
	}

	// ── Build picker UI ────────────────────────────────────────────────────

	function buildUI( selected ) {
		var $ui       = $( '<div class="acf-poe-picker">' );
		var $tags     = $( '<div class="acf-poe-tags">' );
		var $addBtn   = $( '<button type="button" class="button acf-poe-add-btn">' ).text( acfPoeData.i18n.addField );
		var $dropdown = buildDropdown();

		selected.forEach( function ( name ) {
			$tags.append( buildTag( name ) );
		} );

		$addBtn.on( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			toggleDropdown( $ui );
		} );

		$ui.append( $tags ).append( $addBtn ).append( $dropdown );
		return $ui;
	}

	function buildDropdown() {
		var $d      = $( '<div class="acf-poe-dropdown acf-hidden">' );
		var $search = $( '<input type="text" class="acf-poe-search">' ).attr( 'placeholder', acfPoeData.i18n.search );
		var $list   = $( '<ul class="acf-poe-list">' );

		$search.on( 'input', function () {
			var q = $( this ).val().toLowerCase();
			$list.find( 'li' ).each( function () {
				$( this ).toggle( $( this ).text().toLowerCase().indexOf( q ) !== -1 );
			} );
		} );

		$d.append( $search ).append( $list );
		return $d;
	}

	function buildTag( name ) {
		var $tag = $( '<span class="acf-poe-tag">' );
		$tag.append( $( '<span class="acf-poe-tag-label">' ).text( name ) );
		$tag.append(
			$( '<button type="button" class="acf-poe-tag-remove" aria-label="Remove">' )
				.html( '&times;' )
				.on( 'click', function () {
					var $picker = $tag.closest( '.acf-poe-picker' );
					$tag.remove();
					syncInput( $picker );
				} )
		);
		$tag.data( 'name', name );
		return $tag;
	}

	// ── Dropdown toggle & populate ─────────────────────────────────────────

	function toggleDropdown( $ui ) {
		var $d = $ui.find( '.acf-poe-dropdown' );

		if ( ! $d.hasClass( 'acf-hidden' ) ) {
			$d.addClass( 'acf-hidden' );
			return;
		}

		var $ptSelect = $ui.data( '$ptSelect' );
		var types     = getPostTypes( $ptSelect );
		var key       = JSON.stringify( types );

		$d.removeClass( 'acf-hidden' );
		$d.find( '.acf-poe-search' ).val( '' ).trigger( 'input' ).focus();

		if ( cache[ key ] ) {
			renderList( $ui, cache[ key ] );
		} else {
			$d.find( '.acf-poe-list' ).html( '<li class="acf-poe-msg">' + esc( acfPoeData.i18n.loading ) + '</li>' );
			fetchFields( types, function ( fields ) {
				cache[ key ] = fields;
				renderList( $ui, fields );
			}, function () {
				$d.find( '.acf-poe-list' ).html( '<li class="acf-poe-msg acf-poe-msg--error">' + esc( acfPoeData.i18n.error ) + '</li>' );
			} );
		}

		// Close when clicking outside
		$( document ).one( 'click.acf-poe-close', function ( e ) {
			if ( ! $( e.target ).closest( $ui ).length ) {
				$d.addClass( 'acf-hidden' );
			}
		} );
	}

	function renderList( $ui, fields ) {
		var $list    = $ui.find( '.acf-poe-list' ).empty();
		var selected = parseCSV( $ui.data( '$input' ).val() );
		var avail    = fields.filter( function ( f ) {
			return selected.indexOf( f.name ) === -1;
		} );

		if ( ! avail.length ) {
			$list.html( '<li class="acf-poe-msg">' + esc( fields.length ? acfPoeData.i18n.allAdded : acfPoeData.i18n.noFields ) + '</li>' );
			return;
		}

		avail.forEach( function ( field ) {
			var $btn = $( '<button type="button">' )
				.append( $( '<span class="acf-poe-field-label">' ).text( field.label ) )
				.append( $( '<span class="acf-poe-field-name">'  ).text( field.name ) )
				.append( $( '<span class="acf-poe-field-type">'  ).text( field.type ) );

			$btn.on( 'click', function () {
				var $tags = $ui.find( '.acf-poe-tags' );
				$tags.append( buildTag( field.name ) );
				syncInput( $ui );
				$ui.find( '.acf-poe-dropdown' ).addClass( 'acf-hidden' );
			} );

			$list.append( $( '<li>' ).append( $btn ) );
		} );
	}

	// ── AJAX ──────────────────────────────────────────────────────────────

	function fetchFields( postTypes, onSuccess, onError ) {
		$.ajax( {
			url:  acfPoeData.ajaxUrl,
			type: 'POST',
			data: { action: 'acf_poe_get_fields', nonce: acfPoeData.nonce, post_types: postTypes },
			success: function ( res ) {
				if ( res.success ) { onSuccess( res.data ); } else { onError(); }
			},
			error: onError,
		} );
	}

	// ── Helpers ────────────────────────────────────────────────────────────

	function parseCSV( val ) {
		if ( ! val || ! val.trim() ) return [];
		return val.split( ',' ).map( function ( s ) { return s.trim(); } ).filter( Boolean );
	}

	function getPostTypes( $select ) {
		if ( ! $select || ! $select.length ) return [];
		var val = $select.val();
		if ( ! val ) return [];
		return $.isArray( val ) ? val : [ val ];
	}

	function cacheKey( $ptSelect ) {
		return JSON.stringify( getPostTypes( $ptSelect ).slice().sort() );
	}

	function syncInput( $ui ) {
		var names = [];
		$ui.find( '.acf-poe-tag' ).each( function () {
			names.push( $( this ).data( 'name' ) );
		} );
		$ui.data( '$input' ).val( names.join( ',' ) );
	}

	function esc( str ) {
		return $( '<span>' ).text( str ).html();
	}

} )( jQuery );
