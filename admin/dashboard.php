<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Persona Dashboard', 'wp-withpersona'); ?></h1>
  <a href="admin.php?page=wp-withpersona-settings" class="page-title-action">Settings</a>
  <hr class="wp-header-end">
  <small>
    <p>
      All users and verification status are shown here.
    </p>
  </small>
  <!-- WordPress will inject notices here -->

  <div class="wpp-dashboard-content">
    <div class="wpp-users-section">
      <div class="wpp-users-container">
        <div class="wpp-users-list">
          <?php
          // Pagination settings
          $users_per_page = 5;
          $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
          $offset = ($current_page - 1) * $users_per_page;

          // Get total users count for specific roles
          $role__in = array('administrator', 'author', 'subscriber');
          $total_users = count(get_users(array(
            'role__in' => $role__in,
            'fields' => 'ID'
          )));
          $total_pages = ceil($total_users / $users_per_page);

          // Get paginated users
          $the_users = get_users(array(
            'role__in' => $role__in,
            'number' => $users_per_page,
            'offset' => $offset
          ));

          foreach ($the_users as $user) :
            $udata = get_userdata($user->ID);
            $registered = $udata->user_registered;
          ?>
            <div class="wpp-user-card">
              <div class="wpp-user-summary">
                <div class="wpp-user-info">
                  <span class="wpp-user-name"><?php echo esc_html($user->display_name); ?></span>
                  <span class="wpp-user-registered">Member since <?php echo date('M Y', strtotime($registered)); ?></span>
                </div>
                <button user_id="<?php echo esc_attr($user->ID); ?>" class="wpp-show-user button button-primary">
                  <span class="dashicons dashicons-visibility"></span> View Details
                </button>
              </div>

              <div id="details-user-<?php echo esc_attr($user->ID); ?>" class="wpp-user-details">
                <div class="wpp-details-section">
                  <h3>Basic Information</h3>
                  <table class="wpp-userdetails-table">
                    <?php if ($user->get('display_name')) : ?>
                      <tr>
                        <th>Name:</th>
                        <td><?php echo esc_html($user->get('display_name')); ?></td>
                      </tr>
                    <?php endif; ?>
                    <?php if ($user->get('user_email')) : ?>
                      <tr>
                        <th>Email:</th>
                        <td><?php echo esc_html($user->get('user_email')); ?></td>
                      </tr>
                    <?php endif; ?>
                  </table>
                </div>

                <div class="wpp-details-section">
                  <h3>Verification Status</h3>
                  <table class="wpp-userdetails-table">
                    <?php
                    $metas = get_user_meta($user->get('ID'));
                    $persona_verification_status = $metas['persona_verification_status'][0] ?? 'Not verified';
                    $persona_verification_last_checked = $metas['persona_verification_last_checked'][0] ?? time();
                    ?>
                    <tr>
                      <th>Status:</th>
                      <td>
                        <span class="wpp-status-badge wpp-status-<?php echo esc_attr($persona_verification_status); ?>">
                          <?php echo esc_html(ucfirst($persona_verification_status)); ?>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>Last Checked:</th>
                      <td><?php echo date('M d, Y h:i a', $persona_verification_last_checked); ?></td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <button
                          class="wpp-reverify-user button button-secondary"
                          data-user-id="<?php echo esc_attr($user->ID); ?>"
                          data-nonce="<?php echo wp_create_nonce('wpp_reverify_user_' . $user->ID); ?>">
                          <span class="dashicons dashicons-update"></span> Force Re-verification
                        </button>
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if ($total_pages > 1) : ?>
            <div class="wpp-pagination">
              <?php
              $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'array'
              ));

              if ($page_links) {
                echo '<div class="tablenav-pages">';
                echo '<span class="pagination-links">';
                echo join("\n", $page_links);
                echo '</span>';
                echo '</div>';
              }
              ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .wp-header-end {
    margin: 0;
    visibility: hidden;
  }

  .wpp-admin-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }

  .wpp-admin-header h1 {
    margin: 0;
  }

  .wpp-dashboard-content {
    margin-top: 20px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }

  .wpp-users-container {
    /* max-width: 1200px; */
    /* margin: 0 auto; */
  }

  .wpp-user-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    margin-bottom: 15px;
    padding: 15px;
    transition: all 0.3s ease;
  }

  .wpp-user-card:hover {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .wpp-user-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .wpp-user-info {
    display: flex;
    flex-direction: column;
  }

  .wpp-user-name {
    font-size: 16px;
    font-weight: 600;
    color: #1d2327;
  }

  .wpp-user-registered {
    font-size: 13px;
    color: #646970;
  }

  .wpp-user-details {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e4e7;
  }

  .wpp-details-section {
    margin-bottom: 20px;
  }

  .wpp-details-section h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #1d2327;
  }

  .wpp-userdetails-table {
    width: 100%;
    border-collapse: collapse;
  }

  .wpp-userdetails-table th,
  .wpp-userdetails-table td {
    padding: 8px 0;
    text-align: left;
    border-bottom: 1px solid #f0f0f1;
  }

  .wpp-userdetails-table th {
    width: 150px;
    color: #646970;
  }

  .wpp-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
  }

  .wpp-status-completed {
    background: #d1fae5;
    color: #065f46;
  }

  .wpp-status-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .wpp-status-not-verified {
    background: #fee2e2;
    color: #991b1b;
  }

  .wpp-show-user {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .wpp-show-user .dashicons {
    font-size: 16px;
    height: 16px;
    width: 16px;
  }

  .wpp-pagination {
    margin-top: 20px;
    text-align: center;
  }

  .wpp-pagination .tablenav-pages {
    display: inline-block;
    margin: 0;
  }

  .wpp-pagination .pagination-links {
    display: flex;
    gap: 5px;
    justify-content: center;
  }

  .wpp-pagination .page-numbers {
    display: inline-block;
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    text-decoration: none;
    color: #0073aa;
  }

  .wpp-pagination .page-numbers.current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
  }

  .wpp-pagination .page-numbers:hover {
    background: #f0f0f1;
  }

  .wpp-pagination .page-numbers.current:hover {
    background: #0073aa;
  }

  .wpp-reverify-user {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .wpp-reverify-user .dashicons {
    font-size: 16px;
    height: 16px;
    width: 16px;
  }

  .wpp-reverify-user.loading {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .wpp-reverify-user.loading .dashicons {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    $(".wpp-show-user").click(function() {
      const userId = $(this).attr('user_id');
      const detailsPanel = $('#details-user-' + userId);
      const button = $(this);

      detailsPanel.slideToggle(200, function() {
        if (detailsPanel.is(':visible')) {
          button.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
          button.find('span:not(.dashicons)').text('Hide Details');
        } else {
          button.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
          button.find('span:not(.dashicons)').text('View Details');
        }
      });
    });

    $(".wpp-reverify-user").click(function() {
      const button = $(this);
      const userId = button.data('user-id');
      const nonce = button.data('nonce');

      if (button.hasClass('loading')) return;

      button.addClass('loading');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpp_reverify_user',
          user_id: userId,
          nonce: nonce
        },
        success: function(response) {
          if (response.success) {
            // Update the status badge
            const statusBadge = button.closest('.wpp-details-section').find('.wpp-status-badge');
            statusBadge.removeClass().addClass('wpp-status-badge wpp-status-not-verified');
            statusBadge.text('Not verified');

            // Update last checked timestamp
            const lastCheckedCell = button.closest('tr').prev().find('td');
            lastCheckedCell.text('Just now');

            // Show success message
            alert('User verification status has been reset successfully.');
          } else {
            alert('Error: ' + (response.data || 'Failed to reset verification status'));
          }
        },
        error: function() {
          alert('Error: Failed to communicate with the server');
        },
        complete: function() {
          button.removeClass('loading');
        }
      });
    });
  });
</script>