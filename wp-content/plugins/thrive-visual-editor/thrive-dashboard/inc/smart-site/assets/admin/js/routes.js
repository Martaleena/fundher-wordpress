/**
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