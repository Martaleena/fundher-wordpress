var TVD_SS = TVD_SS || {};
TVD_SS.models = TVD_SS.models || {};
TVD_SS.collections = TVD_SS.collections || {};

( function ( $ ) {
	/**
	 * Base Model
	 */
	TVD_SS.models.Base = Backbone.Model.extend( {
		idAttribute: 'ID',
		/**
		 * deep-json implementation for backbone models - flattens any abject, collection etc from the model
		 *
		 * @returns {Object}
		 */
		toDeepJSON: function () {
			var obj = $.extend( true, {}, this.attributes );
			_.each( _.keys( obj ), function ( key ) {
				if ( ! _.isUndefined( obj[ key ] ) && ! _.isNull( obj[ key ] ) && _.isFunction( obj[ key ].toJSON ) ) {
					obj[ key ] = obj[ key ].toJSON();
				}
			} );
			return obj;
		},
		/**
		 * deep clone a backbone model
		 * this will duplicate all included collections, models etc located in the attributes field
		 *
		 * @returns {TVD_SS.models.Base}
		 */
		deepClone: function () {
			return new this.constructor( this.toDeepJSON() );
		},
		/**
		 * ensures the same instance of a collection is used in a Backbone model
		 *
		 * @param {object} data
		 * @param {object} collection_map map with object keys and collection constructors
		 */
		ensureCollectionData: function ( data, collection_map ) {
			_.each( collection_map, _.bind( function ( constructor, key ) {
				if ( ! data[ key ] ) {
					return true;
				}
				var instanceOf = this.get( key ) instanceof constructor;
				if ( ! instanceOf ) {
					data[ key ] = new constructor( data[ key ] );
					return true;
				}
				this.get( key ).reset( data[ key ] );
				data[ key ] = this.get( key );
			}, this ) );
		},
		validation_error: function ( field, message ) {
			return {
				field: field,
				message: message
			};
		},
		/**
		 * Set nonce header before every Backbone sync.
		 *
		 * @param {string} method.
		 * @param {Backbone.Model} model.
		 * @param {{beforeSend}, *} options.
		 * @returns {*}.
		 */
		sync: function ( method, model, options ) {
			var beforeSend;

			options = options || {};

			options.cache = false;

			if ( ! _.isUndefined( TVD_SS.nonce ) && ! _.isNull( TVD_SS.nonce ) ) {
				beforeSend = options.beforeSend;

				options.beforeSend = function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', TVD_SS.nonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};
			}

			return Backbone.sync( method, model, options );
		}
	} );

	/**
	 * Base Collection
	 */
	TVD_SS.collections.Base = Backbone.Collection.extend( {
		/**
		 * helper function to get the last item of a collection
		 *
		 * @return Backbone.Model
		 */
		last: function () {
			return this.at( this.size() - 1 );
		},
		sync: function ( method, model, options ) {
			var beforeSend;

			options = options || {};

			options.cache = false;

			if ( ! _.isUndefined( TVD_SS.nonce ) && ! _.isNull( TVD_SS.nonce ) ) {
				beforeSend = options.beforeSend;

				options.beforeSend = function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', TVD_SS.nonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};
			}

			return Backbone.sync( method, model, options );
		}
	} );

	/**
	 * Breadcrumb Link
	 */
	TVD_SS.models.BreadcrumbLink = TVD_SS.models.Base.extend( {
		defaults: {
			ID: '',
			hash: '',
			label: '',
			full_link: false
		},
		/**
		 * we pass only hash and label, and build the ID based on the label
		 *
		 * @param {object} att
		 */
		initialize: function ( att ) {
			if ( ! this.get( 'ID' ) ) {
				if ( att.label ) {
					this.set( 'ID', att.label.split( ' ' ).join( '' ).toLowerCase() );
				}
			}
			this.set( 'full_link', att.hash.match( /^http/ ) );
		},
		/**
		 *
		 * @returns {String}
		 */
		get_url: function () {
			return this.get( 'full_link' ) ? this.get( 'hash' ) : ( '#' + this.get( 'hash' ) );
		}
	} );

	/**
	 * Breadcrumbs Collection
	 */
	TVD_SS.collections.Breadcrumbs = TVD_SS.collections.Base.extend( {
		model: TVD_SS.models.Base.extend( {
			defaults: {
				hash: '',
				label: ''
			}
		} ),
		/**
		 * helper function allows adding items to the collection easier
		 *
		 * @param {string} route
		 * @param {string} label
		 */
		add_page: function ( route, label ) {
			var _model = new TVD_SS.models.BreadcrumbLink( {
				hash: route,
				label: label
			} );
			return this.add( _model );
		}
	} );

	/**
	 * Field model
	 */
	TVD_SS.models.Field = TVD_SS.models.Base.extend( {
		idAttribute: 'id',
		defaults: {
			group_id: 0,
			name: '',
			type: 0,
			data: {},
			icon:'',
			formated_data:'',
			is_default: 0,
			opened: false,
			created_at: '',
			updated_at: ''
		},
		initialize: function () {
			if ( this.get( 'data' ) === null ) {
				this.set( { data: {} } );
			}
		},
		url: function () {
			var url = TVD_SS.routes.fields;

			if ( this.get( 'id' ) || this.get( 'id' ) === 0 ) {
				url += '/' + this.get( 'id' );
			}

			return url;
		},
		/**
		 * Overwrite Backbone validation
		 * Return something to invalidate the model
		 *
		 * @param {Object} attrs
		 * @param {Object} options
		 */
		validate: function ( attrs, options ) {
			this.errors = [];

			if ( ! attrs.name ) {
				this.errors.push( this.validation_error( 'name', TVD_SS.t.InvalidName ) );
			}


			var type = TVE_Dash.upperFirst( TVD_SS.util.get_field_type_name( parseInt( attrs.type ) ) ),
				fn = 'validate' + type + 'Field';

			if ( typeof this[ fn ] === "function" ) {
				this[ fn ].call( this, attrs.data );
			}

			if ( this.errors.length ) {
				return this.errors;
			}
		},

		/**
		 * Validate text field data
		 */
		validateTextField: function ( data ) {

			if ( ! data.text ) {
				this.errors.push( this.validation_error( 'data_text', TVD_SS.t.InvalidText ) );
			}
		},
		validateAddressField: function ( data ) {
			if ( ! data.address1 ) {
				this.errors.push( this.validation_error( 'data_address1', TVD_SS.t.InvalidAddress ) );
			}

			if ( ! data.city ) {
				this.errors.push( this.validation_error( 'data_city', TVD_SS.t.InvalidCity ) );
			}

			if ( ! data.country ) {
				this.errors.push( this.validation_error( 'data_country', TVD_SS.t.InvalidCountry ) );
			}

			if ( ! data.state ) {
				this.errors.push( this.validation_error( 'data_state', TVD_SS.t.InvalidState ) );
			}

			if ( ! data.zip ) {
				this.errors.push( this.validation_error( 'data_zip', TVD_SS.t.InvalidZip ) );
			}
		},
		validatePhoneField: function ( data ) {
			if ( ! data.phone ) {
				this.errors.push( this.validation_error( 'data_phone', TVD_SS.t.InvalidPhone ) );
			}
		},
		validateEmailField: function ( data ) {
			if ( ! data.email ) {
				this.errors.push( this.validation_error( 'data_email', TVD_SS.t.NoEmail ) );

				return;
			}

			if ( ! ( /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test( data.email ) ) ) {
				this.errors.push( this.validation_error( 'data_email', TVD_SS.t.InvalidEmail ) );
			}
		},
		validateLinkField: function ( data ) {
			if ( ! data.text ) {
				this.errors.push( this.validation_error( 'data_text', TVD_SS.t.InvalidText ) );
			}

			if ( ! data.url ) {
				this.errors.push( this.validation_error( 'data_url', TVD_SS.t.NoURL ) );

				return
			}

			var pattern = new RegExp( '^((ft|htt)ps?:\\/\\/)?' + // protocol
			                          '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name and extension
			                          '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
			                          '(\\:\\d+)?' + // port
			                          '(\\/[-a-z\\d%@_.~+&:]*)*' + // path
			                          '(\\?[;&a-z\\d%@_.,~+&:=-]*)?' + // query string
			                          '(\\#[-a-z\\d_]*)?$', 'i' ); // fragment locator
			if ( ! pattern.test( data.url ) ) {
				this.errors.push( this.validation_error( 'data_url', TVD_SS.t.InvalidURL ) );
			}
		},
		validateLocationField: function ( data ) {
			if ( ! data.location ) {
				this.errors.push( this.validation_error( 'data_location', TVD_SS.t.InvalidLocation ) );
			}
		}
	} );
	/**
	 * Fields collection
	 */
	TVD_SS.collections.Fields = TVD_SS.collections.Base.extend( {
		model: TVD_SS.models.Field
	} );
	/**
	 * Group model
	 */
	TVD_SS.models.Group = TVD_SS.models.Base.extend( {
		idAttribute: 'id',
		defaults: {
			name: '',
			is_default: 0,
			created_at: '',
			updated_at: ''
		},
		initialize: function () {

			this.set( { fields: new TVD_SS.collections.Fields( this.get( 'fields' ) ) } )
		},
		url: function () {
			var url = TVD_SS.routes.groups;

			if ( this.get( 'id' ) || this.get( 'id' ) === 0 ) {
				url += '/' + this.get( 'id' );
			}

			return url;
		},
		/**
		 * Overwrite Backbone validation
		 * Return something to invalidate the model
		 *
		 * @param {Object} attrs
		 * @param {Object} options
		 */
		validate: function ( attrs, options ) {
			var errors = [];

			if ( ! attrs.name ) {
				errors.push( this.validation_error( 'name', TVD_SS.t.InvalidName ) );
			}

			if ( errors.length ) {
				return errors;
			}
		}
	} );
	/**
	 * Groups collection
	 */
	TVD_SS.collections.Groups = TVD_SS.collections.Base.extend( {
		model: TVD_SS.models.Group
	} );

} )( jQuery );