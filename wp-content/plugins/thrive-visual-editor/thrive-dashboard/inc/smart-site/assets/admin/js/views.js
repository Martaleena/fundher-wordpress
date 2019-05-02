/**
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
} )( jQuery );