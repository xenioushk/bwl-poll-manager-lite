<?php

// Data Filters.

add_filter( 'bpm_data_filter', 'bpm_data_filter',10, 3 );

// result_type = number/percentage
// default percentage

function bpm_data_filter( $opt_title, $opt_id, $result_type = 'percentage' ) {

    global $post;

	// echo "<pre>";
	// echo get_the_ID();echo "<br>";
	// echo $opt_title;echo "<br>";
	// echo $opt_id;echo "<br>";
	//
	//
	//
	// echo "</pre>";
}



// BPM Option Filters.
//
// @Description: if filter parameter is two dimentionsal array then it's return first content of array. other wise it's return the field value
// @Since: Version 1.1.2
// @Date: 15-04-2016

add_filter( 'bpm_option_data', 'func_bpm_option_data', 10, 2 );

function func_bpm_option_data( $field_value, $poll_id = 0 ) {

	if ( isset( $field_value[0] ) && is_array( $field_value[0] ) && ! empty( $field_value[0] ) ) {

		return $field_value[0]; // for old version

	} elseif ( isset( $field_value ) && is_array( $field_value ) && ! empty( $field_value ) ) {

		// Handling old data
		// We are going to convert old data format to new data format.
		//
		// echo "<pre>";
		// print_r($field_value);
		// echo "</pre>";
		// global $post;

		$bpm_mod_field_value = [];

		foreach ( $field_value as $key => $value ) {

			$opt_id = wp_rand(); // generate random key for option id.

			// $poll_id = get_the_ID();

				$bpm_mod_field_value[] = [
					'value'  => $value, // set option value.
					'opt_id' => $opt_id, // set option id.
				];

				// we are going to get old option votes and set in to new ID.

				$unique_poll_value      = strtolower( trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', $value ) ) );
				$bpm_old_options_id     = 'bpm_opt_' . $poll_id . '_' . $unique_poll_value;
				$bpm_opt_old_vote_count = get_post_meta( $poll_id, $bpm_old_options_id, true ); // Get option votes.

				// New option meta key.
				$bpm_new_options_id = 'bpm_opt_' . $poll_id . '_' . $opt_id;
				// echo "<br>";
				// echo $value;

				// echo "<br>";
				// echo "New Option ID: " .$bpm_new_options_id;
				// echo "<br>";
				// echo "Old Vote Count: " .$bpm_opt_old_vote_count;
				// echo "<br>";
				// Set old data in to new meta key.
				update_post_meta( $poll_id, $bpm_new_options_id, $bpm_opt_old_vote_count );

		}

		// Remove all Old option fields.
		delete_post_meta( $poll_id, 'bpm_options' );

		// Insert new option field array in to DB.
		update_post_meta( $poll_id, 'bpm_options', $bpm_mod_field_value );

		// Finally update conversions status.
		update_post_meta( $poll_id, 'bpm_opt_slug_conv_status', 1 );

		// echo "<pre>";
		// print_r($bpm_mod_field_value);
		// echo "</pre>";
		return $bpm_mod_field_value; // for old version
	}
	else {

		return ''; // return nothing.

	}

}


// BPM Option Counter.
// @Description: Count each poll options.
// @Since: Version 1.0.4
// @Date: 09-05-2016

add_filter( 'bpm_option_counter', 'func_bpm_option_counter' );

function func_bpm_option_counter( $field_value ) {

	if ( isset( $field_value[0] ) && is_array( $field_value[0] ) && ! empty( $field_value[0] ) ) {

		return $field_value[0]; // for old version

	} elseif ( isset( $field_value ) && is_array( $field_value ) && ! empty( $field_value ) ) {

		return $field_value;
	}
	else {

		return ''; // return nothing.

	}

}