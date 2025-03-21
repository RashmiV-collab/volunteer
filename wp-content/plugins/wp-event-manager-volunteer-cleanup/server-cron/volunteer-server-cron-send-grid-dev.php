<?php 
/*
 ** Server Executing Bulk Alert Mail Cron (e-blast)
 */
add_action('rest_api_init', 
function () {
	register_rest_route(
		'volunteer-cron/v1', '/alert-mail-sendGrid_live',
		array(
		  'methods'  => 'GET',
		  'callback' => 'volunteer_send_alert_send_grid_mail_dev',
		  'permission_callback' => '__return_true',
		)
	);

});

/**
 * function called by server cron
 */
function volunteer_send_alert_send_grid_mail_dev(){ 
    //die();
    //print_R(date('H:i:s'));

    echo "<pre>";
    echo 'File hit but die';
    $myfile = fopen("sendGrid.txt", "a+");
    $txt = "\n\n\nSTART-------------------------Time" . date('Y-m-d H:i')."\n";
    fwrite($myfile, $txt);
    fclose($myfile);
    //print_R(date('Y-m-d H:i'));
    //die();

    $volunteer_settings = get_option('volunteer_custom_settings');
    $enable_alert_cron = (is_array($volunteer_settings) && isset($volunteer_settings['enable_alert_cron']))?$volunteer_settings['enable_alert_cron']:'';

    if('yes' !== $enable_alert_cron){
        echo 'not enabled';
        return;
    }
    if(date("l",strtotime(date('Y-m-d'))) !== 'Tuesday'){
        echo 'today is not tuesday';
        return;
    }

    $volunteer_cron_execution_date = get_option('volunteer_cron_execution_date');
    if(!empty($volunteer_cron_execution_date) && $volunteer_cron_execution_date && strtotime($volunteer_cron_execution_date) == strtotime(date('Y-m-d'))){
        echo 'already executed';
        return;
    }
    
    update_option('volunteer_cron_execution_date',date('Y-m-d'));
    
    echo 'testing';
    //die();

        // if setting matches
        global $wpdb;
        //
        // Log -- 1. log table
        $table_log_parent_name = $wpdb->prefix.'sendgrid_execute_log';
        $table_name = $wpdb->prefix.'wpevents_alert_blast_log';
        $sendgrid_table = $wpdb->prefix . 'wpevents_alert_sendgrid_log';
        
        $tablename = $wpdb->prefix.'postmeta';
        $joinPostTablename = $wpdb->prefix.'posts';
        $joinUsersTablename = $wpdb->prefix.'users';
        $i = 0;

        $postArray = $wpdb->get_results("SELECT a.meta_value,a.meta_key,a.meta_id,a.post_id,c.user_email,c.display_name FROM ".$tablename." as a INNER JOIN ".$joinPostTablename." as b ON a.post_id=b.ID INNER JOIN  ".$joinUsersTablename." as c ON c.ID=b.post_author where (a.meta_key = '_alert_location' or a.meta_key = 'geolocation_lat' or a.meta_key = 'geolocation_lng') and b.post_status='publish' order by a.meta_value ",ARRAY_A);
        $allUsers = [];
        $uniqueZipcodes = [];
        $uniqueZipcodesPostId = [];
        $allPostIdLatLong = [];
        $uniqueZipcodesPostIdLatLongNEW = [];
        $location_event_content = [];
        $date_format = WP_Event_Manager_Date_Time::get_event_manager_view_date_format();
        $time_format = WP_Event_Manager_Date_Time::get_timepicker_format();
        if(isset($postArray) && !empty($postArray)){
            foreach ($postArray as $key => $value) {
                if(isset($value['meta_value']) && !empty($value['meta_value']) && $value['meta_key']=='_alert_location'){
                    $allUsers[] = array(
                        'user_email' => $value['user_email'],
                        'display_name' => $value['display_name'],
                        'post_id' => $value['post_id'],
                        'alert_location' => $value['meta_value'],
                        'alert_name' => $value['meta_value'],
                    );
                }
                if(isset($value['meta_value']) && !empty($value['meta_value']) && !in_array($value['meta_value'],$uniqueZipcodes) && $value['meta_key']=='_alert_location'){
                    $uniqueZipcodes[] = $value['meta_value'];
                    $uniqueZipcodesPostId[] = $value['post_id'];
                    foreach ($postArray as $kk => $vv) {
                        if($value['post_id']==$vv['post_id'] && $vv['meta_key']=='geolocation_lat'){
                            $uniqueZipcodesPostIdLatLongNEW[$value['post_id']][0] = $vv['meta_value'];
                            $uniqueZipcodesPostIdLatLongNEW[$value['post_id']]['alert_location'] = $value['meta_value'];
                        }
                        if($value['post_id']==$vv['post_id'] && $vv['meta_key']=='geolocation_lng'){
                            $uniqueZipcodesPostIdLatLongNEW[$value['post_id']][1] = $vv['meta_value'];
                        }
                    }
                }            
                if($value['meta_key']=='geolocation_lat'){
                    $allPostIdLatLong[$value['post_id']][0] = $value['meta_value'];
                }
                if($value['meta_key']=='geolocation_lng'){
                    $allPostIdLatLong[$value['post_id']][1] = $value['meta_value'];
                }
            }
        }
        //print_r($uniqueZipcodesPostId);
        //print_r($uniqueZipcodes); die();
        //print_r($allPostIdLatLong);

        /*$value = [25.8641207,-80.3045967];
        $events = volunteer_get_matcing_events($value);
        print_R($events);
        die(); 
        if($events) {
            foreach($events as $event){
                $event_content .= volunteer_get_content_email_event_listing($event,$date_format,$time_format);
            }
        } 
        print_R($event_content);
        die();  */

       //print_R(count($allUsers));
        //print_r(count($uniqueZipcodesPostIdLatLongNEW));
       //print_r(count($allPostIdLatLong));
       //die();
        if(isset($uniqueZipcodesPostIdLatLongNEW) && !empty($uniqueZipcodesPostIdLatLongNEW)){
            foreach ($uniqueZipcodesPostIdLatLongNEW as $key => $value) {
                // $key = post_id 
                // $value[0] = geolocation_lat
                // $value[1] = geolocation_lng
                $events = volunteer_get_matcing_events($value);
                //print_r($events);
                if($events) {
                    $event_content = '';
                    foreach($events as $event){
                        $event_content .= volunteer_get_content_email_event_listing($event,$date_format,$time_format);
                    }
                    $uniqueZipcodesPostIdLatLongNEW[$key]['event_content'] = $event_content;
                    $location_event_content[$value['alert_location']] = $event_content;
                } 
            }
        }
        if(isset($allUsers) && !empty($allUsers)){
            $to_arr = array();
            $log_arr = array();
            $log_parent_arr = array();
            $count = 0;
            foreach ($allUsers as $key => $value) {

                //echo $allPostIdLatLong[$value['post_id']][0];
                //echo $allPostIdLatLong[$value['post_id']][1];

                /* $keys = array_keys(array_combine(array_keys($uniqueZipcodesPostIdLatLongNEW), array_column($uniqueZipcodesPostIdLatLongNEW, 'alert_location')),$value['alert_location']);
                print_r($keys);
                if(!empty($keys)){
                    print_r($uniqueZipcodesPostIdLatLongNEW[$keys[0]]);
                } */

                $event_c = '';
                //$event_c = $location_event_content['?33131'];
                if(isset($value['user_email']) && !empty($value['user_email']) && isset($location_event_content[$value['alert_location']])){
                    //if($count<1){
                        $un = '';
                        //if($count>=0 && $count<=2){
                            //$e = 'learningdcm@gmail.com';
                            //$e = 'er.prafullgupta@gmail.com';
                            //$e = 'asad.raza89@gmail.com';
                            //$un = ' ('.$value['user_email'].')';
                        //}
                        /* if($count>=3 && $count<=5){
                            $e = 'techteam.dev01@gmail.com';
                            $un = '('.$value['user_email'].')';
                        } */
                        /* iif($count>=21 && $count<=30){
                            $e = 'asadraza_k3g@hotmail.com';
                        }
                        if($count>=31 && $count<=40){
                            $e = 'asadraza.8.9@gmail.com';
                        }
                        if($count>=41 && $count<=50){
                            $e = 'asadraza89@gmail.com';
                        }
                        if($count>=51 && $count<=60){
                            $e = 'asadraza89@yahoo.com';
                        }
                        if($count>=61 && $count<=70){
                            $e = 'dave.doebler@gmail.com';
                        }
                        if($count>=71 && $count<=80){
                            $e = 'dave@volunteercleanup.org';
                        } */
                        /* $un = '('.$value['user_email'].')';
                        $e = 'learningdcm+'.$count.'@gmail.com'; */

                        $event_c = $location_event_content[$value['alert_location']];
                        $to_arr[] = 
                            [
                                "to" => [
                                    ["email" => $value['user_email']]
                                    //["email" => $e]
                                ],
                                "dynamic_template_data" => [
                                        //"display_name" => $value['display_name'].$un,
                                        "display_name" => $value['display_name'],
                                        "alert_name" =>  $value['alert_name'],
                                        "events" => $event_c,
                                        "base_url" => site_url(),
                                ],
                            ];

                        $log_parent_arr[] = array(
                            "email" => $value['user_email'],
                            "alert_id" => $value['post_id'], 
                        );
                        $log_arr[] = array( 
                            'alert_id' => $value['post_id'], 
                            'status' => 'success', 
                            'reason' => 'done', 
                            'log_date' => date('Y-m-d H:i')
                        ); 

                        //$count++;
                    //}
                }
            }
            //print_r($to_arr);
            //print_r($log_arr);

            /* // adding log on 1st table
            $wpdb->get_results("TRUNCATE TABLE $table_name");
            $insertQuery = "INSERT INTO ". $table_name." (alert_id, status, reason, log_date) VALUES ";
            $insertQueryValues = array();
            foreach($log_arr as $value) {
                array_push( $insertQueryValues, "(" . $value['alert_id'] .", '".$value['status'] ."', '".$value['reason'] ."', '".$value['log_date'] ."')" );
            }
            $insertQuery .= implode( ",", $insertQueryValues );
            $wpdb->query( $insertQuery ); */

            // adding log on 2nd table
            /* $insertQuery = "INSERT INTO ". $table_log_parent_name." (count, request) VALUES (".count($log_parent_arr).",'".json_encode($log_parent_arr)."')";
            $wpdb->query( $insertQuery ); */

            if(isset($to_arr) && !empty($to_arr)){
                /* $to_arr_child = array_chunk($to_arr, 900);
                $log_arr2 = array_chunk($log_arr, 900); */
                $to_arr_child = array_chunk($to_arr, 300);
                $log_arr2 = array_chunk($log_arr, 300);
                $log_parent_arr2 = array_chunk($log_parent_arr, 300);
                //$wpdb->get_results("TRUNCATE TABLE $table_name");
                //print_r($to_arr_child); 
                foreach ($to_arr_child as $key => $value) {
                    //if($key==17){
                        //print_r($value); 
                        //print_r($log_arr2[$key]); 

                        /* $insertQuery = "INSERT INTO ". $table_name." (alert_id, status, reason, log_date) VALUES ";
                        $insertQueryValues = array();
                        foreach($log_arr2[$key] as $v) {
                            array_push( $insertQueryValues, "(" . $v['alert_id'] .", '".$v['status'] ."', '".$v['reason'] ."', '".$v['log_date'] ."')" );
                        }
                        $insertQuery .= implode( ",", $insertQueryValues );
                        $wpdb->query( $insertQuery ); */

                        send_grid_mail_curl_dev($value,$log_parent_arr2[$key]);
                    //}
                }
            }
        }
        exit;


        /* $result = $wpdb->get_results("SELECT meta_value,meta_id FROM ".$tablename." where meta_key = '_alert_location' group by meta_value order by meta_id",ARRAY_A );
        //print_R($result);exit;
        if($result){
            $today = strtotime(date('Y-m-d H:i'));
            $wpdb->get_results( "TRUNCATE TABLE $table_name");
            $wpdb->get_results( "TRUNCATE TABLE $sendgrid_table");
            update_option('volunteer_server_custom_cron_start_time',$today);

            foreach($result as $index => $row){
                // testing
            //$postArray = $wpdb->get_results("SELECT a.meta_value,a.meta_id,a.post_id,c.user_email,c.display_name FROM ".$tablename." as a INNER JOIN ".$joinPostTablename." as b ON a.post_id=b.ID INNER JOIN  ".$joinUsersTablename." as c ON c.ID=b.post_author where a.meta_key = '_alert_location' and a.meta_value='".esc_sql($row['meta_value'])."' and b.post_status='publish' and b.post_author IN(6616,7554,1,2,8,11)",ARRAY_A );
             // actual
             $postArray = $wpdb->get_results("SELECT a.meta_value,a.meta_id,a.post_id,c.user_email,c.display_name FROM ".$tablename." as a INNER JOIN ".$joinPostTablename." as b ON a.post_id=b.ID INNER JOIN  ".$joinUsersTablename." as c ON c.ID=b.post_author where a.meta_key = '_alert_location' and a.meta_value='".esc_sql($row['meta_value'])."' and b.post_status='publish'",ARRAY_A );
               if(!empty($postArray)){
                    $email = $event_content = '';
                    $to_arr = $log_arr = [];
                    $alert = get_post($postArray[0]['post_id']);
                    $wp_alert = new WPEM_Alerts_Notifier();
                    $events  = $wp_alert->get_matching_events( $alert, true );
                    if($events && $events->have_posts()) {
                            ob_start();
                            while ($events->have_posts()) {
                                $events->the_post();
                
                                get_event_manager_template('content-email_event_listing.php', array(), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
                            } 
                            wp_reset_postdata();
                            $event_content = ob_get_clean();
                    } 
                    //
                    foreach($postArray as $post){
                        print_R($i++); echo '<br>';
                        $alert_id = $post['post_id'];
                        if ( $event_content && $post['user_email']) {
                            // send grid
                            $to_arr[] = 
                                [
                                    "to" => [
                                        ["email" => $post['user_email']]
                                    ],
                                    "dynamic_template_data" => [
                                            "display_name" => $post['display_name'],
                                            "alert_name" =>  $alert->post_title,
                                            "events" => $event_content ,
                                            "base_url" => site_url(),
                                    ],
                                ];
                            //Log -- 4. logging
                            $log_arr[] = array( 
                                    'alert_id' => $alert_id, 
                                    'status' => 'success', 
                                    'reason' => 'done', 
                                    'log_date' => date('Y-m-d H:i')
                                ); 
                        }else{
                                //Log -- 4. logging
                            $log_arr[] =   array( 
                                    'alert_id' => $alert_id, 
                                    'status' => 'fail', 
                                    'reason' => 'No Associated Events', 
                                    'log_date' => date('Y-m-d H:i')
                                ); 
                        }
                   }

                 
                   // insert in db
                   $insertQuery = "INSERT INTO ". $table_name." (alert_id, status, reason, log_date) VALUES";
                   $insertQueryValues = array();
                   foreach($log_arr as $value) {
                     array_push( $insertQueryValues, "(" . $value['alert_id'] .", '".$value['status'] ."', '".$value['reason'] ."', '".$value['log_date'] ."')" );
                   }
                   $insertQuery .= implode( ",", $insertQueryValues );
                //   $wpdb->query( $insertQuery );

                   // for all alerts
                    $cron_option = get_option('volunteer_alert_cron_settings');
                    $checked = (is_array($cron_option) && isset($cron_option['enable_volunteer_send_grid']))?$cron_option['enable_volunteer_send_grid']:0;
                  
                    if(!empty($event_content) && !empty($to_arr) && $checked){
                      //send_grid_mail_curl($to_arr,$email);
                   }                            
               }
           }
            
       }
        // cron end-time
        $today = strtotime(date('Y-m-d H:i'));
        update_option('volunteer_server_custom_cron_end_time',$today);     */
}


function volunteer_get_matcing_events($value = array()){
    $result = $events = null;
    $event_arr = array();
	global $wpdb;
    //
    if(isset($value[0]) && ($value[0] != NULL) && isset($value[1]) && ($value[1] != NULL)){
        $latitude = $value[0];
        $longitude = $value[1];
        // Radius of the earth 3959 miles or 6371 kilometers.
        $earth_radius = 3959;
        $distance = 15;
        $where = " 
            $wpdb->posts.post_type IN ('post', 'page', 'attachment', 'e-landing-page', 'event_listing', 'event_organizer', 'event_venue', 'product') AND (wp_posts.post_status = 'publish') AND geolocation_lat.meta_key = 'geolocation_lat' AND geolocation_long.meta_key = 'geolocation_long' GROUP BY $wpdb->posts.ID HAVING distance < $distance ";

        $join = "$wpdb->posts INNER JOIN $wpdb->postmeta geolocation_lat ON ( $wpdb->posts.ID = geolocation_lat.post_id ) INNER JOIN $wpdb->postmeta geolocation_long ON ( $wpdb->posts.ID = geolocation_long.post_id ) ";

        $fields = " $wpdb->posts.*, 
                    geolocation_lat.meta_value as latitude, 
                    geolocation_long.meta_value as longitude, 
                    ( $earth_radius * acos(
                            cos( radians( $latitude ) )
                            * cos( radians( geolocation_lat.meta_value ) )
                            * cos( radians( geolocation_long.meta_value ) 
                            - radians( $longitude ) )
                            + sin( radians( $latitude ) )
                            * sin( radians( geolocation_lat.meta_value ) )
                    ) )
                    AS distance ";

        $orderby = " distance DESC ";
        
        $events = $wpdb->get_results("SELECT {$fields} FROM {$join} WHERE {$where} ORDER BY {$orderby}");
    }

    //
    if($events){
        foreach($events as $e){
            $start_date = $end_date = '';
            $post_arr = get_post_meta($e->ID);
            if(is_array($post_arr) && isset($post_arr['_event_start_date']))
            {	
                $start_date = strtotime($post_arr['_event_start_date'][0]);
                $end_date = strtotime($post_arr['_event_end_date'][0]);
                $extendDate = strtotime('+3 weeks');
                $today = strtotime(date('Y-m-d'));
                if(($start_date >= $today && $start_date <= $extendDate) || ($end_date >= $today && $start_date <= $today)){
                    $event_arr[] = $e->ID;
                }
            }
        }
        if(!empty($event_arr)){ 
            $args = array(
                'post_type' => 'event_listing',
                  //'include' => $event_arr,
                  'post__in' => $event_arr,
                  'meta_key'          => '_event_start_date',
                  'orderby'           => 'meta_value',
                  'order'             => 'ASC',
                  'meta_type'         => 'DATETIME',
                'numberposts'    => 10,
                'meta_query' => array(
                                'relation' => 'OR',
                                array(
                                    'key' => '_private_event', // Replace with the actual meta key for the checkbox field
                                    'value' => '1', // Replace with the value representing the checkbox being checked
                                    'compare' => '!=',
                                ),
                                array(
                                    'key'     => '_private_event',
                                    'compare' => 'NOT EXISTS',
                                )
                )
            );
            $result =  get_posts( $args );
        }
    }
	return $result;
}



function send_grid_mail_curl_dev($to_arr,$log_arr){
    //print_r($to_arr);
    //return false;
    //echo 'sendgrid mail function fit but die'; die();
    global $wpdb;
    $jayParsedAry = [
      "personalizations" => $to_arr, 
      "from" => [
                "email" => "outreach@volunteercleanup.org" 
            ], 
      "template_id" => "d-913d0fbed7de496289f80de87da8d8a6" 
    ]; 

    // Convert the post data to a string
    $postDataString = http_build_query($jayParsedAry);
    // Calculate the size of the post data in bytes
    $postDataSizeBytes = strlen($postDataString);
    // Convert bytes to megabytes
    $postDataSizeMB = round($postDataSizeBytes / (1024 * 1024),2);
    // Output the size of the post data in MB
    //echo "Size of post data: " . $postDataSizeMB . " MB\n\n";
    //return true;
    
    //print_r(count($jayParsedAry));
    //echo  "<br>";
    $data = json_encode($jayParsedAry);
    //print_r(strlen($data));die();
    $url = 'https://api.sendgrid.com/v3/mail/send';
    //$url = 'https://wordpress-1220099-4339390.cloudwaysapps.com/testfile.php';
    $response = wp_remote_post($url, array(
        'body'    => $data,
        'method'    => 'POST',
        'headers' => array(
            'Content-Type'   => 'application/json',
            'Authorization' => 'Bearer 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'
        ),
    ));   

    //print_r($response);
    //echo '<br/><br/><br/><br/><br/>';
    // <save_curl_response_info_on_database_as_log>
    $is_success = 0;
    $response_code = '';
    if(isset($response) && !empty($response)){
        if(isset($response['response']) && !empty($response['response'])){
            if(isset($response['response']['code']) && !empty($response['response']['code']) && ($response['response']['code']==202 || $response['response']['code']==200)){
                $is_success = 1;
                $response_code = $response['response']['code'];
                //echo 'SUCCESS '.$resUnjson['response']['code'];
            }
            else{
                $response_code = $response['response']['code'];
                //echo 'FAILED '.$resUnjson['response']['code'];
            }
        }
        else{
            //echo 'FAILED';
        }
    }
    else{
        //echo 'FAILED';
    }
    $insertQuery = "INSERT INTO ".$wpdb->prefix."sendgrid_execute_log (email_count, is_success, response, response_code, request_data, request_data_size) VALUES (".count($log_arr).",".$is_success.",'".json_encode($response)."','".$response_code."','".json_encode($log_arr)."','".$postDataSizeMB."');";
    //print_r($insertQuery);
    $wpdb->query( $insertQuery );
    // </save_curl_response_info_on_database_as_log>


    $myfile = fopen("sendGrid_code.txt", "a+");
    $txt = "\n\nRESPONSE (POST DATA: ".$postDataSizeMB." MB)-------------------------Time" . date('Y-m-d H:i')."\n";
    $txt .= json_encode($response);
    fwrite($myfile, $txt);
    fclose($myfile);     
}

function volunteer_get_content_email_event_listing($post , $date_format,$time_format){
    $post_arr = get_post_meta($post->ID);
    $post_id = $post->ID;
    $title    = $post->post_title;
    $location = $post_arr['_event_location'][0];
    $link     = get_permalink($post->ID);
    //
    $event_start_date =  $event_start_time =  $event_end_date =  $event_end_time = '';
    
    $start_date = $post_arr['_event_start_date'][0];
    if(strlen($start_date) != 0){
        $event_start_date 	= date_i18n($date_format, strtotime($start_date));
    }
	$end_date   = $post_arr['_event_end_date'][0];
    if(strlen($end_date) != 0){
        $event_end_date 	= date_i18n($date_format, strtotime($end_date));
    }

    $start_time = $post_arr['_event_start_time'][0];
    if(strlen($start_time) != 0){
	    $event_start_time 	= date_i18n($time_format, strtotime($start_time));
    }
    $end_time   = $post_arr['_event_end_time'][0];
    if(strlen($end_time) != 0){
        $event_end_time = date_i18n($time_format, strtotime($end_time));
    }
    //
    $thumbnail  = get_event_thumbnail($post, 'full'); 

    $all_tickets = array();
    if(function_exists('wpem_sell_tickets_get_event_tickets')){
        $atts =  apply_filters( 'event_manager_output_event_sell_tickets_defaults', array('event_id'  => '','orderby'   => '_ticket_priority','order'     => 'ASC') );
        $all_tickets = wpem_sell_tickets_get_event_tickets( $post->ID, $atts['orderby'], $atts['order'] );
    }

$html .= '<tr style="display: flex;border-bottom: 1px solid #f5f5f5; padding: 20px;">
    <td>
        <div class="wpem-event-banner-img" style="background-image: url('.(esc_attr($thumbnail)).'); height: 90px;
        width: 90px;
        background-size: cover!important;
        background-position: center!important;
        border-radius: 4px;
        background-color:#e4e4e4">
        </div>
    </td>
    <td id="dateHide">
        <div class="wpem-event-information" style="float: left;font-size: 15px;line-height: 20px;width: 100%; padding: 0px 15px;">
            <div class="wpem-event-date" style="width: 80px; font-size: 15px; line-height: 20px;">
                <div class="wpem-event-date-type" style="display: flex;">';
                if (!empty($start_date)) { 
                    $html .= '<div class="wpem-from-date" style="width: 40px;">
                        <div class="wpem-date" style="font-size: 29px; line-height: 30px; font-weight: 600; color:#555555">'.(date_i18n('d', strtotime($start_date))).'</div>
                        <div class="wpem-month" style="font-size: 13px; text-transform: uppercase; font-weight: 400; line-height: 15px;  color: #555555;">'.(date_i18n('M', strtotime($start_date))).'</div>
                    </div>';
                } 
                if ($start_date != $end_date && !empty($end_date)) { 
                    $html .= '<div class="wpem-to-date" style="display: flex; float: left; padding-left: 10px;  position: relative; ">
                        <div class="wpem-date-separator" style="position: absolute; left: 0; top: 50%;  transform: translate(0,-50%);  font-size: 20px;  color: #555555">-</div>
                        <div style="padding-left: 10px; padding-top: 2px;">
                            <div class="wpem-date" style="font-size: 15px; line-height: 15px; font-weight: 500; color: #555555">'.(date_i18n('d', strtotime($end_date))).'</div>
                            <div class="wpem-month" style="font-size: 9px; text-transform: uppercase; font-weight: 400; line-height: 12px; color:#555555">'.(date_i18n('M', strtotime($end_date))).'</div>
                        </div>
                    </div>';
                }
        $html .= '</div>
            </div>
        </div>
        <div class="clear:both"></div>
    </td>
    <td class="addFlex">
        <table width="100%" cellpadding="0" cellspacing="0" style="box-sizing: border-box; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; margin: 0; padding: 0; width: 100%;">
            <tr>
                <h3 class="wpem-heading-text" style="font-family: Lexend, Sans-serif; font-size: 22px; line-height: 30px; font-weight: 700; color: #111111; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0; text-transform: capitalize;"><a style="color: #111111;" href="'.($link).'">'.($title).'</a></h3>
            </tr>
            <tr>
                <div class="wpem-event-date-time" style="margin: 5px 0px;  min-height: 22px;  color: #555555;  display: flex;">
                   
                        <img width="20" height="20" src="'.(WPEM_VOLUNTEER_URI.'assets/img/clock-icon.png').'" alt="-" /><span class="wpem-event-date-time-text" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;"> '.($event_start_date);
                                if (!empty($start_time)) {
                        $html .=    ' @ ';
                                }
                        $html .=   $event_start_time;
                                if (!empty($end_date) || !empty($end_time)) {
                                $html .= ' - ';
                                } 
                                if (isset($start_date) && isset($end_date) && $start_date != $end_date) {
                        $html .=   $event_end_date;
                                }
                                if (!empty($end_date) && !empty($end_time)) {
                        $html .=    ' @ ';
                                }
                        $html .=    $event_end_time.'</span>
                </div>
            </tr>
            <tr>
                <div class="wpem-event-location" style="margin: 5px 0px;  min-height: 22px;  color: #555555;  display: flex;">
                    
                    <img width="15" height="20" src="'.(WPEM_VOLUNTEER_URI.'assets/img/map-pointer.png').'" alt="-" /><span class="wpem-event-location-text" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;">';
                                if ($location == 'Online Event' || $location == '') {
                                    $html .=   'Online Event';
                                }else{
                                    $html .=   $location;
                                } 
                    $html .= '</span>
                </div>
            </tr>
            <tr>
                <div class="wpem-event-ticket-type" style="margin: 5px 0px;  min-height: 22px;  color: #555555;">';
                if($all_tickets){ 
                    foreach($all_tickets as $product){
                        $product_id = $product->ID;
                        $product = wc_get_product($product_id);
                        $stock     = (int)get_post_meta($product_id, '_stock', true);
                        $total_sales = get_post_meta($product_id, 'total_sales', true);
                        if(!$total_sales){
                            $total_sales = 0;
                        }
                        $total = $stock + (int) $total_sales;
                        $total = (string)$total;
                        $ticket_type = get_post_meta($product_id, '_ticket_type', true);
                        //
                    $html .= '<div style="display:block;width: 100%;margin:10px 0px">
                        <span class="wpem-ticket-price" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;">'.($ticket_type == 'free' ? 'Free': 'Paid '.$product->get_price_html()).'</span>';

                        if($stock == 0) {
                        $html .=  '<span class="wpem-ticket-quantity wpem-form-group wpem-ticket-sold-out" style="background-color: rgb(255 0 0 / 80%);color: white; padding: 4px 10px;border-radius: 4px; text-align: center;">
                            Sold Out                                
                        </span>';

                        }else{ 
                        
                        $html .=  '<span class="wpem-event-ticket-type-text" style="background: #f5f5f5 ; color: #111111; padding: 5px 7px; display: inline-block; line-height: 15px;  font-weight: 500; font-size: 14px; border-radius: 4px;margin-left:5px;"><span class="remaining-tickets-counter">
                           '.( 'Remaining tickets '. $stock.' out of '.$total).'</span></span>';
                        }
                $html .=    '</div>';
                    } 
                }
              
            $html .=    '</div>
            </tr>
        </table>
    </td>
</tr>';

return $html;
}
?>
