<div class="wrap">
<?php
echo "<h2>" . __( 'Persona Dashboard', 'aql-menu' ) . "</h2>";
///wp-admin/media-upload.php
//$mimetypes = get_allowed_mime_types();
//var_dump ($mimetypes);
?>  


  <div id="dashboard-widgets-wrap"> 

    <div id="dashboard-widgets" class="metabox-holder">

<!--
        <div id="postbox-container-0" class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="dashboard_site_health" class="postbox ">
              <div class="postbox-header">
                 <h2 class="hndle ui-sortable-handle">Persona Dashboard</h2>
              </div>
              <div class='inside'>
                <ul>
                <li>Documents</li>
                </ul>
              </div>
            </div>
             

          </div>
        </div>
-->

	<div id="postbox-container-1" class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="dashboard_site_health" class="postbox ">
              <div class="postbox-header">
                 <h2 class="hndle ui-sortable-handle">Users</h2>
              </div>
              <div class='inside'>
			<div class='aql-users'>
<!--
				<div class="aql-documents-menu">
					<button id="aql-add-doc" class="button button-primary">Add</button>
					<button id="aql-add-docs" class="button button-primary">Upload</button>
				</div>
-->

<div class="list">

  <div class="card col-4 mt-20 mx-auto aql-docs">
     <div class="card-body">
	<ul class="list-group aql-topics">

<?php
    //global $post;
    //$args = array( 'posts_per_page' => 8, 'category' => 'asciidoc' );

    $the_users = get_users( array( 'role__in' => array( 'administrator', 'author', 'subscriber' ) ) );
    foreach ( $the_users as $user ) : 
?>
        <li class="list-group-item">
            <a class="asciidoc-link" target="__blank" href="<?php echo ( esc_html( $user->user_url )); ?>"><?php  echo ( esc_html( $user->display_name ));  ?></a>
	    <button user_id="<?php echo $user->get('ID'); ?>" class="aql-show-user button aql-button-small button-primary">Details</button>
	
	</li>

<div id="details-user-<?php echo $user->get('ID'); ?>" class="detail-panel">
<pre>
<?php print_r($user); ?>
</pre>
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
        $(".aql-show-user").click(function(){
		let user = $(this).attr('user_id');
		console.log("Show user!! " + user);
		$('#details-user-'+user).toggle();

        });
        $("#aql-delete-doc").click(function(){
		console.log("Delete a user!!");
	});

	function uploadFiles(formData){
	    console.log(formData);
	}
});	    





    </script>
