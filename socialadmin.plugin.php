<?php
namespace Habari;

class SocialAdmin extends Plugin
{
	/**
	* Add additional controls to the User page
	*
	* @param FormUI $form The form that is used on the User page
	* @param Post $post The user being edited
	**/
	public function action_form_user( $form, $edit_user )
	{
		$services = Plugins::filter( 'socialauth_services', array() );
		if( count($services) ) {
			$socials = $form->append( 'wrapper', 'linked_socialnets_wrapper', 'Linked Social Nets');
			$socials->class = 'container settings';
			$socials->append( 'static', 'linked_socialnets', _t( '<h2>Linked Social Nets</h2>', __CLASS__ ) );
			
			foreach( $services as $service ) {
				$fieldname = "servicelink_$service";
				$fieldcaption = isset( $edit_user->info->{$fieldname} ) ? _t( 'Linked to %s account', array( $service ), __CLASS__ ) : '<a href="' . $form->get_theme()->socialauth_link( $service, array( 'state' => 'userinfo' ) ) . '">' . _t( 'Link %s account', array( $service ), __CLASS__ ) . '</a>';
				$servicefield = $socials->append( 'text', $fieldname, 'null:null', $fieldcaption );
				$servicefield->value = isset( $edit_user->info->{$fieldname} ) ? $edit_user->info->{$fieldname} : '';
				$servicefield->class[] = 'item clear';
				$servicefield->template = "optionscontrol_text";
			}
			
			$form->move_after( $socials, $form->user_info );
		}
	}
	
	/**
	 * Add the Additional User Fields to the list of valid field names.
	 * This causes adminhandler to recognize the fields and
	 * to set the userinfo record appropriately
	**/
	public function filter_adminhandler_post_user_fields( $fields )
	{
		$services = Plugins::filter( 'socialauth_services', array() );
		if ( !is_array($services) || count( $services ) == 0 ) {
			return;
		}

		foreach($services as $service) {
			$fields[ $service ] = "servicelink_$service";
		}
		return $fields;
	}
	
	/*
	 * Handle the result when a user identified himself
	 */
	public function action_socialauth_identified( $service, $userdata, $state = '' )
	{
		switch($state) {
			case 'userinfo':
				$user = User::identify();
				$fieldname = "servicelink_$service";
				$user->info->{$fieldname} = $userdata['id'];
				$user->update();
				Utils::redirect( URL::get( 'admin', array( 'page' => 'user', 'user' => $user->username ) ) );
				break;
			case 'loginform':
				$users = Users::get( array( 'info' => array( "servicelink_$service" => $userdata['id'] ) ) );
				if( count( $users ) > 1 ) {
					// TODO: Handle multiple linked accounts
				}
				else {
					$user = $users[0];
					$user->remember();
					Eventlog::log( _t( 'Successful %1$s login for %2$s', array( $service, $user->username ), __CLASS__ ), 'info', 'authentication' );
					Utils::redirect( URL::get( 'admin' ) );
				}
				break;
		}
	}
	
	/**
	 * Add the redirect links for the services to the login form
	 * 0.10 style with new FormUI
	**/
	public function action_form_login($form)
	{
		$services = Plugins::filter( 'socialauth_services', array() );
		$html = '';
		foreach( $services as $service ) {
			$html .= '<p><a href="' . $form->get_theme()->socialauth_link($service, array('state' => 'loginform')) . '">' . _t( 'Login with %s', array ( $service ), __CLASS__ ) . '</a></p>';
		}
		$form->append(FormControlStatic::create('socialadmin', 'null:null'))->set_static($html);
	}
}
?>