<div class="wrap">
<?php
echo "<h2>" . __( 'Persona Dashboard', 'wpp-menu' ) . "</h2>";
///wp-admin/media-upload.php
//$mimetypes = get_allowed_mime_types();
//var_dump ($mimetypes);
?>  

  <p><a href="/wp-admin/options-general.php?page=wp_with_persona_settings">Open Settings</a></p>
  <div id="dashboard-widgets-wrap"> 

    <div id="dashboard-widgets" class="metabox-holder">

	<div id="postbox-container-wpp-users" class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="dashboard_site_health" class="postbox ">
              <div class="postbox-header">
                 <h2 class="hndle ui-sortable-handle">Users</h2>
              </div>
              <div class='inside'>
			<div class='wpp-users'>
<div class="list">

  <div class="card col-4 mt-20 mx-auto wpp-users">
     <div class="card-body">
	<ul class="list-group wpp-topics">

<?php
    $the_users = get_users( array( 'role__in' => array( 'administrator', 'author', 'subscriber' ) ) );
    foreach ( $the_users as $user ) :
        $udata = get_userdata( $user->ID );
        $registered = $udata->user_registered; 
?>
        <li class="list-group-item">
            <a class="asciidoc-link" target="__blank" href="<?php echo ( esc_html( $user->user_url )); ?>"><?php  echo ( esc_html( $user->display_name ));  ?></a> Since <?php echo date( "M Y", strtotime( $registered ) ) ?> &nbsp;
	    <button user_id="<?php echo $user->get('ID'); ?>" class="wpp-show-user button wpp-button-small button-primary">Details</button>
	
	</li>

<div id="details-user-<?php echo $user->get('ID'); ?>" class="detail-panel">
<table class="wpp-userdetails-table">
<tr class="wpp-table-first-row">
<td colspan="2">Basic data</td>
</tr>

<?php if ( $user->get('display_name') ): ?>
<tr class="wpp-class-odd">
<td>Name:
</td>
<td><?php echo ( esc_html( $user->get('display_name') ) ); ?>
</td>
</tr>
<?php endif; ?>

<?php if ( $user->get('user_email') ): ?>
<tr class="wpp-class-even">
<td>Email:
</td>
<td><?php echo ( esc_html( $user->get('user_email') ) ); ?>
</td>
</tr>
<?php endif; ?>

<tr class="wpp-table-first-row">
<td colspan="2">Metadata</td>
</tr>


<?php  
$class_even = "table-class-even";
$class_odd = "table-class-odd";
$metas = get_user_meta( $user->get('ID') );
$class_i = $class_odd;
foreach ( $metas as $name=>$value ):
if ($value[0]):
?>
<tr class="<?php echo $class_i; ?>">
<td><?php echo ( esc_html( $name ) ); ?>
</td>
<td><?php 
foreach ($value as $val){
    echo '<span class="wpp-user-meta">' . ( esc_html( $val ) ) . "</span>";
}
$class_i = ($class_i == $class_odd) ? $class_even : $class_odd;
?>
</td>
</tr>
<?php endif; endforeach; ?>


</table>
</div>

    <?php endforeach; ?>
	</ul>
     </div>



</div>

			</div>
              </div>
            </div>
          </div>
        </div>


     </div>
 
  </div>
</div>

<!--
<div id="upload-dialog">
<iframe frameborder="0" width=800 height=600 marginwidth="0" marginheight="0" allowfullscreen src="/wp-admin/media-upload.php"></iframe>
</div>
-->

<style>
.list-group-item {
	display: flex;
}
.asciidoc-link {
	min-width: 300px;
}
.detail-panel {
	display:none;
}
</style>

    <script>
jQuery(document).ready(function ($) {
	console.log("Persona Dashboard...");
        $(".wpp-show-user").click(function(){
		let user = $(this).attr('user_id');
		console.log("Show user!! " + user);
		$('#details-user-'+user).toggle();

        });
        $("#wpp-delete-doc").click(function(){
		console.log("Delete a user!!");
	});

	function uploadFiles(formData){
	    console.log(formData);
	}
});	    





    </script>
