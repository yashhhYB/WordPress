<?php
/**
 * Handles the comment submission process in WordPress.
 *
 * This file processes the comment form submission, validates and sanitizes user input,
 * and inserts the comment into the database. If errors occur, the user is redirected accordingly.
 *
 * Security measures include:
 * - Nonce verification to prevent CSRF attacks.
 * - Input sanitization to prevent XSS attacks.
 * - Error handling for missing or invalid data.
 *
 * @package WordPress
 */

require dirname(__FILE__) . '/wp-load.php';

if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
    wp_die( __( 'Error: This page only accepts POST requests.', 'textdomain' ) );
}

// Verify that the comment submission is coming from a legitimate source.
check_admin_referer( 'comment-form' );

/**
 * Filters the comment data before it is validated and inserted.
 *
 * @since 3.1.0
 * @param array $comment_data The comment data submitted.
 */
$comment_post_ID = (int) $_POST['comment_post_ID'];

// Ensure that the post ID exists.
if ( empty( $comment_post_ID ) || ! get_post( $comment_post_ID ) ) {
    wp_die( __( 'Invalid post ID. The post may have been deleted.', 'textdomain' ) );
}

// Validate and sanitize user-submitted data.
$comment_author       = isset( $_POST['author'] ) ? sanitize_text_field( $_POST['author'] ) : '';
$comment_author_email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
$comment_author_url   = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
$comment_content      = isset( $_POST['comment'] ) ? wp_kses_post( trim( $_POST['comment'] ) ) : '';

// Check if the comment content is empty.
if ( empty( $comment_content ) ) {
    wp_die( __( 'Error: Your comment cannot be empty.', 'textdomain' ) );
}

// Prepare comment data for insertion.
$comment_data = array(
    'comment_post_ID'      => $comment_post_ID,
    'comment_author'       => $comment_author,
    'comment_author_email' => $comment_author_email,
    'comment_author_url'   => $comment_author_url,
    'comment_content'      => $comment_content,
    'comment_type'         => '', // Default to a standard comment.
    'comment_parent'       => 0,  // Parent comment (if any).
    'user_id'              => get_current_user_id(), // Associate with logged-in user.
    'comment_approved'     => 0,  // Hold for moderation by default.
);

// Insert the comment into the database.
$comment_id = wp_insert_comment( wp_slash( $comment_data ) );

if ( ! $comment_id ) {
    wp_die( __( 'Error: Unable to submit comment. Please try again.', 'textdomain' ) );
}

// Redirect the user back to the post after successful submission.
$redirect_url = get_permalink( $comment_post_ID ) . '#comment-' . $comment_id;
wp_safe_redirect( $redirect_url );
exit;
