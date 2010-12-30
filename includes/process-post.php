<?php
			$options = get_option('ContentScheduler_Options');
			// Now, make the array we would pass to wp_update_post
			// This is a local variable, so each time process_post is called, it will be new
			$update_post = array('ID' => $postid);
  	        // Get the Post's ID into the update array
  	        // $update_post['ID'] = $postid;

  	        // STATUS AND VISIBILITY
  	        switch( $options['chg-status'] )
  	        {
				case "No Change":
					// we do not need a post_status key
					break;
				case "Pending":
					$update_post['post_status'] = 'pending';
					break;
				case "Draft":
					$update_post['post_status'] = 'draft';
					break;
				case "Private":
					$update_post['post_status'] = 'private';
					break;
				// default:
					// if it is anything else, let's make sure the post_status key is just gone from the array
					// NOTE: It would be better if we could just not make the array in the first place
					// unset( $update_post['post_status'] );
  	        } // end switch

  	        // ==========
  	        // Now we need a key for CATEGORIES
  	        // First, let's check and see if we want to do Category changing or not.
  	        if( $options['chg-cat-method'] != 'No Change' )
  	        {
  	        	// We do want category changes, so let's procees
				// list of categories we want to work with, as set in Content Scheduler > Options panel
				$category_switch = $options['selcats'];
				// list of categories the post is CURRENTLY in
				$current_category_objs = get_the_category($postid);
				// build a list of the post's current category ID's
				$current_category_ids = array();
				foreach( $current_category_objs as $object )
				{
					$current_category_ids[] = $object->term_id;
				} // end foreach
				
				switch( $options['chg-cat-method'] )
				{
					case "Add selected":
						// we want to have the current categories
						// PLUS the selected categories
						$category_switch = array_merge( $current_category_ids, $category_switch );
						$category_switch = array_unique( $category_switch );
						break;
					case "Remove selected":
						// we want to have the current categories
						// MINUS the selected categories
						$category_switch = array_diff( $current_category_ids, $category_switch );
						break;
					case "Match selected":
						// we want the categories to MATCH the selected categories
						// $category_switch is already set just fine
						break;
					default:
						unset( $update_post['post_category'] );
				} // end switch
				
				// set the 'post_category' part of update_post array
				$update_post['post_category'] = $category_switch;
			} // end if - checking chg-cat-method

  	        // Use the update array to actually update the post
  	        if( !wp_update_post( $update_post ) )
  	        {
  	        	// NOTE: We don't really want this to die
  	        	// Need to properly handle error on updating post, for 1.0
  	        	// for debug
  	        	// $this->log_to_file('debug', 'We failed to update a post');
  	        }
  	        else
  	        {
  	        	// update the post_meta so it won't end up here in our update query again
  	        	// We're not changing the expiration date, so we can look back and know when it expired.
  	        	update_post_meta( $postid, 'cs-enable-schedule', 'Disable' );
  	        }
  	        
			// STICKINESS (Pages do not have STICKY ability)
			// Note: This is stored in the options table, and is not part of post_update
			// get the array of sticky posts
			// What do we want to do with stickiness?
			$sticky_change = $options['chg-sticky'];
			if( $sticky_change == 'Unstick' )
			{
				$sticky_posts = get_option('sticky_posts');
				if( ! empty( $sticky_posts ) )
				{
					// Remove $postid from the $sticky_posts[] array
					foreach( $sticky_posts as $key => $stuck_id )
					{
						if( $stuck_id == $postid )
						{
							// remove $key from $sticky_posts
							unset( $sticky_posts[$key] );
							break;
						} // end if
					} // end foreach
					// Get the new array of stickies back into WP
					update_option('sticky_posts', $sticky_posts);
				} // end if
			} // end if
?>
