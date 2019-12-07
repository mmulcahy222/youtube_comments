<?php
$DEVELOPER_KEY = 'NONE';  
#example of HTTP restful call
#variables
$video_id = $argv[1];
$total_comments = $argv[2];
$filename = 'C:/scripts/youtube.txt';
$text = '';
#must be kept out of for loop
$params = array(
	'part'=>'snippet',
	'videoId'=>$video_id,
	'maxResults'=>100,
	'key' => $DEVELOPER_KEY
);
$arr_context_options=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);
$stream_context = stream_context_create($arr_context_options);
#max_comments of YouTube API being 100 is why we're dividing by 100
for ($count=0; $count < $total_comments / 100; $count++) { 
	$comment_threads_url = 'https://www.googleapis.com/youtube/v3/commentThreads?' . http_build_query($params);
	$api_raw_response = file_get_contents($comment_threads_url, false, $stream_context);
	$response = json_decode($api_raw_response,1);
	foreach ($response['items'] as $item) 
	{
		$id = $item['id'];
		$username = $item['snippet']['topLevelComment']['snippet']['authorDisplayName'];
		$reply_count = $item['snippet']['totalReplyCount'];
		$comment = $item['snippet']['topLevelComment']['snippet']['textDisplay'];
		$comment = trim(html_entity_decode(htmlspecialchars_decode(strip_tags($comment)), ENT_QUOTES | ENT_HTML5));
		$like_count = $item['snippet']['topLevelComment']['snippet']['likeCount'];
		$text .= "----\n---$username---$like_count----\n--$id--\n--\n$comment\n";
		//debug
		if(0)
		{
			var_export($item);
			exit();
		}
		#separate API call for replies
		if ($reply_count > 0) {
			$reply_params = array(
				'part'=>'snippet',
				'parentId'=>$id,
				'textFormat'=>'plainText',
				'maxResults'=>100,
				'key' => $DEVELOPER_KEY
				);
			$reply_url = 'https://www.googleapis.com/youtube/v3/comments?' . http_build_query($reply_params);
			$reply_raw_response = file_get_contents($reply_url);
			$reply_response = json_decode($reply_raw_response,1);
			foreach ($reply_response['items'] as $reply_item) {
				$reply_id = $reply_item['id'];
				$reply_username = $reply_item['snippet']['authorDisplayName'];
				$reply_comment = $reply_item['snippet']['textDisplay'];
				$reply_comment = trim(html_entity_decode(htmlspecialchars_decode(strip_tags($reply_comment)), ENT_QUOTES | ENT_HTML5));
				$reply_like_count = $reply_item['snippet']['likeCount'];
				$text .= "----\n---$reply_username---$reply_like_count----\n--$reply_id--r--\n--\n$reply_comment\n";		
			}
		}
		#end replies to comments processing
	}
	#display on console and put in file
	echo $text . "\n\n\n\n$count\n\n\n\n";
	file_put_contents($filename, $text, FILE_APPEND);
	$text = '';
	$params['pageToken'] = $response['nextPageToken'];
}
?>  