/**
 * Created by Pop Aurelian on 10-Jan-19.
 */

var TVD_SS = TVD_SS || {};
TVD_SS.util = TVD_SS.util || {};

TVD_SS.util.field_types = {
	0: 'text',
	1: 'address',
	2: 'phone',
	3: 'email',
	4: 'link',
	5: 'location'
};

TVD_SS.util.get_field_type_name = function(id) {
	return TVD_SS.util.field_types[id];
};;var TVD_SS = TVD_SS || {};
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

} )( jQuery );;/**
 * Created by Pop Aurelian on 07-Jan-19.
 */
var TVD_SS = TVD_SS || {};
TVD_SS.views = TVD_SS.views || {};


( function ( $ ) {
	$( function () {
		/**
		 * Add/Edit Field Modal
		 */
		TVD_SS.views.AddFieldModal = TVE_Dash.views.Modal.extend( {
			template: TVE_Dash.tpl( 'global-fields/modals/field' ),
			prev_type: null,
			events: {
				'click .tvd-modal-close': 'close',
				'click .tvd-tss-add-another-field': 'addAnotherField',
				'click .tvd-tss-save-field': 'save'
			},
			afterInitialize: function ( data ) {
				this.fields = new TVD_SS.collections.Fields( this.model ? this.model : '' );
				this.listenTo( this.fields, 'add', this.afterRender );
				this.listenTo( this.fields, 'remove', this.afterRender );
				this.fields.on( 'add_field', this.addFieldToCollection, this )
				this.fields.on( 'render_fields', this.renderFields, this )
			},
			afterRender: function () {
				this.renderFields();
				this.renderAddMore();
			},
			/**
			 * Render the add more view
			 */
			renderAddMore: function () {
				var view = new TVD_SS.views.AddMore( {
					el: this.$( '.tvd-tss-fields-add-more' )[ 0 ]
				} );

				view.render();
			},
			/**
			 * Render all the fields
			 */
			renderFields: function () {
				this.$( '.tvd-tss-fields-wrapper' ).empty();
				this.fields.each( this.renderField, this );
				TVE_Dash.materialize( this.$el );
			},
			/**
			 * Render one Field
			 * @param field
			 */
			renderField: function ( field ) {
				var opened = this.fields.findWhere( { opened: true } );

				if ( ! opened && this.fields.last() === field ) {
					field.set( { opened: true } )
				}

				var view = new TVD_SS.views.ModalFieldData( {
					model: field,
					group: this.group,
					collection: this.fields
				} );

				this.$( '.tvd-tss-fields-wrapper' ).append( view.render().$el );
			},
			/**
			 * Add a field to the collection whenever "Add another field is clicked"
			 */
			addFieldToCollection: function () {
				var opened = this.fields.findWhere( { opened: true } ),
					data = { opened: true, data: {} };

				if ( opened ) {
					opened.set( { opened: false } );
				}

				if ( this.group.get( 'id' ) ) {
					data.group_id = this.group.get( 'id' );
				}

				var new_model = new TVD_SS.models.Field( data );
				this.fields.add( new_model );
			},
			/**
			 * Check if previous field is valid before adding another one to the collection
			 */
			addAnotherField: function () {
				var model = this.fields.last();

				model = model.trigger( 'validate' );

				if ( model.errors.length === 0 ) {
					this.addFieldToCollection();
				}
			},
			/**
			 * Save the field
			 */
			save: function () {

				this.tvd_clear_errors();

				var opened = this.fields.findWhere( { opened: true } ).trigger( 'validate' );

				if ( opened.errors.length > 0 ) {
					return;
				}

				TVE_Dash.showLoader();

				var self = this;
				$.ajax( {
					headers: {
						'X-WP-Nonce': TVD_SS.nonce
					},
					type: 'POST',
					url: TVD_SS.routes.fields + '/save_fields/',
					data: { models: this.fields.toJSON() }
				} ).done( function ( response, status, options ) {
					if ( response ) {
						self.collection.add( response );
					}

					TVE_Dash.success( TVD_SS.t.FieldsSaved );
					self.close();
				} ).error( function ( errorObj ) {
					var error = JSON.parse( errorObj.responseText );
					TVE_Dash.err( error.message );
				} ).always( function () {
					TVE_Dash.hideLoader();

				} );
			}
		} );

		/**
		 * Modal for editing a field
		 */
		TVD_SS.views.EditFieldModal = TVE_Dash.views.Modal.extend( {
			template: TVE_Dash.tpl( 'global-fields/modals/edit-field' ),
			events: {
				'click .tvd-tss-field-type': 'selectType',
				'click .tvd-tss-save-field': 'save',
				'input .input-change': 'setInputData',
				'click .tvd-modal-close': 'close'
			},
			afterInitialize: function ( data ) {
				this.listenTo( this.model, 'change:type', this.render );
			},
			afterRender: function () {
				this.renderByType();
			},
			/**
			 * Select the input type
			 *
			 * @param e
			 */
			selectType: function ( e ) {
				this.model.set( { data: {} } );
				this.model.set( { type: $( e.currentTarget ).attr( 'data-id' ) } );

			},
			/**
			 * Set data to the model when inputs are changed
			 *
			 * @param e
			 */
			setInputData: function ( e ) {
				this.tvd_clear_errors();
				var field = $( e.currentTarget ).attr( 'data-field' ),
					props = field.split( '_' );

				if ( props.length === 1 ) {
					this.model.set( props[ 0 ], e.currentTarget.value );
				} else if ( props.length > 1 ) {
					this.model.get( props[ 0 ] )[ props[ 1 ] ] = e.currentTarget.value;
				}
			},
			/**
			 * Render inputs depending on the selected type of field
			 */
			renderByType: function () {
				var type = TVD_SS.util.get_field_type_name( parseInt( this.model.get( 'type' ) ) );

				if ( ! TVD_SS.views[ 'Field' + TVE_Dash.upperFirst( type ) + 'Options' ] ) {
					return;
				}

				var view = new TVD_SS.views[ 'Field' + TVE_Dash.upperFirst( type ) + 'Options' ]( {
					model: this.model,
					el: this.$( '.tvd-tss-field-data-wrapper' )[ 0 ]

				} );

				view.render();
				TVE_Dash.materialize( this.$el );
			},
			/**
			 * Save the data
			 *
			 * @returns {Backbone.View}
			 */
			save: function () {
				this.tvd_clear_errors();

				if ( ! this.model.isValid() ) {
					return this.tvd_show_errors( this.model );
				}

				var self = this,
					xhr = this.model.save();

				if ( xhr ) {
					xhr.done( function ( response, status, options ) {
						self.model.trigger( 'render_field' );

						self.close();
						TVE_Dash.success( TVD_SS.t.FieldSaved );
					} );
					xhr.error( function ( errorObj ) {
						var error = JSON.parse( errorObj.responseText );
						TVE_Dash.err( error.message );
					} );
					xhr.always( function () {
						TVE_Dash.hideLoader();
					} );

				}
			}

		} );

		/**
		 * Add/Edit Group Modal
		 */
		TVD_SS.views.GroupModal = TVD_SS.views.AddFieldModal.extend( {
			template: TVE_Dash.tpl( 'global-fields/modals/group' ),
			events: function () {
				return _.extend( {}, TVD_SS.views.AddFieldModal.prototype.events, {
					'input #tvd-tss-group-name': 'setName',
					'click .tvd-tss-add-fields-to-group': 'startFields'
				} );
			},
			afterInitialize: function ( data ) {
				TVD_SS.views.AddFieldModal.prototype.afterInitialize.apply( this, arguments );


			},
			/**
			 * Render the add more view but only if we have fields added
			 */
			renderAddMore: function () {
				if ( this.fields.length > 0 ) {
					TVD_SS.views.AddFieldModal.prototype.renderAddMore.apply( this, arguments );
				}
			},
			/**
			 * Render the fields but only if we have any added
			 */
			renderFields: function () {
				this.$( '.tvd-tss-fields-wrapper' ).empty();
				if ( this.fields.length === 0 ) {
					var view = new TVD_SS.views.AddGroupField( {} );

					this.$( '.tvd-tss-fields-wrapper' ).append( view.render().$el );
				} else {
					this.fields.each( this.renderField, this );

				}
				TVE_Dash.materialize( this.$el );
			},
			/**
			 * Start adding fields to the group
			 */
			startFields: function () {
				this.fields.add( new TVD_SS.models.Field() );
			},
			/**
			 * Set the group name
			 *
			 * @param e
			 */
			setName: function ( e ) {
				this.group.set( { name: e.currentTarget.value } );
			},
			/**
			 * Save the group
			 */
			save: function () {

				this.tvd_clear_errors();

				if ( ! this.group.isValid() ) {
					return this.tvd_show_errors( this.group );
				}

				if ( this.fields.length > 0 ) {
					this.group.set( { fields: new TVD_SS.collections.Fields( this.fields.toJSON() ) } )
					var opened = this.fields.findWhere( { opened: true } ).trigger( 'validate' );

					if ( opened.errors.length > 0 ) {
						return;
					}
				}

				TVE_Dash.showLoader();

				var xhr = this.group.save(),
					self = this;


				if ( xhr ) {
					xhr.done( function ( response, status, options ) {

						self.group.set( { fields: new TVD_SS.collections.Fields( self.group.get( 'fields' ) ) } );
						self.collection.add( self.group );

						self.close();
						TVE_Dash.success( TVD_SS.t.GroupSaved );
					} );
					xhr.error( function ( errorObj ) {
						var error = JSON.parse( errorObj.responseText );
						TVE_Dash.err( error.message );
					} );
					xhr.always( function () {
						TVE_Dash.hideLoader();
					} );
				}
			}
		} );

		/**
		 * Modal foe editing the group
		 */
		TVD_SS.views.EditGroupModal = TVE_Dash.views.Modal.extend( {
			template: TVE_Dash.tpl( 'global-fields/modals/edit-group' ),
			events: {
				'input #tvd-tss-group-name': 'setName',
				'click .tvd-modal-submit': 'save'
			},
			/**
			 * Set the group name
			 *
			 * @param e
			 */
			setName: function ( e ) {
				this.model.set( { name: e.currentTarget.value } );
			},
			/**
			 * Save the group
			 */
			save: function () {

				this.tvd_clear_errors();

				if ( ! this.model.isValid() ) {
					return this.tvd_show_errors( this.model );
				}
				TVE_Dash.showLoader();

				var id = this.model.get( 'id' ),
					xhr = this.model.save(),
					self = this;


				if ( xhr ) {
					xhr.done( function ( response, status, options ) {

						self.model.set( { fields: new TVD_SS.collections.Fields( self.model.get( 'fields' ) ) } )
						if ( id ) {
							self.model.trigger( 'render_groups' );
						} else {
							self.collection.add( self.model );
						}

						self.close();
						TVE_Dash.success( TVD_SS.t.GroupSaved );
					} );
					xhr.error( function ( errorObj ) {
						var error = JSON.parse( errorObj.responseText );
						TVE_Dash.err( error.message );
					} );
					xhr.always( function () {
						TVE_Dash.hideLoader();
					} );
				}
			}
		} );

		/**
		 * Delete a group or field
		 */
		TVD_SS.views.DeleteModal = TVE_Dash.views.Modal.extend( {
			template: TVE_Dash.tpl( 'global-fields/modals/delete' ),
			events: {
				'click .tvd-delete-item': 'deleteItem'
			},
			afterInitialize: function ( args ) {
				this.$el.addClass( 'tvd-red' );
				var _this = this;
				_.defer( function () {
					_this.$( '.tvd-delete-item' ).focus();
				} );
			},
			/**
			 * Destroy the model
			 * @param e
			 */
			deleteItem: function ( e ) {
				var self = this;

				TVE_Dash.showLoader();
				var xhr = this.model.destroy();
				if ( xhr ) {
					xhr.done( function ( response, status, options ) {
						TVE_Dash.success( self.model.get( 'name' ) + ' ' + TVD_SS.t.ItemDeleted );
					} );
					xhr.error( function ( errorObj ) {
						var error = JSON.parse( errorObj.responseText );
						TVE_Dash.err( error.message );
					} );
					xhr.always( function () {
						TVE_Dash.hideLoader();
						self.close();
					} );
				}
			}
		} );
	} );
} )( jQuery );;/**
 * Created by Pop Aurelian on 07-Jan-19.
 */
var TVD_SS = TVD_SS || {};
TVD_SS.views = TVD_SS.views || {};

( function ( $ ) {
	$( function () {
		/**
		 * remove tvd-invalid class for all inputs in the view's root element
		 *
		 * @returns {Backbone.View}
		 */
		Backbone.View.prototype.tvd_clear_errors = function () {
			this.$( '.tvd-invalid' ).removeClass( 'tvd-invalid' );
			this.$( 'select' ).trigger( 'tvdclear' );
			return this;
		};

		/**
		 *
		 * @param {Backbone.Model|object} [model] backbone model or error object with 'field' and 'message' properties
		 *
		 * @returns {Backbone.View|undefined}
		 */
		Backbone.View.prototype.tvd_show_errors = function ( model ) {
			model = model || this.model;

			if ( ! model ) {
				return;
			}

			var err = model instanceof Backbone.Model ? model.validationError : model,
				self = this,
				$all = $();

			function show_error( error_item ) {
				if ( typeof error_item === 'string' ) {
					return TVE_Dash.err( error_item );
				}
				$all = $all.add( self.$( '[data-field=' + error_item.field + ']' ).addClass( 'tvd-invalid' ).each( function () {
					var $this = $( this );
					if ( $this.is( 'select' ) ) {
						$this.trigger( 'tvderror', error_item.message );
					} else {
						$this.next( 'label' ).attr( 'data-error', error_item.message )
					}
				} ) );
			}

			if ( $.isArray( err ) ) {
				_.each( err, function ( item ) {
					show_error( item );
				} );
			} else {
				show_error( err );
			}
			$all.not( '.tvd-no-focus' ).first().focus();
			/* if the first error message is not visible, scroll the contents to include it in the viewport. At the moment, this is only implemented for modals */
			this.scroll_first_error( $all.first() );

			return this;
		};

		/**
		 * scroll the contents so that the first errored input is visible
		 * currently this is only implemented for modals
		 *
		 * @param {Object} $input first input element that has the error
		 *
		 * @returns {Backbone.View}
		 */
		Backbone.View.prototype.scroll_first_error = function ( $input ) {
			if ( ! ( this instanceof TVE_Dash.views.Modal ) || ! $input.length ) {
				return this;
			}
			var input_top = $input.offset().top,
				content_top = this.$_content.offset().top,
				scroll_top = this.$_content.scrollTop(),
				content_height = this.$_content.outerHeight();
			if ( input_top >= content_top && input_top < content_height + content_top - 50 ) {
				return this;
			}

			this.$_content.animate( {
				'scrollTop': scroll_top + input_top - content_top - 40 // 40px difference
			}, 200, 'swing' );
		};


		/**
		 * Base View
		 */
		TVD_SS.views.Base = Backbone.View.extend( {
			/**
			 * Always try to return this !!!
			 *
			 * @returns {ThriveChurn.views.Base}
			 */
			render: function () {
				return this;
			},
			/**
			 *
			 * Instantiate and open a new modal which has the view constructor assigned and send params further along
			 *
			 * @param ViewConstructor View constructor
			 * @param params
			 */
			modal: function ( ViewConstructor, params ) {
				return TVE_Dash.modal( ViewConstructor, params );
			},
			bind_zclip: function () {
				/**
				 * Keep the old ZClip working
				 */
				TVE_Dash.bindZClip( this.$el.find( 'a.tvd-copy-to-clipboard' ) );

				var $element = this.$el.find( '.tva-sendowl-search' );

				function bind_it() {
					$element.each( function () {
						var $elem = $( this ),
							$input = $elem.prev().on( 'click', function ( e ) {
								this.select();
								e.preventDefault();
								e.stopPropagation();
							} ),
							_default_btn_color_class = $elem.attr( 'data-tvd-btn-color-class' ) || 'tva-copied';

						try {
							$elem.zclip( {
								path: TVE_Dash_Const.dash_url + '/js/util/jquery.zclip.1.1.1/ZeroClipboard.swf',
								copy: function () {
									return jQuery( this ).prev().val();
								},
								afterCopy: function () {
									var $link = jQuery( this );
									$input.select();
									$link.removeClass( _default_btn_color_class ).addClass( 'tva-copied' );
									setTimeout( function () {
										$link.removeClass( 'tva-copied' );
									}, 2000 );
								}
							} );
						} catch ( e ) {
							console.error && console.error( 'Error embedding zclip - most likely another plugin is messing this up' ) && console.error( e );
						}
					} );
				}

				setTimeout( bind_it, 200 );
			}
		} );

		/**
		 * Header View
		 */
		TVD_SS.views.Header = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'header' ),
			render: function () {
				this.$el.html( this.template( {} ) );
				return this;
			}
		} );

		TVD_SS.views.Menu = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'menu' ),
			render: function () {
				this.$el.html( this.template( {} ) );

				return this;
			}
		} );
		/**
		 * breadcrumbs view - renders breadcrumb links
		 */
		TVD_SS.views.Breadcrumbs = TVD_SS.views.Base.extend( {
			el: $( '#tvd-tss-breadcrumbs-wrapper' )[ 0 ],
			template: TVE_Dash.tpl( 'breadcrumbs' ),
			/**
			 * setup collection listeners
			 */
			initialize: function () {
				this.$title = $( 'head > title' );
				this.original_title = this.$title.html();
				this.listenTo( this.collection, 'change', this.render );
				this.listenTo( this.collection, 'add', this.render );
			},
			/**
			 * render the html
			 */
			render: function () {
				this.$el.empty().html( this.template( {links: this.collection} ) );
				return this;
			}
		} );


		/**
		 * Dashboard view (Global Fields for now)
		 */
		TVD_SS.views.Dashboard = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'dashboard' ),
			render: function () {
				this.$el.html( this.template( {} ) );

				return this;
			}
		} );

		TVD_SS.views.GlobalFields = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global_fields' ),
			events: {
				'click .tvd-tss-add-group': 'addGroup',
			},
			initialize: function () {
				this.listenTo( this.collection, 'add', this.render );
				this.listenTo( this.collection, 'remove', this.render );
			},
			render: function () {
				this.$el.html( this.template( {} ) );

				this.renderSideMenu();
				this.renderGroups();
				this.$( '.tvd-tss-group-item' ).first().addClass( 'tvd-active-group' );
				return this;
			},
			renderSideMenu: function () {
				var view = new TVD_SS.views.SideMenu( {
					el: this.$( '.tvd-tss-side-menu' )
				} );

				view.render();
			},
			renderGroups: function () {
				this.collection.each( this.renderGroup, this );

			},
			renderGroup: function ( group ) {
				var expanded = this.collection.findWhere( {expanded: true} );

				if ( ! expanded && this.collection.first() === group ) {
					group.set( {expanded: true} )
				}

				var view = new TVD_SS.views.Group( {
					model: group,
					collection: this.collection
				} );

				this.$( '.tvd-tss-groups-wrapper' ).append( view.render().$el );
			},
			addGroup: function () {
				this.modal( TVD_SS.views.GroupModal, {
					collection: this.collection,
					group: new TVD_SS.models.Group(),
					'max-width': '60%',
					width: '800px'
				} );
			}
		} );

		/**
		 * View for the side menu
		 */
		TVD_SS.views.SideMenu = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'side-menu' ),
			render: function () {

				this.$el.html( this.template( {page: Backbone.history.getFragment()} ) );

				return this;
			}
		} );
		/**
		 * Group view
		 */
		TVD_SS.views.Group = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/group' ),
			tagName: 'li',
			className: 'tvd-tss-group-item',
			events: {
				'click .tvd-tss-add-field': 'addField',
				'click .tvd-tss-delete-group': 'deleteGroup',
				'click .tvd-tss-edit-group': 'editGroup',
				'click .tvd-tss-expand': 'expandGroup'
			},

			initialize: function () {
				this.$el.addClass( 'tvd-tss-group-item-' + this.model.get( 'id' ) );
				this.listenTo( this.model.get( 'fields' ), 'add', this.render );
				this.listenTo( this.model.get( 'fields' ), 'remove', this.render );
				this.listenTo( this.model, 'change:expanded', this.render );
				this.model.on( 'render_groups', this.render, this );
				this.$el.on( 'click', this.activateGroup.bind( this ) );
			},
			activateGroup: function ( e ) {
				this.$el.closest( '.tvd-tss-groups-wrapper' ).find( '.tvd-active-group' ).removeClass( 'tvd-active-group' );
				this.expandGroup();
				this.$el.addClass( 'tvd-active-group' );

			},
			render: function () {
				this.$el.html( this.template( {model: this.model} ) );

				this.renderFields();

				return this;
			},
			/**
			 * Expand the group
			 */
			expandGroup: function () {
				var expanded = this.collection.findWhere( {expanded: true} );

				if ( expanded ) {
					expanded.set( {expanded: false} )
				}
				this.model.set( {expanded: true} );
				this.render();
			},
			/**
			 * Render each group fields
			 */
			renderFields: function () {

				if ( this.model.get( 'expanded' ) ) {
					if ( this.model.get( 'fields' ).length === 0 ) {
						this.$( '.tvd-tss-fields-wrapper' ).html( '<p class="tvd-tss-placeholder">' + TVD_SS.t.NoFields + '</p>' );
					} else {
						this.model.get( 'fields' ).each( this.renderField, this );
					}
				}
			},
			/**
			 * Render one field
			 *
			 * @param field
			 */
			renderField: function ( field ) {
				var view = new TVD_SS.views.Field( {
					model: field,
					collection: this.model.get( 'fields' ),
					group: this.model
				} );

				this.$( '.tvd-tss-fields-wrapper' ).append( view.render().$el )
			},
			/**
			 * Open modal for adding a field
			 */
			addField: function () {
				var model = new TVD_SS.models.Field( {group_id: this.model.get( 'id' )} );

				this.modal( TVD_SS.views.AddFieldModal, {
					model: model,
					collection: this.model.get( 'fields' ),
					group: this.model,
					'max-width': '60%',
					width: '800px'
				} );
			},
			/**
			 * Open delete group modal
			 */
			deleteGroup: function () {
				this.modal( TVD_SS.views.DeleteModal, {
					model: this.model,
					collection: this.collection,
					'max-width': '60%',
					width: '800px'
				} );
			},
			/**
			 * Open edit group modal
			 */
			editGroup: function () {
				this.modal( TVD_SS.views.EditGroupModal, {
					model: this.model,
					collection: this.collection,
					'max-width': '60%',
					width: '800px'
				} );
			}
		} );

		/**
		 * Start adding fields in group view
		 */
		TVD_SS.views.AddGroupField = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/add-group-field' ),
			className: 'tvd-row',
			render: function () {
				this.$el.html( this.template( {} ) );
				return this;
			}
		} );

		/**
		 * Add more button view
		 */
		TVD_SS.views.AddMore = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/add-more' ),
			render: function () {
				this.$el.html( this.template( {} ) );
				return this;
			}
		} );

		/**
		 * Field View
		 */
		TVD_SS.views.Field = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/field' ),
			tagName: 'li',
			className: 'tvd-tss-field-item',
			events: {
				'click .tvd-tss-delete-field': 'deleteField',
				'click .tvd-tss-edit-field': 'editField'
			},
			initialize: function ( options ) {
				this.group = options.group;
				this.model.on( 'render_field', this.render, this );
			},
			render: function () {
				this.$el.html( this.template( {model: this.model} ) );

				return this;
			},
			deleteField: function () {
				this.modal( TVD_SS.views.DeleteModal, {
					model: this.model,
					collection: this.collection,
					'max-width': '60%',
					width: '800px'
				} );
			},
			editField: function () {
				this.modal( TVD_SS.views.EditFieldModal, {
					model: this.model,
					collection: this.collection,
					group: this.group,
					'max-width': '60%',
					width: '800px',
					edit: true
				} );
			}
		} );

		/**
		 * Modal Field Data to be rendered
		 */
		TVD_SS.views.ModalFieldData = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/field-data' ),
			className: 'tvd-new-field-row',
			events: {
				'click .tvd-tss-field-type': 'selectType',
				'click .tvd-tss-show-field': 'showField',
				'click .tvd-tss-delete-field': 'deleteField',
				'input .input-change': 'setInputData'
			},
			initialize: function ( data ) {
				this.listenTo( this.model, 'change:type', this.render );
				this.model.on( 'validate', this.validate, this );
				this.group = data.group
			},
			render: function () {
				this.$el.html( this.template( {model: this.model, group: this.group, collection: this.collection} ) );
				this.renderByType();

				this.$el.toggleClass( 'tve-opened-field', this.model.get( 'opened' ) );

				return this;
			},
			showField: function () {
				var model = this.collection.last().trigger( 'validate' );

				if ( model.errors.length === 0 ) {
					var opened = this.collection.findWhere( {opened: true} );
					if ( opened ) {
						opened.set( {opened: false} );
					}

					this.model.set( {opened: true} );

					this.collection.trigger( 'render_fields' );
				}

			},
			renderByType: function () {
				var type = TVD_SS.util.get_field_type_name( parseInt( this.model.get( 'type' ) ) );

				if ( ! TVD_SS.views[ 'Field' + TVE_Dash.upperFirst( type ) + 'Options' ] || ! this.model.get( 'opened' ) ) {
					return;
				}

				var view = new TVD_SS.views[ 'Field' + TVE_Dash.upperFirst( type ) + 'Options' ]( {
					model: this.model,
					el: this.$( '.tvd-tss-field-data-wrapper' )[ 0 ]

				} );

				view.render();
				TVE_Dash.materialize( this.$el );
			},
			deleteField: function () {
				this.collection.remove( this.model );
			},
			/**
			 * Set the field type
			 * @param e
			 */
			selectType: function ( e ) {
				this.model.set( {data: {}} );
				this.model.set( {type: $( e.currentTarget ).attr( 'data-id' )} );

			},
			/**
			 * Set the data in the model for any input field we have
			 *
			 * @param e
			 */
			setInputData: function ( e ) {
				this.tvd_clear_errors();
				var field = $( e.currentTarget ).attr( 'data-field' ),
					props = field.split( '_' );

				if ( props.length === 1 ) {
					this.model.set( props[ 0 ], e.currentTarget.value );
				} else if ( props.length > 1 ) {
					this.model.get( props[ 0 ] )[ props[ 1 ] ] = e.currentTarget.value;
				}
			},
			validate: function () {

				if ( ! this.model.isValid() ) {
					return this.tvd_show_errors( this.model );
				}

				return true;
			}
		} );

		/**
		 * Text Field options
		 */
		TVD_SS.views.FieldTextOptions = TVD_SS.views.Base.extend( {
			template: TVE_Dash.tpl( 'global-fields/text-options' ),
			render: function () {
				this.$el.html( this.template( {model: this.model} ) );

				return this;
			}
		} );

		/**
		 * Address Field options
		 */
		TVD_SS.views.FieldAddressOptions = TVD_SS.views.FieldTextOptions.extend( {
			template: TVE_Dash.tpl( 'global-fields/address-options' )
		} );

		/**
		 * Phone Field options
		 */
		TVD_SS.views.FieldPhoneOptions = TVD_SS.views.FieldTextOptions.extend( {
			template: TVE_Dash.tpl( 'global-fields/phone-options' )
		} );

		/**
		 * Email Field options
		 */
		TVD_SS.views.FieldEmailOptions = TVD_SS.views.FieldTextOptions.extend( {
			template: TVE_Dash.tpl( 'global-fields/email-options' )
		} );

		/**
		 * Link Field options
		 */
		TVD_SS.views.FieldLinkOptions = TVD_SS.views.FieldTextOptions.extend( {
			template: TVE_Dash.tpl( 'global-fields/link-options' )
		} );

		/**
		 * Location Field options
		 */
		TVD_SS.views.FieldLocationOptions = TVD_SS.views.FieldTextOptions.extend( {
			template: TVE_Dash.tpl( 'global-fields/location-options' ),
			events: {
				'change .input-change': 'setInputData'
			},
			render: function () {
				this.$el.html( this.template( {model: this.model} ) );
				this.setInputData();
				return this;
			},
			setInputData: function () {
				var url = 'https://maps.google.com/maps?q=' + encodeURI( this.model.get( 'data' ).location ? this.model.get( 'data' ).location : 'New York' ) + '&t=m&z=10&output=embed&iwloc=near';
				this.$( '#tvd-tss-google-map' ).html( '<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="' + url + '"></iframe>' );
			}
		} );

	} );
} )( jQuery );;/**
 * Created by Pop Aurelian on 07-Jan-19.
 */

var TVD_SS = TVD_SS || {};
TVD_SS.globals = TVD_SS.globals || {};

( function ( $ ) {
	/**
	 * Router for the app
	 */
	var Router = Backbone.Router.extend( {
		$el: $( '#tvd-tss-wrapper' ),
		routes: {
			'dashboard': 'dashboard',
			'global_fields': 'global_fields',

		},
		header: null,
		dashboard_view: null,
		menu_view: null,
		sidemenu_view: null,
		breadcrumbs: {
			col: null,
			view: null
		},
		/**
		 * set the current page - adds the structure to breadcrumbs and sets the new document title
		 *
		 * @param {string} section page hierarchy
		 * @param {string} label current page label
		 *
		 * @param {Array} [structure] optional the structure of the links that lead to the current page
		 */
		set_page: function ( section, label, structure ) {
			this.breadcrumbs.col.reset();
			structure = structure || {};

			/* Thrive Admin Dashboard is always the first element */
			this.breadcrumbs.col.add_page( TVD_SS.dash_url, TVD_SS.t.Thrive_Dashboard, true );

			_.each( structure, _.bind( function ( item ) {
				this.breadcrumbs.col.add_page( item.route, item.label );
			}, this ) );

			/**
			 * last link - no need for route
			 */
			this.breadcrumbs.col.add_page( '', label );

			/* update the page title */
			var $title = $( 'head > title' );
			if ( ! this.original_title ) {
				this.original_title = $title.html();
			}

			$title.html( label + ' &lsaquo; ' + this.original_title )
		},
		/**
		 * Creates a view for the header
		 */
		renderHeader: function () {
			if ( ! this.header ) {
				this.header = new TVD_SS.views.Header( {
					el: '#tvd-tss-header'
				} );
			} else {
				this.header.setElement( $( '#tvd-tss-header' ) );
			}

			this.header.render();

		},
		/**
		 * Initialize breadcrumbs
		 */
		init_breadcrumbs: function () {
			this.breadcrumbs.col = new TVD_SS.collections.Breadcrumbs();
			this.breadcrumbs.view = new TVD_SS.views.Breadcrumbs( {
				collection: this.breadcrumbs.col
			} )
		},
		/**
		 * Render the menu
		 */
		renderMenu: function() {
			this.menu_view = new TVD_SS.views.Menu({

			});

			this.$el.find('.tvd-tss-menu').html( this.menu_view.render().$el );
		},
		/**
		 * Dashboard view
		 */
		dashboard: function (  ) {
			this.set_page( '', 'Smart Site', [
				{
					route: 'dashboard',
					label: TVD_SS.t.Thrive_Dashboard
				}
			] );

			this.renderHeader();
			this.renderMenu();

			if ( this.dashboard_view ) {
				this.dashboard_view.remove();
			}
			/**
			 * Start the dashboard
			 */
			this.dashboard_view = new TVD_SS.views.Dashboard({
				collection: TVD_SS.globals.groups
			});

			this.$el.find('.tvd-tss-content').html( this.dashboard_view.render().$el );

			TVE_Dash.materialize(this.$el);
		},
		/**
		 * Go to the global fields route
		 */
		global_fields: function (  ) {
			this.set_page( '', 'Global Fields', [
				{
					route: 'dashboard',
					label: TVD_SS.t.SmartSite
				},
				{
					route: 'dashboard',
					label: TVD_SS.t.Thrive_Dashboard
				}

			] );

			this.renderHeader();
			this.renderMenu();

			if ( this.dashboard_view ) {
				this.dashboard_view.remove();
			}
			/**
			 * Start the dashboard
			 */
			this.dashboard_view = new TVD_SS.views.GlobalFields({
				collection: TVD_SS.globals.groups
			});

			this.$el.find('.tvd-tss-content').html( this.dashboard_view.render().$el );

			TVE_Dash.materialize(this.$el);
		}

	});
	$( function () {

		/**
		 * Global Data
		 */
		TVD_SS.globals.groups = new TVD_SS.collections.Groups( TVD_SS.data.groups );

		/**
		 * Start the app
		 */
		TVD_SS.router = new Router;
		TVD_SS.router.init_breadcrumbs();
		Backbone.history.start( {hashchange: true} );

		/**
		 * Start app
		 */
		if ( ! Backbone.history.fragment ) {
			TVD_SS.router.navigate( '#global_fields', {trigger: true} );
		}
	} );

} )( jQuery );