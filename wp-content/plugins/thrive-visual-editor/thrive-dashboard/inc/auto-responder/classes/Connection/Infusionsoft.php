<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 02.04.2015
 * Time: 15:33
 */
class Thrive_Dash_List_Connection_Infusionsoft extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function getType() {
		return 'autoresponder';
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Infusionsoft';
	}

	public function getListSubtitle() {
		return __( 'Choose your Tag Name List', 'thrive-dash' );
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function outputSetupForm() {
		$this->_directFormHtml( 'infusionsoft' );
	}

	/**
	 * just save the key in the database
	 *
	 * @return mixed
	 */
	public function readCredentials() {
		$client_id = ! empty( $_POST['connection']['client_id'] ) ? $_POST['connection']['client_id'] : '';
		$key       = ! empty( $_POST['connection']['api_key'] ) ? $_POST['connection']['api_key'] : '';

		if ( empty( $key ) || empty( $client_id ) ) {
			return $this->error( __( 'Client ID and API key are required', 'thrive-dash' ) );
		}

		$this->setCredentials( $_POST['connection'] );

		$result = $this->testConnection();

		if ( true !== $result ) {
			/* translators: %s: error message */
			$error = __( 'Could not connect to Infusionsoft using the provided credentials (<strong>%s</strong>)', 'thrive-dash' );

			return $this->error( sprintf( $error, $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( 'Infusionsoft connected successfully' );
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
		$result = $this->_getLists();

		if ( is_array( $result ) ) {
			return true;
		}

		/* At this point, $result will be a string */

		return $result;
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return mixed
	 */
	protected function _apiInstance() {
		return new Thrive_Dash_Api_Infusionsoft( $this->param( 'client_id' ), $this->param( 'api_key' ) );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array
	 */
	protected function _getLists() {
		try {
			/** @var Thrive_Dash_Api_Infusionsoft $api */
			$api = $this->getApi();

			$query_data      = array(
				'GroupName' => '%',
			);
			$selected_fields = array( 'Id', 'GroupName' );
			$response        = $api->data( 'query', 'ContactGroup', 1000, 0, $query_data, $selected_fields );

			if ( empty( $response ) ) {
				return array();
			}

			$tags = $response;

			/**
			 * Infusionsoft has a limit of 1000 results to fetch, we should get all tags if the user has more
			 */
			$i = 1;
			while ( count( $response ) === 1000 ) {
				$response = $api->data( 'query', 'ContactGroup', 1000, $i, $query_data, $selected_fields );
				$tags   = array_merge( $tags, $response );
				$i ++;
			}

			$lists = array();

			foreach ( $tags as $item ) {
				$lists[] = array(
					'id'   => $item['Id'],
					'name' => $item['GroupName'],
				);
			}

			return $lists;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * add a contact to a list
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return bool|string true for success or string error message for failure
	 */
	public function addSubscriber( $list_identifier, $arguments ) {
		try {
			/** @var Thrive_Dash_Api_Infusionsoft $api */
			$api = $this->getApi();

			list( $first_name, $last_name ) = $this->_getNameParts( $arguments['name'] );

			$data = array(
				'FirstName' => $first_name,
				'LastName'  => $last_name,
				'Email'     => $arguments['email'],
				'Phone1'    => $arguments['phone'],
			);

			$contact_id = $api->contact( 'addWithDupCheck', $data, 'Email' );

			if ( $contact_id ) {
				$api->APIEmail( 'optIn', $data['Email'], 'thrive opt in' );

				$today          = date( 'Ymj\TG:i:s' );
				$creation_notes = 'A web form was submitted with the following information:';

				if ( ! empty( $arguments['url'] ) ) {
					$creation_notes .= "\nReferring URL: " . $arguments['url'];
				}

				$creation_notes .= "\nIP Address: " . $_SERVER['REMOTE_ADDR'];
				$creation_notes .= "\ninf_field_Email: " . $arguments['email'];
				$creation_notes .= "\ninf_field_LastName: " . $last_name;
				$creation_notes .= "\ninf_field_FirstName: " . $first_name;

				$add_note = array(
					'ContactId'         => $contact_id,
					'CreationDate'      => $today,
					'CompletionDate'    => $today,
					'ActionDate'        => $today,
					'EndDate'           => $today,
					'ActionType'        => 'Other',
					'ActionDescription' => 'Thrive Leads Note',
					'CreationNotes'     => $creation_notes,
				);

				$api->data( 'add', 'ContactAction', $add_note );
			}

			$contact = $api->contact(
				'load',
				$contact_id,
				array(
					'Id',
					'Email',
					'Groups',
				)
			);

			$existing_groups = empty( $contact['Groups'] ) ? array() : explode( ',', $contact['Groups'] );

			if ( ! in_array( $list_identifier, $existing_groups ) ) {
				$api->contact( 'addToGroup', $contact_id, $list_identifier );
			}

			do_action( 'tvd_after_infusionsoft_contact_added', $this, $contact, $list_identifier, $arguments );

			return true;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function getEmailMergeTag() {
		return '~Contact.Email~';
	}

	/**
	 * Retrieve the contact's tags.
	 * Tags in Infusionsoft are named Groups
	 *
	 * @param int $contact_id
	 *
	 * @return array
	 */
	public function get_contact_tags( $contact_id ) {

		$tags = array();

		if ( empty( $contact_id ) ) {
			return $tags;
		}

		$api = $this->getApi();

		$query_data = array(
			'ContactId' => $contact_id,
		);

		$selected_fields = array(
			'GroupId',
			'ContactGroup',
		);

		$saved_tags = $api->data( 'query', 'ContactGroupAssign', 9999, 0, $query_data, $selected_fields );

		if ( ! empty( $saved_tags ) ) {
			/**
			 * set the group id as key in tags array and
			 * set as value the group name
			 */
			foreach ( $saved_tags as $item ) {
				$tags[ $item['GroupId'] ] = $item['ContactGroup'];
			}
		}

		return $tags;
	}

	/**
	 * Retrieve all tags(groups) form Infusionsoft for current connection
	 *
	 * @return array
	 */
	public function get_tags( $use_cache = true ) {

		$tags = array();

		if ( $use_cache ) {
			$lists = $this->getLists();
			foreach ( $lists as $list ) {
				$tags[ $list['id'] ] = $list['name'];
			}

			return $tags;
		}

		$api = $this->getApi();

		$query_data = array(
			'Id' => '%',
		);

		$selected_fields = array(
			'Id',
			'GroupName',
		);

		$saved_tags = $api->data( 'query', 'ContactGroup', 1000, 0, $query_data, $selected_fields );

		$data = $saved_tags;

		/**
		 * Infusionsoft has a limit of 1000 results to fetch, we should get all tags if the user has more
		 */
		$i = 1;
		while ( count( $saved_tags ) === 1000 ) {
			$saved_tags = $api->data( 'query', 'ContactGroup', 1000, $i, $query_data, $selected_fields );
			$data   = array_merge( $data, $saved_tags );
			$i ++;
		}

		if ( ! empty( $data ) ) {
			foreach ( $data as $item ) {
				$tags[ $item['Id'] ] = $item['GroupName'];
			}
		}

		return $tags;
	}

	/**
	 * Add a new Tag(Group) to Infusionsoft
	 *
	 * @param $tag_name
	 *
	 * @return int|null id
	 */
	public function create_tag( $tag_name ) {

		$query_data = array(
			'GroupName' => $tag_name,
		);

		$selected_fields = array(
			'Id',
			'GroupName',
		);

		$tags = $this->getApi()->data( 'query', 'ContactGroup', 1, 0, $query_data, $selected_fields );

		if ( is_array( $tags ) && ! empty( $tags ) ) {

			$tag = $tags[0];

			if ( isset( $tag['Id'] ) ) {

				return $tag['Id'];
			}
		}

		$id = $this->getApi()->data(
			'add',
			'ContactGroup',
			array(
				'GroupName' => $tag_name,
			)
		);

		$this->getLists( false );

		return ! empty( $id ) ? $id : null;
	}
}
