<?php
/**
 * Plugin Name:     Instructor Role Dummy Data Commands
 * Plugin URI:      www.dummy-site.com
 * Description:     Generate dummy data for your instructor role website.
 * Author:          Kumar Rajpurohit
 * Author URI:      https://github.com/kumar-rajpurohit
 * Text Domain:     ir-dummy-data-commands
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Ir_Dummy_Data_Commands
 */

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

// require_once __DIR__.'/class-ir-dummy-data-commands.php';
class Instructor_Role_Dummy_Data_Commands extends \WP_CLI_Command {
	/**
	 * Add dummy instructors
	 */
	public function add_instructors() {
		$select   = array( 'firstName', 'lastName', 'email', 'username', 'password' );
		$response = wp_remote_get(
			add_query_arg(
				array(
					'limit'  => 10,
					'select' => implode( ',', $select ),
					'skip'   => random_int( 1, 90 ),
				),
				'https://dummyjson.com/users'
			)
		);

		if ( is_wp_error( $response ) ) {
			echo "\n\n Couldn't fetch dummy users... Maybe something wrong with the API?";
			return;
		}

		$dummy_user_data = json_decode( wp_remote_retrieve_body( $response ) );
		$new_users       = array();

		foreach ( $dummy_user_data->users as $dummy_user ) {
			$new_user = wp_insert_user(
				array(
					'user_pass'  => $dummy_user->password,
					'user_login' => $dummy_user->username,
					'user_email' => $dummy_user->email,
					'first_name' => $dummy_user->firstName,
					'last_name'  => $dummy_user->lastName,
					'role'       => 'wdm_instructor',
				)
			);

			if ( ! is_wp_error( $new_user ) ) {
				$new_users[] = $new_user;
				echo "\n Created new user {$dummy_user->firstName} {$dummy_user->lastName} with ID {$new_user}";

				echo "\n -- Adding courses for new user";
				// Create courses for instructor.
				$this->add_courses( $new_user );
			}
		}

		if ( empty( $new_users ) ) {
			echo 'No new users created... Some problem in user creation';
		} else {
			echo sprintf( "\n\n Created %d new users", count( $new_users ) );
		}
	}

	/**
	 * Add dummy courses.
	 *
	 * @param int $user_id  Author ID.
	 */
	public function add_courses( $user_id ) {
		$user = get_userdata( $user_id );

		// Check if user exists.
		if ( false === $user ) {
			echo "\n User $user_id does not exist";
			return;
		}

		// Check if learndash active.
		if ( ! class_exists( 'SFWD_LMS' ) ) {
			echo "\n Learndash is not installed or activated, hence cannot create courses.";
			return;
		}

		$response = wp_remote_get(
			add_query_arg(
				array(
					'limit' => 2,
					'skip'  => random_int( 1, 140 ),
				),
				'https://dummyjson.com/posts'
			)
		);

		if ( is_wp_error( $response ) ) {
			echo "\n\n Couldn't fetch dummy posts... Maybe something wrong with the API?";
			return;
		}

		$dummy_course_data = json_decode( wp_remote_retrieve_body( $response ) );
		$new_courses       = array();

		foreach ( $dummy_course_data->posts as $dummy_post ) {
			$new_course = wp_insert_post(
				array(
					'post_title'   => $dummy_post->title,
					'post_content' => $dummy_post->body,
					'post_status'  => 'publish',
					'post_author'  => $user_id,
					'post_type'    => learndash_get_post_type_slug( 'course' ),
				)
			);

			if ( ! is_wp_error( $new_course ) ) {
				$new_courses[] = $new_course;
				echo "\n Created new course {$dummy_post->firstName} {$dummy_post->lastName} with ID {$new_course}";

				// Create course Steps.
				// $this->add_course_steps( $new_user, $new_course, 1 );
			}
		}

		if ( empty( $new_courses ) ) {
			echo "\n No new courses created for user $user_id... Some problem in course creation";
		} else {
			echo sprintf( "\n\n Created %d new courses", count( $new_courses ) );
		}
	}

	/**
	 * Add course steps to a given course
	 *
	 * @param int  $course_id
	 * @param int  $user_id
	 * @param bool $skip_checks
	 */
	public function add_course_steps( $course_id, $user_id, $skip_checks = false ) {
		// Skip user and LD checks if already made.
		if ( ! $skip_checks ) {
			$user = get_userdata( $user_id );

			// Check if user exists.
			if ( false === $user ) {
				echo "\n User $user_id does not exist";
				return;
			}

			// Check if learndash active.
			if ( ! class_exists( 'SFWD_LMS' ) ) {
				echo "\n Learndash is not installed or activated, hence cannot create courses.";
				return;
			}
		}
	}

	/**
	 * Add dummy students
	 *
	 * @param Array $args Arguments in array format.
	 * @param Array $assoc_args Key value arguments stored in associated array format.
	 */
	public function add_students( $args, $assoc_args ) {
		// Check if learndash active.
		if ( ! class_exists( 'SFWD_LMS' ) ) {
			\WP_CLI::line( 'Learndash is not installed or activated.' );
			\WP_CLI::line( 'Exiting peacefully...' );
			return;
		}

		$student_count = 0;
		$course_id     = 0;
		// Extract associative arguments
		$student_count = intval( $assoc_args['count'] );
		$course_id     = intval( $assoc_args['course'] );

		// Check if valid student count.
		if ( empty( $student_count ) ) {
			\WP_CLI::line( 'Please provide no of students to add with --count argument' );
			return;
		}

		// Check if valid course ID passed.
		if ( empty( $course_id ) ) {
			\WP_CLI::line( 'Please provide valid course ID to enroll students into with --course argument' );
			return;
		}

		// Check if valid learndash course.
		if ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $course_id ) ) {
			\WP_CLI::line( "Course ID: $course_id is not a valid LearnDash Course. Try again..." );
			return;
		}

		$select   = array( 'firstName', 'lastName', 'email', 'username', 'password' );
		$response = wp_remote_get(
			add_query_arg(
				array(
					'limit'  => $student_count,
					'select' => implode( ',', $select ),
					'skip'   => random_int( 1, $student_count ),
				),
				'https://dummyjson.com/users'
			)
		);

		if ( is_wp_error( $response ) ) {
			echo "\n\n Couldn't fetch dummy users... Maybe something wrong with the API?";
			return;
		}

		$dummy_user_data = json_decode( wp_remote_retrieve_body( $response ) );
		$new_users       = array();

		foreach ( $dummy_user_data->users as $dummy_user ) {
			$new_user = wp_insert_user(
				array(
					'user_pass'  => $dummy_user->password,
					'user_login' => $dummy_user->username,
					'user_email' => $dummy_user->email,
					'first_name' => $dummy_user->firstName,
					'last_name'  => $dummy_user->lastName,
				)
			);

			if ( ! is_wp_error( $new_user ) ) {
				$new_users[] = $new_user;
				\WP_CLI::line( "Created new user {$dummy_user->firstName} {$dummy_user->lastName} with ID {$new_user}" );

				\WP_CLI::line( '-- Enrolling into a course' );
				// Enroll student into course.
				$this->enroll_user( $new_user, $course_id );
			}
		}

		if ( empty( $new_users ) ) {
			\WP_CLI::line( 'No new users created... Some problem in user creation' );
		} else {
			\WP_CLI::line( sprintf( "\n\n Created %d new users", count( $new_users ) ) );
		}
	}

	/**
	 * Enroll users into courses
	 *
	 * @param int $user_id
	 * @param int $course_id
	 */
	public function enroll_user( $user_id, $course_id ) {
		if ( empty( $user_id ) ) {
			\WP_CLI::line( 'Empty user ID' );
			return;
		}

		if ( empty( $course_id ) ) {
			\WP_CLI::line( 'Empty course ID' );
			return;
		}

		if ( ! function_exists( 'ld_update_course_access' ) ) {
			\WP_CLI::line( 'Learndash Inactive, cannot enroll into courses without LearnDash.' );
			return;
		}

		if ( ld_update_course_access( $user_id, $course_id ) ) {
			\WP_CLI::line( "User $user_id enrolled into Course $course_id successfully!!" );
		} else {
			\WP_CLI::line( "User $user_id NOT enrolled into Course $course_id. :(" );
		}
	}
}

function ir_dummy_cli_register_commands() {
	\WP_CLI::add_command( 'dummy-ir', 'Instructor_Role_Dummy_Data_Commands' );
}

add_action( 'cli_init', 'ir_dummy_cli_register_commands' );
