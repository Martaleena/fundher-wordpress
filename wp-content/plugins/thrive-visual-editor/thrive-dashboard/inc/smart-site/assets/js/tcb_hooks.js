var TVD_SS = TVD_SS || {};

( function ( $ ) {


	if ( TVD_SS.is_editor_page ) {
		TVE.add_filter( 'tcb.inline_shortcodes.insert', shape_shortcode );
	}

	/**
	 * Before shortcode insertion we do some processing of the data which will later shape the shortcode element ( shortcodeData structure can be seen bellow )
	 *
	 * @param shortcodeData
	 * @returns {*}
	 */
	//	shortcodeData = {
	//		key: shortcode_key,
	//		extra_key: shortcode_extra_key,
	//		name: name,
	//      shortcodeName: name,
	//		class: SHORTCODE_CLASS,
	//		content_class: SHORTCODE_CONTENT_CLASS,
	//	    configOptions: [        )
	//			{                   )
	//				key: '',        )        used for inputs that require further configuration
	//				value: '',      )        these will generate inputs inside the froala shortcode dropdown
	//			}                   )
	//		]                       )
	//		options: [                  ]
	//			{                       ]
	//				key: '',            ]   used for additional information passed  through the shortcode itself
	//				value: '',          ]   these don't do much but will b part of the final shortcode structure
	//			}                       ]
	//		]                           ]
	//	};
	function shape_shortcode( shortcodeData ) {
		var shortcode, name;

		_.each( tve_froala_const.inline_shortcodes, function ( group, group_name ) {
			if ( ! shortcode ) {
				shortcode = group.find( function ( item ) {
					return shortcodeData.extra_key && item.extra_param === shortcodeData.extra_key;
				} );
			}
		} );
		if ( shortcode ) {
			name = shortcode.input.id.real_data[ shortcodeData.configOptions.find( function ( item ) {
				return item.key === 'id';
			} ).value ]
		}
		if ( name ) {
			shortcodeData.name = name;
		}

		return shortcodeData;
	}
} )( jQuery );