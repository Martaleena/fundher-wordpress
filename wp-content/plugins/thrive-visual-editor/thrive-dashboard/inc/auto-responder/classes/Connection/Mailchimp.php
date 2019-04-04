<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 02.04.2015
 * Time: 15:33
 */
class Thrive_Dash_List_Connection_Mailchimp extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function getType() {
		return 'autoresponder';
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function getEmailMergeTag() {
		return '*|EMAIL|*';
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Mailchimp';
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function outputSetupForm() {
		$related_api = Thrive_Dash_List_Manager::connectionInstance( 'mandrill' );
		if ( $related_api->isConnected() ) {
			$credentials = $related_api->getCredentials();
			$this->setParam( 'email', $credentials['email'] );
			$this->setParam( 'mandrill-key', $credentials['key'] );
		}

		$this->_directFormHtml( 'mailchimp' );
	}

	/**
	 * just save the key in the database
	 *
	 * @return mixed
	 */
	public function readCredentials() {
		$mandrill_key = ! empty( $_POST['connection']['mandrill-key'] ) ? $_POST['connection']['mandrill-key'] : '';

		if ( isset( $_POST['connection']['mailchimp_key'] ) ) {
			$_POST['connection']['mandrill-key'] = $_POST['connection']['key'];
			$_POST['connection']['key']          = $_POST['connection']['mailchimp_key'];
			$mandrill_key                        = $_POST['connection']['mandrill-key'];
		}

		if ( empty( $_POST['connection']['key'] ) ) {
			return $this->error( __( 'You must provide a valid Mailchimp key', TVE_DASH_TRANSLATE_DOMAIN ) );
		}

		$this->setCredentials( $_POST['connection'] );

		$result = $this->testConnection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to Mailchimp using the provided key (<strong>%s</strong>)', TVE_DASH_TRANSLATE_DOMAIN ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		/** @var Thrive_Dash_List_Connection_Mandrill $related_api */
		$related_api = Thrive_Dash_List_Manager::connectionInstance( 'mandrill' );

		if ( ! empty( $mandrill_key ) ) {
			/**
			 * Try to connect to the email service too
			 */

			$related_api = Thrive_Dash_List_Manager::connectionInstance( 'mandrill' );
			$r_result    = true;
			if ( ! $related_api->isConnected() ) {
				$r_result = $related_api->readCredentials();
			}

			if ( $r_result !== true ) {
				$this->disconnect();

				return $this->error( $r_result );
			}
		} else {
			/**
			 * let's make sure that the api was not edited and disconnect it
			 */
			$related_api->setCredentials( array() );
			Thrive_Dash_List_Manager::save( $related_api );
		}

		return $this->success( __( 'Mailchimp connected successfully', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function testConnection() {
		/**
		 * just try getting a list as a connection test
		 */

		try {
			/** @var Thrive_Dash_Api_Mailchimp $mc */
			$mc = $this->getApi();

			$mc->request( 'lists' );
		} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * disconnect (remove) this API connection
	 */
	public function disconnect() {

		$this->setCredentials( array() );
		Thrive_Dash_List_Manager::save( $this );

		/**
		 * disconnect the email service too
		 */
		$related_api = Thrive_Dash_List_Manager::connectionInstance( 'mandrill' );
		$related_api->setCredentials( array() );
		Thrive_Dash_List_Manager::save( $related_api );

		return $this;
	}

	/**
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return bool|mixed|string|void
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function addSubscriber( $list_identifier, $arguments ) {

		list( $first_name, $last_name ) = $this->_getNameParts( $arguments['name'] );

		$optin     = isset( $arguments['mailchimp_optin'] ) && $arguments['mailchimp_optin'] == 's' ? 'subscribed' : 'pending';
		$interests = new stdClass();
		$email     = strtolower( $arguments['email'] );

		/** @var Thrive_Dash_Api_Mailchimp $api */
		try {
			$api = $this->getApi();
		} catch ( Thrive_Dash_Api_Mailchimp_Error $e ) {
			return $e->getMessage() ? $e->getMessage() : __( 'Unknown Mailchimp Error', TVE_DASH_TRANSLATE_DOMAIN );
		}

		$merge_fields = new stdClass();

		if ( isset( $first_name ) && ! empty( $first_name ) ) {
			$merge_fields->FNAME = $first_name;
		}

		if ( isset( $last_name ) && ! empty( $last_name ) ) {
			$merge_fields->LNAME = $last_name;
		}

		if ( isset( $arguments['name'] ) && ! empty( $arguments['name'] ) ) {
			$merge_fields->NAME = $arguments['name'];
		}

		if ( isset( $arguments['mailchimp_groupin'] ) && $arguments['mailchimp_groupin'] != '0' && ! empty( $arguments['mailchimp_group'] ) ) {
			$group_ids             = explode( ',', $arguments['mailchimp_group'] );
			$params['list_id']     = $list_identifier;
			$params['grouping_id'] = $arguments['mailchimp_groupin'];
			$grouping              = $this->_getGroups( $params );

			if ( ! empty( $grouping ) ) {
				$interests = array();
				foreach ( $grouping[0]->groups as $group ) {
					if ( in_array( $group->id, $group_ids ) ) {
						$interests[ $group->id ] = true;
					}
				}
			}
		}

		if ( isset( $arguments['phone'] ) && ! empty( $arguments['phone'] ) ) {
			$phone_tag  = false;
			$merge_vars = $this->getCustomFields( $list_identifier );
			foreach ( $merge_vars as $item ) {

				if ( $item->type == 'phone' || $item->name == $arguments['phone'] ) {
					$phone_tag                = true;
					$item_name                = $item->name;
					$item_tag                 = $item->tag;
					$merge_fields->$item_name = $arguments['phone'];
					$merge_fields->$item_tag  = $arguments['phone'];
				}
			}

			/**
			 * if we don't have a phone merge field let's create one
			 */
			if ( $phone_tag == false ) {
				try {
					$api->request( 'lists/' . $list_identifier . '/merge-fields', array(
						'name' => 'phone',
						'type' => 'phone',
						'tag'  => 'phone',
					), 'POST' );

					$merge_fields->phone = $arguments['phone'];
				} catch ( Thrive_Dash_Api_Mailchimp_Error $e ) {
					return $e->getMessage() ? $e->getMessage() : __( 'Unknown Mailchimp Error', TVE_DASH_TRANSLATE_DOMAIN );
				} catch ( Exception $e ) {
					return $e->getMessage() ? $e->getMessage() : __( 'Unknown Error', TVE_DASH_TRANSLATE_DOMAIN );
				}
			}
		}

		try {

			$contact = $api->request( 'lists/' . $list_identifier . '/members/' . md5( $email ), array(), 'GET' );

			if ( $contact->status === 'unsubscribed' ) {
				$optin = 'pending';
			}
		} catch ( Exception $exception ) {
		}

		try {

			$data = array(
				'email_address' => $arguments['email'],
				'status'        => isset( $contact->status ) && $contact->status === 'subscribed' ? 'subscribed' : $optin,
				'merge_fields'  => $merge_fields,
				'interests'     => $interests,
				'status_if_new' => $optin,
			);

			// On double optin, send assign the tags directly to the body [known problems on mailchimp tags endpoint]
			if ( isset( $arguments['mailchimp_optin'] ) && $arguments['mailchimp_optin'] === 'd' && isset( $arguments['mailchimp_tags'] ) && ! empty( $arguments['mailchimp_tags'] ) ) {
				$tags         = explode( ',', $arguments['mailchimp_tags'] );
				$data['tags'] = $tags;
			}

			$api->request( 'lists/' . $list_identifier . '/members/' . md5( $email ), $data, 'PUT' );

		} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
			//mailchimp returns 404 if email contact already exists?
			//$e->getMessage() ? $e->getMessage() : __( 'Unknown Mailchimp Error', TVE_DASH_TRANSLATE_DOMAIN );
		} catch ( Exception $e ) {

			return $e->getMessage() ? $e->getMessage() : __( 'Unknown Error', TVE_DASH_TRANSLATE_DOMAIN );
		}

		// Mailchimp tags for other optin beside double
		if ( isset( $arguments['mailchimp_tags'] ) && ! empty( $arguments['mailchimp_tags'] ) && isset( $arguments['mailchimp_optin'] ) && $arguments['mailchimp_optin'] != 'd' ) {
			try {
				$tags = explode( ',', $arguments['mailchimp_tags'] );
				$this->addTagsToContact( $list_identifier, $email, $tags );
			} catch ( Thrive_Dash_Api_Mailchimp_Error $e ) {
			}
		}

		return true;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	protected function _getGroups( $params ) {

		$return    = array();
		$groupings = new stdClass();
		/** @var Thrive_Dash_Api_Mailchimp $api */
		$api   = $this->getApi();
		$lists = $api->request( 'lists', array( 'count' => 1000 ) );

		if ( empty( $params['list_id'] ) && ! empty( $lists ) ) {
			$params['list_id'] = $lists->lists[0]->id;
		}

		foreach ( $lists->lists as $list ) {
			if ( $list->id == $params['list_id'] ) {
				$groupings = $api->request( 'lists/' . $params['list_id'] . '/interest-categories', array( 'count' => 1000 ) );
			}
		}

		if ( $groupings->total_items > 0 ) {
			foreach ( $groupings->categories as $grouping ) {
				//if we have a grouping id in the params, we should only get that grouping
				if ( isset( $params['grouping_id'] ) && $grouping->id !== $params['grouping_id'] ) {
					continue;
				}
				$groups = $api->request( 'lists/' . $params['list_id'] . '/interest-categories/' . $grouping->id . '/interests', array( 'count' => 1000 ) );

				if ( $groups->total_items > 0 ) {
					$grouping->groups = $groups->interests;
				}
				$return[] = $grouping;
			}
		}

		return $return;
	}

	/**
	 * @param $list
	 *
	 * @return array|string|void
	 */
	public function getCustomFields( $list ) {

		try {
			/** @var Thrive_Dash_Api_Mailchimp $api */
			$api = $this->getApi();

			$merge_vars = $api->request( 'lists/' . $list . '/merge-fields' );

			if ( $merge_vars->total_items === 0 ) {
				return array();
			}
		} catch ( Thrive_Dash_Api_Mailchimp_Error $e ) {
			return $e->getMessage() ? $e->getMessage() : __( 'Unknown Mailchimp Error', TVE_DASH_TRANSLATE_DOMAIN );
		} catch ( Exception $e ) {
			return $e->getMessage() ? $e->getMessage() : __( 'Unknown Error', TVE_DASH_TRANSLATE_DOMAIN );
		}

		return $merge_vars->merge_fields;
	}

	/**
	 * @param $list_id
	 * @param $email_address
	 * @param $tags
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function addTagsToContact( $list_id, $email_address, $tags ) {
		if ( ! $list_id || ! $email_address || ! $tags ) {
			throw new Thrive_Dash_Api_Mailchimp_Exception( __( 'Missing required parameters for adding tags to contact', TVE_DASH_TRANSLATE_DOMAIN ) );

			return false;
		}

		$list_tags = $this->getListTags( $list_id );

		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag_name ) {

				if ( isset( $list_tags[ $tag_name ] ) ) {
					// Assign existing tag to contact/subscriber
					$tag_id = $list_tags[ $tag_name ]['id'];

					try {
						$this->assignTag( $list_id, $tag_id, $email_address );
					} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
						$this->_error = $e->getMessage() . ' ' . __( 'Please re-check your API connection details.', TVE_DASH_TRANSLATE_DOMAIN );
					}

					continue;
				}

				try {
					// Create tag and assign it to contact/subscriber
					$this->createAndAssignTag( $list_id, $tag_name, $email_address );
				} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
				}
			}
		}
	}

	/**
	 * Get all tags of a list
	 *
	 * @param $list_id
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function getListTags( $list_id ) {

		$segments_by_name = array();
		$count            = 100; //default is 10
		$offset           = 0;
		$total_items      = 0;

		do {
			/** @var Thrive_Dash_Api_Mailchimp $api */
			$api = $this->getApi();

			$response = $api->request( 'lists/' . $list_id . '/segments', array( 'count' => $count, 'offset' => $offset ), 'GET', true );

			if ( is_object( $response ) && ( isset( $response->total_items ) && $response->total_items > 0 ) ) {
				$total_items = $response->total_items;

				if ( empty( $response->segments ) ) {
					break;
				}

				foreach ( $response->segments as $segment ) {
					$segments_by_name[ $segment->name ]['id']   = $segment->id;
					$segments_by_name[ $segment->name ]['name'] = $segment->name;
					$segments_by_name[ $segment->name ]['type'] = $segment->type;
				}

				$offset += $count;
			}
		} while ( count( $segments_by_name ) < $total_items );

		return $segments_by_name;
	}

	/**
	 * @param $list_id
	 * @param $tag_id
	 * @param $email_address
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function assignTag( $list_id, $tag_id, $email_address ) {
		if ( ! $list_id || ! $tag_id || ! $email_address ) {
			throw new Thrive_Dash_Api_Mailchimp_Exception( __( 'Missing required parameters for adding tags to contact', TVE_DASH_TRANSLATE_DOMAIN ) );

			return false;
		}

		$save_tag = $this->getApi()->request( 'lists/' . $list_id . '/segments/' . $tag_id . '/members', array( 'email_address' => $email_address ), 'POST' );
		if ( is_object( $save_tag ) && isset( $save_tag->id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $list_id
	 * @param $tag_name
	 * @param $email_address
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function createAndAssignTag( $list_id, $tag_name, $email_address ) {
		if ( ! $list_id || ! $tag_name || ! $email_address ) {
			return false;
		}

		try {
			$created_tag = $this->createTag( $list_id, $tag_name, $email_address );
		} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
		}

		if ( is_object( $created_tag ) && isset( $created_tag->id ) ) {
			return $this->assignTag( $list_id, $created_tag->id, $email_address );
		}

		return false;
	}

	/**
	 * @param $list_id
	 * @param $tag_name
	 * @param $email_address
	 *
	 * @return bool|object
	 */
	public function createTag( $list_id, $tag_name, $email_address ) {
		if ( ! $list_id || ! $tag_name || ! $email_address ) {
			return false;
		}

		$tag = $this->getApi()->request( 'lists/' . $list_id . '/segments', array( 'name' => $tag_name, 'static_segment' => array() ), 'POST' );

		return $tag;
	}

	/**
	 * Allow the user to choose whether to have a single or a double optin for the form being edited
	 * It will hold the latest selected value in a cookie so that the user is presented by default with the same option selected the next time he edits such a form
	 *
	 * @param array $params
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function get_extra_settings( $params = array() ) {
		$params['optin'] = empty( $params['optin'] ) ? ( isset( $_COOKIE['tve_api_mailchimp_optin'] ) ? $_COOKIE['tve_api_mailchimp_optin'] : 'd' ) : $params['optin'];
		setcookie( 'tve_api_mailchimp_optin', $params['optin'], strtotime( '+6 months' ), '/' );
		$groups           = $this->_getGroups( $params );
		$params['groups'] = $groups;

		return $params;
	}

	/**
	 * Allow the user to choose whether to have a single or a double optin for the form being edited
	 * It will hold the latest selected value in a cookie so that the user is presented by default with the same option selected the next time he edits such a form
	 *
	 * @param array $params
	 *
	 * @throws Thrive_Dash_Api_Mailchimp_Exception
	 */
	public function renderExtraEditorSettings( $params = array() ) {
		$params['optin'] = empty( $params['optin'] ) ? ( isset( $_COOKIE['tve_api_mailchimp_optin'] ) ? $_COOKIE['tve_api_mailchimp_optin'] : 'd' ) : $params['optin'];
		setcookie( 'tve_api_mailchimp_optin', $params['optin'], strtotime( '+6 months' ), '/' );
		$groups           = $this->_getGroups( $params );
		$params['groups'] = $groups;
		$this->_directFormHtml( 'mailchimp/api-groups', $params );
		$this->_directFormHtml( 'mailchimp/optin-type', $params );
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return mixed
	 */
	protected function _apiInstance() {
		return new Thrive_Dash_Api_Mailchimp( $this->param( 'key' ) );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array
	 */
	protected function _getLists() {

		try {
			/** @var Thrive_Dash_Api_Mailchimp $mc */
			$mc = $this->getApi();

			$raw   = $mc->request( 'lists', array( 'count' => 1000 ) );
			$lists = array();

			if ( empty( $raw->total_items ) || empty( $raw->lists ) ) {
				return array();
			}
			foreach ( $raw->lists as $item ) {

				$lists [] = array(
					'id'   => $item->id,
					'name' => $item->name,
				);
			}

			return $lists;
		} catch ( Thrive_Dash_Api_Mailchimp_Exception $e ) {
			$this->_error = $e->getMessage() . ' ' . __( 'Please re-check your API connection details.', TVE_DASH_TRANSLATE_DOMAIN );

			return false;
		}
	}
}
