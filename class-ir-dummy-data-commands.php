<?php
/**
 * Class Instructor Role Dummy Data Commands
 *
 * @since      0.1.0
 * @package    Instructor_Role_Dummy_Data_Commands
 * @subpackage Instructor_Role_Dummy_Data_Commands/classes
 * @author     Kumar Rajpurohit <https://github.com/kumar-rajpurohit>
 */

namespace Instructor_Role_Dummy_Data_Commands\Classes;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Instructor_Role_Dummy_Data_Commands' ) && class_exists( 'WP_CLI_Command' ) ) {
	class Instructor_Role_Dummy_Data_Commands extends \WP_CLI_Command {
		function add_instructors() {
			$user_data = wp_remote_get(
				add_query_arg(
					array(
						'limit' => 10,
					),
					'https://dummyjson.com/users'
				)
			);

			error_log( 'User Data: ' . print_r( $user_data ) )

			var_dump( $user_data );
		}
	}
	\WP_CLI::add_command( 'dummy-ir', 'Instructor_Role_Dummy_Data_Commands' );
}
