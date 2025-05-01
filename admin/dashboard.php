<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Persona Dashboard', 'wp-withpersona'); ?></h1>
  <a href="/wp-admin/admin.php?page=wp-withpersona-settings" class="page-title-action">Settings</a>
  <hr class="wp-header-end">

  <!-- WordPress will inject notices here -->

  <div class="wpp-dashboard-content">
    <div class="wpp-users-section">
      <div class="wpp-users-container">
        <div class="wpp-users-list">
          <?php
          $the_users = get_users(array('role__in' => array('administrator', 'author', 'subscriber')));
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
                      <td><?php echo date('Y-m-d H:i:s', $persona_verification_last_checked); ?></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
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
  });
</script>