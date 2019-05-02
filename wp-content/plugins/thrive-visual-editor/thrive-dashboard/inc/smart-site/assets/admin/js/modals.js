/**
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
} )( jQuery );